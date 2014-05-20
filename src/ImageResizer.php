<?php
/**
 * James Swift - Image Manager
 * 
 * The following code allows you to automate the resizing of images on your website. 
 * 
 * The ImageResizer class uses the GD2 PHP library and allows you to make simple
 * modifications to images in your filesystem. 
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

require "Image.php";

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
		$image=ob_get_clean();
		return new ResizedImage($image, null, time());
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
