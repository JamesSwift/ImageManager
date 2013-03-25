<?php
/* The SWDF can very easily resize images on the fly for you through a very simple
 * interface. I've made a function: make_img_link($path,$size) which generates a link
 * to a resized version of the image specified, and that's basically it. The first 
 * time someone's browser requests that link, the image is resized on the fly. The 
 * next time, the resized image is loaded from a cached copy. So all you have to do
 * to resize an image is link to it with: make_img_link($path,$size) For example:
 * 
 * <?php print '<img alt="A resized Image" src="'.make_img_link("images/resize-me.jpg",2).'"/>'; ?>
 * 
 * The $size variable in make_img_link() reffernces settings in this file. Look further
 * down for an explaination of those.
 * 
 */
////////////////////////////////////////////////////////////////
// Basic Image Settings:
 
	$_SWDF['settings']['images']['default_size']=0;								//Used by make_img_link() function to choose a size if none is specified.
	$_SWDF['settings']['images']['default_watermark_opacity']=90;				//If not specified in a size, use this opacity for watermarks. Values: 0-100
	$_SWDF['settings']['images']['default_jpeg_quality']=90;					//If not specified in a size, use this value for jpeg compression.
	
	$_SWDF['paths']['images_cache']=$_SWDF['paths']['root']."cache/images/";	//The absolute path to the image cache location.
	$_SWDF['settings']['images']['cache_resized']=true;							//Whether to store cached resized images or re-generate each time (strongly reccomended to be set to true, as resizing images is a slow process). Normally only useful for debugging.
	$_SWDF['settings']['images']['cache_expiry']=60*60*2;						//The time in seconds to keep (and use) cached images.

	$_SWDF['settings']['images']['settings_loaded']=true;						//Ignore this variable. It's just used by the SWDF to check this file has been loaded.

	
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
 * You can also require an authorization signal be passed ot the SWDF_image_resizer_request()
 * function before it will resize an image.
 *	
 * "path"			string	*	The relative path to the directory from $_SWDF['paths']['root']. Examples: "images/" or "graphics/special/". Must end in "/".
 * "allow_sizes"	array		An array of sizes to allow. All other sizes will be blocked unless otherwise specified. To allow all sizes, set to string "all". Default is "all".
 * "deny_sizes"		array		An array of sizes to deny. All other sizes will be allowed unless otherwise specified. To block all sizes, set to string "all". By default, none are blocked.
 * "require_auth"	boolean		When set to true, the SWDF_image_resizer_request() function must be called with the "authorized" argument set to true (called false by default). This adds an extra level of security, as if you forget to add the code to check for authorization before making the request, the request will not be processed. Note: the default ?p=_img resizing script always sets authorized=false. Hence this feature is only really useful if you are calling SWDF_image_resizer_request() manually in your own scripts. If you aren't, ignore this feature.
 * 
 * 
 * Specified settings affect the specified directory and all it's sub-directories, 
 * UNLESS a rule for a sub directory is explicitly specified. In that case, the 
 * sub-directory WILL NOT INHERIT ANY PROPERTIES from it's parent. This means that
 * for example, you could specify a sub-directory that ALLOWS all sizes, even 
 * within a directory that BLOCKS all sizes. Please note that unless a directory
 * or one if it's parents is exlpicitly specified, the image resizer script
 * will not be able to access it. For example, if you only define "images/",
 * the script will not be able to access "graphics/".
*/
////////////////////////////////////////////////////////////////
//Image Directories Options

	//TODO: Allow directory wide caching over-ride
	
	SWDF_add_img_path(Array(
		"path"=>"images/",
		"allow_sizes"=>"all"
	));
	
	SWDF_add_img_path(Array(
		"path"=>"images/restricted/",
		"deny_sizes"=>Array("0")
	));
	
	
////////////////////////////////////////////////////////////////	
/* To prevent users from making the server generate thousands of different resized
 * images by allowing them to chose the size themselves, the SWDF requires you must
 * explicitly declare all allowed sizes before they can be used. To do so, from the
 * following settings create a new array inside: $_SWDF['settings']['images']['sizes'] like so:
 * 
 * $_SWDF['settings']['images']['sizes']['_ID_']=Array(
 *	"id"=>"_ID_",
 *	"method"=>"original"
 * );
 * 
 * Note: * = required,  - = sometimes required
 * 
 * "id"				string	*	The identifier for the size, must be ALPHANUMERIC and must be the same as _ID_ above (the id of the array).
 * "method"			string	*	The method of resizing. Possible values: original|fit|fill|stretch|scale
 * "width"			int		-	The output width of the image (ignored with methods "original" and "scale")
 * "height"			int		-	The output height of the image (ignored with methods "original" and "scale")
 * "scale"			float	-	If method "scale" is used, specify the amount to scale the image by. 2=double size, 0.5=half size etc.
 * "output"			string		The mime type of the output. If unset, no type conversion will occur.
 * "quality"		int			0-100. Jpeg output quality. Default specified above in $_SWDF['settings']['images']['default_jpeg_quality']
 * "watermark"		array		An array of settings to place a specified image as a watermark over the the requested image. Possible settings are indented below:
 *		"path"		 string	-	 The absolute path to the watermark image
 *		"v"			 string		 The vertical position of the water mark over the original image. Possible values: top|center|bottom (default is center)
 *		"h"			 string		 The horizontal position of the water mark over the original image. Possible values: left|center|right (default is center)
 *		"opacity"	 int			 The opacity of the watermark. Between 0 and 100. Default is defined in $_SWDF['settings']['images']['default_watermark_opacity']
 *		"scale"		 float		 Scale the watermark. 1.0 = normal size. 0.5 = half size etc. Default is 1.0.
 *		"repeat"	 boolean		 Repeat the watermark in a pattern? Ignores "v" and "h". Default false.
 * "disable_caching"boolean		Use to overide $_SWDF['settings']['images']['cache_resized'] and prevent caching if required (note: you cannot enable caching if $_SWDF['settings']['images']['cache_resized'] is set to false). Default false.
 */
////////////////////////////////////////////////////////////////
// Image sizes

	//Size 0 - just passed the image through untouched
	$_SWDF['settings']['images']['sizes']['0']=Array(
		"id"=>0,
		"method"=>"original"
	);
	
	//Size 1 - resizes the image to fit insize a 1000x1000 box and adds a copyright watermark
	$_SWDF['settings']['images']['sizes']['1']=Array(
		"id"=>1,
		"method"=>"fit",
		"width"=>300,
		"height"=>300,
		"watermark"=>Array(
			"path"=>$_SWDF['paths']['root']."images/watermark.png",
			"v"=>"center",
			"h"=>"center",
			"scale"=>0.5,
			"opacity"=>50,
			"repeat"=>true,
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
