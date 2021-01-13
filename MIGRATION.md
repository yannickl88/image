# From v1 to v2
With version 2.0.0 the support for animated images has been dropped due to lacking support of the third party library. Because of this, the API has been simplified to reflect this.

Changes:
* `sybio/gif-creator` and `sybio/gif-frame-extractor` are no longer suggested nor supported.
* `Yannickl88\Image\AnimatedImage` has been removed, however this was part of the internal API and should not have been used. No replacement is provided.
* `Yannickl88\Image\StaticImage` has been removed, however this was part of the internal API and should not have been used. Replacement is `Yannickl88\Image\Image`.
* `Yannickl88\Image\ImageInterface` has been removed, use `Yannickl88\Image\Image` now. All modifying methods (such as `crop()` or `fit()`) now return `Yannickl88\Image\Image`.
* Method `Yannickl88\Image\ImageInterface::duration()` has been removed, no alternative is provided.
* Method `Yannickl88\Image\ImageInterface::slice()` has been removed, no alternative is provided.
* Method `Yannickl88\Image\Image::data()` requires an extension now to select the type to save as. Pass '.png' as a value to have the same behavior as v1. 
