[![Build Status](https://travis-ci.org/yannickl88/image.svg?branch=master)](https://travis-ci.org/yannickl88/image)

Library for reading, transforming and writing image files.

This libary was born out of the need to have a consistent API for interacting with images. The goal is to have simple methods for common image tasks like cropping and resizing.

Usage
------------
Supported file extensions:
* PNG
* JPG
* JPEG
* GIF

> **Note:** When saving an image, the PNG format is used for static images and GIF for animated images. It is **not** possible to save as JPG. 

Example usages:
```php
$image = \Yannickl88\Image\AbstractImage::fromFile('/some/image.png');

// Resize to 50 x 50
$thumbnail = $image->resize(50, 50);
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
echo $image->data();
```
> **Note:** Quality is very subjective to the underlying implementation and is very opinionated. PNG quality is based on the [gd quality parameter](https://www.php.net/imagepng). GIF quality is based on the [color space](https://www.php.net/imagetruecolortopalette) (0 = 8 colors, 1 = 32 colors with dithering) 

There is also some additional support for dealing with animated images. These methods also work for static images but do very little.

Eamples usages:
```php
$image = \Yannickl88\Image\AbstractImage::fromFile('/some/image.gif');

// Get duration
var_dump($image->duration()); // float

// Slice image down so it starts from frame 10
$sliced = $image->slice(10);
$sliced->save('/some/sliced.gif');

// Slice image down from frame 10 with a length of 5
$sliced = $image->slice(10, 5);
$sliced->save('/some/sliced.gif');
```

Installation
------------
* `$ composer require yannickl88/image`
* This library follows [semantic versioning](http://semver.org/) strictly.
