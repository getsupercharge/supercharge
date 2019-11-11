<?php declare(strict_types=1);

namespace Supercharge;

class SuperchargeUrl
{
    private const VALID_FIT = ['contain', 'max', 'stretch', 'crop'];

    /** @var string */
    private $imagePath;
    /** @var array */
    private $options = [];

    public function __construct(string $imagePath)
    {
        $this->imagePath = $imagePath;
    }

    public function __toString(): string
    {
        $queryString = '';
        if (! empty($this->options)) {
            $queryString = http_build_query($this->options);
        }

        $baseUrl = config('supercharge.url');

        $imageUrlComponents = parse_url($this->imagePath);
        $imagePath = $imageUrlComponents['path'] ?? '';

        $existingQueryString = $imageUrlComponents['query'] ?? null;
        if ($existingQueryString) {
            if ($queryString) {
                $queryString .= '&' . $existingQueryString;
            } else {
                $queryString = $existingQueryString;
            }
        }

        if ($queryString) {
            $queryString = '?' . $queryString;
        }

        if (! $baseUrl) {
            return $imagePath . $queryString;
        }

        return $baseUrl . '/' . ltrim($imagePath, '/') . $queryString;
    }

    /**
     * Force the width of the image.
     */
    public function width(int $width): self
    {
        $this->options['w'] = $width;

        return $this;
    }

    /**
     * Force the height of the image.
     */
    public function height(int $height): self
    {
        $this->options['h'] = $height;

        return $this;
    }

    /**
     * Configure how the resizing will be applied.
     */
    public function fit(string $method): self
    {
        if (! in_array($method, self::VALID_FIT, true)) {
            throw new SuperchargeException(sprintf(
                'Invalid value for the `fit` parameter in `supercharge()`, it must be one of "%s"',
                implode(', ', self::VALID_FIT)
            ));
        }

        $this->options['fit'] = $method;

        return $this;
    }

    /**
     * Flip the image vertically.
     */
    public function flipVertical(): self
    {
        $this->options['flip'] = 'v';

        return $this;
    }

    /**
     * Flip the image vertically.
     */
    public function flipHorizontal(): self
    {
        $this->options['flip'] = 'h';

        return $this;
    }

    /**
     * Flip the image vertically and horizontally.
     */
    public function flipBoth(): self
    {
        $this->options['flip'] = 'both';

        return $this;
    }

    /**
     * Set the device pixel ratio (1 to 8).
     */
    public function pixelRatio(int $ratio): self
    {
        $this->options['dpr'] = $ratio;

        return $this;
    }
}
