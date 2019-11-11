<?php declare(strict_types=1);

namespace Supercharge\Test;

require_once __DIR__ . '/mock-config.php';

use PHPUnit\Framework\TestCase;
use Supercharge\SuperchargeException;

class SuperchargeUrlTest extends TestCase
{
    public function testSimpleUrl(): void
    {
        $url = supercharge('/foo/bar.jpg');
        $this->assertEquals('/foo/bar.jpg', (string) $url);
    }

    public function testResize(): void
    {
        $url = supercharge('/foo/bar.jpg')
            ->width(250);
        $this->assertEquals('/foo/bar.jpg?w=250', (string) $url);

        $url = supercharge('/foo/bar.jpg')
            ->height(250);
        $this->assertEquals('/foo/bar.jpg?h=250', (string) $url);

        $url = supercharge('/foo/bar.jpg')
            ->width(250)
            ->height(450);
        $this->assertEquals('/foo/bar.jpg?w=250&h=450', (string) $url);
    }

    public function testFit(): void
    {
        $url = supercharge('/foo/bar.jpg')
            ->width(250)
            ->fit('crop');
        $this->assertEquals('/foo/bar.jpg?w=250&fit=crop', (string) $url);

        $url = supercharge('/foo/bar.jpg')
            ->width(250)
            ->fit('max');
        $this->assertEquals('/foo/bar.jpg?w=250&fit=max', (string) $url);

        $url = supercharge('/foo/bar.jpg')
            ->width(250)
            ->fit('stretch');
        $this->assertEquals('/foo/bar.jpg?w=250&fit=stretch', (string) $url);

        $url = supercharge('/foo/bar.jpg')
            ->width(250)
            ->fit('contain');
        $this->assertEquals('/foo/bar.jpg?w=250&fit=contain', (string) $url);
    }

    public function testInvalidFitValue(): void
    {
        $this->expectException(SuperchargeException::class);
        $this->expectExceptionMessage('Invalid value for the `fit` parameter in `supercharge()`, it must be one of "contain, max, stretch, crop"');

        supercharge('/foo/bar.jpg')->fit('foo');
    }

    public function testFlip(): void
    {
        $url = supercharge('/foo/bar.jpg')
            ->flipVertical();
        $this->assertEquals('/foo/bar.jpg?flip=v', (string) $url);

        $url = supercharge('/foo/bar.jpg')
            ->flipHorizontal();
        $this->assertEquals('/foo/bar.jpg?flip=h', (string) $url);

        $url = supercharge('/foo/bar.jpg')
            ->flipBoth();
        $this->assertEquals('/foo/bar.jpg?flip=both', (string) $url);
    }

    public function testPixelRatio(): void
    {
        $url = supercharge('/foo/bar.jpg')
            ->pixelRatio(2);
        $this->assertEquals('/foo/bar.jpg?dpr=2', (string) $url);
    }
}
