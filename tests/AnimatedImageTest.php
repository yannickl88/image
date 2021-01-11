<?php
namespace Yannickl88\Image;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Yannickl88\Image\Exception\ImageException;

/**
 * @covers \Yannickl88\Image\AnimatedImage
 */
class AnimatedImageTest extends TestCase
{
    private static $output_dir;

    public static function setUpBeforeClass(): void
    {
        self::$output_dir = __DIR__ . '/' . uniqid('out_', false);
    }

    protected function tearDown(): void
    {
        $fs = new Filesystem();
        $fs->remove(self::$output_dir);
    }

    public function testGeneric(): void
    {
        $image = AbstractImage::fromFile(__DIR__ . '/resources/image1.gif');

        self::assertSame(245, $image->width());
        self::assertSame(170, $image->height());
        self::assertSame(1.92, $image->duration());
        self::assertSame([157, 148, 132, 0], $image->color(0, 0));
        self::assertIsString($image->data('.gif'));

        $sampled = $image->sampleTo([0, 0, 50, 50]);

        self::assertSame(50, $sampled->width());
        self::assertSame(50, $sampled->height());
        self::assertSame([157, 148, 132, 0], $sampled->color(0, 0));

        $lower_quality = $image->quality(0.5);

        self::assertSame([158, 150, 139, 0], $lower_quality->color(0, 0));

        $cut = $image->slice(0, 1);

        self::assertSame(0.06, $cut->duration());
        self::assertSame([157, 148, 132, 0], $cut->color(0, 0));

        $cut_low_quality = $lower_quality->slice(0, 1)->sampleTo([0, 0, 50, 50]);

        self::assertSame(0.06, $cut_low_quality->duration());
        self::assertSame([156, 150, 140, 0], $cut_low_quality->color(0, 0));

        $fs = new Filesystem();
        $fs->mkdir(self::$output_dir);

        $sampled->save(self::$output_dir . '/out.gif');

        self::assertFileExists(self::$output_dir . '/out.gif');
    }

    public function testSampleToError(): void
    {
        $image = AbstractImage::fromFile(__DIR__ . '/resources/image1.gif');

        $this->expectException(ImageException::class);
        $image->sampleTo([0, 0, 1, 1], [0, 0, 0, 0]);
    }

    public function testQualityInvalid(): void
    {
        $image = AbstractImage::fromFile(__DIR__ . '/resources/image1.gif');

        $this->expectException(\InvalidArgumentException::class);
        $image->quality(-42);
    }

    public function testSliceInvalid(): void
    {
        $image = AbstractImage::fromFile(__DIR__ . '/resources/image1.gif');

        $this->expectException(\InvalidArgumentException::class);
        $image->slice(0, 1000);
    }
}
