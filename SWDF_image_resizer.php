<?php

/**
 * SWDF Image Resier
 * 
 * This script allows you to automate the resizing of images on your website. 
 * 
 * The SWDF_image_resizer uses the GD2 PHP library, and wraps it up in a simple
 * little class. It also contains a system for managing all images on a website, 
 * both securing them and resizing/watermarking them as needed.
 * 
 * For a quick start, see examples.txt.
 * 
 * You are free to use, share and alter/remix this code, provided you distribute 
 * it under the same or a similar license as this work. Please clearly mark any
 * modifications you make (if extensive, a summary at the begining of the file
 * is sufficient). If you redistribute, please include a copy of the LICENSE, 
 * keep the message below intact:
 * 
 * Copyright 2013 James Swift (Creative Commons: Attribution - Share Alike - 3.0)
 * https://github.com/James-Swift/SWDF_image_resizer
 * 
 * @author James Swift <me@james-swift.com>
 * @version v0.1.3
 * @package SWDF_image_resizer
 * @copyright Copyright 2013 James Swift (Creative Commons: Attribution - Share Alike - 3.0)
 */





/**
 * Control access the image resizer's acces to specified directory, with the specified settings.
 * 
 * The function takes a single config/data array which can have any of the follwing elements:
 * 
 * "path"		string		The path (relative to $_SWDF['paths']['root']) to the directory to be secured.<br/>
 * "allow_sizes"	array|string	Sizes to allow. All other sizes will be blocked unless otherwise specified. To allow all sizes, set to string "all". Default is "all".<br/>
 * "deny_sizes"		array|string	Sizes to deny. All other sizes will be allowed unless otherwise specified. To block all sizes, set to string "all". By default, none are blocked.<br/>
 * "require_auth"	bool		When true, SWDF_image_resizer_request() must be called with the "authorized" argument set to true, to allow resizing in this path.<br/>
 * 
 * @example "example_config.php" See example usage.
 * 
 * @param mixed[] $data	<p>An array containing the path to be added and settings to controll access to it.</p>
 *			<p>[path=>string, allow_sizes=>array|string, deny_sizes=>array|string, require_auth=>bool]</p>
 * 
 * @return bool		<p>Returns `true` on success. Triggers a warning and returns false if the data array is mal-formed.
 */
function SWDF_add_img_path($data){
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
 * This function is like SWDF_add_img_path, but instead of loading the data you 
 * send into the $_SWDF settings variable, the data will be stored in the 
 * $_SESSION['_SWDF'] settings variable. This allows directory settings specific 
 * to a user's session to be persistantly stored between requests.
 * 
 * PLEASE NOTE: For security, settings in this vairable aren't automatically 
 * loaded when using any of the SWDF_* functions. You must explicitly call 
 * SWDF_load_user_img_paths() before using the functions, otherwise the user 
 * paths you have defined will be ignored.
 * 
 * The function takes a single config/data array which can have any of the follwing elements:
 * 
 * "path"		string		The path (relative to $_SWDF['paths']['root']) to the directory to be secured.<br/>s
 * "allow_sizes"	array|string	Sizes to allow. All other sizes will be blocked unless otherwise specified. To allow all sizes, set to string "all". Default is "all".<br/>
 * "deny_sizes"		array|string	Sizes to deny. All other sizes will be allowed unless otherwise specified. To block all sizes, set to string "all". By default, none are blocked.<br/>
 * "require_auth"	bool		When true, SWDF_image_resizer_request() must be called with the "authorized" argument set to true, to allow resizing in this path.<br/>
 * 
 * 
 * @param mixed[] $data	<p>An array containing the path to be added and settings to controll access to it.</p>
 *			<p>[path=>string, allow_sizes=>array|string, deny_sizes=>array|string, require_auth=>bool]</p>
 * 
 * @return bool		<p>Returns true on success. If session has not been initiated or is disabled, or if the $data array is malformed will return false.</p>
 */
function SWDF_add_user_img_path($data){
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
 * When you have added user-specific security settings with SWDF_add_user_img_path()
 * function, you need to call this function to load the settings before the
 * SWDF_image_resizer_request() functions will take note of them. They are not 
 * loaded by default as an added security measure.
 * 
 * @global array $_SWDF
 * @return boolean	<p>Returns false if session hasn't been initiated or is disabled.</p>
 *			<p>Returns true if all paths were added.</p>
 *			<p> If one path fails to load, a warrning will be triggered and the 
 *			function will return false (after attempting to load any remaining 
 *			user paths).</p>
 */
function SWDF_load_user_img_paths(){
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
				if (!SWDF_add_img_path($path)){
					$return=false;
				}
			}
		}
		
		return $return;
	}
	
	return false;
}

/**
 * Finds which security settings apply to a particular image.
 * 
 * The function starts at the deepest level of your image's path, then progressivley works it way up a directory at a time, testing each level until it finds a matching path from the ones you predefined earlier.
 * 
 * @param string $image	The image (including relative path from to $_SWDF['paths']['root']) to find infomartion about. 
 * 
 * @return bool|mixed[]	If the image exists and matches a predefined path, the settings for that path will be returned. Otherwise, returns false.
 */
function SWDF_get_img_path_info($image){
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
 * Process a path's settings and produce a list of sizes that are definitely allowed (and that really exist).
 * 
 * Please ensure you have specified your paths and sizes first, otherwise the function will return false. Allowed sizes are added first, then any sizes defined in "deny_sizes" are then removed from the list.
 * 
 * @param string $path The path you want to check.
 * @return boolean|array Returns an array of size ids on success. If the path cannot be found or no global sizes are specified, will return false.
 */
function SWDF_get_allowed_sizes($path){
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


function SWDF_validate_resize_request($image,$size="",$authorized=false){
	global $_SWDF;

	if ($size==""){
		$size=$_SWDF['settings']['images']['default_size'];
	}

	$image=str_replace(Array("\\","//"),"/",$image);
	$image=str_replace(Array("../","./"),"",$image);

	//First, check file exists before other checks.
	if (is_file($_SWDF['paths']['root'].$image)){
		//Get security settings for the path the image is in
		$path=SWDF_get_img_path_info($image);
		//Check path is allowed
		if ($path!=false){
			//Find the allowed sizes for the path the image is in
			$sizes=SWDF_get_allowed_sizes($path['path']);
			//Check whether requested size is allowed
			if (is_array($sizes)){
				if (in_array($size,$sizes)===true && is_array($_SWDF['settings']['images']['sizes'][$size])){
					//Check we are authorized to render
					if (isset($path['require_auth']) && $path['require_auth']===true){
						if ($authorized===true){
					return $_SWDF['settings']['images']['sizes'][$size];
				}
					} else {
						return $_SWDF['settings']['images']['sizes'][$size];
			}
		}
			}
		}
		return false;
	} else {
		return false;
	}
}

function SWDF_clean_image_cache($delete_fname=null, $force=false){
	global $_SWDF;

	//Check caching is enabled (and configured)
	if ($force===true || ( isset($_SWDF['settings']['images']['cache_resized']) && $_SWDF['settings']['images']['cache_resized']===true && isset($_SWDF['settings']['images']['cache_expiry']) && $_SWDF['settings']['images']['cache_expiry']!=null) ){

		//Check cache directory exists
		if (isset($_SWDF['paths']['images_cache']) && $_SWDF['paths']['images_cache']!==null && is_dir($_SWDF['paths']['images_cache']) ) {

			//Create list of files in cache directory
			$dir=scandir($_SWDF['paths']['images_cache']);

			//Cycle through files and delete if appropriate
			if (is_array($dir)){
				foreach($dir as $file){
					//Work out file-name
					$fname=substr($file, 0, strpos($file, "["));

					//Determine whether to delete or not
					if (is_file($_SWDF['paths']['images_cache'].$file) && (filemtime($_SWDF['paths']['images_cache'].$file)<time()-$_SWDF['settings']['images']['cache_expiry'] || $fname===$delete_fname) && substr($file,-5,5)=="cache"){
						unlink($_SWDF['paths']['images_cache'].$file);
					}
				}
				return true;
			}
		}
	}

	//Hmm, something went wrong
	return false;
}


class SWDF_image_resizer {

	private $source;
	private $stream;
	private $type;
	private $width;
	private $height;
	private $img=array();

	public $compatible_mime_types=Array("image/jpeg","image/jp2","image/png","image/gif");
	public $quality;


	public function __construct(){

	}

	public function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct){ 
	   // creating a cut resource 
	   $cut = imagecreatetruecolor($src_w, $src_h); 

	   // copying relevant section from background to the cut resource 
	   imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h); 

	   // copying relevant section from watermark to the cut resource 
	   imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h); 

	   // insert cut resource to destination image 
	   imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct); 
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

					imagealphablending($this->img[$id]['stream'], false);
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
			imagealphablending($this->img['temp']['stream'], false);
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
			imagealphablending($this->img['temp']['stream'], false);
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
			imagealphablending($this->img['temp']['stream'], false);
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
			imagealphablending($this->img['temp']['stream'], false);
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

	public function add_watermark($path,$v="center",$h="center",$opacity=85,$scale=1,$repeat=false,$xpad=0,$ypad=0){
		if ($this->load_image($path,"wm")){

			if ($scale!="" && $scale!=1){
				$this->resize("scale",null,null,$scale,"wm");
			}

			//Repeat the watermark in a pattern?
			if ($repeat==false){
				if ($h=="left"){ $h_pos=0; }
				if ($h=="center"){ $h_pos=($this->img['main']['width']/2)-($this->img['wm']['width']/2); }
				if ($h=="right"){ $h_pos=$this->img['main']['width']-$this->img['wm']['width']; }

				if ($v=="top"){ $v_pos=0; }
				if ($v=="center"){ $v_pos=($this->img['main']['height']/2)-($this->img['wm']['height']/2); }
				if ($v=="bottom"){ $v_pos=$this->img['main']['height']-$this->img['wm']['height']; }


				if ($this->imagecopymerge_alpha(	
											$this->img['main']['stream'], $this->img['wm']['stream'],
											$h_pos,$v_pos,
											0, 0, 
											$this->img['wm']['width'], $this->img['wm']['height'],
											$opacity
				)){
					return true;
				}
			} else {
				$x=$xpad;$i=0;$y=0;
				while ($x<$this->img['main']['width'] || $y<$this->img['main']['height']){
					$this->imagecopymerge_alpha(	$this->img['main']['stream'], $this->img['wm']['stream'],
												$x,$y,
												0, 0, 
												$this->img['wm']['width'], $this->img['wm']['height'],
												$opacity
					);
					$i+=($this->img['wm']['width']/2);
					if ($i>=($this->img['main']['width'])){
						$i=0;
					}
					if ($x<$this->img['main']['width']){
						$x=$x+$this->img['wm']['width']+$xpad;
					} else {
						$y=$y+$this->img['wm']['height']+$ypad;
						$x=-$i;
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

		//Start a new buffer
		ob_start();

		//Output the image
		if ($output_type=="image/jpeg" || $output_type=="image/jp2"){
			if (!$output=imagejpeg($this->img['main']['stream'], null, $this->quality)){
				return false;
			}
		}
		if ($output_type=="image/png"){
			if (!$output=imagepng($this->img['main']['stream'])){
				return false;
			}
		}
		if ($output_type=="image/gif"){
			if (!$output=imagegif($this->img['main']['stream'])){
				return false;
			}
		}

		//Return captured buffer
		return ob_get_clean();
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

function SWDF_image_resizer_request($size,$img,$authorized=false){
	global $_SWDF;
	$return = array();

	//Get details of requested size
	$size=SWDF_validate_resize_request($img,$size,$authorized);

	//Check whether to proceed with resize request
	if ($size!=false && is_array($size)){

		//Get absolute path to image
		$img_path=$_SWDF['paths']['root'].$img;
		$img_path=str_replace(Array("\\","//"),"/",$img_path);
		$img_path=str_replace(Array("../","./"),"",$img_path);
		$orig_img_path=$img_path;

		//Create cache filename
		$cache_file="";
		if (isset($_SWDF['settings']['images']['cache_resized']) && $_SWDF['settings']['images']['cache_resized']===true && @$size['disable_caching']!==true){
					$cache_file=$_SWDF['paths']['images_cache'].basename($img_path)."[".md5($img_path.$size['id'])."].cache";
			//check if it exists
			//print gmdate('D, d M Y H:i:s', filemtime($orig_img_path)).' GMT';exit;
			if (is_file($cache_file) && filemtime($cache_file)>filemtime($orig_img_path) && filemtime($cache_file)>time()-$_SWDF['settings']['images']['cache_expiry']){
				//Change method to "original" so it will just be passed straight through
				$size=Array(
					"method"=>"original"
				);
				//Change img_path to cache file path
				$img_path=$cache_file;
			} else if (is_file($cache_file)) {
				//Delete the old cache file in case it's contents are out-of-date
				unlink($cache_file);
			}
		}	

		//get properties of actual image
		$properties=getimagesize($img_path);

		//Determine resizing method
		if (isset($size['method']) && $size['method']==="original" && (!isset($size['output']) || $size['output']==null)){
			//Check file is an image
			if ($properties!=false){
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

		} else if(in_array($size['method'], Array("original","fit","fill","stretch","scale"))===true) {

			//Load resizer class
			$resizer=new SWDF_image_resizer();

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
			if (isset($size['watermark']) && is_array($size['watermark'])){
				if ($size['watermark']['opacity']==""){
					$size['watermark']['opacity']=$_SWDF['settings']['images']['default_watermark_opacity'];
				}
				$resizer->add_watermark($size['watermark']['path'],@$size['watermark']['v'],@$size['watermark']['h'],$size['watermark']['opacity'],$size['watermark']['scale'],$size['watermark']['repeat'],50,50);
			}

			//Render resized image
			$output = $resizer->output_image(@$size['output']);

			//Save image to cache
			if (isset($_SWDF['settings']['images']['cache_resized']) && $_SWDF['settings']['images']['cache_resized']===true && @$size['disable_caching']!==true){
					file_put_contents($cache_file,$output);
			}

			//Request that the browser cache this page
			$properties=getimagesize($img_path);
			$return['status']=200;
			$return['headers'][]="Content-type: {$properties['mime']}";
			$return['headers'][]='Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($orig_img_path)).' GMT';
			$return['headers'][]='Expires: '.gmdate('D, d M Y H:i:s', time()+$_SWDF['settings']['images']['cache_expiry']).' GMT';

			//Return the image
			$return['data']=$output;

			//Clean the cache directory
			SWDF_clean_image_cache();
		} else {
			die("Invalid method specified for this size.");
		}
	} else {
		//Not allowed to resize this image/image not found
		$return['status']=404;
	}

	return $return;
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
?>