<?php
namespace Yannickl88\Image;

use PHPUnit\Framework\TestCase;
use Yannickl88\Image\Exception\ImageException;

/**
 * @covers \Yannickl88\Image\AnimatedImage
 */
class AnimatedImageTest extends TestCase
{
    public function testGeneric(): void
    {
        $image = AbstractImage::fromFile(__DIR__ . '/resources/image1.gif');

        self::assertSame(245, $image->width());
        self::assertSame(170, $image->height());
        self::assertSame([157, 148, 132, 0], $image->color(0, 0));
        self::assertIsString($image->data());

        $sampled = $image->sampleTo([0, 0, 50, 50]);

        self::assertSame(50, $sampled->width());
        self::assertSame(50, $sampled->height());
        self::assertSame([157, 148, 132, 0], $sampled->color(0, 0));
    }

    public function testSampleToError(): void
    {
        $image = AbstractImage::fromFile(__DIR__ . '/resources/image1.gif');

        $this->expectException(ImageException::class);
        $image->sampleTo([0, 0, 1, 1], [0, 0, 0, 0]);
    }
}
