##Development Branch

**This is the `develop` branch. Using the code in this branch in production is NOT recommended!**

I'm moving towards releasing v0.5.0 (hence the develop branch being branded v0.5.0-dev). Please 
test out the code in this branch and submit bug reports and pull requests.

To see what I'm currently working on check out the [v0.5.0 milestone](https://github.com/JamesSwift/ImageManager/issues?milestone=1&state=open).

<h1>
ImageManager v0.5.0-dev 
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

Copy the repository into a directory accessible by your web-server, then try this URL:

`http://SERVER.COM/PATH_TO_REPO/example.php?size=200x300&img=images/example.jpg`

Basically, the ImageManager works like this:

- You define some `sizes` in the config. These control output formats, dimensions, and watermarking.
- You define some `paths` in the config. These dictate which sizes are allowed in which directories.

You only have to do that once. From then on when you want an image of a particular 
size on your web page, you just pass the parameters in the `src` attribute of the `<img/>` tag, like so:

	<img src="img.php?size=___ID-OF-SIZE___&img=___PATH-TO-IMAGE___" />

To get started, investigate the file `config/exampleConfig.json` and try creating new sizes to use with `example.php`.

## Get The Code

To get a copy of the code, at your terminal type:

    git clone git://github.com/JamesSwift/ImageManager.git
    git submodule init
    git submodule update

or alternatively you can 
[download a zipped version](https://github.com/JamesSwift/ImageManager/archive/master.zip).

## Upgrade Notes

//TODO

## Branching Model

The SWDF uses the branching/development model described 
[here](http://nvie.com/posts/a-successful-git-branching-model/).

If you wish to test the latest development version, checkout branch 
[develop](https://github.com/JamesSwift/ImageManager/tree/develop).

## Versioning

Releases will be numbered with the following format: `<major>.<minor>.<patch>`

But please note that during alpha development we will remain at version v0.*.*

For more information please visit [http://semver.org/](http://semver.org/).

## License

<span xmlns:dct="http://purl.org/dc/terms/" property="dct:title">ImageManager</span> by 
<a xmlns:cc="http://creativecommons.org/ns#" href="https://github.com/JamesSwift/ImageManager" property="cc:attributionName" rel="cc:attributionURL">James Swift</a>
 is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/3.0/deed.en_US">Creative Commons Attribution-ShareAlike 3.0 Unported License</a>.
