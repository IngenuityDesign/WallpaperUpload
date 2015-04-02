<?php

class Args {
	
	private $args;
	
	public function getInputDirectory() {
		//this will be the last argument
		$maybeIt = $this->args[count($this->args) - 1];
		//check if it is prefixed with a dash
		if (preg_match("#^-+#", $maybeIt)) {
			return false;	
		}
		
		//replace home string
		if (!$home = $_SERVER['HOME']) $home = $_SERVER['HOMEDRIVE'] + $_SERVER['HOMEPATH'];
		
		$maybeIt = preg_replace("#^[~]#", $_SERVER['HOME'], $maybeIt);
		//this seems to be it. we need to maybe suffix it with up to /* so we will remove it and add it again
		$maybeIt = realpath($maybeIt);
		return $maybeIt;
	}
	
	public function __construct() {
		global $argv;
		$this->args = $argv;
	}
	
	//app specific stuff
	public function showProgress() {
		if (!$this->showMemory() && !$this->isVerbose()) return true;
		return false;	
	}
	
	public function showMemory() {
		return $this->s( '--memory' );
	}
	
	public function showErrors() {
		return $this->s( '--errors' );	
	}
	
	//app specific stuff
	public function isVerbose() {
		return $this->s( '-v' );
	}
	
	public function noResize() {
		return $this->s( '--no-resize' );	
	}
	
	public function FTP() {
		return $this->s( '--upload' );	
	}
	
	private function s( $str ) {
		if ( in_array( $str, $this->args )) return true;
		return false;
	}

	public function noClean() {
		return $this->s( '--no-clean' );
	}

	public function tmpDirectory($default) {
		$str = false;
		foreach( $this->args as $arg ) {
			if (preg_match("#^-tmp=#", $arg)) {
				$str = preg_replace("#^-tmp=#", "", $arg);

				if (!$home = $_SERVER['HOME']) $home = $_SERVER['HOMEDRIVE'] + $_SERVER['HOMEPATH'];
		
				$str = preg_replace("#^[~]#", $_SERVER['HOME'], $str);
				//this seems to be it. we need to maybe suffix it with up to /* so we will remove it and add it again
				$str = realpath($str);

				if (!is_writable($str)) {
					throw new Exception("Tmp directory is not writable");
				}
			}
		}
		
		return $str ? $str : $default;
	}
		
}