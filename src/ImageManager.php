<?php
/**
 * SWDF_image_resizer
 * 
 * This script allows you to automate the resizing of images on your website. 
 * 
 * The ImageManager uses the GD2 PHP library, and wraps it up in a simple
 * little class. It also contains a system for managing all images on a website, 
 * both securing them and resizing/watermarking them as needed.
 * 
 * You are free to use, share and alter/remix this code, provided you distribute 
 * it under the same or a similar license as this work. Please clearly mark any
 * modifications you make (if extensive, a summary at the begining of the file
 * is sufficient). If you redistribute, please include a copy of the LICENSE, 
 * keep the message below intact:
 * 
 * Copyright 2013 James Swift (Creative Commons: Attribution - Share Alike - 3.0)
 * https://github.com/JamesSwift/ImageManager
 * 
 * @author James Swift <me@james-swift.com>
 * @version v0.4.0
 * @package ImageManager
 * @copyright Copyright 2013 James Swift (Creative Commons: Attribution - Share Alike - 3.0)
 */

namespace JamesSwift;



/**
 * Control access the image resizer's acces to specified directory, with the specified settings.
 * 
 * The function takes a single config/data array which can have any of the follwing elements:
 * 
 * "path"		string		The path (relative to $_SWDF['paths']['root']) to the directory to be secured.<br/>
 * "allow_sizes"	array|string	Sizes to allow. All other sizes will be blocked unless otherwise specified. To allow all sizes, set to string "all". Default is "all".<br/>
 * "deny_sizes"		array|string	Sizes to deny. All other sizes will be allowed unless otherwise specified. To block all sizes, set to string "all". By default, none are blocked.<br/>
 * "require_auth"	bool		When true, image_resizer_request() must be called with the "authorized" argument set to true, to allow resizing in this path.<br/>
 * 
 * @example "example_config.php" See example usage.
 * 
 * @param mixed[] $data	<p>An array containing the path to be added and settings to controll access to it.</p>
 *			<p>[path=>string, allow_sizes=>array|string, deny_sizes=>array|string, require_auth=>bool]</p>
 * 
 * @return bool		<p>Returns `true` on success. Triggers a warning and returns false if the data array is mal-formed.
 */
function add_img_path($data){
	global $_SWDF;
	
	//Check integrety of data
	if (isset($data) && is_array($data) && isset($data['path']) && $data['path']!==null){
		//Normalize path to end with a /
		$data['path'].="/";
		$data['path']=str_replace(Array("\\","//"),"/",$data['path']);
		
		//Store the data in the settings array
		$_SWDF['settings']['images']['paths'][$data['path']]=$data;
		return true;
	}
	
	//Warn user if unable to add path data, as it may lead to a security breach
	if (isset($data['path'])){
		trigger_error("A misconfigured settings file has lead to an error while trying to add image path settings for path: ".$data['path']." Please check your SWDF_image_resizer settings, as this misconfiguration may lead to a security breach.", E_USER_WARNING);
	} else {
		trigger_error("A misconfigured settings file has lead to an error while trying to add image path settings. Please check your SWDF_image_resizer settings, as this misconfiguration may lead to a security breach." , E_USER_WARNING);
	}
	return false;
}

/**
 * Persistantly control access to specified path, with the specified settings.
 * 
 * This function is like add_img_path, but instead of loading the data you 
 * send into the $_SWDF settings variable, the data will be stored in the 
 * $_SESSION['_SWDF'] settings variable. This allows directory settings specific 
 * to a user's session to be persistantly stored between requests.
 * 
 * PLEASE NOTE: For security, settings in this vairable aren't automatically 
 * loaded when using any of the * functions. You must explicitly call 
 * load_user_img_paths() before using the functions, otherwise the user 
 * paths you have defined will be ignored.
 * 
 * The function takes a single config/data array which can have any of the follwing elements:
 * 
 * "path"		string		The path (relative to $_SWDF['paths']['root']) to the directory to be secured.<br/>s
 * "allow_sizes"	array|string	Sizes to allow. All other sizes will be blocked unless otherwise specified. To allow all sizes, set to string "all". Default is "all".<br/>
 * "deny_sizes"		array|string	Sizes to deny. All other sizes will be allowed unless otherwise specified. To block all sizes, set to string "all". By default, none are blocked.<br/>
 * "require_auth"	bool		When true, image_resizer_request() must be called with the "authorized" argument set to true, to allow resizing in this path.<br/>
 * 
 * 
 * @param mixed[] $data	<p>An array containing the path to be added and settings to controll access to it.</p>
 *			<p>[path=>string, allow_sizes=>array|string, deny_sizes=>array|string, require_auth=>bool]</p>
 * 
 * @return bool		<p>Returns true on success. If session has not been initiated or is disabled, or if the $data array is malformed will return false.</p>
 */
function add_user_img_path($data){
	//check if session has been initiated
	if (session_active() && isset($_SESSION)){
		
		//Check integrety of data
		if (isset($data) && is_array($data) && isset($data['path']) && $data['path']!==null){
			
			//Normalize path to end with a /
			$data['path'].="/";
			$data['path']=str_replace(Array("\\","//"),"/",$data['path']);

			//Store the data in the session
			$_SESSION['_SWDF']['images']['paths'][$data['path']]=$data;

			return true;
		}
	}
	return false;
}

/**
 * Loads user-specific directory security settings related to image resizing.
 * 
 * When you have added user-specific security settings with add_user_img_path()
 * function, you need to call this function to load the settings before the
 * image_resizer_request() functions will take note of them. They are not 
 * loaded by default as an added security measure.
 * 
 * @global array $_SWDF
 * @return boolean	<p>Returns false if session hasn't been initiated or is disabled.</p>
 *			<p>Returns true if all paths were added.</p>
 *			<p> If one path fails to load, a warrning will be triggered and the 
 *			function will return false (after attempting to load any remaining 
 *			user paths).</p>
 */
function load_user_img_paths(){
	global $_SWDF;
	//check if session has been initiated
	if (session_active() && isset($_SESSION)){
		
		//Check $_SWDF has been loaded
		if (!isset($_SWDF)){
			$_SWDF=array();
		}
		
		//Even if no paths were defined, return true
		$return=true;
		
		//Check some paths are defined
		if (isset($_SESSION['_SWDF']['images']['paths']) && is_array($_SESSION['_SWDF']['images']['paths'])){
			foreach ($_SESSION['_SWDF']['images']['paths'] as $path){
				//If one of the paths fails, return false
				if (!add_img_path($path)){
					$return=false;
				}
			}
		}
		
		return $return;
	}
	
	return false;
}

/**
 * Finds which security settings apply to a particular image and returns them.
 * 
 * The function starts at the deepest level of your image's path, then progressivley works it way up a directory at a time, testing each level until it finds a matching path from the ones you predefined earlier.
 * 
 * @param string $image	The image (including relative path from to $_SWDF['paths']['root']) to find infomartion about. 
 * 
 * @return bool|mixed[]	If the image exists and matches a predefined path, the settings for that path will be returned. Otherwise, returns false.
 */
function get_img_path_info($image){
	global $_SWDF;

	//First, check all needed data exists, and that image exists
	if (
		isset($_SWDF) && 
		isset($_SWDF['paths']['root']) && 
		isset($image) && $image!=null && 
		isset($_SWDF['settings']['images']['paths']) &&
		is_file($_SWDF['paths']['root'].$image)
	){
		
		//Remove the image from the path, and normalize the path
		$image_path=str_replace(Array("\\","//"),"/",dirname($image));
		$image_path_parts=explode("/",$image_path);
		
		//Find closest matching predefined path
		if (is_array($image_path_parts)){
			
			foreach ($image_path_parts as $part){
				
				if (sizeof($image_path_parts)>0){
					
					//Combine $image_path_parts into a new path
					$new_path_level=implode("/",$image_path_parts)."/";

					//Check if this new path is explicetly defined
					if (isset($_SWDF['settings']['images']['paths'][$new_path_level]) && is_array($_SWDF['settings']['images']['paths'][$new_path_level])){
						
						//A match was found, return the data for that path
						return $_SWDF['settings']['images']['paths'][$new_path_level];
						
					//If no match found, move up one level and try again
					} else array_pop($image_path_parts);
				}
			}
		}

	}
	
	//Something went wrong
	return false;
}

/**
 * Process a path's settings and produce an array of sizes that are definitely allowed (and that really exist).
 * 
 * Please ensure you have specified your paths and sizes first, otherwise the function will return false. Allowed sizes are added first, then any sizes defined in "deny_sizes" are then removed from the list.
 * 
 * @param string $path The path you want to check.
 * @return boolean|array Returns an array of size ids on success. If the path cannot be found or no global sizes are specified, will return false.
 */
function get_allowed_sizes($path){
	global $_SWDF;

	//Check needed resources are available
	if (!(	isset($path) && 
		$path !== null &&
		isset($_SWDF) &&
		isset($_SWDF['settings']['images']['paths']) &&
		isset($_SWDF['settings']['images']['paths'][$path]) &&
		is_array($_SWDF['settings']['images']['paths'][$path]) &&
		isset($_SWDF['settings']['images']['sizes']) &&
		is_array($_SWDF['settings']['images']['sizes'])
	)) return false;
	
	//Create shortcut to path settings
	$path=$_SWDF['settings']['images']['paths'][$path];
	
	$allowed_sizes=Array();
	
	//If "allow_sizes" was left blank or set to all, populate the array with all available sizes
	if ($path['allow_sizes']==="all" || $path['allow_sizes']===NULL){
		foreach($_SWDF['settings']['images']['sizes'] as $id=>$size){
			$allowed_sizes[$id]=$id;
		}
		
	//If "allow_sizes" was an array instead, populate the array with those sizes
	} else if ($path['allow_sizes']!=NULL && is_array($path['allow_sizes'])){
		foreach($path['allow_sizes'] as $id){
			//Check each size actually exists first
			if (isset($_SWDF['settings']['images']['sizes'][$id])){
				$allowed_sizes[$id]=$id;
			}
		}
	}
	
	//Now remove any sizes explicitly denied
	if (isset($path['deny_sizes']) && $path['deny_sizes']==="all"){
		//All sizes are denied, set the array to blank
		$allowed_sizes=Array();
		
	//Just some sizes are denied
	} else if (isset($path['deny_sizes']) && is_array($path['deny_sizes'])){
		foreach ($path['deny_sizes'] as $id){
			if (isset($_SWDF['settings']['images']['sizes'][$id]) && isset($allowed_sizes[$id])){
				unset($allowed_sizes[$id]);
			}
		}
	}
	
	//Return the array of allowed sizes
	return $allowed_sizes;

}

/**
 * Check the user is allowed to resize the specified image to the size they requested.
 * 
 * Note: This function doesn't by default take note of paths stored by add_user_img_path(). 
 * You must explicitly call load_user_img_paths() before calling this function if you want
 * them to be taken into account when granting a request.
 * 
 * @param  string	   $image	Required. The path, relative to $_SWDF['paths']['root'], to the image you wish to resize.
 * 
 * @param  string|null	   $size	Optional. The id of the size you wish to resize the image to. If not specified, 
 *					$_SWDF['settings']['images']['default_size'] will be used (if configured).
 * 
 * @param  bool		   $authorized	Optional. If the path the image falls under has it's "require_auth" property set to true, 
 *					the resize request will fail unless you set this argument to true. It's primarily intended 
 *					as a saftey-net for building your own security system on top of the SWDF_image_resizer.
 * 
 * @return boolean|mixed[]		<p>If the request is allowed, an array containing the details of the requested size will be returned.</p>
 *					<p>If request is not authorized or you have failed to define any sizes or paths, will return false</p>
 */
function validate_resize_request($image,$size=null,$authorized=false){
	global $_SWDF;

	//Check needed resources are available
	if (!(	isset($_SWDF) &&
		isset($_SWDF['paths']['root']) &&
		isset($_SWDF['settings']['images']) &&
		isset($_SWDF['settings']['images']['sizes']) &&
		isset($_SWDF['settings']['images']['paths']) &&
		isset($image) &&
		is_string($image)
	)) return false;
	
	//Load default size if none specified
	if (isset($size) && !is_string($size)){
		if (isset($_SWDF['settings']['images']['default_size'])){
			$size=$_SWDF['settings']['images']['default_size'];
		} else {
			//If default size wasn't specified, bail out
			return false;
		}
	}

	//Normalize the $image request, and prevent back-references
	$image=str_replace(Array("\\","//"),"/",$image);
	$image=str_replace(Array("../","./"),"",$image);

	//First, check file exists before other checks.
	if (is_file($_SWDF['paths']['root'].$image)){
		
		//Get security settings for the path the image is in
		$path=get_img_path_info($image);
		
		//Check path is allowed
		if ($path!==false){
			
			//Find the allowed sizes for the path the image is in
			$sizes=get_allowed_sizes($path['path']);
			
			//Check whether requested size is allowed
			if (is_array($sizes)){
				
				if (in_array($size,$sizes)===true && is_array($_SWDF['settings']['images']['sizes'][$size])){
					
					//Size is allowed, just one final thing. Check we are authorized to render
					
					//Authorization required
					if (isset($path['require_auth']) && $path['require_auth']===true){
						if ($authorized===true){
							return $_SWDF['settings']['images']['sizes'][$size];
						}
						
					//No authorization required, just return the size to be rendered
					} else {
						return $_SWDF['settings']['images']['sizes'][$size];
					}
				}
			}
		}
	}
	
	//Something went wrong
	return false;
}

/**
 * Resize an image according to the settings you earlier specified
 * 
 * You must specify your settings in the $_SWDF variable as per the documentation before calling this function. It will then check your user's request to see if the file they have requested can be displayed to them at the size they requested. If so, it will resize it and return the raw image data for your to display.
 * 
 * @param string $img The path (relative to $_SWDF['paths']['root']) of the image to be resized.
 * @param string $requested_size The id of a size specified in $_SWDF['settings']['images']['sizes'] that the user whiches to resize the image to.
 * @param bool $authorized Whether this request has been authorized. Normally you can ignor this variable, it's only needed if you have added your own security layer ontop of the SWDF_image_resizer tool.
 * 
 * @return bool|mixed[] <p>If required dependancies could not be loaded (E.G. $_SWDF), the function will return false.</p><br/>
 *			<p>If the request cannot be processed because, for example, the file cannot be found or access is denied, the function will return an array similar to this:<br/>
 *			array("status"=>"404","data"=>"File not found");</p><br/>
 *			<p>If the request was successful, the function will return an array similar to this:<br/>
 *			array(<br/>
 *				"status"=>"200",<br/>
 *				"headers"=>array("Last-Modified"=>"Wed, 27 Mar 2013 00:47:53 GMT", "Content-Type"=>"image/jpeg"),<br/>
 *				"cache_location"=>"/tmp/1.jpg[aabbccddeeff].cache",<br/>
 *				"data"=>RAW_IMAGE_DATA<br/>
 *			);<br/>
 *			You can choose to use the data in the header section to render the image directly to the user, or ignore it do something different with the returned image data.</p>
 */
function image_resizer_request($img, $requested_size=null, $authorized=false){
	global $_SWDF;
	
	//Check all dependancies are loaded
	if (!(
		isset($_SWDF) &&
		isset($_SWDF['paths']['root']) &&
		isset($_SWDF['settings']['images']) &&
		isset($_SWDF['settings']['images']['sizes']) &&
		isset($_SWDF['settings']['images']['paths'])
	)) return false;
	
	//This function returns an array. Initiate it.
	$return = array();

	//Get details of requested size
	$size=validate_resize_request($img,$requested_size,$authorized);

	//Check whether to proceed with resize request
	if ($size!==false && is_array($size)){

		//Get absolute path to image
		$img_path=$_SWDF['paths']['root'].$img;
		
		//Normalize path
		$img_path=str_replace(Array("\\","//"),"/",$img_path);
		
		//Prevent direcotry traversal
		$img_path=str_replace(Array("../","./"),"",$img_path);
		
		//Note for later use
		$orig_img_path=$img_path;

		//Work out where the cached image will be/is stored
		$cache_file=null;
		//Is caching enabled? (both globally and for this size)
		if ( isset($_SWDF['settings']['images']['cache_resized']) && 
		     isset($_SWDF['paths']['images_cache']) &&
		     $_SWDF['settings']['images']['cache_resized']===true && 
		     @$size['disable_caching']!==true
		){
			//Create path to cached file
			$cache_file=$_SWDF['paths']['images_cache'].basename($img_path)."[".md5($img_path.json_encode($size))."].cache";

			//Check if a cached version exists (and it hasn't expired)
			if ( is_file($cache_file) && 
			     filemtime($cache_file)>filemtime($orig_img_path) && 
			     filemtime($cache_file)>time()-$_SWDF['settings']['images']['cache_expiry']
			){
				//It's valid, so let's forget about resizing and point straight to the cached version
				$img_path=$cache_file;
				$size=Array("method"=>"original");

			//If the cache file exists, but is now out of date, remove it to keep the cache clean
			} else if (is_file($cache_file)) {
				unlink($cache_file);
			}
		}	

		//Get properties of image to be resized
		$properties=getimagesize($img_path);

		//If method=original just pass the data straight through
		if (isset($size['method']) && $size['method']==="original" && is_file($img_path)){
			//Check file is an image
			if ($properties!==false){
				//just pass image through script
				$return['status']=200;
				$return['headers'][]="Content-type: {$properties['mime']}";
				$return['headers'][]='Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($orig_img_path)).' GMT';
				$return['headers'][]='Expires: '.gmdate('D, d M Y H:i:s', time()+$_SWDF['settings']['images']['cache_expiry']).' GMT';
				$return['data']=file_get_contents($img_path);
			} else {
				//Invalid image
				$return['status']=404;
			}

		//If method is any other kind, make a SWDF_image_resizer() request
		} else if(in_array($size['method'], Array("original","fit","fill","stretch","scale"))===true && is_file($img_path)) {

			//Load resizer class
			$resizer=new \JamesSwift\ImageResizer();

			//Set JPEG quality
			$resizer->quality=$_SWDF['settings']['images']['default_jpeg_quality'];
			if (isset($size['quality']) && $size['quality']!=null){
				$resizer->quality=$size['quality'];
			}

			//load source
			$resizer->load_image($img_path);

			//resize image
			$resizer->resize($size['method'],@$size['width'],@$size['height'],@$size['scale']);

			//add watermark
			if (isset($size['watermark']) && is_array($size['watermark']) && isset($size['watermark']['path']) && is_file($size['watermark']['path']) ){
				if (isset($size['watermark']['opacity']) && ctype_digit(isset($size['watermark']['opacity']))){
					$size['watermark']['opacity']=$_SWDF['settings']['images']['default_watermark_opacity'];
				}
				$resizer->add_watermark($size['watermark']['path'],@$size['watermark']['v'],@$size['watermark']['h'],@$size['watermark']['opacity'],@$size['watermark']['scale'],@$size['watermark']['repeat'],5,5);
			}

			//Render resized image
			$output = $resizer->output_image(@$size['output'])->getImageData();

			//Save image to cache
			if ($cache_file!==null && is_string($cache_file)){
				if (!is_dir($_SWDF['paths']['images_cache']))
					mkdir($_SWDF['paths']['images_cache'], 0777, true);
				file_put_contents($cache_file,$output);
			}

			//Find data about original file
			$properties=getimagesize($img_path);
			
			//Build return headers
			$return['status']=200;
			
			//Find mime-type
			if (isset($size['output']) && $size['output']!==null){
				$return['headers'][]="Content-type: {$size['output']}";
			} else if ($properties!==false){
				$return['headers'][]="Content-type: {$properties['mime']}";
			}
			
			//Last-modified
			$return['headers'][]='Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($orig_img_path)).' GMT';
			
			//Expiry
			if (isset($_SWDF['settings']['images']['cache_expiry'])){
				$return['headers'][]='Expires: '.gmdate('D, d M Y H:i:s', time()+$_SWDF['settings']['images']['cache_expiry']).' GMT';
			}
			
			//Tell the user where the cached version lives
			$return['cache_location']=$cache_file;

			//Return the image
			$return['data']=$output;

			//Clean the cache directory every now and then
			if (rand(0,1)===1) clean_image_cache();
		} else {
			die("Invalid method specified for this size.");
		}
		
	//If we ended up here, either the file is not found/access denied, or the resizing method doesn't exist
	} else {
		$return['status']=404;
		$return['data']="File not found";
	}

	//Return the data
	return $return;
}

/**
 * Clean the resized images cache
 * 
 * If you are trying to delete all the cached versions of a particular image, you can specify it in the first argument and it will delete any cache file starting with that name.
 * By default, this function will only opperate if caching is enabled, but you can force it to operate by setting the second argument to true.
 * 
 * @param string|null $delete_fname Optional. The name (NOT PATH) of an image (E.G. "hello.jpg"). Any time a cache file starting with that name is encountered it will be deleted, regardless of if it has expired or not.
 * @param bool $force Optional. Force cleaning fo the image cache, even when caching has been disabled. (You must have specified $_SWDF['paths']['images_cache'] and $_SWDF['settings']['images']['cache_expiry'] for this to work)
 * @return bool 
 */
function clean_image_cache($delete_fname=null, $force=false){
	global $_SWDF;

	//Has a file called $delete_fname been deleted in this sweep?
	$deleted=false;
		
	//Check caching is enabled (and configured)
	if ( isset($_SWDF) && ($force===true || ( 
	      isset($_SWDF['settings']['images']['cache_resized']) && 
	      $_SWDF['settings']['images']['cache_resized']===true && 
	      isset($_SWDF['settings']['images']['cache_expiry']) && 
	      $_SWDF['settings']['images']['cache_expiry']!=null
	    ) )
	){

		//Check cache directory exists
		if (isset($_SWDF['paths']['images_cache']) && $_SWDF['paths']['images_cache']!==null && is_dir($_SWDF['paths']['images_cache']) && isset($_SWDF['settings']['images']['cache_expiry']) && ctype_digit($_SWDF['settings']['images']['cache_expiry'])) {

			//Create list of files in cache directory
			$dir=scandir($_SWDF['paths']['images_cache']);

			//Cycle through files and delete if appropriate
			if (is_array($dir)){
				foreach($dir as $file){
					//Work out file-name
					$fname=substr($file, 0, strpos($file, "["));

					//Determine whether to delete or not
					if (is_file($_SWDF['paths']['images_cache'].$file) && 
					    substr($file,-5,5)=="cache" &&
					    (
					     filemtime($_SWDF['paths']['images_cache'].$file)<time()-$_SWDF['settings']['images']['cache_expiry'] || 
					     $fname===$delete_fname
					    )
					){
						unlink($_SWDF['paths']['images_cache'].$file);
						if ($fname===$delete_fname) $deleted=true;
					}
				}
				
				//If the user was specifically trying to delete one file, return true or false
				if ($delete_fname!==null){
					return $deleted;
				}
				
				//Else, the scan was successful. return true
				return true;
			}
		}
	}

	//Hmm, something went wrong
	return false;
}

//Make backwards compatible with earlier version of PHP
//This isn't a perfect test for whether a session is active or not, but 
if (!function_exists('session_status')){
    function session_active(){
        return defined('SID');   
    }
} else {
    function session_active(){
        return (session_status() === 2);   
    }        
}




#############################################################################################################



class Exception extends \Exception {
	//Nothing to do here yet
}

//TODO: Completely rewrite class, add phpDoc
class ImageResizer {

	private $source;
	private $stream;
	private $type;
	private $width;
	private $height;
	private $img=array();

	private $compatible_mime_types=Array("image/jpeg","image/jp2","image/png","image/gif");
	public $quality;

	
	public function filter_opacity($img_name, $opacity=100) {

		if (!isset($this->img[$img_name]))
			return false;
		
		$img=&$this->img[$img_name]['stream'];
		$opacity /= 100;

		//get image width and height
		$w = imagesx($img);
		$h = imagesy($img);

		//turn alpha blending off
		imagealphablending($img, false);

		//find the most opaque pixel in the image (the one with the smallest alpha value)
		$minAlpha = 127;
		for ($x = 0; $x < $w; $x++) {
			for ($y = 0; $y < $h; $y++) {
				$alpha = ( imagecolorat($img, $x, $y) >> 24 ) & 0xFF;
				if ($alpha < $minAlpha)
					$minAlpha = $alpha;
			}
		}

		//loop through image pixels and modify alpha for each
		for ($x = 0; $x < $w; $x++) {
			for ($y = 0; $y < $h; $y++) {
				//get colors for this pixel
				$pixel = imagecolorat($img, $x, $y);
				$colors = imagecolorsforindex($img, $pixel);
				
				//calculate new alpha
				if ($minAlpha !== 127){
					$colors['alpha'] = 127 + ( ( (127 * $opacity) * ( $colors['alpha'] - 127 ) ) / ( 127 - $minAlpha ) );
				} else {
					$colors['alpha'] += 127 * $opacity;
				}
				
				//get the color index with new alpha
				$newPixel = imagecolorallocatealpha($img, $colors['red'],$colors['green'], $colors['blue'], $colors['alpha']);
				
				//set pixel with the new color + opacity
				imagesetpixel($img, $x, $y, $newPixel);
			}
		}
		
		return true;
	}

	public function load_image($source,$id="main"){
		if (is_file($source)){
			$properties=getimagesize($source);
			if ($properties!=false){
				if (in_array($properties['mime'],$this->compatible_mime_types)===true){
					if ($properties['mime']=="image/jpeg" || $properties['mime']=="image/jp2"){
						if (!$this->img[$id]['stream']=imagecreatefromjpeg($source)){
							return false;
						}
					}
					if ($properties['mime']=="image/png"){
						if (!$this->img[$id]['stream']=imagecreatefrompng($source)){
							return false;
						}
					}
					if ($properties['mime']=="image/gif"){
						if (!$this->img[$id]['stream']=imagecreatefromgif($source)){
							return false;
						}
					}

					imagealphablending($this->img[$id]['stream'], true);
					imagesavealpha($this->img[$id]['stream'], true);

					$this->img[$id]['source']=$source;
					$this->img[$id]['type']=$properties['mime'];
					$this->img[$id]['width']=$properties[0];
					$this->img[$id]['height']=$properties[1];
					return true;
				}

			}

		}
		return false;
	}

	public function resize($method,$n_width=null,$n_height=null,$scale=null,$img_id="main"){
		
		if ($method==="original"){
			return true;
		} else if ($method==="fit"){
			
			if ($this->img[$img_id]['width']>=$this->img[$img_id]['height']){
				$scale=$n_width/$this->img[$img_id]['width'];
				if ($scale*$this->img[$img_id]['height']>$n_height){
					$scale=$n_height/$this->img[$img_id]['height'];
				}
			} else {
				$scale=$n_height/$this->img[$img_id]['height'];
				if ($scale*$this->img[$img_id]['width']>$n_width){
					$scale=$n_width/$this->img[$img_id]['width'];
				}					
			}

			$n_width=$this->img[$img_id]['width']*$scale;
			$n_height=$this->img[$img_id]['height']*$scale;

			//Create blank image
			$this->img['temp']['stream']=imagecreatetruecolor($n_width,$n_height);
			
			//Make background transparent
			imagefill($this->img['temp']['stream'], 0, 0, 2130706432);
			
			//Allow alpha to be used
			imagealphablending($this->img['temp']['stream'], true);
			imagesavealpha($this->img['temp']['stream'], true);

			//resize image
			if (imagecopyresampled($this->img['temp']['stream'], $this->img[$img_id]['stream'], 0, 0, 0, 0, $n_width, $n_height, $this->img[$img_id]['width'], $this->img[$img_id]['height'])){

				//place in stream
				$this->img[$img_id]['stream']=$this->img['temp']['stream'];
				$this->img[$img_id]['width']=$n_width;
				$this->img[$img_id]['height']=$n_height;
				return true;
			}

		} else if ($method==="fill"){
			//Create blank image
			$this->img['temp']['stream']=imagecreatetruecolor($n_width,$n_height);
			
			//Make background transparent
			imagefill($this->img['temp']['stream'], 0, 0, 2130706432);
			
			//imagealphablending($this->img['temp']['stream'], true);
			imagesavealpha($this->img['temp']['stream'], true);

			//Determine scale
			if ($n_width<=$n_height){
				$scale=$this->img[$img_id]['width']/$n_width;
				if ($scale*$n_height>$this->img[$img_id]['height']){
					$scale = $this->img[$img_id]['height']/$n_height;
				}
			} else {
				$scale=$this->img[$img_id]['height']/$n_height;
				if ($scale*$n_width>$this->img[$img_id]['width']){
					$scale = $this->img[$img_id]['width']/$n_width;
				}
			}				

			$s_width=$n_width*$scale;
			$s_height=$n_height*$scale;

			$left=($this->img[$img_id]['width']-$s_width)/2;
			$top=($this->img[$img_id]['height']-$s_height)/2;

			//resize image
			if (imagecopyresampled($this->img['temp']['stream'], $this->img[$img_id]['stream'], 0, 0, $left, $top, $n_width, $n_height, $s_width, $s_height)){

				//place in stream
				$this->img[$img_id]['stream']=$this->img['temp']['stream'];
				$this->img[$img_id]['width']=$n_width;
				$this->img[$img_id]['height']=$n_height;
				return true;
			}

		} else if ($method==="stretch"){
			$this->img['temp']['stream']=imagecreatetruecolor($n_width,$n_height);
			
			//Make background transparent
			imagefill($this->img['temp']['stream'], 0, 0, 2130706432);
			
			//imagealphablending($this->img['temp']['stream'], true);
			imagesavealpha($this->img['temp']['stream'], true);

			if (imagecopyresampled($this->img['temp']['stream'], $this->img[$img_id]['stream'], 0, 0, 0, 0, $n_width, $n_height, $this->img[$img_id]['width'], $this->img[$img_id]['height'])){
				$this->img[$img_id]['stream']=$this->img['temp']['stream'];
				$this->img[$img_id]['width']=$n_width;
				$this->img[$img_id]['height']=$n_height;					
				return true;
			}
		} else if ($method==="scale"){
			$n_width  = $this->img[$img_id]['width']*$scale;
			$n_height = $this->img[$img_id]['height']*$scale;

			$this->img['temp']['stream']=imagecreatetruecolor($n_width, $n_height);
			//Make background transparent
			imagefill($this->img['temp']['stream'], 0, 0, 2130706432);
			//imagealphablending($this->img['temp']['stream'], true);
			imagesavealpha($this->img['temp']['stream'], true);

			if (imagecopyresampled($this->img['temp']['stream'], $this->img[$img_id]['stream'], 0, 0, 0, 0, $this->img[$img_id]['width']*$scale, $this->img[$img_id]['height']*$scale, $this->img[$img_id]['width'], $this->img[$img_id]['height'])){				
				$this->img[$img_id]['stream']=$this->img['temp']['stream'];
				$this->img[$img_id]['width']=$this->img[$img_id]['width']*$scale;
				$this->img[$img_id]['height']=$this->img[$img_id]['height']*$scale;					
				return true;
			}
		}
		return false;
	}

	public function add_watermark($path,$vAlign="center",$h="center",$opacity=100,$scale=1,$repeat=false,$vPad=0,$hPad=0){
		if ($this->load_image($path,"wm")){

			if ($scale!="" && $scale!=1){
				$this->resize("scale",null,null,$scale,"wm");
			}
			
			if ($opacity!=100){
				$this->filter_opacity("wm",$opacity);
			}
			
			//Repeat the watermark in a pattern?
			if ($repeat==false){
				//Default center
				$h_pos=($this->img['main']['width']/2)-($this->img['wm']['width']/2);
				
				if ($h=="left"){ $h_pos=0; }
				if ($h=="right"){ $h_pos=$this->img['main']['width']-$this->img['wm']['width']; }

				//Default center
				$v_pos=($this->img['main']['height']/2)-($this->img['wm']['height']/2);
				if ($vAlign=="top"){ $v_pos=0; }
				if ($vAlign=="bottom"){ $v_pos=$this->img['main']['height']-$this->img['wm']['height']; }

				//Merge the watermark onto the background
				imagecopy(	$this->img['main']['stream'],
						$this->img['wm']['stream'],
						$h_pos,
						$v_pos,
						0,
						0,
						$this->img['wm']['width'],
						$this->img['wm']['height']
				);

				return true;
				
			} else {
				//Turn percentages into decimal
				//$hPad=round( ($hPad/100)*$this->img['main']['width'] );
				//$vPad=round( ($vPad/100)*$this->img['main']['height'] );
				
				$x=floor($hPad/2);
				$y=floor($vPad/2);
				$i=0;
				while ($x<$this->img['main']['width'] && $y<$this->img['main']['height'] ){
					//Place a watermark
					imagecopy(	$this->img['main']['stream'],
							$this->img['wm']['stream'],
							$x,
							$y,
							0,
							0,
							$this->img['wm']['width'],
							$this->img['wm']['height']
					);
					
					//Walk it right one step
					$x+=$this->img['wm']['width']+$hPad;
					
					//Move down to a new line
					if ($x>=$this->img['main']['width']){
						
						$y+=($this->img['wm']['height']+$vPad);
						$i++;
						//Offset the new line
						$x=0-round( ($i%3)*(($this->img['wm']['width']+$hPad)/3) );
					}
				}
								
			}
		}
		return false;
	}

	public function output_image($output_type=null){
		if ($output_type==""){
			$output_type=$this->img['main']['type'];
		}

		imageinterlace($this->img['main']['stream'], true);
		
		//Start a new buffer
		ob_start();

		//Output the image
		if ($output_type=="image/jpeg" || $output_type=="image/jp2"){
			if (!imagejpeg($this->img['main']['stream'], null, $this->quality)){
				return false;
			}
		}
		if ($output_type=="image/png"){
			if (!imagepng($this->img['main']['stream'])){
				return false;
			}
		}
		if ($output_type=="image/gif"){
			if (!imagegif($this->img['main']['stream'])){
				return false;
			}
		}
		
		//Return captured buffer
		return new ResizedImage(ob_get_clean(),null,time());
	}

	public function destory(){
		foreach($this->img as $img){
			if (is_object($img) && $img->stream!=NULL){
				imagedestroy($img->stream);
			}
		}
	}

	public function __destruct(){
		$this->destory();
	}
}










//TODO: Add phpDoc
class Image {
	protected $_img;
	protected $_mime;
	protected $_originalLocation;
	protected $_allowedOutputFormats = array("image/jpeg","image/jp2","image/png","image/gif");
	protected $_expires;
	protected $_lastModified;
	
	//TODO: Add phpDoc
	public function __construct($img, $expires=null, $lastModified=null){
		
		//Check expires is valid
		if (!ctype_digit($expires) && $expires!==null)
			throw new Exception("Can't create new Image object. Please specify a valid value for \$expires (positive integer, or null).", 500);
			
		//Check lastModified is valid
		if (!ctype_digit($lastModified) && $lastModified!==null)
			throw new Exception("Can't create new Image object. Please specify a valid value for \$lastModified (positive integer, or null).", 500);
			
		//Load finfo to find the mime type of the passed file/string
		$finfo = new \finfo(FILEINFO_MIME_TYPE);
		
		//If a file reference was passed, load it into memory
		if (strlen($img)<1000 && ctype_print($img) && is_file($img)){
		
			//Try to read mime data
			$mime=$finfo->file($img);
	
			//Load data
			$this->_originalLocation=$img;
			$img=file_get_contents($img);
			
			//Store Last Modified
			$this->setLastModified(null,true);
		} else {
			//Store Last Modified
			$this->setLastModified($lastModified);
			
			//Try to read mime data from passed string
			$mime=$finfo->buffer($img);
		}
		
		//Check mime type is allowed (and that the image was readable)		
		if ($mime==="text/plain")
			throw new Exception("Unable to load image. Please check your are passing a valid path, or the content of a valid image file.", 500);
		if (in_array($mime, $this->_allowedOutputFormats)===false)
			throw new Exception("Unable to load image. Unsupported mime-type: ".$mime, 500);
			
		//Populate data
		$this->_img=$img;
		$this->_mime=$mime;
		$this->setExpires($expires);
	}
	
	//TODO: Add phpDoc
	public function setExpires($expires=null){
		//Check we're storing a valid value
		if (!ctype_digit($expires) && $expires!==null)
			throw new Exception("Can't create set Expires header, please specify a valid value (positive integer, or null).", 500);
		
		//Store the value
		$this->_expires=$expires;
		return true;
	}
	
	//TODO: Add phpDoc
	public function setLastModified($lastModified=null, $setFromFile=false){

		//Load the last modified from file?
		if ($setFromFile===true){
			if ($this->_originalLocation!==null)
				$lastModified=filemtime($this->_originalLocation);
			else return false;
		}
			
		//Check we're storing a valid value
		if (!ctype_digit($lastModified) && $lastModified!==null)
			throw new Exception("Can't create set Last-Modified header, please specify a valid value (positive integer, or null).", 500);

		//Store the value
		$this->_lastModified=$lastModified;
		return true;
	}
	
	//TODO: Add phpDoc
	public function getExpires(){
		return $this->_expires;
	}
	
	//TODO: Add phpDoc
	public function getLastModified(){
		return $this->_lastModified;
	}
		
	//TODO: Add phpDoc
	public function outputExpiresHeader(){
		if ($this->_expires===null)
			return false;
		
		header("Expires: ".gmdate('D, d M Y H:i:s', $this->_expires));
		return true;
	}
	
	
	//TODO: Add phpDoc
	public function outputLastModifiedHeader(){
		if ($this->_lastModified===null)
			return false;

		header("Last-Modified: ".gmdate('D, d M Y H:i:s', $this->_lastModified));
		return true;
	}
	
	//TODO: Add phpDoc
	public function getLocation(){
		if ($this->_originalLocation!==null)
			return $this->_originalLocation;
		return false;
	}
	
	//TODO: Add phpDoc
	public function getMimeType(){
		return $this->_mime;
	}
	
	//TODO: Add phpDoc
	public function getImageData(){
		return $this->_img;
	}
	
	//TODO: Add phpDoc
	public function getSize(){
		return strlen($this->getImageData());
	}
	
	//TODO: Add phpDoc
	public function getImageDimensions(){
		$img = imagecreatefromstring($this->getImageData());
		if ($img===false) return false;
		return array("width"=>imagesx($img),"height"=>imagesy($img));
	}
	
	//TODO: Add phpDoc
	public function outputData(){
		print $this->getImageData();
		return true;
	}
	
	//TODO: Add phpDoc
	public function outputHTTP($headers=null) {
		
		//Output any additional headers
		if (is_array($headers)===true){
			foreach ($headers as $type=>$header){
				header($type.": ".$header);
			}
		}
		
		//Output caching variables
		$this->outputLastModifiedHeader();
		$this->outputExpiresHeader();
		
		//Output the image
		header("Content-Type: ".$this->getMimeType());
		$this->outputData();
		
		return true;
	}
	
	//TODO: Add phpDoc
	public function saveAs($where) {
		return file_put_contents($where, $this->getImageData());
	}	
}

//TODO: Add phpDoc
class ResizedImage extends Image {

}

//TODO: Add phpDoc
class CachedImage extends Image {
	
	//TODO: Add phpDoc
	public function deleteCachedCopy(){
		if ($this->_originalLocation===null)
			return false;
		return unlink($this->_originalLocation);
	}
}
?>