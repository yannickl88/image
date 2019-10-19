<?php
declare(strict_types=1);

namespace Yannickl88\Image;

use GifFrameExtractor\GifFrameExtractor;
use Yannickl88\Image\Exception\FileNotFoundException;
use Yannickl88\Image\Exception\ImageException;
use Yannickl88\Image\Exception\UnsupportedExtensionException;

abstract class AbstractImage implements ImageInterface
{
    /**
     * Return the resource of the representing image.
     *
     * @return resource
     */
    abstract protected function resource();

    /**
     * Create an image based on a file.
     *
     * @param string $file
     * @return ImageInterface
     * @throws FileNotFoundException
     * @throws ImageException
     */
    public static function fromFile(string $file): ImageInterface
    {
        if (!file_exists($file)) {
            throw new FileNotFoundException("Cannot find file \"$file\".");
        }

        $extension = substr($file, strrpos($file, '.'));

        // Is it an animated fig? In that case, make an AnimatedImage. Else use StaticImage.
        if ($extension === '.gif' && GifFrameExtractor::isAnimatedGif($file)) {
            $gfe = new GifFrameExtractor();
            $gfe->extract($file);

            $dimensions = $gfe->getFrameDimensions();

            $width = max(array_column($dimensions, 'width'));
            $height = max(array_column($dimensions, 'height'));

            return new AnimatedImage(
                $gfe->getFrameImages(), $gfe->getFrameDurations(), $width, $height
            );
        }

        switch ($extension) {
            case '.png':
                $image = @imagecreatefrompng($file);
                break;
            case '.jpg':
            case '.jpeg':
                $image = @imagecreatefromjpeg($file);
                break;
            case '.gif':
                $image = @imagecreatefromgif($file);
                break;
            default:
                throw new UnsupportedExtensionException($extension, [".png", ".jp(e)g", ".gif"]);
        }

        if (false === $image) {
            throw new ImageException("Could not create image from file \"$file\".");
        }

        return new StaticImage($image);
    }

    public function rect(): array
    {
        return [0, 0, $this->width(), $this->height()];
    }

    public function orientation(): int
    {
        if ($this->width() > $this->height()) {
            return ImageInterface::ORIENTATION_LANDSCAPE;
        }

        return ImageInterface::ORIENTATION_PORTRAIT;
    }

    public function color(int $x, int $y): array
    {
        $res = $this->resource();
        $rgb = imagecolorsforindex($res, imagecolorat($this->resource(), $x, $y));

        return array_values($rgb);
    }

    public function resize(int $width, int $height): ImageInterface
    {
        return $this->sampleTo($this->rect(), [0, 0, $width, $height]);
    }

    public function crop(array $rect): ImageInterface
    {
        return $this->sampleTo($rect, [0, 0, $rect[2], $rect[3]]);
    }

    public function save(string $filename): void
    {
        file_put_contents($filename, $this->data());
    }
}