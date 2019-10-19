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

Installation
------------
* `$ composer require yannickl88/image`
* This library follows [semantic versioning](http://semver.org/) strictly.