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
require("SWDF_image_resizer.php");

//Register GET variables
$size = @$_GET['size'];	//Requested output size
$img  = @$_GET['img'];	//Path (relative to "base" defined in config) to image to be resized

//Load resizer
try {
	//Load the resizer
	$resizer=new \SWDF\secureImageResizer();
	
	//Load the configuration (and save any enhancments that can be made to the file, back to it)
	$resizer->loadConfig("example_config2.json", true, true);
	
	//Resize the requested image
	try {
		$new_image = $resizer->resize($img, $size, "images/jpeg");
		$new_image->outputHttp();
	
	//Catch errors while resizing
	} catch (\Exception $e){
		
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