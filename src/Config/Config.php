<?php declare(strict_types=1);

namespace Supercharge\Cli\Config;

use Symfony\Component\Yaml\Yaml;

class Config
{
    public static function read(): self
    {
        $file = 'supercharge.yml';
        if (! file_exists($file)) {
            return new self;
        }

        $config = Yaml::parse(file_get_contents($file));

        return new self(...$config);
    }

    /**
     * @param string[] $beforeCommands
     * @param string[] $ignorePaths
     */
    public function __construct(
        public array $beforeCommands = [],
        public array $ignorePaths = [],
    )
    {
    }
}