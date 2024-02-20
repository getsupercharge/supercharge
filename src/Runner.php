<?php declare(strict_types=1);

namespace Supercharge\Cli;

use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Ratchet\Client\WebSocket;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use RuntimeException;
use Supercharge\Cli\Config\Config;
use Supercharge\Cli\Report\InvalidXml;
use Supercharge\Cli\Report\JUnitReport;
use function Laravel\Prompts\error;
use function Laravel\Prompts\progress;

class Runner
{
    private Api $api;

    public function __construct(
        private Config $config,
    )
    {
        $this->api = new Api;
    }

    /**
     * @param list<string> $commands
     *
     * @throws JsonException
     * @throws GuzzleException
     * @throws InvalidXml
     */
    public function runTests(string $project, string $hash, array $commands, string $directory): JUnitReport
    {
        ['body' => $body] = $this->api->post('/api/runs', [
            'project' => $project,
            'commands' => $commands,
            'directory' => $directory,
            'beforeCommands' => $this->config->beforeCommands,
            'environmentVariables' => $this->config->environment,
            'packageHash' => $hash,
        ]);
        if (! is_array($body) || ! isset($body['id'], $body['jobCount'])) {
            throw new RuntimeException('Invalid response from the API');
        }
        $runId = $body['id'];
        $jobCount = (int) $body['jobCount'];

        $progress = progress('Running tests', $jobCount);
        $progress->start();

        $junitReport = new JUnitReport;

        /** @var LoopInterface $loop */
        $loop = Loop::get();

        $jobRetrieved = 0;

        // Ideally we should connect to the websocket API *before* starting the jobs,
        // to avoid missing any events. However we don't have the run ID before we start the jobs.
        $this->api->connectWebsocket("runs.$runId", function ($payload, WebSocket $connection) use (&$jobRetrieved, &$jobCount, $junitReport, $progress) {
            $messagePayload = json_decode((string) $payload, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($messagePayload)) return;
            $event = $messagePayload['event'] ?? null;
            $job = $messagePayload['data']['job'] ?? null;
            if ($event === 'App\\Events\\JobCompletedEvent' && is_array($job) && $job['junitXmlReport']) {
                $junitReport->merge($job['junitXmlReport']);
                $progress->advance();
                $jobRetrieved++;
            }

            if ($jobRetrieved >= $jobCount) {
                // Break out of the loop
                $connection->close();
            }
        }, function ($e) use ($progress, $loop) {
            $loop->stop();
            $progress->finish();
            error('Failed to connect to the websocket API: ' . $e->getMessage());
            exit(1);
        });

        $loop->run();

        $progress->finish();

        return $junitReport;
    }
}
