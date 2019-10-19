<?php
declare(strict_types=1);

namespace Yannickl88\Image\Exception;

class FileNotFoundException extends ImageException
{
    public function __construct(string $filename, \Throwable $previous = null)
    {
        parent::__construct(sprintf('Cannot find file "%s".', $filename), 0, $previous);
    }
}