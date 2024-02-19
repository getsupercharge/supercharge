<?php declare(strict_types=1);

namespace Supercharge\Cli\Commands;

use RuntimeException;
use Supercharge\Cli\Config\Config;
use Supercharge\Cli\Package;
use Supercharge\Cli\PHPUnit\PHPUnit;
use Supercharge\Cli\Runner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;
use function Termwind\render;

class PhpUnitCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('phpunit')
            ->setDescription('Run PHPUnit tests')
            ->addOption('project', null, InputOption::VALUE_REQUIRED, 'Supercharge project name')
            ->addOption('directory', null, InputOption::VALUE_REQUIRED, 'Directory from which to run the PHPUnit tests')
            ->addOption('binary', null, InputOption::VALUE_REQUIRED, 'Override the PHPUnit binary path')
            ->addOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Read configuration from XML file')
            ->addOption('max-tests', null, InputOption::VALUE_REQUIRED, 'Maximum number of tests to run', 0)
            ->addOption('hash', null, InputOption::VALUE_REQUIRED, 'Hash of the code to test')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startTime = microtime(true);

        render(<<<'HTML'
            <div class="py-1 ml-1">
                <div class="px-1 bg-blue-300 text-black">Supercharge</div>
            </div>
        HTML);

        $directory = $input->getOption('directory') ?: '.';
        if (! is_dir($directory)) {
            throw new RuntimeException("Directory $directory does not exist");
        }

        $config = Config::read();

        $project = $input->getOption('project') ?: $config->project;
        if ($project === null) {
            throw new RuntimeException('Project name is required, either as a CLI option or in the supercharge.yml file');
        }

        $hash = $input->getOption('hash');

        // Zip the code
        $package = new Package();
        $hash = $package->upload($config, $hash);

        // For testing we can limit the number of tests
        $maxTests = (int) $input->getOption('max-tests');

        // PHPUnit options
        $phpUnitOptions = [];
        if ($input->getOption('configuration') !== null) {
            $phpUnitOptions[] = '--configuration';
            $phpUnitOptions[] = $input->getOption('configuration');
        }

        $phpunit = new PHPUnit($directory, binary: $input->getOption('binary'), phpUnitOptions: $phpUnitOptions);
        $phpunit->checkIsInstalled();

        $allTests = spin(
            fn() => $phpunit->listTests(),
            'Listing tests',
        );

        $totalTests = count($allTests);
        if ($maxTests > 0) {
            $allTests = array_slice($allTests, 0, $maxTests);
            info("Found $totalTests PHPUnit tests, keeping " . count($allTests));
        } else {
            info('Found ' . count($allTests) . ' PHPUnit tests');
        }

        if (empty($allTests)) {
            error('No tests found, exiting');
            return 1;
        }

        $commands = $phpunit->prepareListOfCommands($allTests);

        $runner = new Runner($config);
        $report = $runner->runTests($project, $hash, $commands, $directory);

        // log to file
        file_put_contents('junit.xml', $report->dump());

        $report->display();

        $seconds = round(microtime(true) - $startTime);
        $durationAsString = ($seconds > 60) ? floor($seconds / 60) . 'min ' . ($seconds % 60) . 's' : $seconds . 's';
        $runDuration = round($report->getResults()['totalTime']);
        $runDurationAsString = ($runDuration > 60) ? floor($runDuration / 60) . 'min ' . ($runDuration % 60) . 's' : $runDuration . 's';
        render(<<<HTML
            <div class="ml-1">
                <span class="text-gray">Duration:</span> $durationAsString
                <span class="text-gray ml-1">(run time: $runDurationAsString)</span>
            </div>
        HTML);

        return $report->hasFailures() ? 1 : 0;
    }
}
