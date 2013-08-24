<h1>
ImageManager v0.4.0 
<a rel="license" href="http://creativecommons.org/licenses/by-sa/3.0/deed.en_US" style="float:right;"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-sa/3.0/88x31.png" /></a>
</h1>

ImageManager (formerly SWDF_image_resizer) is a group of php classes to make automatically 
re-sizing images on your domain easy, peasy, lemon and squeezy. Oh, and secure and simple.

This library is still in the alpha stage of development, meaning the API is prone to 
change with each release. Each released version should be completely usable however, so 
feel free to try it out for yourself. Just beware, upgrades will probably break your 
implementation.

## Quick Start

The ImageManager comes with a ready to use example implementation. 

To test it out, copy the repository into a directory accessible by your web-server. 
In your web browser, you should then be able to navigate to:

`http://SERVER.COM/PATH_TO_REPO/example.php?size=200x300&img=images/example.jpg`

This should produce a water-marked image using the two images in `images/`.

Next, investigate the file `config/exampleConfig.php` and try creating new sizes.

## Get The Code

To get a copy of the code, at your terminal type:

`git clone git://github.com/James-Swift/SWDF_image_resizer.git`

or alternatively you can 
[download a zipped version](https://github.com/James-Swift/SWDF_image_resizer/archive/master.zip).

## Upgrade Notes

This release is a stepping stone to v0.5.0 which has been in development for a few
months. v0.5.0 has essentially been completely rewritten, but some of the old code 
has merely been upgraded. v0.5.0 isn't quite ready for release yet, but I realised 
that I could push out v0.4.0 in the mean time with the upgraded code and let users
benefit from it's bug fixes. I've let a few of the name changes slip through from 
the yet-to-be-released v0.5.0, but for the most part you'll only have to change 
your namespace references to be able to continue to use your old implementation.

+ All functions and classes are now in the namespace `JamesSwift`.
+ Files have been moved around a bit and renamed. Check your `include`s.
+ Transparent PNGs now blend properly on top of each other, maintaining the transparent background.
+ JPEGs are now progressive downloads (to make loading appear faster).
+ The alignment of repeating watermarks in has changed slightly.
+ Caching now works correctly.
+ If the `$_SWDF['paths']['images_cache']` path doesn't exist, it will be created.
+ The array returned by `image_resizer_request()` now spells `cache_location` correctly.
+ The class `SWDF_image_resizer` has been renamed to `ImageResizer`.
+ The `ImageResizer::output_image` method now returns an `Image()` object. (use $Image->outputData() to emulate the old behaviour).
+ `ImageResizer::$compatible_mime_types` is now private.
+ `ImageResizer::imagecopymerge_alpha` has been removed.
+ `ImageResizer::add_watermark` Opacity now defaults to 100.

## Branching Model

The SWDF uses the branching/development model described 
[here](http://nvie.com/posts/a-successful-git-branching-model/).

If you wish to test the latest development version, checkout branch 
[develop](https://github.com/James-Swift/SWDF_image_resizer/tree/develop).

## Versioning

Releases will be numbered with the following format: `<major>.<minor>.<patch>`

But please note that during beta development we will remain at version v0.*.*

For more information please visit [http://semver.org/](http://semver.org/).

## License

<span xmlns:dct="http://purl.org/dc/terms/" property="dct:title">ImageManager</span> by 
<a xmlns:cc="http://creativecommons.org/ns#" href="https://github.com/James-Swift/SWDF_image_resizer" property="cc:attributionName" rel="cc:attributionURL">James Swift</a>
 is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/3.0/deed.en_US">Creative Commons Attribution-ShareAlike 3.0 Unported License</a>.