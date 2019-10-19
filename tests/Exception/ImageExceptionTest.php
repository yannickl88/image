<?php
namespace Yannickl88\Image\Exception;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Yannickl88\Image\Exception\ImageException
 */
class ImageExceptionTest extends TestCase
{
    public function testGeneric()
    {
        $previous = new \RuntimeException();
        $e = new ImageException("foobar", 24, $previous);

        self::assertSame("foobar", $e->getMessage());
        self::assertSame(24, $e->getCode());
        self::assertSame($previous, $e->getPrevious());
    }
}
