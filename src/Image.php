<?php
/**
 * James Swift - Image Manager
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
		if (is_file($img)){
		
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