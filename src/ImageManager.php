<?php
/**
 * James Swift - Image Manager
 * 
 * The following code allows you to automate the resizing of images on your website. 
 * 
 * The ImageResizer class uses the GD2 PHP library and allows you to make simple
 * modifications to images in your filesystem. 
 * 
 * The SecureImageResizer class implements a system for managing all images
 * on a website, both securing them and resizing/watermarking them as needed 
 * using the ImageResizer class.
 * 
 * For a quick start, see examples.txt.
 * 
 * You are free to use, share and alter/remix this code, provided you distribute 
 * it under the same or a similar license as this work. Please clearly mark any
 * modifications you make (if extensive, a summary at the begining of the file
 * is sufficient). If you redistribute, please include a copy of the LICENSE, 
 * keep the message below intact:
 * 
 * Copyright 2014 James Swift (Creative Commons: Attribution - Share Alike - 3.0)
 * https://github.com/JamesSwift/ImageManager
 * 
 * @author James Swift <me@james-swift.com>
 * @version v0.5.0-dev
 * @package JamesSwift/ImageManager
 * @copyright Copyright 2014 James Swift (Creative Commons: Attribution - Share Alike - 3.0)
 */

namespace JamesSwift\ImageManager;

class Exception extends \Exception {
	//Nothing to do here yet
}

require "SecureImageResizer.php";
