<?php declare(strict_types=1);

namespace Supercharge\Cli;

use GuzzleHttp\Client;
use RuntimeException;
use Supercharge\Cli\Config\Config;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;

class Package
{
    private const FILENAME = '.supercharge/code.zip';
    private Filesystem $fs;
    private Api $api;

    public function __construct()
    {
        $this->fs = new Filesystem;
        $this->api = new Api;
    }

    /**
     * @return string Package hash
     */
    public function upload(Config $config, string|null $hash): string
    {
        if ($hash) {
            $alreadyExists = spin(
                fn () => $this->checkIfHashExists($hash),
                'Checking if code is already uploaded for hash ' . $hash,
            );
            if ($alreadyExists) {
                info('Code already uploaded');
                return $hash;
            }
        }

        $this->createZip($config);

        $hash = $hash ?: sha1_file(self::FILENAME);
        if ($hash === false) {
            throw new RuntimeException('Failed to hash the zip file');
        }

        // Upload to S3
        $start = microtime(true);
        spin(
            function () use ($hash) {
                $this->doUpload($hash);
            },
            'Uploading code',
        );
        $duration = round(microtime(true) - $start, 1);
        info("Code uploaded in $duration seconds");

        $this->fs->remove('.supercharge');

        return $hash;
    }

    private function doUpload(string $hash): void
    {
        // Check if we should upload
        ['response' => $response, 'body' => $body] = $this->api->post('/api/runs/packages', [
            'hash' => $hash,
        ]);
        if ($response->getStatusCode() === 304) {
            info('Code already uploaded');
            return;
        }
        if (! is_array($body) || ! isset($body['url'], $body['headers'])) {
            throw new RuntimeException('Invalid response from the API');
        }
        $url = $body['url'];
        $headers = $body['headers'];

        // Upload to S3
        $client = new Client;
        $response = $client->put($url, [
            'body' => fopen(self::FILENAME, 'rb'),
            'headers' => $headers,
        ]);
        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Failed to upload code');
        }
    }

    private function createZip(Config $config): void
    {
        $this->fs->remove(self::FILENAME);
        if (! $this->fs->exists('.supercharge')) {
            $this->fs->mkdir('.supercharge');
        }

        $start = microtime(true);
        spin(
            function () use ($config) {
                $ignoredPaths = ['.supercharge/*', '.git/*', '.idea/*', ...$config->ignorePaths];
                $command = ['zip', '-r', self::FILENAME, '.'];
                foreach ($ignoredPaths as $path) {
                    $command[] = '-x';
                    $command[] = $path;
                }
                $process = new Process($command);
                $process->mustRun();
            },
            'Creating zip file',
        );
        // Zip the code
        $duration = round(microtime(true) - $start, 1);
        info("Zip file created in $duration seconds");
    }

    private function checkIfHashExists(string $hash): bool
    {
        ['response' => $response] = $this->api->post('/api/runs/packages', [
            'hash' => $hash,
        ]);
        return $response->getStatusCode() === 304;
    }
}
