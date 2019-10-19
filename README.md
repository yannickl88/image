Library for reading, transforming and writing image files.

Usage
------------

Example usage:
```php
$image = AbstractImage::fromFile('/some/image.png');
$thumbnail = $image->sampleTo([0, 0, 50, 50]);
$thumbnail->save('/some/thumbnail.png');
```

Installation
------------
* `$ composer require yannickl88/image`
* This library follows [semantic versioning](http://semver.org/) strictly.