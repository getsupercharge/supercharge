<?php declare(strict_types=1);

namespace Supercharge\Cli\Report;

use DOMDocument;
use function Termwind\render;

class JUnitReport
{
    private DOMDocument $xmlData;

    public function __construct()
    {
        // Use DomDocument instead of SimpleXML
        $this->xmlData = new DOMDocument('1.0', 'UTF-8');
        $this->xmlData->loadXML('<testsuites></testsuites>');
    }

    public function merge(string $xml): void
    {
        $xmlData = new DOMDocument('1.0', 'UTF-8');
        $success = @$xmlData->loadXML($xml);
        if (! $success) {
            throw new InvalidXml("Could not load invalid XML: \n$xml");
        }
        foreach ($xmlData->documentElement->childNodes as $testsuite) {
            $this->xmlData->documentElement->appendChild(
                $this->xmlData->importNode($testsuite, true),
            );
        }
    }

    public function mergeReport(JUnitReport $report): void
    {
        foreach ($report->xmlData->documentElement->childNodes as $testsuite) {
            $this->xmlData->documentElement->appendChild(
                $this->xmlData->importNode($testsuite, true),
            );
        }
    }

    public function addError(string $testName, string $errorDetails): void
    {
        $testName = htmlspecialchars($testName, ENT_XML1);
        $errorDetails = nl2br(htmlspecialchars($errorDetails, ENT_XML1, 'UTF-8'));
        $xml = <<<XML
            <testsuites>
                <testsuite name="" tests="1" assertions="0" errors="1" warnings="0" failures="0" skipped="0" time="0">
                    <testsuite name="Tests" tests="1" assertions="0" errors="1" warnings="0" failures="0" skipped="0" time="0">
                        <testcase name="$testName" assertions="0">
                            <error type="Error">$errorDetails</error>
                        </testcase>
                    </testsuite>
                </testsuite>
            </testsuites>
        XML;
        try {
            $this->merge($xml);
        } catch (InvalidXml) {
            $xml = <<<XML
                <testsuites>
                    <testsuite name="" tests="1" assertions="0" errors="1" warnings="0" failures="0" skipped="0" time="0">
                        <testsuite name="Tests" tests="1" assertions="0" errors="1" warnings="0" failures="0" skipped="0" time="0">
                            <testcase name="$testName" assertions="0">
                                <error type="Error">Invalid XML in the JUnit report, the original test output could not be reported</error>
                            </testcase>
                        </testsuite>
                    </testsuite>
                </testsuites>
            XML;
            $this->merge($xml);
        }
    }

    public function dump(): string
    {
        return $this->xmlData->saveXML();
    }

    public function display(): void
    {
        $failures = $this->getFailures();

        foreach ($failures as $failure) {
            $message = nl2br(htmlspecialchars($failure->message, ENT_HTML5, 'UTF-8'));
            render(<<<HTML
                <div class="my-1">
                    <div>
                        <span class="px-1 font-bold bg-red text-white mr-1">FAILED</span>
                        <span class="font-bold">$failure->testName</span>
                    </div>
                    <div>
                        $message
                    </div>
                </div>
            HTML,
            );
        }

        $results = $this->getResults();
        $reportLine = [];
        if ($results['failure'] > 0) {
            $reportLine[] = "<span class='font-bold text-red'>{$results['failure']} failed</span>";
        }
        if ($results['error'] > 0) {
            $reportLine[] = "<span class='font-bold text-red'>{$results['error']} errored</span>";
        }
        if ($results['skipped'] > 0) {
            $reportLine[] = "<span class='font-bold text-yellow'>{$results['skipped']} skipped</span>";
        }
        if ($results['success'] > 0) {
            $reportLine[] = "<span class='font-bold text-green'>{$results['success']} passed</span>";
        }
        $reportString = implode(', ', $reportLine);

        render(<<<HTML
            <div class="my-1 ml-1 text-gray">
                Tests: $reportString
                ({$results['total']} tests, {$results['assertions']} assertions)
            </div>
        HTML,
        );
    }

    public function hasFailures(): bool
    {
        $results = $this->getResults();
        return $results['failure'] > 0 || $results['error'] > 0;
    }

    /**
     * Number of tests that ran successfully
     *
     * @return array{total: int, success: int, failure: int, error: int}
     */
    public function getResults(): array
    {
        // For each `testsuite` root node, get the `tests` attribute and sum them
        $tests = 0;
        $failures = 0;
        $errors = 0;
        $skipped = 0;
        $assertions = 0;
        $totalTime = 0;
        foreach ($this->xmlData->getElementsByTagName('testsuite') as $testsuite) {
            // Only count root nodes
            if ($testsuite->parentNode !== $this->xmlData->documentElement) {
                continue;
            }

            $tests += (int) $testsuite->getAttribute('tests');
            $failures += (int) $testsuite->getAttribute('failures');
            $errors += (int) $testsuite->getAttribute('errors');
            $skipped += (int) $testsuite->getAttribute('skipped');
            $assertions += (int) $testsuite->getAttribute('assertions');
            $totalTime += (float) $testsuite->getAttribute('time');
        }

        return [
            'total' => $tests,
            'success' => $tests - $failures - $errors,
            'failure' => $failures,
            'error' => $errors,
            'skipped' => $skipped,
            'assertions' => $assertions,
            'totalTime' => $totalTime,
        ];
    }

    /**
     * @return TestFailure[]
     */
    public function getFailures(): array
    {
        $failures = [];
        foreach ($this->xmlData->getElementsByTagName('failure') as $failure) {
            $testName = $failure->parentNode->getAttribute('name');
            $message = $failure->getAttribute('message') ?: $failure->textContent;
            $failures[] = new TestFailure($testName, $message);
        }
        foreach ($this->xmlData->getElementsByTagName('error') as $error) {
            $testName = $error->parentNode->getAttribute('name');
            $message = $error->getAttribute('message') ?: $error->textContent;
            $failures[] = new TestFailure($testName, $message);
        }
        return $failures;
    }
}
