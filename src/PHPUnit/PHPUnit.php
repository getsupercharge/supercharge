<?php declare(strict_types=1);

namespace Supercharge\Cli\PHPUnit;

use Exception;
use RuntimeException;
use SimpleXMLElement;
use Symfony\Component\Process\Process;
use function Laravel\Prompts\info;

class PHPUnit
{
    private readonly string $binary;

    public function __construct(
        private readonly string $directory = '.',
        ?string $binary = 'vendor/bin/phpunit',
        private readonly array $phpUnitOptions = [],
    )
    {
        $this->binary = $binary ?: 'vendor/bin/phpunit';
    }

    public function checkIsInstalled(): void
    {
        if (! file_exists($this->directory . '/' . $this->binary)) {
            throw new RuntimeException('PHPUnit cannot be found. Install it with: composer require --dev phpunit/phpunit');
        }
    }

    /**
     * @return list<array{class: string, method: string, dataSet: string|null}>
     */
    public function listTests(): array
    {
        $allTests = [];

        $tmpFile = tempnam(sys_get_temp_dir(), 'supercharge');
        $process = new Process([
            $this->binary,
            ...$this->phpUnitOptions,
            '--list-tests-xml',
            $tmpFile,
        ], $this->directory);
        $process->mustRun();

        // Read the XML file
        $xmlContent = file_get_contents($tmpFile);
        try {
            $xml = new SimpleXMLElement($xmlContent);
        } catch (Exception $e) {
            throw new RuntimeException('Could not parse PHPUnit XML output from file ' . $tmpFile . ': ' . $e->getMessage(), 0, $e);
        }
        foreach ($xml->testCaseClass as $testClass) {
            foreach ($testClass->testCaseMethod as $testMethod) {
                $className = (string) $testClass['name'];
                $method = (string) $testMethod['name'];
                $dataSet = $testMethod['dataSet'] ? (string) $testMethod['dataSet'] : null;
                $allTests[] = [
                    'class' => $className,
                    'method' => $method,
                    'dataSet' => $dataSet,
                ];
            }
        }

        unlink($tmpFile);

        return array_values($allTests);
    }

    /**
     * @param list<array{class: string, method: string, dataSet: string|null}> $allTests
     * @param int $numberOfChunks The number of chunks to create
     * @return list<string> A list of CLI commands.
     */
    public function prepareListOfCommands(array $allTests, int $numberOfChunks = 30): array
    {
        $chunks = array_chunk($allTests, (int) ceil(count($allTests) / $numberOfChunks));

        $testsPerChunk = count($chunks[0]);
        info("$testsPerChunk tests in each instance");

        return array_map(function (array $tests) {
            $patterns = [];
            foreach ($tests as $test) {
                // The filter is a regex so we must escape arguments (e.g. `\` must be escaped)
                $testClass = preg_quote($test['class'], '/');
                $testMethod = preg_quote($test['method'], '/');
                // Each data set is a separate test
                // That is because some tests are very slow for each data set
                $pattern = "$testClass::$testMethod";
                if ($test['dataSet'] ?? false) {
                    $pattern .= " with data set " . preg_quote($test['dataSet'], '/');
                }
                $patterns[] = $pattern;
            }
            $filter = '^(' . implode('|', array_map(fn($pattern) => "($pattern)", $patterns)) . ')$';

            $commandParts = [
                $this->binary,
                ...$this->phpUnitOptions,
                '--filter',
                $filter,
                // Add junit logging
                '--log-junit',
                '/tmp/junit/junit.xml',
            ];
            return implode(' ', array_map('escapeshellarg', $commandParts));
        }, $chunks);
    }
}
