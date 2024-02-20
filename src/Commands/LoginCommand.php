<?php declare(strict_types=1);

namespace Supercharge\Cli\Commands;

use RuntimeException;
use Supercharge\Cli\Config\Config;
use Supercharge\Cli\Config\TokenStorage;
use Supercharge\Cli\Package;
use Supercharge\Cli\PHPUnit\PHPUnit;
use Supercharge\Cli\Runner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\info;
use function Laravel\Prompts\password;
use function Laravel\Prompts\spin;
use function Termwind\render;

class LoginCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('login')
            ->setDescription('Log into Supercharge')
            ->addOption('token', null, InputOption::VALUE_REQUIRED, 'Supercharge CLI token')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Open https://getsupercharge.com/cli/connect in the browser
        $token = $input->getOption('token');

        if ($token === null) {
            $this->openBrowser('https://getsupercharge.com/cli/connect');
            info('Please log in, create a CLI token, then paste it below.');

            $token = password('Supercharge CLI token');
        } elseif (! is_string($token)) {
            throw new RuntimeException('The token must be a string');
        }

        $tokenStorage = new TokenStorage;
        $tokenStorage->store($token);

        return 0;
    }

    private function openBrowser(string $url): void
    {
        if (PHP_OS_FAMILY === 'Darwin') {
            exec('open ' . escapeshellarg($url));
        } elseif (PHP_OS_FAMILY === 'Linux') {
            exec('xdg-open ' . escapeshellarg($url));
        } elseif (PHP_OS_FAMILY === 'Windows') {
            exec('start ' . escapeshellarg($url));
        }

        info("If the browser does not open, open $url in your browser.");
    }
}
