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
        $image = AbstractImage::fromFile(__DIR__ . '/resources/image2.png');

        self::assertSame(700, $image->width());
        self::assertSame(875, $image->height());
        self::assertSame(0.0, $image->duration());
        self::assertSame([124, 192, 217, 0], $image->color(0, 0));
        self::assertIsString($image->data('.png'));

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

    public function testSavePng(): void
    {
        $image = AbstractImage::fromFile(__DIR__ . '/resources/image2.png');

        $fs = new Filesystem();
        $fs->mkdir(self::$output_dir);

        $image->save(self::$output_dir . '/out.png');
        self::assertFileExists(self::$output_dir . '/out.png');
    }

    public function testSaveWebp(): void
    {
        if (!function_exists('imagewebp')) {
            $this->markTestSkipped('No webp support from current PHP version.');
        }

        $image = AbstractImage::fromFile(__DIR__ . '/resources/image2.png');

        $fs = new Filesystem();
        $fs->mkdir(self::$output_dir);

        $image->save(self::$output_dir . '/out.webp');
        self::assertFileExists(self::$output_dir . '/out.webp');
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

    public function testTransparency(): void
    {
        $image = AbstractImage::fromFile(__DIR__ . '/resources/image8.png');

        self::assertSame(127, $image->color(0, 0)[3]);
        self::assertSame(0, $image->color(50, 50)[3]);

        try {
            $res = imagecreatefromstring($image->data('.png'));

            self::assertSame(127, imagecolorsforindex($res, imagecolorat($res, 0, 0))['alpha']);
            self::assertSame(0, imagecolorsforindex($res, imagecolorat($res, 50, 50))['alpha']);
        } finally {
            imagedestroy($res);
        }

        try {
            $res = imagecreatefromstring($image->resize(50, 50)->data('.png'));

            self::assertSame(127, imagecolorsforindex($res, imagecolorat($res, 0, 0))['alpha']);
            self::assertSame(0, imagecolorsforindex($res, imagecolorat($res, 25, 25))['alpha']);
        } finally {
            imagedestroy($res);
        }

        try {
            $res = imagecreatefromstring($image->slice(0)->data('.png'));

            self::assertSame(127, imagecolorsforindex($res, imagecolorat($res, 0, 0))['alpha']);
            self::assertSame(0, imagecolorsforindex($res, imagecolorat($res, 50, 50))['alpha']);
        } finally {
            imagedestroy($res);
        }
    }
}
