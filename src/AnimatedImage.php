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
    private const QUALITY_HIGH_COLORS = 32;
    private const QUALITY_LOW_COLORS = 8;

    private $frames;
    private $durations;
    private $_width;
    private $_height;
    private $quality;

    /**
     * @param resource[] $frames
     * @param int[] $durations
     * @param int $width
     * @param int $height
     * @param int|null $quality null or larger than 0
     *
     * @internal Use AbstractImage::fromFile()
     */
    public function __construct(array $frames, array $durations, int $width, int $height, ?int $quality = null)
    {
        $this->frames = $frames;
        $this->durations = $durations;
        $this->_width = $width;
        $this->_height = $height;
        $this->quality = $quality;
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

            if (null !== $this->quality) {
                imagetruecolortopalette($new_frame, true, $this->quality);
            }

            $new_frames[] = $new_frame;
        }

        return new self($new_frames, $this->durations, $target[2], $target[3], $this->quality);
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

    public function duration(): float
    {
        return array_sum($this->durations) / 100.0;
    }

    public function quality(float $quality): ImageInterface
    {
        if ($quality < 0 || $quality > 1) {
            throw new \InvalidArgumentException('Quality must be a value between 0 and 1.');
        }

        $frames = [];
        $real_quality = (int) round(self::QUALITY_LOW_COLORS + (self::QUALITY_HIGH_COLORS - self::QUALITY_LOW_COLORS) * $quality);

        foreach ($this->frames as $frame) {
            $copy = imagecreatetruecolor($this->_width, $this->_height);

            imagecopy($copy, $frame, 0, 0, 0, 0, $this->_width, $this->_height);
            imagetruecolortopalette($copy, true, $real_quality);

            $frames[] = $copy;
        }

        return new self(
            $frames,
            $this->durations,
            $this->_width,
            $this->_height,
            $real_quality
        );
    }

    public function slice(int $offset, ?int $length = null): ImageInterface
    {
        $frames = [];

        if ($offset + $length >= count($this->frames)) {
            throw new \InvalidArgumentException(sprintf('Length + offset cannot exceed frame count (max %d).', count($this->frames)));
        }

        foreach (array_slice($this->frames, $offset, $length) as $frame) {
            $copy = imagecreatetruecolor($this->_width, $this->_height);

            imagecopy($copy, $frame, 0, 0, 0, 0, $this->_width, $this->_height);

            if (null !== $this->quality) {
                imagetruecolortopalette($copy, true, $this->quality);
            }

            $frames[] = $copy;
        }

        return new self(
            $frames,
            array_slice($this->durations, $offset, $length),
            $this->_width,
            $this->_height,
            $this->quality
        );
    }
}