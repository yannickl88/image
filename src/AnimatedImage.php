<?php
declare(strict_types=1);

namespace Yannickl88\Image;

use GifCreator\GifCreator;
use Yannickl88\Image\Exception\ImageException;

/**
 * @internal use ImageInterface
 */
class AnimatedImage extends AbstractImage
{
    private $frames;
    private $durations;
    private $_width;
    private $_height;

    /**
     * @param resource[] $frames
     * @param int[] $durations
     * @param int $width
     * @param int $height
     *
     * @internal Use AbstractImage::fromFile()
     */
    public function __construct(array $frames, array $durations, int $width, int $height)
    {
        $this->frames = $frames;
        $this->durations = $durations;
        $this->_width = $width;
        $this->_height = $height;
    }

    public function __destruct()
    {
        foreach ($this->frames as $frame) {
            if (is_resource($frame)) {
                imagedestroy($frame);
            }
        }
    }

    protected function resource()
    {
        return $this->frames[0];
    }

    public function sampleTo(array $source, ?array $target = null): ImageInterface
    {
        if (null === $target) {
            $target = [0, 0, $source[2], $source[3]];
        }

        $new_frames = [];

        foreach ($this->frames as $frame) {
            $new_frame = @imagecreatetruecolor($target[2], $target[3]);

            // apply transformation
            if (!is_resource($new_frame) || false === @imagecopyresampled(
                $new_frame,
                $frame,
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

            $new_frames[] = $new_frame;
        }

        return new self($new_frames, $this->durations, $target[2], $target[3]);
    }

    public function data(): string
    {
        $gc = new GifCreator();
        $gc->create($this->frames, $this->durations);

        return $gc->getGif();
    }

    public function width(): int
    {
        return $this->_width;
    }

    public function height(): int
    {
        return $this->_height;
    }
}