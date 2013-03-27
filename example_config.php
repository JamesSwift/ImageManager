<?php
////////////////////////////////////////////////////////////////
// Basic Image Settings:
 
	$_SWDF['settings']['images']['default_size']=0;					//Used by make_img_link() function to choose a size if none is specified.
	$_SWDF['settings']['images']['default_watermark_opacity']=20;			//If not specified in a size, use this opacity for watermarks. Values: 0-100
	$_SWDF['settings']['images']['default_jpeg_quality']=90;			//If not specified in a size, use this value for jpeg compression.
	
	$_SWDF['paths']['images_cache']=$_SWDF['paths']['root']."cache/";		//The absolute path to the image cache location.
	$_SWDF['settings']['images']['cache_resized']=false;				//Whether to store cached resized images or re-generate each time (strongly reccomended to be set to true, as resizing images is a slow process). Normally only useful for debugging.
	$_SWDF['settings']['images']['cache_expiry']=60*60*2;				//The time in seconds to keep (and use) cached images.

	$_SWDF['settings']['images']['settings_loaded']=true;				//Ignore this variable. It's just used by the SWDF to check this file has been loaded.

	
////////////////////////////////////////////////////////////////	
/* For security reasons, the SWDF doesn't allow the resizer script to resize just any
 * image. You need to pre-declare directories where the resizer is allowed to operate.
 * You can  restrict which sizes are allowed in which directories. To do so, use 
 * the: SWDF_add_img_path() function in this file like so:
 * 
 * SWDF_add_img_path( Array(
 *	"path"=>"images/",
 *	"allow_sizes"=>"all",
 *	"deny_sizes"=>Array(1,2,3)
 * ));
 *	
 * You can also require an authorisation signal be passed to the SWDF_image_resizer_request()
 * function before it will resize an image.
 *	
 * "path"		string	*	The relative path to the directory from $_SWDF['paths']['root']. Examples: "images/" or "graphics/special/". Must end in "/".
 * "allow_sizes"	array		An array of sizes to allow. All other sizes will be blocked unless otherwise specified. To allow all sizes, set to string "all". Default is "all".
 * "deny_sizes"		array		An array of sizes to deny. All other sizes will be allowed unless otherwise specified. To block all sizes, set to string "all". By default, none are blocked.
 * "require_auth"	boolean		When set to true, the SWDF_image_resizer_request() function must be called with the "authorized" argument set to true (it's false by default). Rather than security this variable is basically there to remind you to make sure you have implemented some security measure prior to allowing images in this directory to be resized.
 * 
 * 
 * Specified settings affect the specified directory and all it's sub-directories, 
 * UNLESS a rule for a sub directory is explicitly specified. In that case, the 
 * sub-directory WILL NOT INHERIT ANY PROPERTIES from it's parent. This means that
 * for example, you could specify a sub-directory that ALLOWS all sizes, even 
 * within a directory that BLOCKS all sizes. Please note that unless a directory
 * or one if it's parents is explicitly specified, the SWDF_image_resizer_request()
 * will not be able to access it. For example, if you only define "images/",
 * the script will not be able to access "graphics/".
*/
////////////////////////////////////////////////////////////////
//Image Directories Options
	
	SWDF_add_img_path(Array(
		"path"=>"images/",
		"allow_sizes"=>"all"
	));
	
	SWDF_add_img_path(Array(
		"path"=>"images/restricted/",
		"deny_sizes"=>Array("0","2")
	));
	
	
////////////////////////////////////////////////////////////////	
/* The SWDF requires you must explicitly declare all allowed sizes before they can be used.
 * To do so, from the following settings create a new array inside 
 * $_SWDF['settings']['images']['sizes']
 * like so:
 * 
 * $_SWDF['settings']['images']['sizes']['_ID_']=Array(
 *	"id"=>"_ID_",
 *	"method"=>"original"
 * );
 * 
 * Note: * = required,  - = sometimes required
 * 
 * "id"			string	*	The identifier for the size, must be ALPHANUMERIC and must be the same as _ID_ above (the id of the array).
 * "method"		string	*	The method of resizing. Possible values: original|fit|fill|stretch|scale
 * "width"		int	-	The output width of the image (ignored with methods "original" and "scale")
 * "height"		int	-	The output height of the image (ignored with methods "original" and "scale")
 * "scale"		float	-	If method "scale" is used, specify the amount to scale the image by. 2=double size, 0.5=half size etc.
 * "output"		string		The mime type of the output. If unset, no type conversion will occur.
 * "quality"		int		0-100. Jpeg output quality. Default specified above in $_SWDF['settings']['images']['default_jpeg_quality']
 * "watermark"		array		An array of settings to place a specified image as a watermark over the the requested image. Possible settings are indented below:
 *	"path"		 string	-	The absolute path to the watermark image
 *	"v"		 string		The vertical position of the water mark over the original image. Possible values: top|center|bottom (default is center)
 *	"h"		 string		The horizontal position of the water mark over the original image. Possible values: left|center|right (default is center)
 *	"opacity"	 int		The opacity of the watermark. Between 0 and 100. Default is defined in $_SWDF['settings']['images']['default_watermark_opacity']
 *	"scale"		 float		Scale the watermark. 1.0 = normal size. 0.5 = half size etc. Default is 1.0.
 *	"repeat"	 boolean	Repeat the watermark in a pattern? Ignores "v" and "h". Default false.
 * "disable_caching"	boolean		Use to override $_SWDF['settings']['images']['cache_resized'] and prevent caching if required (note: you cannot enable caching if $_SWDF['settings']['images']['cache_resized'] is set to false). Default false.
 */
////////////////////////////////////////////////////////////////
// Image sizes

	//Size 0 - just passed the image through untouched
	$_SWDF['settings']['images']['sizes']['0']=Array(
		"id"=>0,
		"method"=>"original"
	);
	
	//Size 1 - resizes the image to fit inside a 1000x1000 box and adds a copyright watermark
	$_SWDF['settings']['images']['sizes']['1']=Array(
		"id"=>1,
		"method"=>"fit",
		"width"=>400,
		"height"=>800,
		"watermark"=>Array(
			"path"=>$_SWDF['paths']['root']."images/watermark.png",
			"scale"=>1.5,
			"opacity"=>40,
			"repeat"=>true
		),
	);
	
	//Size 2 - Scales the image by 2x
	$_SWDF['settings']['images']['sizes']['2']=Array(
		"id"=>2,
		"method"=>"scale",
		"scale"=>2
	);

////////////////////////////////////////////////////////////////	
?>
