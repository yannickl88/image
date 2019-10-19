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

    /**
     * @param resource $resource
     *
     * @internal Use AbstractImage::fromFile()
     */
    public function __construct($resource)
    {
        $this->_resource = $resource;
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

    public function sampleTo(array $source, ?array $target = null): ImageInterface
    {
        if (null === $target) {
            $target = [0, 0, $source[2], $source[3]];
        }

        $new = new StaticImage(@imagecreatetruecolor($target[2], $target[3]));

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

    public function data(): string
    {
        ob_start();

        imagepng($this->_resource);
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
}