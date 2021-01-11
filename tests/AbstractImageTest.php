<?php
namespace Yannickl88\Image;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Yannickl88\Image\Exception\FileNotFoundException;
use Yannickl88\Image\Exception\ImageException;
use Yannickl88\Image\Exception\UnsupportedExtensionException;

/**
 * @covers \Yannickl88\Image\AbstractImage
 */
class AbstractImageTest extends TestCase
{
    private static $output_dir;

    public static function setUpBeforeClass(): void
    {
        self::$output_dir = __DIR__ . '/' . uniqid('out_', false);
    }

    public function allFilesProvider()
    {
        return [
            [AnimatedImage::class, __DIR__ . '/resources/image1.gif'],
            [StaticImage::class, __DIR__ . '/resources/image2.png'],
            [StaticImage::class, __DIR__ . '/resources/image3.jpg'],
            [StaticImage::class, __DIR__ . '/resources/image4.jpeg'],
            [StaticImage::class, __DIR__ . '/resources/image5.gif'],
        ];
    }

    public function orientationProvider()
    {
        return [
            [ImageInterface::ORIENTATION_LANDSCAPE, __DIR__ . '/resources/image1.gif'],
            [ImageInterface::ORIENTATION_PORTRAIT, __DIR__ . '/resources/image2.png'],
            [ImageInterface::ORIENTATION_LANDSCAPE, __DIR__ . '/resources/image3.jpg'],
            [ImageInterface::ORIENTATION_LANDSCAPE, __DIR__ . '/resources/image4.jpeg'],
            [ImageInterface::ORIENTATION_LANDSCAPE, __DIR__ . '/resources/image5.gif'],
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
        self::assertInstanceOf($expected_class, AbstractImage::fromFile($filename));
    }

    public function testFromBadFileName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File name does not contain a . extension (example.jpg)');

        AbstractImage::fromFile(__DIR__ . '/resources/foobar');
    }

    public function testFromFileNotExists(): void
    {
        $this->expectException(FileNotFoundException::class);

        AbstractImage::fromFile('foobar.gif');
    }

    public function testFromFileUnsupported(): void
    {
        $this->expectException(UnsupportedExtensionException::class);

        AbstractImage::fromFile(__DIR__ . '/resources/image6.bmp');
    }

    public function testFromFileCorrupt(): void
    {
        $this->expectException(ImageException::class);

        AbstractImage::fromFile(__DIR__ . '/resources/corrupt.jpg');
    }

    public function testGeneric(): void
    {
        $image = AbstractImage::fromFile(__DIR__ . '/resources/image2.png');

        self::assertSame([0, 0, 700, 875], $image->rect());
        self::assertSame([124, 192, 217, 0], $image->color(0, 0));
        self::assertSame([0, 0, 100, 100], $image->resize(100, 100)->rect());
        self::assertSame([0, 0, 100, 100], $image->crop([10, 10, 100, 100])->rect());

        $fs = new Filesystem();
        $fs->mkdir(self::$output_dir);

        $image->save(self::$output_dir . '/out.png');

        self::assertFileExists(self::$output_dir . '/out.png');
    }

    public function testSaveBadFileName(): void
    {
        $image = AbstractImage::fromFile(__DIR__ . '/resources/image2.png');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File name does not contain a . extension (example.jpg)');

        $image->save(self::$output_dir . '/foobar');
    }

    public function testUnsupportedFileName(): void
    {
        $image = AbstractImage::fromFile(__DIR__ . '/resources/image2.png');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot save image as ".test", it is not supported by the type. Supported extensions are: ".png", ".webp".');

        $image->save(self::$output_dir . '/foo.test');
    }

    /**
     * @dataProvider orientationProvider
     */
    public function testOrientation(int $expected, string $filename): void
    {
        $image = AbstractImage::fromFile($filename);

        self::assertSame($expected, $image->orientation());
    }

    /**
     * @dataProvider fitProvider
     */
    public function testFit(array $expected_rect, bool $exact, string $filename): void
    {
        $image = AbstractImage::fromFile($filename);

        self::assertSame($expected_rect, $image->fit(100, 100, $exact)->rect());
    }
}
