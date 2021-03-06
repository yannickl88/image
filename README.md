Easy reading, transforming and writing image files using PHP
----------

[![Latest Version](http://img.shields.io/packagist/v/yannickl88/image.svg?style=flat-square)](https://github.com/yannickl88/image/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://github.com/yannickl88/image)
[![Build Status](https://img.shields.io/travis/yannickl88/image/master.svg?style=flat-square)](https://travis-ci.org/yannickl88/image)

Library for reading, transforming and writing image files.

This libary was born out of the need to have a consistent API for interacting with images. The goal is to have simple methods for common image tasks like cropping and resizing.

Usage
------------
Supported file extensions for reading:
* PNG
* JPG
* JPEG
* GIF

Supported file extension for writing:
* PNG
* WEBP (if mod gd has been enabled with WebP support)

Example usages:
```php
$image = \Yannickl88\Image\Image::fromFile('/some/image.png');

// Resize to 50 x 50
$thumbnail = $image->resize(50, 50);
$thumbnail->save('/some/thumbnail.png');

// Fit the image to a width and height of 50, 50 while maintaining it's aspect ratio.
$thumbnail = $image->fit(50, 50);
$thumbnail->save('/some/thumbnail.png');

// Crop at 50, 50 with a square of 100 x 100
$thumbnail = $image->crop([50, 50, 100, 100]);
$thumbnail->save('/some/thumbnail.png');

// Resize and crop at the same time
$thumbnail = $image->sampleTo([50, 50, 100, 100], [0, 0, 50, 50]);
$thumbnail->save('/some/thumbnail.png');

// Set the quality, where 0 being low and 1 high (value between 0 and 1)
$compressed = $image->quality(0.25);
$compressed->save('/some/preview.png');

// Get image width
var_dump($image->width()); // int

// Get image height
var_dump($image->height()); // int

// Get image bounding box
var_dump($image->rect()); // array(0, 0, width, height)

// Get a color at a given coordinate
var_dump($image->color(0, 0)); // array(red, green, blue, alpha)

// Get the image orientation
var_dump($image->orientation()); // ImageInterface::ORIENTATION_LANDSCAPE

// Get the raw data and output it
header('Content-type: image/png');
header('Content-Disposition: filename="image.png"');
echo $image->data('.png');
```

Migration
------------
See [Migration Guide](MIGRATION.md).

Installation
------------
* `$ composer require yannickl88/image`
* This library follows [semantic versioning](http://semver.org/) strictly.
