Swift Web Development Framework - Image Resizer v0.1.3
==================


A PHP class and set of functions to make automatically re-sizing images on your web site simple and secure.

This project's code has basically just been copied and pasted from the main [SWDF](https://github.com/James-Swift/SWDF) repo. Updates are coming slowly, focusing on improving the code (particularly rationalising the return values of the functions) and adding Documentation (phpDoc) to the code. Although the code is still being neatened up, it's ready to use.

To get a copy of the code, at your terminal type:

`git clone git://github.com/James-Swift/SWDF_image_resizer.git`

or alternatively you can [download a zipped version](https://github.com/James-Swift/SWDF_image_resizer/archive/master.zip).

## Quick Start

The SWDF_image_resizer comes with a ready to use example implementation. 

To test it out, copy the repository into a directory accessible by your web-server. In your web browser, you should then be able to navigate to:

`http://SERVER.COM/PATH_TO_REPO/example.php?size=1&img=images/example.jpg`

This should produce a water-marked image by combining the two images in `images`.

Next, investigate the file `example_config.php` and try creating new sizes.

## Branching Model

The SWDF uses the branching/development model described [here](http://nvie.com/posts/a-successful-git-branching-model/).

If you wish to test the latest development version, checkout branch [develop](https://github.com/James-Swift/SWDF_image_resizer/tree/develop).

## Versioning

Releases will be numbered with the following format:

`<major>.<minor>.<patch>`

The current version number can be found at the top of the README.md file and the SWDF_image_resizer file.

For more information please visit [http://semver.org/](http://semver.org/).

## License: Creative Commons Attribution - Share Alike 3.0

<a rel="license" href="http://creativecommons.org/licenses/by-sa/3.0/deed.en_US"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-sa/3.0/88x31.png" /></a><br /><span xmlns:dct="http://purl.org/dc/terms/" property="dct:title">SWDF_Image_resizer</span> by <a xmlns:cc="http://creativecommons.org/ns#" href="https://github.com/James-Swift/SWDF_image_resizer" property="cc:attributionName" rel="cc:attributionURL">James Swift</a> is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/3.0/deed.en_US">Creative Commons Attribution-ShareAlike 3.0 Unported License</a>.