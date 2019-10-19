<?php
declare(strict_types=1);

namespace Yannickl88\Image\Exception;

class UnsupportedExtensionException extends ImageException
{
    public function __construct(string $extension, array $suported, \Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Unsupported file format "%s", supported are: "%s".', $extension, implode('", "', $suported)),
            0,
            $previous
        );
    }
}