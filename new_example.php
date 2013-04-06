<?php
/*
 * Copyright 2013 James Swift (Creative Commons: Attribution - Share Alike - 3.0)
 * https://github.com/James-Swift/SWDF_image_resizer
 * 
 * This file creates a way to resize images by passing settings in a URL. 
 * Examples:
 * 
 * "example3.php?size=thumbnail&img=images/product.jpg"
 * "example3.php?size=watermarked_big3&img=images/photos/spain.png"
 * 
 */

//Load dependencies
require_once("ImageResizer.php");

//Register GET variables
$size = @$_GET['size'];	//Requested output size
$img  = @$_GET['img'];	//Path (relative to "base" defined in config) to image to be resized

//Catch configuration errors
try {
	//Load the resizer
	$resizer=new \swdf\SecureImageResizer();
	
	//Define the base path (all other paths are relative to this point)
	$resizer->set("base", dirname(__FILE__) );
	
	//Load the configuration
	$resizer->loadConfig("example_config2.json");
	
	//Catch errors while resizing
	try {
		//Resize the requested image
		$new_image = $resizer->resize($img, $size, "images/jpeg");

		//Output the image to the user
		$new_image->outputHttp();
		
	} catch (\Exception $e){
		//TODO: setup error codes so can return correct http response
		print "Sorry, your request couldn't be processed:\n";
		print $e->getMessage();
	}

	
//Catch configuration errors
} catch (\Exception $e){
	
	//An error was found in your configuration
	header($_SERVER['SERVER_PROTOCOL']." 500 Internal Server Error", true, 500);
	print $e->getMessage();
	
}
?>