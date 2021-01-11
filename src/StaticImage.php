<?php
declare(strict_types=1);

namespace Yannickl88\Image;

use Yannickl88\Image\Exception\ImageException;

/**
 * @internal use ImageInterface
 */
class StaticImage extends AbstractImage
{
    private $_resource;
    private $_width;
    private $_height;
    private $quality;

    /**
     * @param resource $resource
     * @param int|null $quality
     *
     * @internal Use AbstractImage::fromFile()
     */
    public function __construct($resource, ?int $quality = null)
    {
        $this->_resource = $resource;
        $this->quality = $quality;

        if (is_resource($this->_resource)) {
            // Enable alpha blending
            imagealphablending($this->_resource, false);
            imagesavealpha($this->_resource, true);
        }
    }

    public function __destruct()
    {
        if (is_resource($this->_resource)) {
            imagedestroy($this->_resource);
        }
    }

    protected function resource()
    {
        return $this->_resource;
    }

    protected function supportedFileExtensionsForSaving(): array
    {
        $supported_extensions = ['.png'];

        if (function_exists('imagewebp')) {
            $supported_extensions[] = '.webp';
        }

        return $supported_extensions;
    }

    public function sampleTo(array $source, ?array $target = null): ImageInterface
    {
        if (null === $target) {
            $target = [0, 0, $source[2], $source[3]];
        }

        $new = new StaticImage(@imagecreatetruecolor($target[2], $target[3]), $this->quality);

        // apply transformation
        if (!is_resource($new->_resource) || false === @imagecopyresampled(
            $new->_resource,
            $this->_resource,
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

    public function data(string $extension): string
    {
        ob_start();

        switch ($extension) {
            case '.webp':
                imagewebp($this->_resource, null, (int) round((($this->quality ?? 7.2) / 9.0) * 100));
                break;
            default:
                imagepng($this->_resource, null, $this->quality ?? -1);
                break;
        }
        $image_data = ob_get_contents();

        ob_end_clean();

        return $image_data;
    }

    public function width(): int
    {
        if (null === $this->_width) {
            $this->_width = imagesx($this->_resource);
        }

        return $this->_width;
    }

    public function height(): int
    {
        if (null === $this->_height) {
            $this->_height = imagesy($this->_resource);
        }

        return $this->_height;
    }

    public function duration(): float
    {
        return 0.0;
    }

    public function quality(float $quality): ImageInterface
    {
        if ($quality < 0 || $quality > 1) {
            throw new \InvalidArgumentException('Quality must be a value between 0 and 1.');
        }

        $real_quality = (int) round(9 - ($quality * 9.0));

        $copy = imagecreatetruecolor($this->width(), $this->height());

	    // Enable alpha blending
	    imagealphablending($copy, false);
	    imagesavealpha($copy, true);

        imagecopy($copy, $this->_resource, 0, 0, 0, 0, $this->_width, $this->_height);

        return new self($copy, $real_quality);
    }

    public function slice(int $offset, ?int $length = null): ImageInterface
    {
        $copy = imagecreatetruecolor($this->width(), $this->height());

	    // Enable alpha blending
	    imagealphablending($copy, false);
	    imagesavealpha($copy, true);

        imagecopy($copy, $this->_resource, 0, 0, 0, 0, $this->_width, $this->_height);

        return new self($copy, $this->quality);
    }
}
