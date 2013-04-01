<?php
/*
 * Copyright 2013 James Swift (Creative Commons: Attribution - Share Alike - 3.0)
 * https://github.com/James-Swift/SWDF_image_resizer
 * 
 * 
 * Use this file like this:
 * <img src="example.php?size=SIZE&img=PATH_TO_IMAGE" />
 * 
 */

//Define absolute path to the root of your project (must end with /)
$_SWDF['paths']['root']=str_replace(Array('\\',"\\","//"),"/",dirname(__FILE__)."/");

//Load GET variables
$size=@$_GET['size'];
$img=@$_GET['img'];

//Load dependencies
require("SWDF_image_resizer.php");
require("example_config.php");

//Make resize request
$result=\SWDF\image_resizer_request($img,$size,false);

//Handle returned data, mapping headers etc. and output image
if (isset($result['status'])){
	//Set HTTP status Code
	header(":",null,$result['status']);

	//Set headers
	if (isset($result['headers']) && is_array($result['headers'])){
		foreach($result['headers'] as $header){
			header($header);
		}
	}

	//Output image
	if (isset($result['data'])){
		print $result['data'];
	}
} else {
	//Something went wrong, trigger a 500 error
	header(":",null,500);
}
?>