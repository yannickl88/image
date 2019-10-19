<?php
namespace Yannickl88\Image\Exception;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Yannickl88\Image\Exception\FileNotFoundException
 */
class FileNotFoundExceptionTest extends TestCase
{
    public function testGeneric()
    {
        $previous = new \RuntimeException();
        $e = new FileNotFoundException("foobar.png", $previous);

        self::assertSame('Cannot find file "foobar.png".', $e->getMessage());
        self::assertSame($previous, $e->getPrevious());
    }
}
