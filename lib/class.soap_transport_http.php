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
	
	var $protocol_version = '1.0';
	var $encoding;
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
	/**
	 * soap_transport_http::send()
	 * 
	 * @param $data
	 * @param integer $timeout
	 * @return 
	 **/
	function send($data, $timeout=0) {
	    flush();
		//global $timer;
		//$timer->setMarker('http::send(): soapaction = '.$this->soapaction);
		$this->debug('entered send() with data of length: '.strlen($data));

		if($this->proxyhost != '' && $this->proxyport != ''){
			$this->debug('setting proxy host and port');
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
		$this->debug('socket connected');
		//$timer->setMarker('opened socket connection to server');
		
		$credentials = '';
		if($this->username != '') {
			$this->debug('setting http auth credentials');
			$credentials = 'Authorization: Basic '.base64_encode("$this->username:$this->password").'\r\n';
		}

		if($this->proxyhost && $this->proxyport){
			$this-> outgoing_payload = "POST $this->url HTTP/$this->protocol_version\r\n";
		} else {
			$this->outgoing_payload = "POST $this->path HTTP/$this->protocol_version\r\n";
		}

		if($this->encoding != ''){
			if(function_exists('gzdeflate')){
				$encoding_headers = "Accept-Encoding: $this->encoding\r\n".
				"Connection: close\r\n";
				set_magic_quotes_runtime(0);
			}
		}
		
		$this->outgoing_payload .=
			"User-Agent: $this->title/$this->version\r\n".
			//"User-Agent: Mozilla/4.0 (compatible; MSIE 5.5; Windows NT 5.0)\r\n".
			"Host: ".$this->host."\r\n".
			$credentials.
			"Content-Type: text/xml\r\nContent-Length: ".strlen($data)."\r\n".
			$encoding_headers.
			"SOAPAction: \"$this->soapaction\""."\r\n\r\n".
			$data;
		
		// send
		if(!fputs($fp, $this->outgoing_payload, strlen($this->outgoing_payload))) {
			$this->setError('couldn\'t write message data to socket');
			$this->debug('Write error');
		}
		//$timer->setMarker('wrote data to socket');
		$this->debug('wrote data to socket');
		
		// get response
	    $this->incoming_payload = '';
		//$start = time();
        //$timeout = $timeout + $start;*/
		//while($data = fread($fp, 32768) && $t < $timeout){
		//$timer->setMarker('starting fread()');
		$strlen = 0;
		while( $data = fread($fp, 32768) ){
			$this->incoming_payload .= $data;
			//$t = time();
			$strlen += strlen($data);
	    }
		//$timer->setMarker('finished fread(), bytes read: '.$strlen);
		/*$end = time();
		if ($t >= $timeout) {
			$this->setError('server response timed out');
			return false;
		}*/

		$this->debug('received '.strlen($this->incoming_payload).' bytes of data from server');
		
		// close filepointer
		fclose($fp);
		$this->debug('closed socket');
		
		// connection was closed unexpectedly
		if($this->incoming_payload == ''){
			$this->setError('no response from server');
			return false;
		}
		
		$this->debug('received incoming payload: '.strlen($this->incoming_payload));
		$data = $this->incoming_payload."\r\n\r\n\r\n\r\n";
		
		//$res = preg_split("/\r?\n\r?\n/s",$data);
		
		// remove 100 header
		if(ereg('^HTTP/1.1 100',$data)){
			if($pos = strpos($data,"\n\n")){
				$data = ltrim(substr($data,$pos));
			} elseif($pos = strpos($data,"\r\n\r\n")){
				$data = ltrim(substr($data,$pos));
			}
		}//
		//print 'w/o 100:-------------<pre>'.$data.'</pre><br>--------------<br>';
		
		// separate content from HTTP headers
        if(preg_match("/(.*?)\r?\n\r?\n(.*)/s",$data,$result)) {
			$this->debug('found proper separation of headers and document');
			$this->debug('getting rid of headers, stringlen: '.strlen($data));
			$header_array = explode("\r\n",$result[1]);
			$data = $result[2];
			$this->debug('cleaned data, stringlen: '.strlen($clean_data));
			// clean headers
			foreach($header_array as $header_line){
				$arr = explode(':',$header_line);
				$headers[trim($arr[0])] = trim($arr[1]);
			}
			//print "headers: $result[1]<br>";
			//print "data: $result[2]<br>";
		} else {
			$this->setError('no proper separation of headers and document');
			return false;
		}
		
		// decode transfer-encoding
		if($headers['Transfer-Encoding'] == 'chunked'){
			$data = $this->decodeChunked($data);
			//print "<pre>\nde-chunked:\n---------------\n$data\n\n---------------\n</pre>";
		}
		// decode content-encoding
		if($headers['Content-Encoding'] != ''){
			if($headers['Content-Encoding'] == 'deflate' || $headers['Content-Encoding'] == 'gzip'){
    			// if decoding works, use it. else assume data wasn't gzencoded
    			if(function_exists('gzinflate')){
					if($headers['Content-Encoding'] == 'deflate' && $degzdata = @gzinflate($data)){
    					$data = $degzdata;
					} elseif($headers['Content-Encoding'] == 'gzip' && $degzdata = gzinflate(substr($data, 10))){
						$data = $degzdata;
					} else {
						$this->setError('Errors occurred when trying to decode the data');
					}
					//print "<xmp>\nde-inflated:\n---------------\n$data\n-------------\n</xmp>";
    			} else {
					$this->setError('The server sent deflated data. Your php install must have the Zlib extension compiled in to support this.');
				}
			}
		}
		
		if(strlen($data) == 0){
			$this->debug('no data after headers!');
			$this->setError('no data present after HTTP headers');
			return false;
		}
		$this->debug('end of send()');
		return $data;
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
	
	function setEncoding($enc='gzip, deflate'){
		$this->encoding = $enc;
		$this->protocol_version = '1.1';
	}
	
	function decodeChunked($message) {
    
    	//CHUNKED MESSAGES ARE FORMATTED LIKE THIS (Extension Not Supported)
    	//HEXA_CHUNK_SIZE|CRLF|DATA|HEXA_CHUNK_SIZE|CRLF|HEXA_CHUNK_SIZE|CRLF|DATA|HEXA_CHUNK_SIZE|...|0
    	//(0 means next CHUNK SIZE=0)
    	//pipe are not in chunked message (just for your eyes...)
    	$CRLF_LENGTH = 2;// equal to "\r\n"
    	$chunk_pos = 0; //Start at position 0 of message
    	$crlf_pos = strpos ($message , "\r\n" , $chunk_pos);//Look for first 
    	$chunk_size = chop(substr($message,$chunk_pos,$crlf_pos));
    	$octets_to_read = hexdec($chunk_size);
    	$start_read = $crlf_pos + $CRLF_LENGTH;
    	while($octets_to_read > 0){
    		$buffer .= substr($message,$start_read,$octets_to_read);
    		$chunk_pos = $start_read + $octets_to_read + $CRLF_LENGTH;
    		if( strlen($message) > $chunk_pos ) {
    			$crlf_pos = @strpos($message , "\r\n" , $chunk_pos);
        		$chunk_size = chop(substr($message,$chunk_pos,$crlf_pos-$chunk_pos));
        		$octets_to_read = hexdec($chunk_size);
        		$start_read = $crlf_pos + $CRLF_LENGTH;
    		} else {
				$octets_to_read = 0;
			}
    	}
    	return $buffer;
    }
}

?>
