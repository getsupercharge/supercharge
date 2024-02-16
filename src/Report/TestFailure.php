<?php declare(strict_types=1);

namespace Supercharge\Cli\Report;

class TestFailure
{
    public function __construct(
        public string $testName,
        public string $message,
    )
    {
    }
}
