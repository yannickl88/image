<?php
declare(strict_types=1);

namespace Yannickl88\Image;

use Yannickl88\Image\Exception\FileNotFoundException;
use Yannickl88\Image\Exception\ImageException;
use Yannickl88\Image\Exception\UnsupportedExtensionException;

/**
 * Generic image wrapper, support a range of transformation and properties for
 * a given image. All operations will return a copy with the applied operation.
 */
class Image
{
    public const ORIENTATION_LANDSCAPE = 1;
    public const ORIENTATION_PORTRAIT = 2;

    private $resource;
    private $imageWidth;
    private $imageHeight;
    private $quality;

    /**
     * Create an image based on a file.
     *
     * @param string $file
     * @return self
     * @throws FileNotFoundException
     * @throws ImageException
     */
    public static function fromFile(string $file): self
    {
        if (!file_exists($file)) {
            throw new FileNotFoundException("Cannot find file \"$file\".");
        }

        $pos = strrpos($file, '.');
        if ($pos === false) {
            throw new \InvalidArgumentException("File name does not contain a . extension (example.jpg)");
        }

        $extension = substr($file, $pos);

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

        return new Image($image);
    }

    /**
     * @param resource $resource
     * @param int|null $quality
     */
    private function __construct($resource, ?int $quality = null)
    {
        $this->resource = $resource;
        $this->quality = $quality;

        if (is_resource($this->resource)) {
            // Enable alpha blending
            imagealphablending($this->resource, false);
            imagesavealpha($this->resource, true);
        }
    }

    public function __destruct()
    {
        if (is_resource($this->resource)) {
            imagedestroy($this->resource);
        }
    }

    /**
     * Return bounding rectangle of the image. The array will be in order of: [x, y, width, height]
     *
     * @return int[]
     */
    public function rect(): array
    {
        return [0, 0, $this->width(), $this->height()];
    }

    /**
     * Return the width of the image.
     *
     * @return int
     */
    public function width(): int
    {
        if (null === $this->imageWidth) {
            $this->imageWidth = imagesx($this->resource);
        }

        return $this->imageWidth;
    }

    /**
     * Return the height of the image.
     *
     * @return int
     */
    public function height(): int
    {
        if (null === $this->imageHeight) {
            $this->imageHeight = imagesy($this->resource);
        }

        return $this->imageHeight;
    }

    /**
     * Return the orientation of the image. In the case where both width and height are equal, the image will be
     * considered portrait.
     *
     * @see self::ORIENTATION_LANDSCAPE
     * @see self::ORIENTATION_PORTRAIT
     *
     * @return int
     */
    public function orientation(): int
    {
        if ($this->width() > $this->height()) {
            return self::ORIENTATION_LANDSCAPE;
        }

        return self::ORIENTATION_PORTRAIT;
    }

    /**
     * Return the color at the given coordinate. Returned color is in RGBA format. ([red, green, blue, alpha]) where
     * each channel is a value between 0 and 255.
     *
     * @param int $x
     * @param int $y
     * @return int[]
     */
    public function color(int $x, int $y): array
    {
        $res = $this->resource;
        $rgb = imagecolorsforindex($res, imagecolorat($res, $x, $y));

        return array_values($rgb);
    }

    /**
     * Return an image with the given quality. Quality must be between 0 and 1 where 0 is low quality and 1 high
     * quality.
     *
     * @param float $quality
     * @return self
     * @throws \InvalidArgumentException when an invalid quality is given
     */
    public function quality(float $quality): self
    {
        if ($quality < 0 || $quality > 1) {
            throw new \InvalidArgumentException('Quality must be a value between 0 and 1.');
        }

        $real_quality = (int) round(9 - ($quality * 9.0));

        $copy = imagecreatetruecolor($this->width(), $this->height());

        // Enable alpha blending
        imagealphablending($copy, false);
        imagesavealpha($copy, true);

        imagecopy($copy, $this->resource, 0, 0, 0, 0, $this->imageWidth, $this->imageHeight);

        return new self($copy, $real_quality);
    }

    /**
     * Return a resized image based on the give new width and height.
     *
     * @param int $width
     * @param int $height
     * @return self
     */
    public function resize(int $width, int $height): self
    {
        return $this->sampleTo($this->rect(), [0, 0, $width, $height]);
    }

    /**
     * Return a cropped image based on the given rectangle. This should be in the format [x, y, width, height].
     *
     * @param int[] $rect
     * @return self
     */
    public function crop(array $rect): self
    {
        return $this->sampleTo($rect, [0, 0, $rect[2], $rect[3]]);
    }

    /**
     * Resize the image to fit inside the width and height. If already smaller and $exact is TRUE, the images will be
     * enlarged to make it fit. This will respect the aspect ratio of the image.
     *
     * @param int $width
     * @param int $height
     * @param bool $exact
     * @return self
     */
    public function fit(int $width, int $height, bool $exact = false): self
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

    /**
     * Sample the image to a new size based on a source rectangle and a target rectangle. If no target was given, the
     * size of the source will be used.
     *
     * Example usages:
     *    $img->sampleTo([0, 0, 100, 100], [0, 0, 100, 100]) // Crop
     *    $img->sampleTo($img->rect(), [0, 0, 100, 100]) // Resize to 100 x 100
     *    $img->sampleTo([0, 0, 100, 100], [0, 0, 50, 50]) // Crop and resize to 50 x 50
     *
     * @param int[] $source
     * @param int[]|null $target
     * @return self
     */
    public function sampleTo(array $source, ?array $target = null): self
    {
        if (null === $target) {
            $target = [0, 0, $source[2], $source[3]];
        }

        $new = new Image(@imagecreatetruecolor($target[2], $target[3]), $this->quality);

        // apply transformation
        if (!is_resource($new->resource) || false === @imagecopyresampled(
            $new->resource,
            $this->resource,
            $target[0],
            $target[1],
            $source[0],
            $source[1],
            $target[2],
            $target[3],
            $source[2],
            $source[3]
        )) {
            throw new ImageException('Cannot resample image.');
        }

        return $new;
    }

    /**
     * Return the binary data for the image. The format to use is selected based on the given extension.
     *
     * @param string $extension
     * @return string
     * @throws UnsupportedExtensionException
     */
    public function data(string $extension): string
    {
        ob_start();

        try {
            if ($extension === '.png') {
                imagepng($this->resource, null, $this->quality ?? -1);
            } else if ($extension === '.webp' && function_exists('imagewebp')) {
                imagewebp($this->resource, null, (int) round((($this->quality ?? 7.2) / 9.0) * 100));
            } else {
                $supported = ['.png'];

                if (function_exists('imagewebp')) {
                    $supported[] = '.webp';
                }

                throw new UnsupportedExtensionException($extension, $supported);
            }

            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    /**
     * Save the image. The format to use is selected based on the given extension.
     *
     * Supported are png and webp
     *
     * @param string $filename
     * @throws UnsupportedExtensionException
     */
    public function save(string $filename): void
    {
        $pos = strrpos($filename, '.');
        if ($pos === false) {
            throw new \InvalidArgumentException("File name does not contain a . extension (example.jpg)");
        }

        $extension = substr($filename, $pos);

        file_put_contents($filename, $this->data($extension));
    }
}
