<?php



/**
* caches instances of the wsdl class
* 
*	Because of limitations of flock, specifically that it is called with
*	a file handle, mutual exclusion is not implemented by locking the
*	file containing serialized WSDL.  Further, because semaphores and
*	alternative IPC mechanisms are not available in PHP on all platforms
*	or all builds, mutual exclusion is not based on them, either.
*	Instead, a single lock file is used by all instances of this class
*	for all WSDL URLs.  This could create scalability issues for applications
*	with many NuSOAP clients or servers.
*
* @author   Scott Nichol <snichol@computer.org>
* @version  $Id$
* @access public 
*/
class wsdlcache {
	var $fplock;

	/*
	* default constructor
    *
    * @access   public
    */
	function wsdlcache() {
	}

	/**
	* creates the filename used to cache a wsdl instance
	*
	* @param string $wsdl The URL of the wsdl instance
	* @return string The filename used to cache the instance
	* @access protected
	*/
	function createFilename($wsdl) {
		return 'wsdlcache-' . md5($wsdl);
	}

	/**
	* gets a wsdl instance from the cache
	*
	* @param string $wsdl The URL of the wsdl instance
	* @return object The cached wsdl instance, null if the instance is not in the cache
	* @access public
	*/
	function get($wsdl) {
		$filename = $this->createFilename($wsdl);
		$this->obtainMutex();
		$fp = @fopen($filename, "r");
		if ($fp) {
			$s = implode("", @file($filename));
			fclose($fp);
		} else {
			$s = null;
		}
		$this->releaseMutex();
		return (!is_null($s)) ? unserialize($s) : null;
	}

	/**
	* obtains the local mutex
	*
	* @access protected
	*/
	function obtainMutex() {
		$this->fplock = fopen("wsdlcache-lock", "w");
		flock($this->fplock, LOCK_EX);
	}

	/*
	* adds a wsdl instance to the cache
	*
	* @param object $wsdl_instance The wsdl instance to add
	* @access public
	*/
	function put($wsdl_instance) {
		$filename = $this->createFilename($wsdl_instance->wsdl);
		$s = serialize($wsdl_instance);
		$this->obtainMutex();
		$fp = fopen($filename, "w");
		fputs($fp, $s);
		fclose($fp);
		$this->releaseMutex();
	}

	/**
	* releases the local mutex
	*
	* @access protected
	*/
	function releaseMutex() {
		flock($this->fplock, LOCK_UN);
		fclose($this->fplock);
	}

	/**
	* removes a wsdl instance from the cache
	*
	* @param string $wsdl The URL of the wsdl instance
	* @return boolean Whether there was an instance to remove
	* @access public
	*/
	function remove($wsdl) {
		$filename = $this->createFilename($wsdl);
		$this->obtainMutex();
		$ret = unlink($filename);
		$this->releaseMutex();
		return $ret;
	}
}
?>
