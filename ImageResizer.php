<?php
/**
 * SWDF Image Resier
 * 
 * This script allows you to automate the resizing of images on your website. 
 * 
 * The ImageResizer class uses the GD2 PHP library and allows you to make simple
 * modifications to images in your filesystem. 
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
 * Copyright 2013 James Swift (Creative Commons: Attribution - Share Alike - 3.0)
 * https://github.com/James-Swift/SWDF_image_resizer
 * 
 * @author James Swift <me@james-swift.com>
 * @version v0.3.0
 * @package SWDF_image_resizer
 * @copyright Copyright 2013 James Swift (Creative Commons: Attribution - Share Alike - 3.0)
 */

namespace swdf;


class Exception extends \Exception {
	//Nothing to do here
}

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

	public function add_watermark($path,$v="center",$h="center",$opacity=100,$scale=1,$repeat=false,$xpad=0,$ypad=0){
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
				if ($v=="top"){ $v_pos=0; }
				if ($v=="bottom"){ $v_pos=$this->img['main']['height']-$this->img['wm']['height']; }

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
				$xpad=round( ($xpad/100)*$this->img['main']['width'] );
				$ypad=round( ($ypad/100)*$this->img['main']['height'] );
				
				$x=$xpad/2;
				$y=$ypad/2;
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
					$x+=$this->img['wm']['width']+$xpad;
					
					//Move down to a new line
					if ($x>=$this->img['main']['width']){
						$y+=($this->img['wm']['height']+$ypad);
						$i++;
						//Offset the new line
						$x=0-round( ($i%3)*(($this->img['wm']['width']+$xpad)/3) );
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


class SecureImageResizer {
	const   VERSION = "v0.3.0";
	protected $_config;
	protected $_paths;
	protected $_sizes;
	protected $_allowedOutputFormats = array("image/jpeg","image/jp2","image/png","image/gif");
	protected $_allowedMethods = array("original","fit","fill","stretch","scale");
		
	public function __construct($config=null){
		//Load default config
		$this->loadDefaultConfig();
			
		//Allow passing config straight through constructor
		if ($config!==null){
			if ($this->loadConfig($config)===false){
				throw new Exception("Unable to load passed config.");
			}
		}
	}
	
	public function sanitizePath($path, $removeLeading=false, $addTrailing=false){

		//Check we're dealing with a path
		if (!isset($path) || !is_string($path) || $path==="")
			throw new Exception("Cannot sanitize file-path. It must be a non-empty string.");
		
		//Add trailing slash
		if ($addTrailing===true) $path=$path."/";
		
		//Turn all slashes round the same way
		$path=str_replace(Array("\\","/",'\\',"//"),"/",$path);
		
		//Remove redundant references to ./
		$path=substr(str_replace("/./","/",$path."/"), 0, -1);

		//Check path for directory traversing
		if (strpos("/".$path."/", "/../")!==false)
			throw new Exception("Cannot sanitize file path: '".$path."'. It appears to contain an attempt at directory traversal which may be a security breach.");

		//Remove leading slash
		if ($removeLeading===true && substr($path,0,1)==="/")
			$path=substr($path,1);
		
		return $path;
	
	}
	
	protected function _loadConfigFromFile($file){
		
		//Does the file exist?
		if (!is_file($file))
			return false;

		//Atempt to decode it
		$config = json_decode(file_get_contents($file),true);
		
		//Return false on failure
		return ($config === null) ? false : $config;
	}
	
	public function loadDefaultConfig(){
		$this->_config=array(
			"cachePath"=>\sys_get_temp_dir()."/SWDF/imageCache",
			"enableCaching"=>true,
			"cacheTime"=>2419200, //28 days
			"defaultWatermarkOpacity"=>50,
			"defaultOutputFormat"=>"image/jpeg",
			"defaultJpegQuality"=>90
		);
		$this->_paths=array();
		$this->_sizes=array(); 	
	}
	
	protected function _loadSignedConfig($config, $clearOld=false){
		
		//Check we're dealing with a signed config
		if (!isset($config['signedHash']))
			return false;

		//Recheck hash to see if it is valid
		if ($this->_signConfig($config)!==$config['signedHash'])
			return false;

		//Load the signed (previously checked) paths and sizes
		$this->_sizes=$config['sizes']+$this->_sizes;
		$this->_paths=$config['paths']+$this->_paths;

		//Unset value we don't want in our $this->_config array;
		unset($config['paths'],$config['sizes'],$config['signedHash']);
		
		//Load the signed config settings
		$this->_config=$config+$this->_config;

		return true;
		
	}
	
	public function loadConfig($loadFrom, $clearOld=false, $saveChanges=true){
		
		//If they called this function with no config, just return null
		if ($loadFrom===null) return null;
				
		//If we have been passed an array, load that
		if (is_array($loadFrom)) {
			$config=$loadFrom;
			
		//If not, try to load from JSON file
		} else if (is_string($loadFrom) && is_file($loadFrom)) {
			$config=$this->_loadConfigFromFile($loadFrom);
			if ($config===false)
				throw new Exception("Unable to parse config file: ".$loadFrom);
		}
		
		//Were we able to load $config from somewhere?
		if (!isset($config)) 
			throw new Exception("Unable to load configuration. Please pass a config array or a valid absolute path to a JSON file.");

		//Reset the class if requested
		if ($clearOld===true) 
			$this->loadDefaultConfig();
		
		//Has this configuration been signed previously? (if so load it without error checking to save CPU cycles)
		if (isset($config['signedHash']) && $this->_loadSignedConfig($config) )
			return $config;

		//Process configuration
		$newConfig=array();

		//Set the settings
		foreach ($config as $name=>$setting){
			if ($name!=="paths" && $name!=="sizes")
				$newConfig[$name] = $this->set($name,$setting);
		}

		//Call $this->addSize with all sizes as arguments
		if (isset($config['sizes'])===true && is_array($config['sizes']))
			$newConfig['sizes']=call_user_func_array(array($this, "addSize"), $config['sizes']);

		//Call $this->addPath with all paths as arguments
		if (isset($config['paths'])===true && is_array($config['paths']) && sizeof($config['paths'])>0)
			$newConfig['paths']=call_user_func_array(array($this, "addPath"), $config['paths']);

		//Check if we should save changes back to the file
		if ($saveChanges===true && is_string($loadFrom)){

			//Sign this new config
			$newConfig['signedHash'] = $this->_signConfig($newConfig);

			//write it back to disk
			file_put_contents($loadFrom, json_indent(json_encode($newConfig)));
		}

		return $newConfig;
	}
	
	public function getConfig(){
		//Load basic config
		$config=$this->_config;
		
		//Load paths and sizes (Remove IDs)
		$config['paths']=$this->_paths;
		$config['sizes']=$this->_sizes;
		
		return $config;
	}
	
	public function getSignedConfig(){
		
		//Get config to sign
		$config = $this->getConfig();

		//Sign the config
		$config['signedHash'] = $this->_signConfig($config);
		
		//Sign and return it
		return $config;
	}
	
	protected function _signConfig($config){
		
		//Check the config array actually exists
		if (!(isset($config) && is_array($config)))
			return false;
		
		//Remove any previous signature
		unset($config['signedHash']);
	
		//Stringify it and hash it
		return	hash("crc32",
				var_export($config, true).
				" <- Compatible config file for SWDF/secureImageResizer ".
				self::VERSION.
				" by James Swift"
			);
	}
	

	public function saveConfig($file, $overwrite=false, $format="json", $varName="SWDF_secureImageResizer_config_array"){
		
		if ($overwrite===false && is_file($file)) 
			throw new Exception("Unable to save settings. File '".$file."' already exists, and method is in non-overwrite mode.", 5);
		
		if ($format==="json"){
			if (file_put_contents($file, json_indent(json_encode($this->getSignedConfig())) )!==false )
				return true;
		
		} else if ($format==="php"){
			if (file_put_contents($file, "<"."?php \$".$varName." =\n".var_export($this->getSignedConfig(), true).";\n?".">")!==false )
				return true;
		}
		
		throw new Exception("An unknown error occured and the settings could not be saved to file: ".$file, 6);
	}
	
	public function set($setting, $value){
		
		//Perform sanitization/standardization
		
		//Base path
		if ($setting==="base"){
			//Check type
			if (is_string($value)!==true || $value==="")
				throw new Exception("Cannot set '".$setting."'. Must be non-null string.");
			
			//Use correct slash and add trailing slash
			$value=$this->sanitizePath($value, false, true);
			
			//Check directory exists
			if (is_dir($value)===false)	
				throw new Exception("Cannot set '".$setting."'. Specified location '".$value."' is unreadable or doesn't exist.");
			
			
			
		//Cache Path
		} else if ($setting==="cachePath"){
			//Check type
			if (is_string($value)!==true || $value==="")
				throw new Exception("Cannot set '".$setting."'. Must be non-null string.");
			
			//Use correct slash and add trailing slash
			$value=$this->sanitizePath($value, false, true);
			
			//Check directory exists (and create it if it doesn't)
			if (is_dir($value)===false)
				if (!mkdir($value, 0777, true) || is_dir($value)===false) 
					throw new Exception("Cannot set '".$setting."'. Specified location '".$value."' is unreadable or doesn't exist.");
			
				
		//Enable Caching
		} else if ($setting==="enableCaching"){
			//Check type
			if (is_bool($value)===false)
				throw new Exception("Cannot set '".$setting."'. Must be of type boolean. Type give is ".gettype($value));
			
			
		//Cache Time - maximum age of cache files
		} else if ($setting==="cacheTime"){
			$value=(int)$value;
			
			
		//Default JPEG quality
		} else if ($setting==="defaultJpegQuality"){
			$value=(int)$value;
			if ($value<0 || $value>100)
				throw new Exception("Cannot set '".$setting."'. Must be between 0 and 100");
		
		//Default watermark opacity
		} else if ($setting==="defaultWatermarkOpacity"){
			$value=(int)$value;
			if ($value<0 || $value>100)
				throw new Exception("Cannot set '".$setting."'. Must be between 0 and 100");
		
		//Default output format
		} else if ($setting==="defaultOutputFormat"){
			//Check type
			if (gettype($value)!=="string")
				throw new Exception ("Cannot set '".$setting."'. Must be non-null string. Default is: '".$this->_defaultConfig[$setting])."'";
			
			//Check value
			if (in_array($value,$this->getAllowedOutputFormats())===false)
				throw new Exception ("Cannot set '".$setting."'. Invalid output format. Allowed formats are: ".implode(", ",$this->getAllowedOutputFormats()));
		
		//Default output size
		} else if ($setting==="defaultSize"){
			//check type
			if (gettype($value)!=="string")
				throw new Exception ("Cannot set '".$setting."'. Must be non-null string. Default is: '".$this->_defaultConfig[$setting])."'";
			
		//Ignore signedHash
		} else if ($setting==="signedHash"){
			//Ignore me
		
		//Catch unknown settings
		} else {
			throw new Exception("Cannot set '".$setting."'. Specified setting doesn't exist.");
		}
		
		//Store the verfied setting
		$this->_config[$setting]=$value;
		
		return $value;
		
	}
	
	public function get($setting){
		if (isset($setting) && isset($this->_config[$setting])){
			return $this->_config[$setting];
		}
		return null;
	}
	
	public function getAllowedOutputFormats(){
		return $this->_allowedOutputFormats;
	}
	

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	public function addPath(array $path /*, $path, $path, $path ... */){
		
		//Get list of arguments
		$paths = func_get_args();
		
		//Check for last variable being $allowOverwrite
		$allowOverwrite=false;
		if (is_array($paths) && sizeof($paths>1) && is_bool(end($paths)) )
			$allowOverwrite=array_pop($paths);
		
		//Check we're dealing with an non-empty array
		if (!isset($paths) || !is_array($paths) || sizeof($paths)<1)
			throw new Exception("Cannot add path(s). You must pass one or more non-empty arrays as arguments to this method.");
		
		//Create blank array to hold sanitized data
		$newPaths=array();
		
		//loop through paths and add them
		foreach($paths as $path){

			//Check type
			if (!is_array($path) || sizeof($path)===0)
				throw new Exception("Cannot add path. Paths must be non-empty arrays");

			//Check required elements are there
			if (isset($path['path'])===false || !is_string($path['path']) || $path['path']==="" )
				throw new Exception("Cannot add path. The passed array must contain a non-empty 'path' element.");

			//Create blank array for sanitized data
			$newPath=&$newPaths[$path['path']];
			
			//Sanitize variables
			$newPath['path']=$this->sanitizePath($path['path'],true,true);
			if (isset($path['disableCaching']))
				$newPath['disableCaching']=(bool)$path['disableCaching'];

			//Check path doesn't already exist
			if ($this->isPath($newPath['path']) && $allowOverwrite!==true)
				throw new Exception("Cannot add path '".$newPath['path']."'. It already exists.");
			
			//If allowSizes defined, remove any keys, convert to strings, and add it
			if (isset($path['allowSizes']) && is_array($path['allowSizes']))
				foreach($path['allowSizes'] as $size)
					$newPath['allowSizes'][]=(string)$size;

			//If denySizes defined, remove any keys, convert to string, and add it
			if (isset($path['denySizes']) && is_array($path['denySizes']))
				foreach($path['denySizes'] as $size)
					$newPath['denySizes'][]=(string)$size;

			//Store the new path
			$this->_paths[$newPath['path']]=$newPath;
		}
		
		//Returned the sanitized data
		return $newPaths;
	}
	
	public function getPath($path){
		//Check path is string
		if (!is_string($path))
			return false;
		
		//Add slash if missing
		if (substr($path, -1, 1)!=="/")
			$path.="/";
		
		//Check path exists
		if (isset($this->_paths[(string)$path]))
			return $this->_paths[$path];

		return false;
	}
	
	public function getPaths(){
		return $this->_paths;
	}
	
	public function isPath($path){
		if (isset($this->_paths[$path])) return true;
		return false;
	}
	
	public function removePath($path){
		if (isset($this->_paths[$path])){
			unset($this->_paths[$path]);
			return true;
		} 
		return false;
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		
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
			throw new Exception("Cannot add size(s). You must pass one or more non-empty arrays to this method.");

		//Create array to hold sanitized data
		$newSizes=array();
		
		//loop through sizes and add them
		foreach($sizes as $size){

			//Check type
			if (!is_array($size) || sizeof($size)===0)
				throw new Exception("Cannot add size. Paths must be non-empty arrays");

			//Check required elements are there
			if (	isset($size['id'])===false	|| $size['id']===""	|| !is_string($size['id']) ||				
				isset($size['method'])===false	|| $size['method']==="" || !is_string($size['method'])
			){
				if (isset($size['id']))
					throw new Exception("Cannot add size '".(string)$size['id']."'. The passed array must contain non-empty 'id' and 'method' elements.");
				
				throw new Exception("Cannot add size. The passed array must contain non-empty 'id' and 'method' elements.");						
			}
			
			//Create array to hold sanitized data
			$newSize=&$newSizes[$size['id']];
			
			//Sanitize data
								$newSize['id']			= $size['id'];
								$newSize['method']		= strtolower($size['method']);
			if (isset($size['width']))		$newSize['width']		= (int)$size['width'];
			if (isset($size['height']))		$newSize['height']		= (int)$size['height'];
			if (isset($size['scale']))		$newSize['scale']		= (float)$size['scale'];
			if (isset($size['outputFormat']))	$newSize['outputFormat']	= strtolower($size['outputFormat']);
			if (isset($size['jpegOutputQuality']))	$newSize['jpegOutputQuality']	= (int)$size['jpegOutputQuality'];
			if (isset($size['disableCaching']))	$newSize['disableCaching']	= (bool)$size['disableCaching'];
			
			//Check id
			if (preg_match("/[^0-9a-zA-Z_\-]/", $size['id'])!==0)
				throw new Exception("Cannot add size. '".$size['id']."'. The id element must contain only numbers, letters, underscores or dashes. ");	

			//Check method exists
			if (in_array($newSize['method'], $this->_allowedMethods)===false)
				throw new Exception("Cannot add size. '".$newSize['id']."'. It has an invalid method element. Valid methods are: ".implode(", ", $this->allowedMethods));	
			
			//Checks for methods "fit", "fill", "stretch"
			if ($newSize['method']==="fit" || $newSize['method']==="fill" || $newSize['method']==="stretch")
				if (!isset($newSize['width']) || !isset($newSize['height']) )
					throw new Exception("Cannot add size. '".$newSize['id']."'. Width and Height must be defined for method '".$newSize['method']."'");	
			
			//Checks for method "scale""
			if ($newSize['method']==="scale")
				if (!isset($newSize['scale']) || $newSize['scale']<=0 )
					throw new Exception("Cannot add size. '".$newSize['id']."'. Element 'scale' must be defined as a positive number when using method '".$newSize['method']."'");	
				
			//Check output format
			if (isset($newSize['outputFormat']) && in_array($newSize['outputFormat'], $this->_allowedOutputFormats)===false)
				throw new Exception("Cannot add size. '".$newSize['id']."'. If defined, element 'outputFormat' must be one of: ".implode(", ",$this->_allowedOutputFormats).". Given output was: ".$newSize['outputFormat']);	

			//Check quality
			if (isset($newSize['jpegOutputQuality']) && ($newSize['jpegOutputQuality']<0 || $newSize['jpegOutputQuality']>100))
				throw new Exception("Cannot add size. '".$newSize['id']."'. If defined, element 'jpegOutputQuality' must be between 0 and 100. Given was: ".$newSize['jpegOutputQuality']);	

			//Check watermark
			try {
				if (isset($size['watermark'])){
					$newWatermark=$this->_checkWatermark($size['watermark']);
					$newSize['watermark']=$newWatermark;
				}
			} catch (Exception $e){
				//Tweak the message
				throw new Exception("Cannot add size '".$newSize['id']."'. Misconfigured watermark array: \n".$e->getMessage(), $e->getCode(), $e);
			}
			
			//Discard any other elements and store the new path
			$this->_sizes[$newSize['id']]=$newSize;

		}
		
		return $newSizes;
		
	}
	
	protected function _checkWatermark($watermark){
		
		$newWatermark=array();
		
		//Check we're dealing with something loosly resembling a watermark
		if (!isset($watermark) || !is_array($watermark) || sizeof($watermark)===0)
			return null;
			
		//Check for path
		if (!isset($watermark['path']) && is_string($watermark['path']) && $watermark['path']!=="")
			throw new Exception("No path specified for watermark image. Must be none empty string.");
		
		//Sanitize path
		$newWatermark['path']=$this->sanitizePath($watermark['path']);
		
		//Check it exists
		if (!is_file($watermark['path']))
			throw new Exception("Cannot find watermark image at path: ".$watermark['path']);
		
		//Sanitize other variables
		if (isset($watermark['scale']))		$newWatermark['scale']	 = (float)$watermark['scale'];
		if (isset($watermark['v']))		$newWatermark['v']	 = strtolower($watermark['v']);
		if (isset($watermark['v']))		$newWatermark['v']	 = strtolower($watermark['v']);
		if (isset($watermark['opacity']))	$newWatermark['opacity'] = (float)$watermark['opacity'];
		if (isset($watermark['repeat']))	$newWatermark['repeat']	 = (bool)$watermark['repeat'];
		
		//Check v and h are valid (unless repeat=true
		if (!isset($newWatermark['repeat']) || $newWatermark['repeat']!==true ){
			if ( isset($newWatermark['v']) && ( in_array($newWatermark['v'], array("top","center","bottom"))===false) )
				throw new Exception("Watermark element 'v' not correctly configured. Should be either: top, center or bottom.");
			if ( isset($newWatermark['h']) && ( in_array($newWatermark['h'], array("left","center","right"))===false) )
				throw new Exception("Watermark element 'h' not correctly configured. Should be either: left, center or right.");
		}
		
		//Check opacity
		if (isset($newWatermark['opacity']) && ( $newWatermark['opacity']<0 || $newWatermark['opacity']>100) )
			throw new Exception("Watermark opacity not correctly configured. Should be between 0 and 100. '".$newWatermark['opacity']."' given.");
		
		return $newWatermark;
	}


	public function getSize($size){
		if (isset($this->_sizes[$size])){
			return $this->_sizes[$size];
		}
		return false;
	}
	
	public function getSizes(){
		return $this->_sizes;
	}
	
	public function isSize($size){
		if (isset($this->_sizes[$size])) return true;
		return false;
	}
	
	public function removeSize($size){
		if (isset($this->_sizes[$size])){
			unset($this->_sizes[$size]);
			return true;
		}
		return false;
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	public function resize($img, $size=null){ 
		$this->validateResizeRequest($img, $size);
				
		return new resizedImage(); 
	}
	
	public function validateResizeRequest($img, $requestedSize=null){
		
		//Check "base" defined
		if (!isset($this->_config['base']))
			throw new Exception("The base path hasn't been configured. Please configure it and try again. For help, consult the documentation.", 500);
		
		//If no size specified, load default size
		if ($requestedSize===null || !is_string($requestedSize))
			if (isset($this->_config['defaultSize']))
				$requestedSize=$this->_config['defaultSize'];
			else 
				throw new Exception("No size specified, and no default size defined. Unable to validate request.", 404);
			
		
		//Check size exists
		$size = $this->getSize($requestedSize);
		if (!isset($size) || !is_array($size) || sizeof($size)<=0)
			throw new Exception("The size you requested ('".$size."') doesn't exist. Unable to validate request.", 404);
		
		//Check image defined
		if (!isset($img) || !is_string($img) || $img==="")
			throw new Exception("Please specify an image to resize.", 404);
		
		//Sanitize image path
		$img = $this->sanitizePath($img,true);
		
		//Check image exists
		if (!is_file($this->_config['base'].$img))
			throw new Exception("The image you requested could not be located.", 404);
			
		//Find which path rule applies
		$path = $this->getApplicablePath($img);
		
		//Check path allowed
		if ($path===null)
			throw new Exception("Access denied. Access to the image you requested is restricted.", 403);
		
		//Get allowed sizes for this path
		$allowedSizes = $this->getAllowedSizes($path['path']);

		//Check this size is allowed
		
		return true;
	}
	
	public function getApplicablePath($img){
		
		//Clean up path
		$img = $this->sanitizePath($img,true);
		
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
			if (isset($this->_paths[implode("/", $path)])."/")
				return $this->getPath(implode("/", $path)."/");
				
			//No, so move up a directory
			array_pop($path);
			$i++;	
		}
		
		//The path couldn't be found
		return null;
	}
	
	public function getAllowedSizes($forPath){
		//Check path exists
		if (!is_string($forPath) || !isset($this->_paths[$forPath]))
			return null;
		
		$path = $this->_paths[$forPath];
		$allowedSizes = array();
		
		//By default load allowSizes
		if (isset($path['allowSizes']) && is_array($path['allowSizes']))
			$allowedSizes=$path['allowSizes'];
		
		//If allowSizes not defined, set to be all sizes, else set to contents
		if ( !isset($path['allowSizes']) || (is_array($path['allowSizes']) && sizeof($path['allowSizes'])<=0) || strtolower($path['allowSizes'])==="all" )
			$allowedSizes = array_keys($this->_sizes);
		
		//If denySizes defined, subtract from previous array
		if (isset($path['denySizes']) && is_array($path['denySizes']) )
			array_diff($allowedSizes, $path['denySizes']);
		
		//return array
		return $allowedSizes;
	}
}

class resizedImage {
	public function outputHttp() {}
	public function save() {}
}









/**
 * Indents a flat JSON string to make it more human-readable.
 * 
 * Slightly modified by James Swift 2013
 * 
 * @author Dave Perrett
 * @copyright Copyright Dave Perret 2008 - see http://www.daveperrett.com/articles/2008/03/11/format-json-with-php/
 * @param string $json The original JSON string to process.
 * @return string Indented version of the original JSON string.
 */
function json_indent($json, $indentStr = "\t", $newLine = "\n", $unescapeSlashes=true) {

	$json = str_replace(array("\n", "\r"), "", $json);
	if ($unescapeSlashes===true) $json = str_replace('\/', "/", $json);
	$result = '';
	$pos = 0;
	$strLen = strlen($json);
	$prevChar = '';
	$outOfQuotes = true;

	for ($i = 0; $i <= $strLen; $i++) {

		$char = substr($json, $i, 1);

		if ($char == '"' && $prevChar != '\\') {
			$outOfQuotes = !$outOfQuotes;
		} else if (($char == '}' || $char == ']') && $outOfQuotes) {
			$result .= $newLine;
			$pos--;
			for ($j = 0; $j < $pos; $j++) {
				$result .= $indentStr;
			}
		}

		$result .= $char;

		if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
			$result .= $newLine;
			if ($char == '{' || $char == '[') {
				$pos++;
			}

			for ($j = 0; $j < $pos; $j++) {
				$result .= $indentStr;
			}
		}

		$prevChar = $char;
	}

	return $result;
}
?>