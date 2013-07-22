James Swift - Image Manager v0.4.0
==================

A PHP library to make automatically re-sizing images on your web site simple, safe and secure.

This library is still in the beta stage of development, meaning the API is prone to 
change. Each released version should be completely usable however, so feel free to 
try it out for yourself. Just beware upgrades at this point may break your implementation.

Please note: for anyone upgrading from <= v0.3.0, the API has been completely 
rewritten in OOP. As far as implementing the new API, there should only be a 
few lines of code to change, but sadly you will have to at least partially 
rewrite your configuration file to meet the new standards.

## Get The Code

To get a copy of the code, at your terminal type:

`git clone git://github.com/James-Swift/SWDF_image_resizer.git`

or alternatively you can 
[download a zipped version](https://github.com/James-Swift/SWDF_image_resizer/archive/master.zip).

## Quick Start

The ImageManager comes with a ready to use example implementation. 

To test it out, copy the repository into a directory accessible by your web-server. 
In your web browser, you should then be able to navigate to:

`http://SERVER.COM/PATH_TO_REPO/example.php?size=200x300&img=images/example.jpg`

This should produce a water-marked image using the two images in `images/`.

Next, investigate the file `config/exampleConfig.json` and try creating new sizes.

## Branching Model

The SWDF uses the branching/development model described 
[here](http://nvie.com/posts/a-successful-git-branching-model/).

If you wish to test the latest development version, checkout branch 
[develop](https://github.com/James-Swift/SWDF_image_resizer/tree/develop).

## Versioning

Releases will be numbered with the following format: `<major>.<minor>.<patch>`

But please note that during beta development we will remain at version v0.*.*

For more information please visit [http://semver.org/](http://semver.org/).

## License: Creative Commons Attribution - Share Alike 3.0

<a rel="license" href="http://creativecommons.org/licenses/by-sa/3.0/deed.en_US">
<img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-sa/3.0/88x31.png" /></a>
<br /><span xmlns:dct="http://purl.org/dc/terms/" property="dct:title">SWDF_Image_resizer</span> by 
<a xmlns:cc="http://creativecommons.org/ns#" href="https://github.com/James-Swift/SWDF_image_resizer" property="cc:attributionName" rel="cc:attributionURL">James Swift</a>
 is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/3.0/deed.en_US">Creative Commons Attribution-ShareAlike 3.0 Unported License</a>.