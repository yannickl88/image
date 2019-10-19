<?php
namespace Yannickl88\Image\Exception;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Yannickl88\Image\Exception\UnsupportedExtensionException
 */
class UnsupportedExtensionExceptionTest extends TestCase
{
    public function testGeneric()
    {
        $previous = new \RuntimeException();
        $e = new UnsupportedExtensionException('.png', ['.foo', '.bar'], $previous);

        self::assertSame('Unsupported file format ".png", supported are: ".foo", ".bar".', $e->getMessage());
        self::assertSame($previous, $e->getPrevious());
    }
}
