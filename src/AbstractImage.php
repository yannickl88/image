<?php
declare(strict_types=1);

namespace Yannickl88\Image;

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
     * Return a list of extensions allows for saving. If the extension for
     * which 'save' is called not in the list, an error will be triggered.
     */
    abstract protected function supportedFileExtensionsForSaving(): array;

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

        $pos = strrpos($file, '.');
        if ($pos === false) {
            throw new \InvalidArgumentException("File name does not contain a . extension (example.jpg)");
        }

        $extension = substr($file, $pos);

        // Is it an animated fig? In that case, make an AnimatedImage. Else use StaticImage.
        // Install "sybio/gif-creator" and "sybio/gif-frame-extractor" for animated gif support.
        if ($extension === '.gif'
            && class_exists(\GifFrameExtractor\GifFrameExtractor::class)
            && \GifFrameExtractor\GifFrameExtractor::isAnimatedGif($file)
        ) {
            $gfe = new \GifFrameExtractor\GifFrameExtractor();
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
        $rgb = imagecolorsforindex($res, imagecolorat($res, $x, $y));

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

    public function fit(int $width, int $height, bool $exact = false): ImageInterface
    {
        $rect = $this->rect();

        $new_width = $rect[2];
        $new_height = $rect[3];
        $ratio = $new_width / $new_height;

        // Is it smaller than the given width AND height?
        if ($rect[2] < $width && $rect[3] < $height) {
            if (!$exact) {
                return $this;
            }

            $new_width = $width;
            $new_height = $new_width / $ratio;
        }

        if ($new_width > $width) {
            $new_width = $width;
            $new_height = $new_width / $ratio;
        }

        if ($new_height > $height) {
            $new_height = $height;
            $new_width = $new_height * $ratio;
        }

        return $this->sampleTo($rect, [0, 0, (int) round($new_width), (int) round($new_height)]);
    }

    public function save(string $filename): void
    {
        $pos = strrpos($filename, '.');
        if ($pos === false) {
            throw new \InvalidArgumentException("File name does not contain a . extension (example.jpg)");
        }

        $extension = substr($filename, $pos);
        $extensions = $this->supportedFileExtensionsForSaving();

        if (!in_array($extension, $extensions, true)) {
            throw new \LogicException(sprintf(
                'Cannot save image as "%s", it is not supported by the type. Supported extensions are: "%s".',
                $extension,
                implode('", "', $extensions)
            ));
        }

        file_put_contents($filename, $this->data($extension));
    }
}
