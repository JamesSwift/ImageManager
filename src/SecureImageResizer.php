<?php
/**
 * James Swift - Image Manager
 * 
 * The following code allows you to automate the resizing of images on your website. 
 * 
 * The SecureImageResizer class implements a system for managing all images
 * on a website, both securing them and resizing/watermarking them as needed 
 * using the ImageResizer class.
 * 
 * For a quick start, see examples.txt.
 * 
 * You are free to use, share and alter/remix this code, provided you distribute 
 * it under the same or a similar license as this work. Please clearly mark any
 * modifications you make (if extensive, a summary at the begining of the file
 * is sufficient). If you redistribute, please include a copy of the LICENSE, 
 * keep the message below intact:
 * 
 * Copyright 2014 James Swift (Creative Commons: Attribution - Share Alike - 3.0)
 * https://github.com/JamesSwift/ImageManager
 * 
 * @author James Swift <me@james-swift.com>
 * @version v0.5.0-dev
 * @package JamesSwift/ImageManager
 * @copyright Copyright 2014 James Swift (Creative Commons: Attribution - Share Alike - 3.0)
 */

namespace JamesSwift\ImageManager;

require "submodules/PHPBootstrap/PHPBootstrap.php";
require "Image.php";

//TODO: Add hook to secure paths with user-defined function
//TODO: Add phpDoc
class SecureImageResizer extends \JamesSwift\PHPBootstrap\PHPBootstrap {
	
	const VERSION = "v0.5.0-dev";
	protected $_config;
	protected $_paths;
	protected $_sizes;
	protected $_allowedOutputFormats = array("original","image/jpeg","image/jp2","image/png","image/gif");
	protected $_allowedMethods = array("original","fit","fill","stretch","scale");
		
		
	//TODO: Add phpDoc
	public function loadDefaultConfig(){
		$this->_config=array(
			"base"=>$this->sanitizeFilePath(dirname(__FILE__), false, true),
			"enableCaching"=>true,
			"cacheTime"=>60*60, //1 Hour
			"defaultWatermarkOpacity"=>50,
			"defaultOutputFormat"=>"original",
			"defaultJpegQuality"=>90
		);
		$this->set("cachePath",$this->sanitizeFilePath(\sys_get_temp_dir()."/JamesSwift/ImageManager/imageCache/", false, true));
		$this->_paths=array();
		$this->_sizes=array(); 	
	}
	
	
	
	//TODO: Add phpDoc
	protected function _sanitizeConfig($config){

		//Process configuration
		$newConfig=array();

		//Set the settings
		foreach ($config['_config'] as $name=>$setting){
			if ($name!=="paths" && $name!=="sizes")
				$newConfig['_config'][$name] = $this->set($name,$setting);
		}

		//Call $this->addSize with all sizes as arguments
		if (isset($config['_sizes'])===true && is_array($config['_sizes']))
			$newConfig['_sizes']=call_user_func_array(array($this, "addSize"), $config['_sizes']);

		//Call $this->addPath with all paths as arguments
		if (isset($config['_paths'])===true && is_array($config['_paths']) && sizeof($config['_paths'])>0)
			$newConfig['_paths']=call_user_func_array(array($this, "addPath"), $config['_paths']);

		return $newConfig;
	}
	
	//TODO: Add phpDoc
	public function set($setting, $value){
		
		//Perform sanitization/standardization
		
		//Base path
		if ($setting==="base"){
			//Check type
			if (is_string($value)!==true || $value==="")
				throw new\Exception("Cannot set '".$setting."'. Must be non-null string.", 500);
			
			//Use correct slash and add trailing slash
			$value=$this->sanitizeFilePath($value, false, true);
			
			//Check directory exists
			if (is_dir($value)===false)	
				throw new\Exception("Cannot set '".$setting."'. Specified location '".$value."' is unreadable or doesn't exist.", 500);
			
			
			
		//Cache Path
		} else if ($setting==="cachePath"){
			//Check type
			if (is_string($value)!==true || $value==="")
				throw new\Exception("Cannot set '".$setting."'. Must be non-null string.", 500);
			
			//Use correct slash and add trailing slash
			$value=$this->sanitizeFilePath($value, false, true);
			
			//Check directory exists (and create it if it doesn't)
			if (is_dir($value)===false)
				if (!mkdir($value, 0777, true) || is_dir($value)===false) 
					throw new\Exception("Cannot set '".$setting."'. Specified location '".$value."' is unreadable or doesn't exist.", 500);
			
				
		//Enable Caching
		} else if ($setting==="enableCaching"){
			//Check type
			if (is_bool($value)===false)
				throw new\Exception("Cannot set '".$setting."'. Must be of type boolean. Type give is ".gettype($value), 500);
			
			
		//Cache Time - maximum age of cache files
		} else if ($setting==="cacheTime"){
			if (ctype_digit($value)===false)
				throw new\Exception("Cannot set '".$setting."'. Must be positive integar. Given value was: '".$value."'.", 500);
			$value=(int)$value;
			
			
			
		//Default JPEG quality
		} else if ($setting==="defaultJpegQuality"){
			$value=(int)$value;
			if ($value<0 || $value>100)
				throw new\Exception("Cannot set '".$setting."'. Must be between 0 and 100", 500);
		
		//Default watermark opacity
		} else if ($setting==="defaultWatermarkOpacity"){
			$value=(int)$value;
			if ($value<0 || $value>100)
				throw new\Exception("Cannot set '".$setting."'. Must be between 0 and 100", 500);
		
		//Default output format
		} else if ($setting==="defaultOutputFormat"){
			//Check type
			if (gettype($value)!=="string")
				throw new\Exception ("Cannot set '".$setting."'. Must be non-null string. Default is: '".$this->_defaultConfig[$setting]."'", 500);
			
			$value=strtolower($value);
			
			//Check value
			if (in_array($value,$this->getAllowedOutputFormats())===false && $value!=="original")
				throw new\Exception ("Cannot set '".$setting."'. Invalid output format. Allowed formats are: ".implode(", ",$this->getAllowedOutputFormats()), 500);
		
		//Default output size
		} else if ($setting==="defaultSize"){
			//check type
			if (gettype($value)!=="string")
				throw new\Exception ("Cannot set '".$setting."'. Must be non-null string. Default is: '".$this->_defaultConfig[$setting]."'", 500);
		
		//Ignore signedHash
		} else if ($setting==="signedHash"){
			//Ignore me
		
		//Catch unknown settings
		} else {
			throw new\Exception("Cannot set '".$setting."'. Specified setting doesn't exist.", 501);
		}
		
		//Store the verfied setting
		$this->_config[$setting]=$value;
		
		return $value;
		
	}
	
	//TODO: Add phpDoc
	public function get($setting){
		if (isset($setting) && isset($this->_config[$setting])){
			return $this->_config[$setting];
		}
		return null;
	}
	
	//TODO: Add phpDoc
	public function getAllowedOutputFormats(){
		return $this->_allowedOutputFormats;
	}
	

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	//TODO: Add phpDoc
	public function addPath(array $path /*, $path, $path, $path ... , $allowOverwrite=false*/){
		
		//Get list of arguments
		$paths = func_get_args();
		
		//Check for last variable being $allowOverwrite
		$allowOverwrite=false;
		if (is_array($paths) && sizeof($paths>1) && is_bool(end($paths)) )
			$allowOverwrite=array_pop($paths);
		
		//Check we're dealing with an non-empty array
		if (!isset($paths) || !is_array($paths) || sizeof($paths)<1)
			throw new\Exception("Cannot add path(s). You must pass one or more non-empty arrays as arguments to this method.", 500);
		
		//Create blank array to hold sanitized data
		$newPaths=array();
		
		//loop through paths and add them
		foreach($paths as $path){

			//Check type
			if (!is_array($path) || sizeof($path)===0)
				throw new\Exception("Cannot add path. Paths must be non-empty arrays", 500);

			//Check required elements are there
			if (isset($path['path'])===false || !is_string($path['path']) || $path['path']==="" )
				throw new\Exception("Cannot add unamed path. The passed array must contain a non-empty 'path' element pointing to a directory, which also serves as it's ID.", 500);

			//Create blank array for sanitized data
			$newPath=&$newPaths[$path['path']];
			
			//Sanitize variables
			$newPath['path']=$this->sanitizeFilePath($path['path'],true,true);
			if (isset($path['disableCaching']))
				$newPath['disableCaching']=(bool)$path['disableCaching'];

			//Check path doesn't already exist
			if ($this->isPath($newPath['path']) && $allowOverwrite!==true)
				throw new\Exception("Cannot add path '".$newPath['path']."'. It already exists and \$allowOverwrite, isn't set to true.", 500);
			
			//If defaultOuputFormat defined, check it is allowed and add it
			if (isset($path['defaultOutputFormat']))
				if (!is_string($path['defaultOutputFormat']) || in_array(strtolower($path['defaultOutputFormat']), $this->getAllowedOutputFormats())===false)
					throw new\Exception("Cannot add path '".$newPath['path']."'. The defaultOutputFormat you specified isn't allowed. It must be one of: ".implode(", ",$this->getAllowedOutputFormats()), 500);
				else
					$newPath['defaultOutputFormat']=strtolower($path['defaultOutputFormat']);
				
			//If defaultJpegQuality defined, check it is allowed and add it
			if (isset($path['defaultJpegQuality']))
				if (!is_int($path['defaultJpegQuality']) || $path['defaultJpegQuality']<0 || $path['defaultJpegQuality']>100)
					throw new\Exception("Cannot add path '".$newPath['path']."'. The defaultJpegQuality must be between 0 and 100. You specified: ".$path['defaultJpegQuality'], 500);
				else
					$newPath['defaultJpegQuality']=(int)$path['defaultJpegQuality'];
			
			//If allowSizes defined, remove any keys, convert to strings, and add it
			if (isset($path['allowSizes']) && is_array($path['allowSizes']))
				foreach($path['allowSizes'] as $size)
					$newPath['allowSizes'][]=(string)$size;
			
			//If allowSizes is "all" set it
			if (!isset($newPath['allowSizes']) && isset($path['allowSizes']) && is_string($path['allowSizes']) && (strtolower($path['allowSizes'])==="all" || strtolower($path['allowSizes'])==="none"))
				$newPath['allowSizes']=strtolower($path['allowSizes']);

			//If denySizes defined, remove any keys, convert to string, and add it
			if (isset($path['denySizes']) && is_array($path['denySizes']))
				foreach($path['denySizes'] as $size)
					$newPath['denySizes'][]=(string)$size;
			
			//If denySizes is "all" set it
			if (!isset($newPath['denySizes']) && isset($path['denySizes']) && is_string($path['denySizes']) && (strtolower($path['denySizes'])==="all" || strtolower($path['denySizes'])==="none"))
				$newPath['denySizes']=strtolower($path['denySizes']);
			
			//TODO: Aliases
			if (isset($path['alias']) && is_array($path['alias']))
				foreach($path['alias'] as $alias)
					$newPath['alias'][]=(string)$alias;
			
			//TODO: Security
			if (isset($path['auth']) && is_array($path['auth']))
				$newPath['auth']=$path['auth'];
			
			//Store the new path
			$this->_paths[$newPath['path']]=$newPath;
		}
		
		//Returned the sanitized data
		return $newPaths;
	}
	
	//TODO: Add phpDoc
	public function getPath($path){
		//Check path is string
		if (!is_string($path))
			return false;
		
		//Add slash if missing
		if (substr($path, -1, 1)!=="/")
			$path.="/";
		
		//Check path exists
		if (isset($this->_paths[$path]))
			return $this->_paths[$path];

		return false;
	}
	
	//TODO: Add phpDoc
	public function getPaths(){
		return $this->_paths;
	}
	
	//TODO: Add phpDoc
	public function isPath($path){
		if (isset($this->_paths[$path])) return true;
		return false;
	}
	
	//TODO: Add phpDoc
	public function removePath($path){
		if (isset($this->_paths[$path])){
			unset($this->_paths[$path]);
			return true;
		} 
		return false;
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	//TODO: Add phpDoc
	public function addSize(array $size /*, $size, $size, $size ... */){
		//Get list of arguments
		$sizes = func_get_args();
		
		//Check for last variable being $allowOverwrite
		$allowOverwrite=false;
		if (is_array($sizes) && sizeof($sizes>1) && is_bool(end($sizes))){
			$allowOverwrite=array_pop($sizes);
		}

		//Check we're dealing with an array
		if (!isset($sizes) || !is_array($sizes) || sizeof($sizes)<1)
			throw new\Exception("Cannot add size(s). You must pass one or more non-empty arrays to this method.", 500);

		//Create array to hold sanitized data
		$newSizes=array();
		
		//loop through sizes and add them
		foreach($sizes as $size){

			//Check type
			if (!is_array($size) || sizeof($size)===0)
				throw new\Exception("Cannot add size. Paths must be non-empty arrays", 500);

			//Check required elements are there
			if (	isset($size['id'])===false	|| $size['id']===""	|| !is_string($size['id']) ||				
				isset($size['method'])===false	|| $size['method']==="" || !is_string($size['method'])
			){
				if (isset($size['id']))
					throw new\Exception("Cannot add size '".(string)$size['id']."'. The passed array must contain non-empty 'id' and 'method' elements.", 500);
				
				throw new\Exception("Cannot add size. The passed array must contain non-empty 'id' and 'method' elements.", 500);						
			}
			
			//Create array to hold sanitized data
			$newSize=&$newSizes[$size['id']];
			
			//Sanitize data
								$newSize['id']			= $size['id'];
								$newSize['method']		= strtolower($size['method']);
			if (isset($size['width']))		$newSize['width']		= (int)$size['width'];
			if (isset($size['height']))		$newSize['height']		= (int)$size['height'];
			if (isset($size['scale']))		$newSize['scale']		= (float)$size['scale'];
			if (isset($size['defaultOutputFormat']))$newSize['defaultOutputFormat']	= strtolower($size['defaultOutputFormat']);
			if (isset($size['jpegQuality']))	$newSize['jpegQuality']		= (int)$size['jpegQuality'];
			if (isset($size['disableCaching']))	$newSize['disableCaching']	= (bool)$size['disableCaching'];
			
			//Check id
			if (preg_match("/[^0-9a-zA-Z_\-]/", $size['id'])!==0)
				throw new\Exception("Cannot add size. '".$size['id']."'. The id element must contain only numbers, letters, underscores or dashes.", 500);	

			//Check method exists
			if (in_array($newSize['method'], $this->_allowedMethods)===false)
				throw new\Exception("Cannot add size. '".$newSize['id']."'. It has an invalid method element. Valid methods are: ".implode(", ", $this->allowedMethods), 500);	
			
			//Checks for methods "fit", "fill", "stretch"
			if ($newSize['method']==="fit" || $newSize['method']==="fill" || $newSize['method']==="stretch")
				if (!isset($newSize['width']) || !isset($newSize['height']) )
					throw new\Exception("Cannot add size. '".$newSize['id']."'. Width and Height must be defined for method '".$newSize['method']."'", 500);	
			
			//Checks for method "scale""
			if ($newSize['method']==="scale")
				if (!isset($newSize['scale']) || $newSize['scale']<=0 )
					throw new\Exception("Cannot add size. '".$newSize['id']."'. Element 'scale' must be defined as a positive number when using method '".$newSize['method']."'", 500);	
				
			//Check output format
			if (isset($newSize['defaultOutputFormat']) && in_array($newSize['defaultOutputFormat'], $this->_allowedOutputFormats)===false)
				throw new\Exception("Cannot add size. '".$newSize['id']."'. If defined, element 'defaultOutputFormat' must be one of: ".implode(", ",$this->_allowedOutputFormats).". Given output was: ".$newSize['defaultOutputFormat'], 500);	

			//Check quality
			if (isset($newSize['jpegQuality']) && ($newSize['jpegQuality']<0 || $newSize['jpegQuality']>100))
				throw new\Exception("Cannot add size. '".$newSize['id']."'. If defined, element 'jpegQuality' must be between 0 and 100. Given was: ".$newSize['jpegQuality'], 500);	

			//Check watermark
			try {
				if (isset($size['watermark'])){
					$newWatermark=$this->_checkWatermark($size['watermark']);
					$newSize['watermark']=$newWatermark;
				}
			} catch (Exception $e){
				//Tweak the message
				throw new\Exception("Cannot add size '".$newSize['id']."'. Misconfigured watermark array: \n".$e->getMessage(), $e->getCode(), $e);
			}
			
			//Discard any other elements and store the new path
			$this->_sizes[$newSize['id']]=$newSize;

		}
		
		return $newSizes;
		
	}
	
	//TODO: Add phpDoc
	protected function _checkWatermark($watermark){
		
		$newWatermark=array();
		
		//Check we're dealing with something loosly resembling a watermark
		if (!isset($watermark) || !is_array($watermark) || sizeof($watermark)===0)
			return null;
			
		//Check for path
		if (!isset($watermark['path']) && is_string($watermark['path']) && $watermark['path']!=="")
			throw new\Exception("No path specified for watermark image. Must be none empty string.", 500);
		
		//Sanitize path
		$newWatermark['path']=$this->sanitizeFilePath($watermark['path']);
		
		//Check it exists
		if (!is_file($this->_config['base'].$watermark['path']))
			throw new\Exception("Cannot find watermark image at path: ".$watermark['path'], 500);
		
		//Sanitize other variables
		if (isset($watermark['scale']))		$newWatermark['scale']	 = (float)$watermark['scale'];
		if (isset($watermark['vAlign']))	$newWatermark['vAlign']	 = strtolower($watermark['vAlign']);
		if (isset($watermark['hAlign']))	$newWatermark['hAlign']	 = strtolower($watermark['hAlign']);
		if (isset($watermark['opacity']))	$newWatermark['opacity'] = (float)$watermark['opacity'];
		if (isset($watermark['repeat']))	$newWatermark['repeat']	 = (bool)$watermark['repeat'];
		if (isset($watermark['vPad']))		$newWatermark['vPad']	 = (int)$watermark['vPad'];
		if (isset($watermark['hPad']))		$newWatermark['hPad']	 = (int)$watermark['hPad'];
		
		//Check vAlign and hAlign are valid (unless repeat=true)
		if (!isset($newWatermark['repeat']) || $newWatermark['repeat']!==true ){
			if ( isset($newWatermark['vAlign']) && ( in_array($newWatermark['vAlign'], array("top","center","bottom"))===false) )
				throw new\Exception("Watermark element 'vAlign' not correctly configured. Should be either: top, center or bottom.", 500);
			if ( isset($newWatermark['hAlign']) && ( in_array($newWatermark['hAlign'], array("left","center","right"))===false) )
				throw new\Exception("Watermark element 'hAlign' not correctly configured. Should be either: left, center or right.", 500);
		} else {
			unset($newWatermark['vAlign'], $newWatermark['hAlign']);
		}
		
		//Check vPad and hPad are in range
		if (isset($newWatermark['repeat']) && $newWatermark['repeat']===true ){
			//Get dimensions of watermark image
			$properties=getimagesize($this->_config['base'].$newWatermark['path']);
			if ($properties===false)
				throw new\Exception("Unable to read dimensions of watermark image. Please check the 'path' element is pointing to a valid image. Given path was: ".$this->_config['base'].$newWatermark['path'], 500);
			
			if (isset($newWatermark['vPad']) && $newWatermark['vPad']<=($properties[1]*-1) )
				throw new\Exception("Watermark element 'vPad' out of bounds. Minimum setting for given image is: ".(($properties[1]*-1)+1), 500);
			
			if (isset($newWatermark['hPad']) && $newWatermark['hPad']<=($properties[0]*-1) )
				throw new\Exception("Watermark element 'hPad' out of bounds. Minimum setting for given image is: ".(($properties[0]*-1)+1), 500);
				
			
		} else {
			unset($newWatermark['vPad'], $newWatermark['hPad']);
		}
		
		//Check opacity
		if (isset($newWatermark['opacity']) && ( $newWatermark['opacity']<0 || $newWatermark['opacity']>100) )
			throw new\Exception("Watermark opacity not correctly configured. Should be between 0 and 100. '".$newWatermark['opacity']."' given.", 500);
		
		return $newWatermark;
		
	}

	//TODO: Add phpDoc
	public function getSize($size){
		if (isset($this->_sizes[$size])){
			return $this->_sizes[$size];
		}
		return false;
	}
	
	//TODO: Add phpDoc
	public function getSizes(){
		return $this->_sizes;
	}
	
	//TODO: Add phpDoc
	public function isSize($size){
		if (isset($this->_sizes[$size])) return true;
		return false;
	}
	
	//TODO: Add phpDoc
	public function removeSize($size){
		if (isset($this->_sizes[$size])){
			unset($this->_sizes[$size]);
			return true;
		}
		return false;
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	//TODO: Add phpDoc
	public function request($img, $size=null, $outputFormat=null){ 
		
		//Validate the request. If invalid, an\Exception will be thrown and passed back up to the caller
		$request = $this->validateRequest($img, $size, $outputFormat);
		
		//If requested original, just return the image
		if ($request['size']['method']==="original" && strtolower(image_type_to_mime_type(exif_imagetype($this->_config['base'].$request['img'])))===$request['finalOutputFormat']) {
			if ($request['useCache']===true) {
				return new Image($this->_config['base'].$request['img'], time()+$this->_config['cacheTime']); 
			} else {
				return new Image($this->_config['base'].$request['img']);
			}
		}
	
		//Check if we should use cached version
		if ($request['useCache']===true){
			//Load cached version if it exists
			$cachedImage = $this->getCachedImage($request['img'], $request['size'], $request['path'], $request['finalOutputFormat']);
			
			//If exists, return it
			if ($cachedImage instanceof CachedImage)
				return $cachedImage;
		}
		
		//If no cached version, render a new version
		
		//Init ImageResizer
		require "ImageResizer.php";
		$resizer = new ImageResizer();

		//Set JPEG Quality
		$resizer->quality=$request['finalJpegQuality'];
		
		//Load the image to be resized
		$resizer->load_image($this->_config['base'].$request['img']);
			
		//Resize the image
		if ($request['size']['method']!=="original") {
			$params=$request['size']+array("method"=>null,"width"=>null,"height"=>null,"scale"=>null);
			$resizer->resize($params['method'],$params['width'],$params['height'],$params['scale']);
		}
		
		//Add watermark
		if (isset($request['size']['watermark']) && is_array($request['size']['watermark'])){
			$params=$request['size']['watermark']+array("path"=>null,"vAlign"=>null,"hAlign"=>null,"opacity"=>$this->_config['defaultWatermarkOpacity'],"scale"=>null,"repeat"=>null,"vPad"=>null,"hPad"=>null);
			$resizer->add_watermark($params['path'],$params['vAlign'],$params['hAlign'],$params['opacity'],$params['scale'],$params['repeat'],$params['vPad'],$params['hPad']);
		}
		
		//Render the image in desired output format
		$resizedImage = $resizer->output_image($request['finalOutputFormat']);
		
		//Cache image if enabled
		if ($request['useCache']===true){
			$cacheName=$this->_generateCacheName($request['img'], $request['size'], $request['path'], $request['finalOutputFormat']);
			if ($cacheName!=false)
				$resizedImage->saveAs($this->_config['cachePath'].$cacheName);
		}
		
		//Create new ResizedImage object, fill it with data and return it
		if ($request['useCache']===true)
			$resizedImage->setExpires(time()+$this->_config['cacheTime']);
		
		//Clean the cache (as in theory new images won't be generated very often)
		$this->cleanCache();

		return $resizedImage;
	}
	
	//TODO: Add phpDoc
	public function validateRequest($img, $requestedSize=null, $outputFormat=null){
		
		//Check "base" defined
		if (!isset($this->_config['base']))
			throw new\Exception("The base path hasn't been configured. Please configure it and try again. For help, consult the documentation.", 500);
		
		//If no size specified, load default size
		if ($requestedSize===null || !is_string($requestedSize))
			if (isset($this->_config['defaultSize']))
				$requestedSize=$this->_config['defaultSize'];
			else 
				throw new\Exception("No size specified, and no default size defined. Unable to process request.", 404);
			
		//Check size exists
		$size = $this->getSize($requestedSize);
		if (!isset($size) || !is_array($size) || sizeof($size)<=0)
			throw new\Exception("The size you requested doesn't exist. Unable to process request.", 404);
		
		//Check image defined
		if (!isset($img) || !is_string($img) || $img==="")
			throw new\Exception("Please specify an image to resize.", 404);
		
		//Sanitize image path
		$img = $this->sanitizeFilePath($img,true);
		
		//Check image exists
		if (!is_file($this->_config['base'].$img))
			throw new\Exception("The image you requested could not be located.", 404);
			
		//Find which path rule applies
		$path = $this->getApplicablePath($img);
		
		//Check path allowed
		if ($path===null)
			throw new\Exception("Access denied. Access to the directory containing the image you requested is restricted.", 403);
		
		//Get allowed sizes for this path
		$allowedSizes = $this->getAllowedSizes($path['path']);

		//Check this size is allowed
		if (in_array($size['id'], $allowedSizes)===false)
			throw new\Exception("The image size you requested is not allowed in the image's directory.", 403);
		
		//Check the outputFormat is allowed
		if ($outputFormat!==null)
			if (!is_string($outputFormat) || in_array(strtolower($outputFormat), $this->getAllowedOutputFormats())===false)
				throw new\Exception("The image format you requested isn't supported. The following formats are supported: ".implode(", ",$this->getAllowedOutputFormats()), 404);
			
		//Check the final format is allowed
		$finalOutputFormat = $this->getFinalOutputFormat($img, $path, $size, $outputFormat);
		
		//Work out final Jpeg Quality for this request
		$finalJpegQuality = $this->getFinalJpegQuality($img, $path, $size);
		
		$useCache=false;
		if (	isset($this->_config['enableCaching']) && $this->_config['enableCaching']===true &&
			!(isset($path['disableCaching']) && $path['disableCaching']===true) && 
			!(isset($size['disableCaching']) && $size['disableCaching']===true)
		)
			$useCache=true;
		
		//Return sanitized data
		return array(
			"img"=>$img,
			"path"=>$path,
			"size"=>$size,
			"finalOutputFormat"=>strtolower($finalOutputFormat),
			"finalJpegQuality"=>$finalJpegQuality,
			"useCache"=>$useCache
			
		);
	}
	
	//TODO: Add phpDoc
	public function getApplicablePath($img){
		
		//Clean up path
		$img = $this->sanitizeFilePath($img,true);
		
		//Create array of path parts
		$path=explode("/",$img);

		//Check array of use
		if (!is_array($path) || sizeof($path)<=1)
			return null;
		
		//remove image from $path
		array_pop($path);
		
		//Cycle through paths untill match is found
		$pathSize=sizeof($path);$i=0;
		while($i<$pathSize){
			//Does this path exist
			if (isset($this->_paths[implode("/", $path)."/"]))
				return $this->getPath(implode("/", $path)."/");
				
			//No, so move up a directory
			array_pop($path);
			$i++;	
		}
		
		//The path couldn't be found
		return null;
	}
	
	//TODO: Add phpDoc
	public function getAllowedSizes($forPath){
		//Check path exists
		if (!is_string($forPath) || !isset($this->_paths[$forPath]))
			return null;
		
		$path = $this->_paths[$forPath];
		$allowedSizes = array();

		//By default load allowSizes (if they exists)
		if (isset($path['allowSizes']) && is_array($path['allowSizes']))
			$allowedSizes=$path['allowSizes'];

		//If allowSizes not defined (or set to "all"), set to be all sizes, else set to contents
		if ( !isset($path['allowSizes']) || (is_array($path['allowSizes']) && sizeof($path['allowSizes'])<=0) || $path['allowSizes']==="all" )
			$allowedSizes = array_keys($this->_sizes);

		//If denySizes defined, subtract from previous array
		if (isset($path['denySizes']) && is_array($path['denySizes']) )
			$allowedSizes = array_diff($allowedSizes, $path['denySizes']);

		//If denySizes set to "all", just return a blank array
		if (isset($path['denySizes']) && is_string($path['denySizes']) && $path['denySizes']==="all")
			$allowedSizes=array();
		
		//If denySizes set to "none" set to be all sizes
		if ( isset($path['denySizes']) && is_string($path['denySizes']) && $path['denySizes']==="none" )
			$allowedSizes = array_keys($this->_sizes);

		//return array
		return $allowedSizes;
	}
	
	//TODO: Add phpDoc
	public function getFinalOutputFormat($img, array $path, array $size, $outputFormat=null) {
		
		//Sanitize the image
		$img = $this->sanitizeFilePath($img, true);
		
		//Check the image exists
		if (!is_file($this->_config['base'].$img))
			throw new\Exception("The image could not be located.", 404);

		$final = $this->_config['defaultOutputFormat'];
		
		if (isset($path['defaultOutputFormat']))
			$final=$path['defaultOutputFormat'];
		
		if (isset($size['defaultOutputFormat']))
			$final=$size['defaultOutputFormat'];
		
		if ($outputFormat!==null)
			$final=strtolower($outputFormat);
		
		//If we're in "original" mode, get the output type of the image
		if ($final==="original"){
			
			//Read the mime type from the image
			$final = strtolower(image_type_to_mime_type(exif_imagetype($this->_config['base'].$img)));

			//Check the image was readable
			if ($final===false)
				throw new\Exception("Couldn't read from the specified image. It may be corrupt.", 500);
		}

		//Check the detected mime type is allowed
		if (in_array($final, $this->getAllowedOutputFormats())!==false && $final!=="original")
			return $final;
		
		//A bad mime type was detected
		throw new\Exception("Unable to determine an allowed output mime-type for this request. Please check the server configuration. The requested mime-type was: ".$final, 500);
	}

	//TODO: Add phpDoc
	public function getFinalJpegQuality($img, array $path, array $size) {
		
		//Sanitize the image
		$img = $this->sanitizeFilePath($img, true);
		
		//Check the image exists
		if (!is_file($this->_config['base'].$img))
			throw new\Exception("The image could not be located.", 404);

		if (isset($this->_config['defaultJpegQuality']))
			$final = $this->_config['defaultJpegQuality'];
		
		if (isset($path['defaultJpegQuality']))
			$final=$path['defaultJpegQuality'];
		
		if (isset($size['jpegQuality']))
			$final=$size['jpegQuality'];
	
		if (isset($final))
			return $final;
		
		//No quality specified anywhere. Something is seriously wrong.
		throw new\Exception("Unable to determine which JPEG quality should be used with this request. Please check your configuration files as this should be impossible.", 500);
	}
	
	
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	//TODO: Add phpDoc
	public function isCached($img, $size, $path, $outputFormat){
		
		//Does the original image exist?
		if (!is_file($this->_config['base'].$img))
			return false;
		
		//Stringify the settings for this image
		$cacheName = $this->_generateCacheName($img, $size, $path, $outputFormat);

		//Check we were able to generate a cache name
		if ($cacheName === false)
			return false;
		
		//Does a cached version of the image exist?
		if (!is_file($this->_config['cachePath'].$cacheName))
			return false;
		
		//Check the cache file isn't obsolete
		if (filemtime($this->_config['base'].$img) > filemtime($this->_config['cachePath'].$cacheName))
			return false;
		
		//Check the cached file hasn't expired
		if ($this->_config['cacheTime']>0 && filemtime($this->_config['cachePath'].$cacheName) < time()-$this->_config['cacheTime'])
			return false;
		
		return $cacheName;
	}
	
	//TODO: Add phpDoc
	public function getCachedImage($img, $size, $path, $outputFormat){
		
		//Check the cached image exists
		$cacheName = $this->isCached($img, $size, $path, $outputFormat);
		
		//Is the cached image existant and up-to-date
		if ($cacheName===false )
			return null;
		
		//Load data into new CachedImage
		$modified = filemtime($this->_config['cachePath'].$cacheName);
		
		return new CachedImage(
			$this->_config['cachePath'].$cacheName, 
			$modified+$this->_config['cacheTime']
		);
	}
	
	//TODO: Add phpDoc
	protected function _generateCacheName($img, $size, $path, $outputFormat){
		
		//If they passed the name of a size, try to get it
		if (is_string($size))
			$size=$this->getSize($size);
		
		//Check we managed to get the size array
		if (!is_array($size))
			return false;
		
		//Check we managed to get the path array
		if (!is_array($path))
			return false;
		
		//Sort the array
		array_multisort($size);
		
		//Hash everything into a filename
		return md5($this->_config['base'].$img)."-".md5(json_encode($size).json_encode($path).json_encode($outputFormat)).".cache";
	}
	
	//TODO: Add phpDoc
	public function cleanCache($emptyCache=false){
		//Check cached files are accessible
		if (!isset($this->_config['cachePath']) || !is_dir($this->_config['cachePath']))
			return false;
		
		//If cacheTime is infinte, there's no point scanning
		if ($this->_config['cacheTime']===0)
			return true;
		
		//Create an array of all files in the cache location
		$files=scandir($this->_config['cachePath']);
		
		//If the array is empty then bug out, there's nothing to do
		if (!is_array($files))
			return true;
		
		//Cycle through each file in the directory
		foreach($files as $file){
			//Check file ends with .cache and isn't a directory
			if (strtolower(substr($file,-6))!==".cache" || is_dir($file))
				continue;
			
			//Check if file has expired, and unlink
			if (filemtime($this->_config['cachePath']."/".$file) < time()-$this->_config['cacheTime'] || $emptyCache===true)
				if (!unlink($this->_config['cachePath']."/".$file))
					return false;
		}
		return true;
		
	}

}