<?php
/*
 * Copyright 2013 James Swift (Creative Commons: Attribution - Share Alike - 3.0)
 * https://github.com/James-Swift/SWDF_image_resizer
 * 
 * This file creates a way to resize images by passing settings in a URL. 
 * Examples:
 * 
 * "new_example.php?size=thumbnail&img=images/product.jpg"
 * "new_example.php?size=watermarked_big3&img=images/photos/spain.png"
 * 
 */

//Load dependencies
require("src/ImageTools.php");

//Register GET variables
$size	= (isset($_GET['size'])) ?	$_GET['size']	: null;	//Requested output size
$img	= (isset($_GET['img'])) ?	$_GET['img']	: null;	//Path (relative to "base" defined in config) to image to be resized
$format = (isset($_GET['format'])) ?	$_GET['format'] : null;	//The mime-type of ourput (e.g. image/jpeg)

//Catch configuration errors (approx 0.4ms)
try {
	//Load the resizer
	$resizer=new \JamesSwift\SecureImageResizer();
	
	//Define the base path (most other paths are relative to this point)
	$resizer->set("base", dirname(__FILE__) );
	
	//Load the configuration
	$resizer->loadConfig("config/exampleConfig.json");
	
	//Catch errors while resizing
	try {
		//Resize the requested image
		$newImage = $resizer->request($img, $size, $format);

		//Output the image to the user
		$newImage->outputHttp();
		
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