<?php declare(strict_types=1);

namespace Supercharge\Cli\Config;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class Config
{
    public static function read(): self
    {
        $file = 'supercharge.yml';
        if (! file_exists($file)) {
            return new self;
        }

        $yaml = file_get_contents($file);
        if ($yaml === false) {
            throw new RuntimeException('Failed to read supercharge.yml');
        }
        $config = Yaml::parse($yaml);
        if (! is_array($config)) {
            throw new RuntimeException('Invalid config in supercharge.yml');
        }

        return new self(...$config);
    }

    /**
     * @param string[] $beforeCommands
     * @param string[] $ignorePaths
     * @param string[] $environment
     */
    public function __construct(
        public ?string $project = null,
        public array $beforeCommands = [],
        public array $ignorePaths = [],
        public array $environment = [],
    )
    {
    }
}
