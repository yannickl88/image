<?php
namespace Yannickl88\Image;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Yannickl88\Image\Exception\FileNotFoundException;
use Yannickl88\Image\Exception\ImageException;
use Yannickl88\Image\Exception\UnsupportedExtensionException;

/**
 * @covers \Yannickl88\Image\Image
 */
class StaticImageTest extends TestCase
{
    private static $output_dir;

    public static function setUpBeforeClass(): void
    {
        self::$output_dir = __DIR__ . '/' . uniqid('out_', false);
    }

    public function allFilesProvider()
    {
        return [
            [Image::class, __DIR__ . '/resources/image1.gif'],
            [Image::class, __DIR__ . '/resources/image2.png'],
            [Image::class, __DIR__ . '/resources/image3.jpg'],
            [Image::class, __DIR__ . '/resources/image4.jpeg'],
            [Image::class, __DIR__ . '/resources/image5.gif'],
        ];
    }

    public function orientationProvider()
    {
        return [
            [Image::ORIENTATION_LANDSCAPE, __DIR__ . '/resources/image1.gif'],
            [Image::ORIENTATION_PORTRAIT, __DIR__ . '/resources/image2.png'],
            [Image::ORIENTATION_LANDSCAPE, __DIR__ . '/resources/image3.jpg'],
            [Image::ORIENTATION_LANDSCAPE, __DIR__ . '/resources/image4.jpeg'],
            [Image::ORIENTATION_LANDSCAPE, __DIR__ . '/resources/image5.gif'],
        ];
    }

    public function fitProvider(): array
    {
        return [
            [[0, 0, 100, 69], false, __DIR__ . '/resources/image1.gif'],
            [[0, 0, 100, 69], true, __DIR__ . '/resources/image1.gif'],
            [[0, 0, 80, 100], false, __DIR__ . '/resources/image2.png'],
            [[0, 0, 80, 100], true, __DIR__ . '/resources/image2.png'],
            [[0, 0, 100, 67], false, __DIR__ . '/resources/image3.jpg'],
            [[0, 0, 100, 67], true, __DIR__ . '/resources/image3.jpg'],
            [[0, 0, 100, 67], false, __DIR__ . '/resources/image4.jpeg'],
            [[0, 0, 100, 67], true, __DIR__ . '/resources/image4.jpeg'],
            [[0, 0, 100, 67], false, __DIR__ . '/resources/image5.gif'],
            [[0, 0, 100, 67], true, __DIR__ . '/resources/image5.gif'],
            [[0, 0, 60, 75], false, __DIR__ . '/resources/image7.png'],
            [[0, 0, 80, 100], true, __DIR__ . '/resources/image7.png'],
        ];
    }

    protected function tearDown(): void
    {
        $fs = new Filesystem();
        $fs->remove(self::$output_dir);
    }

    /**
     * @dataProvider allFilesProvider
     */
    public function testFromFile(string $expected_class, string $filename): void
    {
        self::assertInstanceOf($expected_class, Image::fromFile($filename));
    }

    public function testFromBadFileName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File name does not contain a . extension (example.jpg)');

        Image::fromFile(__DIR__ . '/resources/foobar');
    }

    public function testFromFileNotExists(): void
    {
        $this->expectException(FileNotFoundException::class);

        Image::fromFile('foobar.gif');
    }

    public function testFromFileUnsupported(): void
    {
        $this->expectException(UnsupportedExtensionException::class);

        Image::fromFile(__DIR__ . '/resources/image6.bmp');
    }

    public function testFromFileCorrupt(): void
    {
        $this->expectException(ImageException::class);

        Image::fromFile(__DIR__ . '/resources/corrupt.jpg');
    }

    public function testSaveBadFileName(): void
    {
        $image = Image::fromFile(__DIR__ . '/resources/image2.png');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File name does not contain a . extension (example.jpg)');

        $image->save(self::$output_dir . '/foobar');
    }

    public function testUnsupportedFileName(): void
    {
        $image = Image::fromFile(__DIR__ . '/resources/image2.png');

        $this->expectException(UnsupportedExtensionException::class);

        $image->save(self::$output_dir . '/foo.test');
    }

    /**
     * @dataProvider orientationProvider
     */
    public function testOrientation(int $expected, string $filename): void
    {
        $image = Image::fromFile($filename);

        self::assertSame($expected, $image->orientation());
    }

    /**
     * @dataProvider fitProvider
     */
    public function testFit(array $expected_rect, bool $exact, string $filename): void
    {
        $image = Image::fromFile($filename);

        self::assertSame($expected_rect, $image->fit(100, 100, $exact)->rect());
    }

    public function testGeneric(): void
    {
        $image = Image::fromFile(__DIR__ . '/resources/image2.png');

        self::assertSame(700, $image->width());
        self::assertSame(875, $image->height());
        self::assertSame([0, 0, 700, 875], $image->rect());
        self::assertSame([124, 192, 217, 0], $image->color(0, 0));
        self::assertSame([0, 0, 100, 100], $image->resize(100, 100)->rect());
        self::assertSame([0, 0, 100, 100], $image->crop([10, 10, 100, 100])->rect());
        self::assertIsString($image->data('.png'));

        $sampled = $image->sampleTo([0, 0, 50, 50]);

        self::assertSame(50, $sampled->width());
        self::assertSame(50, $sampled->height());
        self::assertSame([124, 192, 217, 0], $sampled->color(0, 0));

        $lower_quality = $image->quality(0.5);

        self::assertSame([124, 192, 217, 0], $lower_quality->color(0, 0));

        $fs = new Filesystem();
        $fs->mkdir(self::$output_dir);

        $image->save(self::$output_dir . '/out.png');

        self::assertFileExists(self::$output_dir . '/out.png');
    }

    public function testSavePng(): void
    {
        $image = Image::fromFile(__DIR__ . '/resources/image2.png');

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

        $image = Image::fromFile(__DIR__ . '/resources/image2.png');

        $fs = new Filesystem();
        $fs->mkdir(self::$output_dir);

        $image->save(self::$output_dir . '/out.webp');
        self::assertFileExists(self::$output_dir . '/out.webp');
    }

    public function testSampleToError(): void
    {
        $image = Image::fromFile(__DIR__ . '/resources/image2.png');

        $this->expectException(ImageException::class);
        $image->sampleTo([0, 0, 1, 1], [0, 0, 0, 0]);
    }

    /**
     * @dataProvider invalidQualityProvider
     */
    public function testQualityInvalid(int $quality): void
    {
        $image = Image::fromFile(__DIR__ . '/resources/image2.png');

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
        $image = Image::fromFile(__DIR__ . '/resources/image8.png');

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
    }
}
