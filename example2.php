<?php
/*
 * Copyright 2013 James Swift (Creative Commons: Attribution - Share Alike - 3.0)
 * https://github.com/James-Swift/SWDF_image_resizer
 * 
 * This file creates a way to resize images by passing settings in a URL. 
 * Examples:
 * 
 * "example2.php?size=thumbnail&img=images/product.jpg"
 * "example2.php?size=watermarked_big3&img=images/photos/spain.png"
 * 
 * There is some brief explaination of what is going on, but for a proper
 * dicussion, see the documentation.
 * 
 */

//Load dependencies
require("SWDF_image_resizer.php");

//Register GET variables
$size = @$_GET['size'];	//Requested output size
$img  = @$_GET['img'];	//Path (relative to "base" defined below) to image to be resized

//Catch exceptions
try {

	/**
	 * First, Load a new instance of the resizer.
	 * 
	 * You can pass configuration settings to the resizer in one of three ways:
	 * 
	 * 1. Pass them in an array or file to the constructor:		$resizer = new secureImageResizer(array | PATH_TO_JSON_FILE )
	 * 2. Load them after initilizing with:				$resizer->loadConfig( array | PATH_TO_JSON_FILE )
	 * 3. Set them one by one after initilizing:			$resizer->set( NAME, VALUE )
	 * 
	 * Or a combination of the three. If you use $resizer->loadConfig(), it will 
	 * clear all previous settings (includeing paths and sizes) and re-initialize
	 * with the new settings. Using $resizer->set() overwrites only the value it
	 * replaces. NOTE: While the other two methods can create sizes and paths, 
	 * the set() method can only alter settings. See: addPath() and addSize()
	 * 
	 * We normally recommend storing your config in a seperate file as best
	 * practice, but for simplicity it has been incorporated below:
	 */
	$resizer=new \SWDF\secureImageResizer();

	/**
	 * Second, define the base and other settings.
	 * 
	  * The "base" setting is the absolute filesytem path to the logical root or
	 * "base" of the folders where you keep your images. Usually, this is your
	 * web-root (i.e. /var/www or /home/user/public_html) as all images are usually
	 * stored somewhere in there. However, it doesn't have to be in a publicly 
	 * accessible directory. It could be, for example, a home directory. As long as php
	 * has write access to it, and that's where you store your images, it doesn't 
	 * matter.
	 * 
	 * There are other settings you can define here as well. Examples are included.
	 * See the documentation for full specification.
	 */
	$resizer->set("base", dirname(__FILE__) );

	/** Inteligently cache resized images. This speeds up subsequent requests. If the 
	 * source file is modified, the cached file is automatically refreshed. */
	$resizer->set("enableCaching", true );

	/** How long to keep a cached file before deleting. Set to zero for infinity*/
	$resizer->set("cacheTime", 60*60*24 );

	/** You can define a default output image format for files. This can be overridden
	 *  on a size by size basis, and/or by specifing the third argument to the resize()
	 *  method. It defaults to "image/jpeg". */
	$resizer->set("defaultOutputFormat", "image/jpeg");

	/** Default jpeg quality. Can be overwriteen on a size-by-size basis. */
	$resizer->set("defaultJpegQuality", 90 );

	/** Default jpeg quality. Can be overwriteen on a size-by-size basis. */
	$resizer->set("defaultWatermarkOpacity", 10 );

	/**
	 * Third. Add some sizes.
	 * 
	 * Consult the documentation for details of avilable settings, but for now,
	 * suffice it to say that you must explicitly define all sizes your website
	 * can produce here, you limit when they can be used later. Give them an ID
	 * to easily reuse them later. A few examples are included:
	 * 
	 * You can add these sizes by calling $resizer->addSize() over and over, or
	 * alternatively you can add them all in one call (uses less resources).
	 */
	
	//Size by size method:
	$resizer->addSize(array(
		"id"=>"original",
		"method"=>"original"
	));
	
	$resizer->addSize(array(
		"id"=>"product_image",
		"method"=>"fit",
		"width"=>400,
		"height"=>800,
		"watermark"=>array(
			"img"=>"images/watermark.png",
			"scale"=>1.5,
			"opacity"=>40,
			"repeat"=>true
		)
	));
	
	//Multiple sizes in one call method:
	$resizer->addSize(
		array(
			"id"=>"2x",
			"method"=>"scale",
			"scale"=>2
		),
		array(
			"id"=>"200x300",
			"method"=>"fill",
			"width"=>200,
			"height"=>300,
			"quality"=>90,
			"defaultOutputFormat"=>"image/png"
		)
	);


	/**
	 * Fourth. Define some paths.
	 * 
	 * The image resizer by default doesn't allow you to resize any images. You 
	 * must tell it what folders it is allowed to operate in, and (optionally)
	 * what sizes can be used in which folders (all sizes are allowed by default).
	 * 
	 * You can add these paths by calling $resizer->addPath() over and over, or
	 * alternatively you can add them all in one call (which uses less resources)
	 * like so:
	 */
	$resizer->addPath(
		array(
			"path"=>"images/"
		),
		array(
			"path"=>"images/products/",
			"allowSizes"=>array("product_image", "200x300")
		),
		array(
			"path"=>"images/original_scans/",
			"denySizes"=>array("original","2x")
		)
	);
	
	/**
	 * Fith. Process the user's request.
	 * 
	 * This checks that the requested file and size exists, that the file's
	 * location allows it to be resized to the requested size etc., then does
	 * the actual resizing.
	 * 
	 * You pass in the relative location of the image to be resized and the 
	 * requested size. Optionally, you can also pass an output format. This 
	 * over-rides any defaults previously defined.
	 */

	try {
		$new_image = $resizer->resize($img, $size, "images/jpeg");

	/**
	 * Lastly, check it went ok, then output it.
	 * 
	 * There are other things you can do with the resized file. You don't have
	 * to display it now. You can save it to a new permenant location for example.
	 * Check the documentation for more ideas.
	 */

		$new_image->outputHttp();
	} catch (\Exception $e){
		print "Sorry, your request couldn't be processed:\n";
		print $e->getMessage();
	}
} catch (\Exception $e){
	//An error was found in your configuration
	header($_SERVER['SERVER_PROTOCOL']." 500 Internal Server Error", true, 500);
	print $e->getMessage();
}
?>