<?php declare(strict_types=1);

namespace Supercharge\Cli\Config;

use JsonException;
use Symfony\Component\Filesystem\Filesystem;

class TokenStorage
{
    private string $path;
    private Filesystem $fs;

    public function __construct()
    {
        $this->fs = new Filesystem;
        // In the home directory
        $this->path = $_SERVER['HOME'] . '/.supercharge/config.json';
    }

    /**
     * @throws JsonException
     */
    public function store(string $token): void
    {
        if (! $this->fs->exists(dirname($this->path))) {
            $this->fs->mkdir(dirname($this->path));
        }

        file_put_contents($this->path, json_encode([
            'token' => $token,
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
    }

    /**
     * @throws JsonException
     */
    public function load(): ?string
    {
        if ($_SERVER['SUPERCHARGE_TOKEN'] ?? null) {
            return $_SERVER['SUPERCHARGE_TOKEN'];
        }

        if (! $this->fs->exists($this->path)) {
            return null;
        }

        $config = json_decode(file_get_contents($this->path), true, 512, JSON_THROW_ON_ERROR);
        return $config['token'];
    }
}
