<?php

include('phar://wpupload.phar/progress.php');
require('phar://wpupload.phar/args.php');

function print_verbose( $string ) {
	global $args;
	if ($args->isVerbose()) echo $string;
}

function print_memory( ) {
	global $args;
	if ($args->showMemory()) printf("%.0dMB currently in use memory out of %.0dMB (File #%d)\n", memory_get_usage()/1024, ini_get('memory_limit'), $i);	
}

function status_bar( $i, $news ) {
	global $args;
	if ($args->showProgress()) show_status($i, count($news));	
}

global $args;
$args = new Args();

if ($args->showErrors()) {
	ini_set('display_errors', '1');
	error_reporting(E_ALL);
}

class IngenuityWallpaperFile {
	
	private static $wallpapers = array();
	
	private $path;
	
	private $height,$width,$month,$year,$type,$subtype;
	
	function __construct( $filepath ) {
		
		$this->path = $filepath;
		//now get the good stuff from the filepath
		if (preg_match("#(?P<month>[a-z]+)(?P<year>[0-9]{2,4})_(?P<type>[^_]+)_(?P<subtype>[^_]+)_(?P<width>[0-9]{3,4})x(?P<height>[0-9]{3,4})#i", $filepath, $matches)) {
			$this->month = $matches['month'];
			$this->year = $matches['year'];
			$this->type = $matches['type'];
			$this->subtype = $matches['subtype'];
			$this->width = $matches['width'];
			$this->height = $matches['height'];
			
			//add it to the configuration file 
			$key = sprintf('%s%s_%s.jpg', $matches['month'], $matches['year'], $matches['type']);
			if (!array_key_exists( $key, self::$wallpapers )) {
				
				switch (strtolower($this->type)) {
					case 'texture': $name = "Texture"; break;
					case 'image': $name = "Picture This"; break;
					case 'purple': $name = "Chill Out"; break;
					default: $name = "Amp it Up";
						
				}
				
				self::$wallpapers[$key] = array('supports' => array('festive','cal','quote1','quote2'), 'sizes' => array(), 'guid' => $key, 'name' => $name);
				
			}
			
			//add supporting size if it doesnt already exist
			$sizestr = sprintf("%sx%s", $this->width, $this->height );
			if (!in_array( $sizestr, self::$wallpapers[$key]['sizes'] )) self::$wallpapers[$key]['sizes'][] = sprintf("%sx%s", $this->width, $this->height );

			
		} else {
			print_verbose("Could not parse " . $filepath . PHP_EOL);
		}
		
		
	}
	
	public function getHeight() {return $this->height;}
	public function getYear() {return $this->year;}
	public function getType() {return $this->type;}
	public function getSubtype() {return $this->subtype;}
	public function getWidth() {return $this->width;}
	
	private $fileWidth, $fileHeight, $mime;
	
	private $newImage;
	
	private function getFInfo() {
		list($this->fileWidth, $this->fileHeight, $this->mime) = getimagesize($this->path);
	}
	
	public function moveToExportFolder() {
	
	}
	
	public static function generateConfig() {
		$wallpapers = array();
		foreach( self::$wallpapers as $w ) {
			$wallpapers[] = (object) $w;	
		}
		$config = array(
			'wallpapers' => $wallpapers,
			'overlays' => (object) array(
				'festive' => (object) array(
					'name' => "Iconic",
					'suffix' => "_festivecal",
					'id' => "festive"
					),
				'cal' => (object) array(
					'name' => "Time Tracker",
					'suffix' => "_cal",
					'id' => "cal"
					),
				'quote1' => (object) array(
					'name' => "Inspire Me",
					'suffix' => "_quote1",
					'id' => "quote1"
					),
				'quote2' => (object) array(
					'name' => "Entertain Me",
					'suffix' => "_quote2",
					'id' => "quote2"
					)
			),
		);
		
		return $config;
			
	}
	
	public function createNew() {
		switch ($this->mime) {
			case IMAGETYPE_GIF:
				$newImg = imagecreatefromgif($this->path);
				break;
			case IMAGETYPE_JPEG:
				$newImg = imagecreatefromjpeg($this->path);
				break;
			case IMAGETYPE_PNG:
				$newImg = imagecreatefrompng($this->path);
				break;
            default:
                return false;
		}
		if ($newImg === false) {
			return false;
		}
		
		//get proper aspect ratio
		$aspect = $this->width / $this->height;
		$thumbnail_gd_image = imagecreatetruecolor($this->width, $this->height);
		imagecopyresampled($thumbnail_gd_image, $newImg, 0, 0, 0, 0, $this->width, $this->height, $this->fileWidth, $this->fileHeight);
		imagejpeg($thumbnail_gd_image, ABSPATH . ".tmp" . DIRECTORY_SEPARATOR . basename($this->path), 90);
		
		
		if ($this->width == 1920) {
			//create regular preview
			$fileName = ABSPATH . ".tmp" . DIRECTORY_SEPARATOR . sprintf("preview_%s%d_%s_%s.jpg", $this->month, $this->year, $this->type, $this->subtype);
			imagejpeg($thumbnail_gd_image, $fileName, 90);
		} elseif ($this->width == 320) {
			//create mobile preview
			$fileName = sprintf("preview_mobile_%s%d_%s_%s.jpg", $this->month, $this->year, $this->type, $this->subtype);
			copy( $this->path, ABSPATH . '.tmp' . DIRECTORY_SEPARATOR . $fileName ); //done
		}
		
		$newImg = NULL;
		$thumbnail_gd_image = NULL;
		
		unset ($newImg, $thumbnail_gd_image);
		
	}
	
	public function maybeSize() {
		global $args;
		@$this->getFInfo();
		if ($this->mime) {
			if ($args->isVerbose()) printf("Resizing %s from %dx%d to %dx%d\n", basename($this->path), $this->fileWidth, $this->fileHeight, $this->width, $this->height);
			$this->createNew();
		}
		
	}
	
}

function ListIn($dir, $prefix = '') {
  $dir = rtrim($dir, '\\/');
  $result = array();

    $h = opendir($dir);
    while (($f = readdir($h)) !== false) {
      if ($f !== '.' and $f !== '..') {
        if (is_dir("$dir/$f")) {
          $result = array_merge($result, ListIn("$dir/$f", "$prefix$f/"));
        } else {
          $result[] = $prefix.$f;
        }
      }
    }
    closedir($h);

  return $result;
}

//we want to get input from args
$input = ($args->getInputDirectory()) ? $args->getInputDirectory() : 'input';

if (is_dir($input)) {
	$x = ListIn($input);
} else die("Could not find directory: " . $input . PHP_EOL );

define('ABSPATH', dirname(__FILE__) . DIRECTORY_SEPARATOR );

$files = array();
$i = 0;
printf("Resizing %d total files.\n", count($x));

if (!$args->noResize()) {
	foreach( $x as $file ) {
		$i++;
		status_bar($i, $x);
		print_memory();
		if (stristr($file, 'thumb')) continue;
		$fullpath = $input . DIRECTORY_SEPARATOR . $file;
		$c = new IngenuityWallpaperFile( $fullpath );
		if (!$args->noResize()) $c->maybeSize();
		$files[] = $c;
	}
	print_verbose($i . " files resized and put in the .tmp folder\n");
	print_verbose("Generating configuration file.\n");
	
	$conf = IngenuityWallpaperFile::generateConfig();
	$json = json_encode($conf);
	
	//we got the script now we can write it
	print_verbose("Writing configuration file.\n");
    Phar::interceptFileFuncs();
    if (file_exists( ABSPATH . '.tmp/conf.php')) {
        $handle = fopen( '.tmp/conf.php', 'w+' );
        fwrite( $handle, $json );
        fclose( $handle );
    } else {
        echo "We were not able to find a conf.php file" . PHP_EOL;
    }

}

if ($args->FTP()):

	echo "Now to try to upload them via FTP!\n";
	
	$conf = parse_ini_file('conf.ini');
	$ftp_server = isset($conf['server']) ? $conf['server'] : false;
	$ftp_user_name= isset($conf['user']) ? $conf['user'] : false;
	$ftp_user_pass= isset($conf['password']) ? $conf['password'] : false;
	$remote_file = isset($conf['path']) ? $conf['path'] : "/%s";
	// set up basic connection 
	$conn_id = ftp_connect($ftp_server); 
	
	// login with username and password 
	$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass); 
	ftp_pasv( $conn_id, true );
	// upload a file 
	$news = ListIn( ABSPATH . '.tmp');
	try {
		$i = 0;
		foreach($news as $file) {
			$file = ABSPATH . '.tmp' . DIRECTORY_SEPARATOR . $file;
			$i++;
			status_bar( $i, $news);
			if (basename($file) == "conf.php") {
				//something special happens here	
				if (ftp_put($conn_id, '/wallpaper/ajax/conf.php', $file, FTP_ASCII)) { 
					print_verbose( "Successfully updated configuration\n" ); 
				} else { 
					print_verbose( "There was a problem while uploading configuration\n" );
				} 		
			} else {
				if (ftp_put($conn_id, sprintf($remote_file, basename($file)), $file, FTP_BINARY )) { 
					print_verbose( "Successfully uploaded $file\n" ); 
				} else { 
					print_verbose( "There was a problem while uploading $file\n" ); 
				} 
			}
		}
	
	} catch (Exception $e) {
		echo "Terminated\n";
	}
	ftp_close($conn_id); 
	print_verbose( "Closed connection\n" );
	
endif;

echo "Cleaning .tmp directory\n";

foreach(ListIn('.tmp') as $file ) {
	$file = ABSPATH . '.tmp' . DIRECTORY_SEPARATOR . $file;
	@unlink($file);	
}


echo "Done working\n";