<?php
declare(strict_types=1);

namespace Yannickl88\Image;

interface ImageInterface
{
    public const ORIENTATION_LANDSCAPE = 1;
    public const ORIENTATION_PORTRAIT = 2;

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
     * @return ImageInterface
     */
    public function sampleTo(array $source, ?array $target = null): self;

    /**
     * Return a resized image based on the give new width and height.
     *
     * @param int $width
     * @param int $height
     * @return ImageInterface
     */
    public function resize(int $width, int $height): self;

    /**
     * Return a cropped image based on the given rectangle. This should be in the format [x, y, width, height].
     *
     * @param int[] $rect
     * @return ImageInterface
     */
    public function crop(array $rect): self;

    /**
     * Return the orientation of the image. In the case where both width and height are equal, the image will be
     * considered portrait.
     *
     * @see self::ORIENTATION_LANDSCAPE
     * @see self::ORIENTATION_PORTRAIT
     *
     * @return int
     */
    public function orientation(): int;

    /**
     * Return the height of the image.
     *
     * @return int
     */
    public function height(): int;

    /**
     * Return the width of the image.
     *
     * @return int
     */
    public function width(): int;

    /**
     * Return bounding rectangle of the image. The array will be in order of: [x, y, width, height]
     *
     * @return int[]
     */
    public function rect(): array;

    /**
     * Save the image to the best possible format. PNG for static images, GIF for animated ones.
     *
     * @param string $filename
     */
    public function save(string $filename): void;

    /**
     * Return the binary data for the image. PNG for static images, GIF for animated ones.
     *
     * @return string
     */
    public function data(): string;

    /**
     * Return the color at the given coordinate. Returned color is in RGBA format. ([red, green, blue, alpha]) where
     * each channel is a value between 0 and 255.
     *
     * @param int $x
     * @param int $y
     * @return int[]
     */
    public function color(int $x, int $y): array;
}