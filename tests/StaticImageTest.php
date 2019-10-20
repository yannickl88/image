<?php
namespace Yannickl88\Image;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Yannickl88\Image\Exception\ImageException;

/**
 * @covers \Yannickl88\Image\StaticImage
 */
class StaticImageTest extends TestCase
{
    public function testGeneric(): void
    {
        $image = AbstractImage::fromFile(__DIR__ . '/resources/image2.png');

        self::assertSame(700, $image->width());
        self::assertSame(875, $image->height());
        self::assertSame(0.0, $image->duration());
        self::assertSame([124, 192, 217, 0], $image->color(0, 0));
        self::assertIsString($image->data());

        $sampled = $image->sampleTo([0, 0, 50, 50]);

        self::assertSame(50, $sampled->width());
        self::assertSame(50, $sampled->height());
        self::assertSame([124, 192, 217, 0], $sampled->color(0, 0));

        $cut = $image->slice(0, 1);

        self::assertSame(0.0, $cut->duration());
        self::assertSame([124, 192, 217, 0], $cut->color(0, 0));

        $lower_quality = $image->quality(0.5);

        self::assertSame([124, 192, 217, 0], $lower_quality->color(0, 0));
    }

    public function testSampleToError(): void
    {
        $image = AbstractImage::fromFile(__DIR__ . '/resources/image2.png');

        $this->expectException(ImageException::class);
        $image->sampleTo([0, 0, 1, 1], [0, 0, 0, 0]);
    }

    /**
     * @dataProvider invalidQualityProvider
     */
    public function testQualityInvalid(int $quality): void
    {
        $image = AbstractImage::fromFile(__DIR__ . '/resources/image2.png');

        $this->expectException(\InvalidArgumentException::class);
        $image->quality($quality);
    }

    public function invalidQualityProvider()
    {
        return [
            [-1],
            [10],
            [42],
        ];
    }
}
