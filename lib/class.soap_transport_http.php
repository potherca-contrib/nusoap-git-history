<?php

/**
* transport class for sending/receiving data via HTTP and HTTPS
* NOTE: PHP must be compiled with the CURL extension for HTTPS support
* HTTPS support is experimental!
*
* @access public
*/
class soap_transport_http extends nusoap_base {

	var $username = '';
	var $password = '';
	var $url;
    var $proxyhost = '';
    var $proxyport = '';

	/**
	* constructor
	*/
	function soap_transport_http($url){
		$this->url = $url;
		$u = parse_url($url);
		foreach($u as $k => $v){
			$this->debug("$k = $v");
			$this->$k = $v;
		}
		if(isset($u['query']) && $u['query'] != ''){
			//$this->path .= $u['query'];
            $this->path .= '?' . $u['query'];
		}
		if(!isset($u['port']) && $u['scheme'] == 'http'){
			$this->port = 80;
		}
	}

	/**
	* if authenticating, set user credentials here
	*
	* @param    string $user
	* @param    string $pass
	* @access   public
	*/
	function setCredentials($username, $password) {
		$this->username = $username;
		$this->password = $password;
	}

	/**
	* set the soapaction value
	*
	* @param    string $soapaction
	* @access   public
	*/
	function setSOAPAction($soapaction) {
		$this->soapaction = $soapaction;
	}

	/**
	* set proxy info here
	*
	* @param    string $proxyhost
	* @param    string $proxyport
	* @access   public
	*/
	function setProxy($proxyhost, $proxyport) {
		$this->proxyhost = $proxyhost;
		$this->proxyport = $proxyport;
	}

	/**
	* send the SOAP message via HTTP 1.0
	*
	* @param    string $msg message data
	* @param    integer $timeout set timeout in seconds
	* @return	string data
	* @access   public
	*/
	function send($data, $timeout=0) {
	    flush();
		$this->debug('entered send() with data of length: '.strlen($data));

		if($this->proxyhost != '' && $this->proxyport != ''){
			$host = $this->proxyhost;
			$port = $this->proxyport;
		} else {
			$host = $this->host;
			$port = $this->port;
		}
		if($timeout > 0){
			$fp = fsockopen($host, $port, $this->errno, $this->error_str, $timeout);
		} else {
			$fp = fsockopen($host, $port, $this->errno, $this->error_str);
		}

		if (!$fp) {
			$this->debug('Couldn\'t open socket connection to server: '.$server);
			$this->setError('Couldn\'t open socket connection to server: '.$server);
			return false;
		}

		$credentials = '';
		if($this->username != '') {
			$credentials = 'Authorization: Basic '.base64_encode("$this->username:$this->password").'\r\n';
		}

		if($this->proxyhost && $this->proxyport){
			$this-> outgoing_payload = "POST $this->url HTTP/1.0\r\n";
		} else {
			$this->outgoing_payload = "POST $this->path HTTP/1.0\r\n";
		}

		if($this->gzip){
			// set header
			//$gzip = "Accept-Encoding: gzip, deflate\r\n";
			/* gzip our output if possible
			if(function_exists('gzencode') && $gzdata = gzencode($data)){
				$gzip = "Accept-Encoding: gzip\r\n";
				$data = $gzdata;
			}*/
		}
		
		$this->outgoing_payload .=
			"User-Agent: $this->title/$this->version\r\n".
			//"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)\r\n".
			"Host: ".$this->host."\r\n".
			$credentials.
			"Content-Type: text/xml\r\nContent-Length: ".strlen($data)."\r\n".
			$gzip.
			//"Accept: */*\r\n".
			"SOAPAction: \"$this->soapaction\""."\r\n\r\n".
			$data;
		
		// send
		if(!fputs($fp, $this->outgoing_payload, strlen($this->outgoing_payload))) {
			$this->setError('couldn\'t write message data to socket');
			$this->debug('Write error');
		}

		// get response
	    $this->incoming_payload = '';
	    while ($data = fread($fp, 32768)) {
			$this->incoming_payload .= $data;
	    }
		
		// close filepointer
		fclose($fp);
		$data = $this->incoming_payload;
		/*if($this->gzip){
			// if decoding works, use it. else assume data wasn't gzencoded
			if($degzdata = @gzinflate($data)){
				$data = $degzdata;
			}
		}*/
		//print "data: <xmp>$data</xmp>";
		// separate content from HTTP headers
        if(preg_match("/([^<]*?)\r?\n\r?\n(<.*>)/s",$data,$result)) {
			$this->debug('found proper separation of headers and document');
			$this->debug('getting rid of headers, stringlen: '.strlen($data));
			$clean_data = $result[2];
			$this->debug('cleaned data, stringlen: '.strlen($clean_data));
		} else {
			$this->setError('no proper separation of headers and document.');
			return false;
		}
		if(strlen($clean_data) == 0){
			$this->debug('no data after headers!');
			$this->setError('no data present after HTTP headers.');
			return false;
		}
		return $clean_data;
	}


	/**
	* send the SOAP message via HTTPS 1.0 using CURL
	*
	* @param    string $msg message data
	* @param    integer $timeout set timeout in seconds
	* @return	string data
	* @access   public
	*/
	function sendHTTPS($data, $timeout=0) {
	    flush();
		$this->debug('entered sendHTTPS() with data of length: '.strlen($data));
		// init CURL
		$ch = curl_init();

		// set proxy
		if($this->proxyhost && $this->proxyport){
			$host = $this->proxyhost;
			$port = $this->proxyport;
		} else {
			$host = $this->host;
			$port = $this->port;
		}
		// set url
		$hostURL = ($port != '') ? "https://$host:$port" : "https://$host";
		// add path
		$hostURL .= $this->path;

		curl_setopt($ch, CURLOPT_URL, $hostURL);
		// set other options
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// set timeout
		if($timeout != 0){
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		}

		$credentials = '';
		if($this->username != '') {
			$credentials = 'Authorization: Basic '.base64_encode("$this->username:$this->password").'\r\n';
		}

		if($this->proxyhost && $this->proxyport){
			$this-> outgoing_payload = "POST $this->url HTTP/1.0\r\n";
		} else {
			$this->outgoing_payload = "POST $this->path HTTP/1.0\r\n";
		}

		$this->outgoing_payload .=
			"User-Agent: $this->title v$this->version\r\n".
			"Host: ".$this->host."\r\n".
			$credentials.
			"Content-Type: text/xml\r\nContent-Length: ".strlen($data)."\r\n".
			"SOAPAction: \"$this->soapaction\""."\r\n\r\n".
			$data;

		// set payload
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->outgoing_payload);

		// send and receive
		$this->incoming_payload = curl_exec($ch);
		$data = $this->incoming_payload;

        $cErr = curl_error($ch);

		if($cErr != ''){
        	$err = 'cURL ERROR: '.curl_errno($ch).': '.$cErr.'<br>';
			foreach(curl_getinfo($ch) as $k => $v){
				$err .= "$k: $v<br>";
			}
			$this->setError($err);
			curl_close($ch);
	    	return false;
		}

		curl_close($ch);

		// separate content from HTTP headers
		if(ereg("^(.*)\r?\n\r?\n",$data)) {
			$this->debug('found proper separation of headers and document');
			$this->debug('getting rid of headers, stringlen: '.strlen($data));
			$clean_data = ereg_replace("^[^<]*\r\n\r\n","", $data);
			$this->debug('cleaned data, stringlen: '.strlen($clean_data));
		} else {
			$this->setError('no proper separation of headers and document.');
			return false;
		}
		if(strlen($clean_data) == 0){
			$this->debug('no data after headers!');
			$this->setError('no data present after HTTP headers.');
			return false;
		}

		return $clean_data;
	}
}

?>
