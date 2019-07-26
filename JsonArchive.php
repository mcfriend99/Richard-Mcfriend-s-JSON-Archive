<?php 


/** 
 * 
 * 
 * 
 * ******************************************************
 * 
 * 
 * Richard Mcfriend's JSON Archive
 * 
 * ================================
 * 
 * An archive based on the JavaScript Object Notation
 * 
 * #JSONDoesMore
 * 
 * Version: 1.0.0
 * 
 * *******************************************************
 * 
 * 
 * Copyright, 2015
 * 
 * Developed by:
 * Richard mcfriend
 * richardmcfriend@live.com
 * 
 * for
 * 
 * "The World"
 * 
 * *******************************************************
 *  
 * /**  
 *  * TODO
 *  * ====
 *  * 
 *  * # Add to archive
 *  * # Change archive key - requires old and new key
 *  * # Add expiry date to archive after which it will no longer open even with a correct key.
 *  * # Extract a single file from an archive.
 *  * # Extend compression level to 16.
 *  */
 */


// GLOBAL DEFINITIONS
define("JSON_ARCHIVE_NO_FILENAME_ERROR", "Cannot add a non-existing file. Supply a filename as a second argument to have your input treated as data and save to the supplied filename.");
define("JSON_ARCHIVE_FILEREAD_ERROR", "The file you intent to add or decompress cannot be accessed. Something may be wrong with the file, or you may not have access to read from its location.");
define("JSON_ARCHIVE_MALFORMED", "The archive is damaged or broken.");

// Doubt if you will ever come across this error in your lifetime,
// but you might just be the guy its gonna happen to.
define("JSON_ARCHIVE_INVALID_SETNAME", "setName cannot use the name supplied. Reverting to default settings.");

// This might not mean much to you (PHP fans), but it means much to 
// the guy reading from javascript or perl.
define("JSON_ARCHIVE_INVALID_COMPRESSION_LEVEL", "You cannot set compression level that is not an integer, below 0 or above 9.");

// We won't be breaking system security and settings!
define("JSON_ARCHIVE_FILESAVE_FAILED", "Could not write the archive to file. Please check that the directory is write enabled.");

// DOn't break this security check when modifying it. It helps
// to keep the objective of SON in focus.
define("JSON_ARCHIVE_CHECKSUM_FAILED", "The checksum of the archive failed. The archive seems to be broken.");

// This does not explicitly mean no file was extracted.
// it simply means some files were not.
define("JSON_ARCHIVE_HALTED_DECOMPRESSION", "The decompression was halted either because you stopped having permission to write in outputs parent directory or SON counld no longer access file writer or the archive is damaged.");

// SON is a security enable archive type.
define("JSON_ARCHIVE_NOKEY_ERROR", "This archive is secured. Please provide a key.");
define("JSON_ARCHIVE_WRONGKEY_ERROR", "The key supplied to this archive is invalid.");

// Don't hail!
// You can archive an entire directory too.
// Thank you! No pictures.
define("JSON_ARCHIVE_INVALID_DIRECTORY", "No directory supplied or the supplied directory does not exist.");

define("JSON_ARCHIVE_WRONGINDEX_ERROR", "An integer index representing a file in an archive must be supplied.");
define("JSON_ARCHIVE_NONEWNAME_ERROR", "A new name must be provided for rename to work.");
define("JSON_ARCHIVE_EMPTY_ERROR", "The archive contains no file.");
define("JSON_ARCHIVE_OUTOFRANGE_EXCEPTION", "The file index supplied is greater that the highest possible file index of the archive.");


class JsonArchive {
	
	
	// JsonArchive Version
	private $version = "1.0.0";
	
	// the file(s) being processed
	private $files = array();
	
	/** 
	 * key to the archive if any is supplied
	 * Do not mistakenly change it to null. null & "" are different things here.
	 */
	private $key = ""; 
	
	// compression level (the level of compression)
	private $index = 9;
	
	// security level
	private $securelevel = 0;
	
	// global error reciever
	private $error = array();
	
	// holds the uncompressed size value...
	private $uncompressed = 0;
	
	// archive name
	private $arcname = "SON";
	
	// The compressor's name:
	// Making reference here to your app gives the credit of compression to you.
	// Might be useful in creating head info's like:
	// >>> Compressed by PHP SON Engine. Copyright, 2014
	// (PLS mind the python).
	private $compressedby = "SON";
	
	// A place you can attach any other info you like to the archive:
	// Looking into the limitations compressed by can offer...
	// I adviced you don't use it in place of compressed by:
	// Can contain anything you want available to info but not files (or so)...
	private $comments = "";
	
	// array of names of files in the archive
	private $filenames = array();
	
	// array of sizes of files in the archive
	private $filesizes = array();
	
	// array of types of files in the archive
	private $filetypes = array();
	
	// array of last modified dates of files in the archive
	private $filelastmods = array();
	
	// array of contents of files in the archive
	// Lookign at: Security
	protected $filecontents = array();
	
	// number of files in the archive
	private $filecount = 0;
	
	// boolean check if we are decompressing or not
	private $decompressing = false;
	
	// boolean check if we are decompressing or not
	// Lookign at: Security
	protected $fileheader = null;
	
	// A file name we will use to create temporary files if necessary.
	protected $dollyfile = "0_0_0___0_0_______0";
	
	/** 
	 * When purified results are requeste, only JSON results are returned.
	 * This is the global variable that determines that.
	 * Looking at: Compatiblity, Flexibility, Browser & Platform Support.
	 * 
	 * Note: Unless write is implicitly enabled by a call to a function,
	 * a purified result will never return any file
	 * 
	 * Default: false
	 */
	private $purified = false;
	
	// If a created time already exists in an archive,
	// we don't want to overwrite it while renaming, deleting etc.
	private $readycreatedtime = 0;
	
	// Set to true when deleting from an archive
	// Look at: Simplicity
	// Deferentiating: Rename & Delete
	private $deleting = false;
	
	
	
	/** 
	 * MAIN METHOD 
	 * ============
	 * For adding a file to the archive.
	 * Accepts file and text data. 
	 * A name is not necessarily provided for a file as it adds it 
	 * using the file's name unless a name is provided.
	 * But for text data, a name must be provided.
	 */
	function add($data, $name = null){
		
		$input = null; $size = 0; $type = null; $lastmod = 0;
		
		// make name safe for all platforms...
		$name = str_replace('\\', '/', $name);
		
		// Check to see if a file is supplied
		if(file_exists($data) || is_dir($data)){
			
			// If a name is not supplied,
			if(empty($name)){
				// set name to the file name.
				$name = $data;
			}
			
			// Get input from file if not a directory.
			if(!is_dir($data)){
				$input = base64_encode((bzcompress(file_get_contents($data))));
			} else {
				$input = "";
			}
			$size = @filesize($data);
			$type = @mime_content_type($data);
			$lastmod = @filemtime($data);
			
			// Cross-check our file input for security.
			// we dont want to add files that can break up the archive.
			// Note to:: Reduce the possibility of "Damaged Archive" by 80%.
			if(!$input){
				
				// The file may just be empty
				if($input != ""){
					// Something is wrong with the file...
					$this->error[] .= JSON_ARCHIVE_FILEREAD_ERROR;
					return false;
				}
			}
		} else {
			
			// Assume text data is supplied.
			if(!empty($name)){
				
				// Yes a text data have been supplied. Thats our input
				$input = $data;
				
				$size = strlen($input);
				
				/** 
				 * Getting mime type of plain text will alway be string,
				 * an empty file will alwaay be x-empty irrespective of what
				 * they ought to be.
				 * 
				 * So we hack through with quick file create, get mime type and remove
				 * 
				 * Looking at: Accuracy
				 */
				
				// We don't want to guess our dolly cannot be a filename
				// And we don't want to risk aomone having a file 
				// named dollyfile12345
				$rando = rand(0, 999999999999);
				
				// create a file named dollyfilewhatever containing the input
				file_put_contents($this->dollyfile.$rando, $data);
				
				// get mime type of that file...
				$type = mime_content_type($this->dollyfile.$rando);
				
				// remove the file. We only need the mime type.
				// Looking at: Accuracy
				unlink($this->dollyfile.$rando);
				
				$lastmod = @time();
				
			} else {
				
				// Not a file and no name is supplied
				$this->error[] .= JSON_ARCHIVE_NO_FILENAME_ERROR;
				return false;
				
			}
		}
		
		// Add to the files list.
		if($input != ""){
			array_push($this->files, array("name" => $name, "value" => $input, "size" => $size, "type" => $type, "lastmodified" => $lastmod));
		}
		
		// increment the uncompressed size here...
		$this->uncompressed += $size;
		
		// increment number of files 
		$this->filecount++;
		
		return true;
		
	}
	
	/** 
	 * Sets the password or key to the archive.
	 * If it is called, anyone or app trying to open 
	 * the archive will need to provide the same key to do so
	 * 
	 * Looking at: Security
	 */
	function setKey($key){
		$this->key = $key;
	}
	
	/** 
	 * This should be called to control if only JSON output should be used or not.
	 * @accepts: boolean.
	 * @anything else: sets it to false.
	 */
	function purify($bool = true){
		
		// Incase the user forgets or skips the last comment,
		if(!is_bool($bool)){
			$bool = false;
		}
		
		// .
		$this->purified = $bool;
	}
	
	
	function setName($name){
		
		// Only printable names...
		if(is_string($name)){
			$name = str_replace("/", "", $name);
			$this->arcname = $name;
		} else {
			$error[] .= JSON_ARCHIVE_INVALID_SETNAME;
		}
	}
	
	// This name will be printed as the author of the archive...
	function setAuthor($name){
		// Only printable characters...
		if(is_string($name)){
			$this->compressedby = $name;
		}
	}
	
	
	function setCompressionLevel($level){
		
		// Only integers allowed...
		$level = preg_replace("/\D+/", "", $level);
		
		if(empty($level)){
			// reset level
			$level = 9;
		}
		
		// reset over & under flow.
		if($level < 1) $level = 1;
		elseif($level > 9) $level = 9;
		
		$this->index = $level;
	}
	
	// Adds comment to the file...
	function addComment($comments){
		$this->comments = $comments;
	}
	
	// Adds comment to the file...
	function version(){
		return $this->version;
	}
	
	/** 
	 * MAIN METHOD 
	 * ============
	 * For getting error messages
	 */
	function error(){
		// Only if errors exist
		if(sizeof($this->error) > 0){
			return $this->error[0];
		}
		
		return false;	
	}
	
	/** 
	 * 
	 * MAIN METHOD
	 * ===========
	 * Makes the archive.
	 * If a file name is not specified, it uses the default > SON
	 * 
	 */
	function compress($outfile = null, $write = true){
	
		// if we are decompressing,  reset file count 
		// Looking at: accuracy and flexibility
		if($this->decompressing) $this->filecount = 0;
		
		// array of encoded files...
		$encoded = array();
		
		// we won't be working on empty files after all...
		if(empty($this->files))return false;
		
		foreach($this->files as $file){
			$json_encoded = @json_encode($file);
			
			// Making sure we don't have invalid files
			// Looking at: Security
			// Note to:: Reduce the possibility of "Damaged Archive" by a further 10%.
			if($json_encoded){
				array_push($encoded, array($json_encoded));
			}
		}
		
		$attributes = array(
			"name" => $this->arcname,                  		// name of the archive
			"uncompressedsize" => $this->uncompressed, 		// size before compression
			"created" => ($this->readycreatedtime!=0?$this->readycreatedtime:time()), 					   		// creation time
			"compressedby" => $this->compressedby, 	   		// who is compressing this
			"comments" => $this->comments, 			   		// add anything else
			"version" => $this->version, 			   		// SON Version
			"compressionlevel" => $this->index, 	   		// compression level
			"totalfiles" => sizeof($this->files), 	   		// total number of files
			"secure" => ($this->key==null?0:1), 	   		// secured or not
			"key"=> crc32($this->key)						// security key
		);
		
		// Only proceed in the abscence of no previous error.
		// #Saving ourself some processing power
		if(empty($this->error)){
			
			// the content of our archive
			$content = @json_encode($encoded);
			
			// attributes of our archive passed in the header...
			$header = @json_encode($attributes);
			
			// commit all to 3 groups of header, content and crc32
			// NB: crc32 is important decompressing. It hepls to check if we
			// have no break or error in our decompression.
			$to_be_compressed = @json_encode(array("header" => $header, "content" => $content, "sum" => crc32($content)));
			
			// KEEP IT SIMPLE
			// just encode
			$gencoded = gzencode(crc32($this->key).$to_be_compressed, $this->index);
			
			// and
			// compress
			$gcompressed = gzcompress($gencoded, $this->index);
			
			// If we are authorized to output a file
			if($write == true){
			
				// if we are provided with an output file name,
				if(!empty($outfile)){
					
					// make sure the extension is properly appended
					if(!substr($outfile, (strlen($outfile) - 5), (strlen($outfile) - 1)) == ".son")$outfile .= ".son";
					$res = file_put_contents($outfile, $gcompressed);
				} else {
					// Output our default file name
					$res = file_put_contents($this->arcname.".son", $gcompressed);
				}
				
				if(!$res){
					
					// Something must be wrong!!!
					$this->error[] .= JSON_ARCHIVE_FILESAVE_FAILED;
					return false;
				}
			}
			
			$this->fileheader = @json_decode($header);
			
			if($this->purified != true){
				return $gcompressed;
			} else {
				return $to_be_compressed;
			}
		} else {
			return false;
		}
	}
	
	/** 
	 * PROTECTED SUB-METHOD
	 * ====================
	 * Called to decrypt files 
	 * performs main decompression!...
	 * 
	 * CAUTION: Edit with caution!!!
	 */
	protected function predecompress($get){
		
		// decompress
		$gdecompress = gzuncompress($get);
		
		/** 
		 * # Then decode.
		 * While doing this, we must make sure to handle our crc32 correctly
		 */
		 // We don't want to replace "0" in our content.
		if(crc32($this->key) != 0){
		
			$gdecode = str_replace(crc32($this->key), "", gzdecode($gdecompress));
			
		} else {
			
			// First character is crc32.
			$gdecode = substr(gzdecode($gdecompress), 1, (strlen(gzdecode($gdecompress)) - 1));
		}
		
		// jdecode the content of decompression
		$have_been_decompressed = @json_decode($gdecode);
		
		if(!$have_been_decompressed){
			$this->error[] .= JSON_ARCHIVE_NOKEY_ERROR;
			return false;
		}
		
		// get headers
		$header = $have_been_decompressed->header;
		
		// get content
		$content = $have_been_decompressed->content;
		
		// get sum
		$sum = $have_been_decompressed->sum;
		
		return array($header, $content, $sum);
		
	}
	
	/** 
	 * MAIN METHOD 
	 * ============
	 * This is the decompression method. It decompresses to file 
	 * and headers...
	 * 
	 * The third parameter should be set to false to stop it 
	 * from writing to files...
	 */
	function decompress($file, $folder = null, $write = true){
		
		if(!file_exists($file)){
			
			$this->error[] .= JSON_ARCHIVE_FILEREAD_ERROR;
			return false;
		}
		
		// let apps know we are decompressing
		$this->decompressing = true;
		
		// get the content of the file...
		$get = @file_get_contents($file);
		
		// make sure file is readable.
		if(!$get){
			
			$this->error[] .= JSON_ARCHIVE_FILEREAD_ERROR;
			return false;
		}
		
		// .......
		$predec = $this->predecompress($get);
		
		// Only continue if no error.
		if(!empty($this->error)){
			return false;
		} 
		
		$header = $predec[0];
		$content = $predec[1];
		$sum = $predec[2];
		
		// check sum for validity
		if(crc32($content) == $sum){
			
			// get attributes
			$attributes = @json_decode($header);
			
			$this->fileheader = $attributes;
			
			if(!empty($attributes->key)){
				
				if(empty($this->key)){
					
					$this->error[] .= JSON_ARCHIVE_NOKEY_ERROR;
					return false;
				} else {
					
					// proper hash our key
					$key = crc32($this->key);
					
					// make sure our key is correct
					if($this->key != $attributes->key){
						
						$this->error[] .= JSON_ARCHIVE_WRONGKEY_ERROR;
						return false;
					}
				}
			}
			
			// get files
			$files = @json_decode($content);
			
			for($i = 0; $i < sizeof($files); $i++){
				
				$thisfile = $files[$i][0];
			
				$thiscontent = @json_decode($thisfile);
				
				if(!$thiscontent){
				
					$this->error[] .= JSON_ARCHIVE_EMPTY_ERROR;
					return;
				}
				
				$name = $thiscontent->name;
				$contains = $thiscontent->value;
				$size = $thiscontent->size;
				$type = $thiscontent->type;
				$lastmod = $thiscontent->lastmodified;
				
				// set them private for easier access when apps demand
				array_push($this->filenames, $name);
				array_push($this->filesizes, $size);
				array_push($this->filetypes, $type);
				array_push($this->filelastmods, $lastmod);
				array_push($this->filecontents, $contains);
				$this->filecount++;
				
				$pure_files = array();
				
				$bdecompressed = bzdecompress(base64_decode($contains));
				
				// array files
				array_push($pure_files, array(
					"name" => $name,
					"size" => $size,
					"type" => $type,
					"lastmodified" => $lastmod,
					"content" => base64_encode($bdecompressed)
				));
				
				if($bdecompressed){
				
					if($this->purified == false){
				
						if($write == true){
							if(!empty($folder) && is_string($folder)){
						
								// if directory does not exists, create it.
								if(!is_dir($folder)){
						
									if($write == true)
										@mkdir($folder, 0777);
								}
							} else {
						
								// we can't keep null. else it will appear in our file name
								$folder = "";
							}
					
							// our file path 
							$fullpath = $folder.($folder==""?"":"/");
						
							// rename
							$namey = explode("/", $name);
							$namez = $namey[sizeof($namey) - 1];
						
							// create non exising folders
						
							// directory we start creating from
							$start_dir = "";
							for($j = 0; $j < sizeof($namey) - 1; $j++){
							
								// make directory if not exisits
								if(!is_dir($fullpath.$start_dir.$namey[$j])) 
									@mkdir($fullpath.$start_dir.$namey[$j]);
							
								// increment start_dir
								$start_dir .= $namey[$j]."/";
							}
				
					
							// we don't want to overwrite existing names and we
							// still want to save processing power...
							if(file_exists($fullpath.$start_dir.$namez)){
						
								$namex = explode(".", $namez);
						
								// incase we have more than one dot,
								if(sizeof($namex) > 0){
				
									$nameg = "";
									for($l = 0; $l < (count($namex) - 1); $l++){
										$nameg .= $namex[$l];
									}
						
									$name = $fullpath.$start_dir.$nameg." - ( SON renamed )".".".$namex[count($namex) - 1];
								}
								else $name = $namex[0];
							} else {
					
								$name = $fullpath.$start_dir.$namez;
							}
				
				// if($bdecompressed){
				
						// Unless we are asked not to write to file,
						// if($write == true){
					
							$create = file_put_contents($name, $bdecompressed);
				
							if(!$create){
					
								$this->error[] .= JSON_ARCHIVE_HALTED_DECOMPRESSION;
								return false;
							}
				
					// } else {
						
						// if($this->purified == false){
							
							// return binary
							// return $bdecompressed;
						
						// } else {
							
							// return JSON
							// return $content;
						
						// }
					
					// }
				
						}
				//.
				
					}
				
			// } else {
				
				// return array.
				// return true;
			
				} else {
					
					$this->error[] .= JSON_ARCHIVE_HALTED_DECOMPRESSION;
					return false;
				}
			
			} /* else {
			
			$this->error[] .= JSON_ARCHIVE_CHECKSUM_FAILED;
			return false;
			
		} */
		
		} else {
	
			$this->error[] .= JSON_ARCHIVE_CHECKSUM_FAILED;
			return false;
		}
		
		if($this->purified == true){
			return @json_encode($pure_files);
		} else {
			return $pure_files;
		}
	}
	
	/** 
	 * MAIN METHOD
	 * ==============
	 * This is the main method that should be called tto get any information
	 * about an archive.
	 * 
	 * It is not necessarily passed a file if it is just to get informations
	 * about the archive we are about to compress.
	 */
	function getHeader($file = null){
		
		// when we are decompressing,
		if($this->decompressing && empty($file)){
		
			// we assume the decompressed file...
			$header = $this->fileheader;
		} elseif($this->decompressing == false && empty($file)){
		
			if(!empty($this->fileheader)){
				// we assume the decompressed file...
				$header = $this->fileheader;
			}
		} else {
			// we need a file.
			if(!file_exists($file) || empty($file)){
			
				$this->error[] .= JSON_ARCHIVE_FILEREAD_ERROR;
				return false;
			}
			
			$get = @file_get_contents($file);
			
			// make sure file is readable.
			if(!$get){
				$this->error[] .= JSON_ARCHIVE_FILEREAD_ERROR;
				return false;
			}
			
			$decoded = $this->predecompress($get);
		
			// Only continue if no error.
			if(!empty($this->error)){
				return false;
			} 
			
			$header = @json_decode($decoded[0]);
		}
		
		if(!empty($header)){
		
			// make sure our archive is still secured!!
			// Looking at: security
			if(!empty($header->key)){
				
				if(empty($this->key)){
					$this->error[] .= JSON_ARCHIVE_NOKEY_ERROR;
					return false;
				} else {
					
					// proper hash our key
					$key = crc32($this->key);
					
					// make sure our key is correct
					if($this->key != $header->key){
						
						$this->error[] .= JSON_ARCHIVE_WRONGKEY_ERROR;
						return false;
					}
				}
			}
			
			$return_header = array(
				"name" => $header->name,  // name of the archive
				"uncompressedsize" => round($header->uncompressedsize / 1024, 2)."KB", // size before compression
				"created" => $header->created, 	// creation time
				"compressedby" => $header->compressedby, // who is compressing this
				"comments" => $header->comments, // add anything else
				"version" => $header->version, // SON Version
				"compressionlevel" => $header->compressionlevel, // compression level
				"totalfiles" => $header->totalfiles, // total files in archive
				"secure" => $header->secure, // secured or not
				"compressedsize" => round(filesize($file) / 1024, 2)."KB"
			);
				
			if($this->purified == false){	
				// return Array
				return $return_header;
			} else {
				// return JSON
				return @json_encode($return_header);
			}
		} else {
			return false;
		}
	}
	
	
	/** 
	 * MAIN METHOD 
	 * ===========
	 * Called to archive an entire directory/folder.
	 */
	function addDirectory($dir = null){
	
		$dir = str_replace('\\', '/', $dir);
		
		if(empty($dir) || !is_dir($dir)){
			
			$this->error[] .= JSON_ARCHIVE_INVALID_DIRECTORY;
			return false;
		}
		
		// Thanks to php.net
		// http://php.net/manual/en/class.recursivedirectoryiterator.php
		$dir_object = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::SELF_FIRST);
		
		foreach($dir_object as $name => $object){
			
			$name = str_replace("\\", "/", $name);
			// Uncomment the next line to exclude hidden and or damaged files.
			//if(is_readable($name))
				$this->add($name);
		}
	
	}
	
	
	// SUB-METHOD
	// Returns information about files in an archive
	function getFilesInfo($archive){
	
		if(!file_exists($archive)){
			
			// Ouch!
			$this->error[] .= JSON_ARCHIVE_FILEREAD_ERROR;
			return false;
		}
		
		// We must still decompress our archive.
		// But obviously, we don't need it to write out files.
		$description = $this->decompress($archive, null, false);
		
		// Only continue if no error.
		if(!empty($this->error)){
			
			return false;
		}
		
		if($description){
		
			// Get these private details for an easier approach.
			$names = 	$this->filenames;
			$sizes =	$this->filesizes;
			$types =	$this->filetypes;
			$lastmods =	$this->filelastmods;
			$contents =	$this->filecontents;
			
			if(sizeof($names)!=sizeof($sizes)){
				// We have a malformed archive...
				$this->error[] .= JSON_ARCHIVE_MALFORMED;
				return false;
			}
			
			$return = array();
			
			for($i = 0; $i < sizeof($names); $i++){
			
				array_push( $return,
					array(
						"index" => $i,
						"name" => $names[$i],
						"size" => $sizes[$i],
						"type" => $types[$i],
						"lastmodified" => $lastmods[$i],
						"content" => $contents[$i]
					)
				);
			}
			
			if($this->purified == false){
			
				// return Array
				return $return;
			} else {
			
				// return JSON 
				return @json_encode($return);
			}
		
		} else {
			return false;
		}
	}
	
	/** 
	 * MAIN METHOD 
	 * ===========
	 * Called to rename a file in an archive.
	 */
	function rename($file = null, $index = null, $newname = null){
		
		// We need a file,
		if(!file_exists($file)){
			// Ouch!
			$this->error[] .= JSON_ARCHIVE_FILEREAD_ERROR;
			return false;
		}
		
		// an index
		if(!is_numeric($index)){
			// No way!
			$this->error[] .= JSON_ARCHIVE_WRONGINDEX_ERROR;
			return false;
		}
		
		// and a name to start.
		// Looking ahead: delete
		if(empty($newname) && !$this->deleting == true){
		
			// No way!
			$this->error[] .= JSON_ARCHIVE_NONEWNAME_ERROR;
			return false;
		}
		
		/** 
		 * #  We'll keep this out for now to allow the possibility of moving files.
		 * #  Uncomment it to restrict moving of files
		 * // Make sure our new name is safe..
		 * if(preg_match("/\\|\//", $newname)){
		 * 
		 * 	// No way!
		 * 	$this->error[] .= JSON_ARCHIVE_NONEWNAME_ERROR;
		 * 	return false;
		 * 
		 * } 
		 */
		
		// Looking at: Purified
		if($this->purified != true){
			
			// Check total files fom header.
			if($this->getHeader($file)["totalfiles"] < 1){
		
				$this->error[] .= JSON_ARCHIVE_EMPTY_ERROR;
				return false;
			}
		} else {
			// Go to unpurified state
			$header_info = @json_decode($this->getHeader($file));
			
			// Check total files fom header.
			if($header_info->totalfiles < 1){
				$this->error[] .= JSON_ARCHIVE_EMPTY_ERROR;
				return false;
			}
		}
		
		$get = @file_get_contents($file);
			
		// make sure file is readable.
		if(!$get){
			$this->error[] .= JSON_ARCHIVE_FILEREAD_ERROR;
			return false;
		}
			
		$decoded = $this->predecompress($get);
		
		// Only continue if no error.
		if(!empty($this->error)){
			return false;
		}
			
		$header = $decoded[0];
		$content = $decoded[1];
		$sum = $decoded[2];
		
		$to_be_processed = @json_decode($content);
		
		$forked_header = @json_decode($header);
		
		$this->readycreatedtime = $forked_header->created;
		
		if($index >= sizeof($to_be_processed)){
			
			$this->error[] .= JSON_ARCHIVE_OUTOFRANGE_EXCEPTION;
			return false;
		}
		
		// $rebinded = array(); # useless
		
		$this->uncompressed = 0;
		
		for($i = 0; $i < sizeof($to_be_processed); $i++){
		
			$thisfile = $to_be_processed[$i][0];
			
			$thiscontent = @json_decode($thisfile);
				
			if($i != $index){
				$name = $thiscontent->name;
				$value = $thiscontent->value;
				$size = $thiscontent->size;
				$type = $thiscontent->type;
				$lastmod = $thiscontent->lastmodified;
				
				// add files.
				array_push($this->files, array("name" => $name, "value" => $value, "size" => $size, "type" => $type, "lastmodified" => $lastmod));
				
				$this->uncompressed += $size;
			} else {
				
				// Looking ahead: delete
				if(!$this->deleting == true){
				
					$name = $newname;
					$value = $thiscontent->value;
					$size = $thiscontent->size;
					$type = $thiscontent->type;
					$lastmod = $thiscontent->lastmodified;
					
					// add files.
					array_push($this->files, array("name" => $name, "value" => $value, "size" => $size, "type" => $type, "lastmodified" => $lastmod));
					
					$this->uncompressed += $size;
				} else {
					array_push($this->files, array());
				}
			}
		}
		
		$finalize = $this->compress($file, $file);
		
		if(!$finalize){
			return false;
		}
		
		// Looking at: Purified state
		return $finalize;
	}
	
	/** 
	 * MAIN METHOD 
	 * ===========
	 * Called to delete a file in an archive.
	 * The delete function is simply an enhanced rename function.
	 * It renames a file to null. Striping it out of the archive.
	 */
	function delete($file = null, $index = null){
		
		$this->deleting = true;
		
		// making our call to rename.
		$call = $this->rename($file, $index);
		
		// We are no longer deleting. Even if delete fails.
		$this->deleting = false;
		
		if(!$call){
			$this->error[] .= JSON_ARCHIVE_DELETE_ERROR;
			return false;
		}
		
		return $call;
	}
}


// $h = new JsonArchive;
// $h->add("favicon.ico");
// $h->addDirectory("n1");
// $h->setKey("hello");
// $h->purify();
// $h->compress();
// echo $h->decompress("SON.son","son",false);
// echo $h->rename("SON.son",0,"icon.ico");
// $h->delete("SON.son",0);
// echo $h->getFilesInfo("SON.son")[0]["name"];
// echo $h->getHeader("SON.son")["uncompressedsize"];
// echo $h->error();

/* 

// Testing
==========

// $h = new JsonArchive;
// $h->add("favicon.ico");
// $h->setKey("hello");
// $h->purify();
// $h->compress();
// echo $h->decompress("SON.son","son");
// echo $h->error();


 */

?>
