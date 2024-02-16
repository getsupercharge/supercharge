<?php declare(strict_types=1);

namespace Supercharge\Cli;

use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use Supercharge\Cli\Config\Config;
use Supercharge\Cli\Report\InvalidXml;
use Supercharge\Cli\Report\JUnitReport;
use Throwable;
use function Laravel\Prompts\progress;

class Runner
{
    private Api $api;

    public function __construct(
        private readonly Config $config,
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
    public function runTests(string $hash, array $commands, string $directory): JUnitReport
    {
        $jobCount = 9999999;

        $progress = progress(label: 'Running tests', steps: $jobCount);
        $progress->start();

        $junitReport = new JUnitReport;

        /** @var LoopInterface $loop */
        $loop = Loop::get();

        $jobRetrieved = 0;

        // Connect to the websocket API *before* starting the jobs, else we miss some events
        $this->api->connectWebsocket(function ($payload, $connection) use (&$jobRetrieved, &$jobCount, $junitReport, $progress) {
            $messagePayload = json_decode((string) $payload, true, 512, JSON_THROW_ON_ERROR);
            $status = $messagePayload['status'];
            if (($status === 'success' || $status === 'failure') && $messagePayload['junitXmlReport']) {
                $junitReport->merge($messagePayload['junitXmlReport']);
                $progress->advance();
                $jobRetrieved++;
            }

            if ($jobRetrieved >= $jobCount) {
                // Break out of the loop
                $connection->close();
            }
        });

        try {
            ['body' => $body] = $this->api->post('/api/runs', [
                'commands' => $commands,
                'directory' => $directory,
                'beforeCommands' => $this->config->beforeCommands,
                'environmentVariables' => $this->config->environment,
                'packageHash' => $hash,
            ]);
        } catch (Throwable $e) {
            $loop->stop();
            $progress->finish();
            throw $e;
        }
        $jobCount = $body['jobCount'];
        $progress->steps = $jobCount;
        $progress->total = $jobCount;

        $loop->run();

        $progress->finish();

        return $junitReport;
    }
}
