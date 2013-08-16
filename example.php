<?php
/*
 * Copyright 2013 James Swift (Creative Commons: Attribution - Share Alike - 3.0)
 * https://github.com/James-Swift/SWDF_image_resizer
 * 
 * This file implements a way to resize images by passing settings in a URL. 
 * Examples:
 * 
 * "new_example.php?size=product_image&img=images/example.jpg"
 * "new_example.php?size=2x&img=images/example.jpg"
 * 
 */

//Load dependencies
require("src/ImageManager.php");

//Register GET variables
$size	= (isset($_GET['size'])) ?	$_GET['size']	: null;	//Requested output size
$img	= (isset($_GET['img'])) ?	$_GET['img']	: null;	//Path (relative to "base" defined in config) of image to be resized
$format = (isset($_GET['format'])) ?	$_GET['format'] : null;	//[optional] The mime-type of output (e.g. image/jpeg)

//Catch configuration errors
try {
	//Load the resizer
	$resizer=new \JamesSwift\SecureImageResizer();
	
	//Define the base (other specified paths are now relative to this point)
	$resizer->set("base", dirname(__FILE__) );
	
	//Load the configuration
	$resizer->loadConfig("config/exampleConfig.json");
	
	//Catch errors while resizing
	try {
		//Resize the requested image
		$newImage = $resizer->request($img, $size, $format);

		//Output the image to the user
		$newImage->outputHTTP();
		
	} catch (\Exception $e){
		//TODO: Update all error codes so this part can return correct http response
		print "Sorry, your request couldn't be processed:<br/>";
		print $e->getMessage();
	}
	
	//Clean the cache (optional)
	$resizer->cleanCache();
	
//Catch configuration errors
} catch (\Exception $e){
	
	//An error was found in your configuration
	header($_SERVER['SERVER_PROTOCOL']." 500 Internal Server Error", true, 500);
	print $e->getMessage();
	
}
?>