<?php

/*

NuSOAP - Web Services Toolkit for PHP

Copyright (c) 2002 NuSphere Corporation

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

If you have any questions or comments, please email:

Dietrich Ayala
dietrich@ganx4.com
http://dietrich.ganx4.com/nusoap

NuSphere Corporation
http://www.nusphere.com

*/

// make errors handle properly in windows (thx, thong@xmethods.com)
//error_reporting(2039);
//error_reporting(E_ALL);

/* load classes

// necessary classes
require_once('class.soapclient.php');
require_once('class.soap_val.php');
require_once('class.soap_parser.php');
require_once('class.soap_fault.php');

// transport classes
require_once('class.soap_transport_http.php');

// optional add-on classes
require_once('class.xmlschema.php');
require_once('class.wsdl.php');

// server class
require_once('class.soap_server.php');*/

/**
*
* nusoap_base
*
* @author   Dietrich Ayala <dietrich@ganx4.com>
* @version  v 0.6.2
* @access   public
*/

class nusoap_base {

	var $title = 'NuSOAP';
	var $version = '0.6.2';
	var $error_str = false;
    var $debug_str = '';
	// toggles automatic encoding of special characters
	var $charencoding = true;

    /**
	*  set schema version
	*
	* @var      XMLSchemaVersion
	* @access   public
	*/
	var $XMLSchemaVersion = 'http://www.w3.org/2001/XMLSchema';
	
    /**
	*  set default encoding
	*
	* @var      soap_defencoding
	* @access   public
	*/
	//var $soap_defencoding = 'UTF-8';
    var $soap_defencoding = 'ISO-8859-1';

	/**
	*  load namespace uris into an array of uri => prefix
	*
	* @var      namespaces
	* @access   public
	*/
	var $namespaces = array(
		'SOAP-ENV' => 'http://schemas.xmlsoap.org/soap/envelope/',
		'xsd' => 'http://www.w3.org/2001/XMLSchema',
		'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
		'SOAP-ENC' => 'http://schemas.xmlsoap.org/soap/encoding/',
		'si' => 'http://soapinterop.org/xsd');
	/**
	* load types into typemap array
	* is this legacy yet?
	* @var      typemap
	* @access   public
	*/
	var $typemap = array(
	'http://www.w3.org/2001/XMLSchema' => array(
		'string'=>'string','boolean'=>'boolean','float'=>'double','double'=>'double','decimal'=>'double',
		'duration'=>'','dateTime'=>'string','time'=>'string','date'=>'string','gYearMonth'=>'',
		'gYear'=>'','gMonthDay'=>'','gDay'=>'','gMonth'=>'','hexBinary'=>'string','base64Binary'=>'string',
		// derived datatypes
		'normalizedString'=>'string','token'=>'string','language'=>'','NMTOKEN'=>'','NMTOKENS'=>'','Name'=>'','NCName'=>'','ID'=>'',
		'IDREF'=>'','IDREFS'=>'','ENTITY'=>'','ENTITIES'=>'','integer'=>'integer','nonPositiveInteger'=>'integer',
		'negativeInteger'=>'integer','long'=>'integer','int'=>'integer','short'=>'integer','byte'=>'integer','nonNegativeInteger'=>'integer',
		'unsignedLong'=>'','unsignedInt'=>'','unsignedShort'=>'','unsignedByte'=>'','positiveInteger'=>''),
	'http://www.w3.org/1999/XMLSchema' => array(
		'i4'=>'','int'=>'integer','boolean'=>'boolean','string'=>'string','double'=>'double',
		'float'=>'double','dateTime'=>'string',
		'timeInstant'=>'string','base64Binary'=>'string','base64'=>'string','ur-type'=>'array'),
	'http://soapinterop.org/xsd' => array('SOAPStruct'=>'struct'),
	'http://schemas.xmlsoap.org/soap/encoding/' => array('base64'=>'string','array'=>'array','Array'=>'array'),
    'http://xml.apache.org/xml-soap' => array('Map')
	);

	/**
	*  entities to convert
	*
	* @var      xmlEntities
	* @access   public
	*/
	var $xmlEntities = array('quot' => '"','amp' => '&',
		'lt' => '<','gt' => '>','apos' => "'");

	/**
	* adds debug data to the class level debug string
	*
	* @param    string $string debug data
	* @access   private
	*/
	function debug($string){
		$this->debug_str .= get_class($this).": $string\n";
	}

	/**
	* returns error string if present
	*
	* @return   boolean $string error string
	* @access   public
	*/
	function getError(){
		if($this->error_str != ''){
			return $this->error_str;
		}
		return false;
	}

	/**
	* sets error string
	*
	* @return   boolean $string error string
	* @access   private
	*/
	function setError($str){
		$this->error_str = $str;
	}

	/**
	* serializes PHP values in accordance w/ section 5
	* @return	string
    * @access	public
	*/
	function serialize_val($val,$name=false,$type=false,$name_ns=false,$type_ns=false,$attributes=false){
    	if(is_object($val) && get_class($val) == 'soapval'){
        	return $val->serialize();
        }
		$this->debug( "in serialize_val: $val, $name, $type, $name_ns, $type_ns");
		// if no name, use item
		$name = (!$name|| is_numeric($name)) ? 'soapVal' : $name;
		// if name has ns, add ns prefix to name
		$xmlns = '';
        if($name_ns){
			$prefix = 'nu'.rand(1000,9999);
			$name = $prefix.':'.$name;
			$xmlns .= " xmlns:$prefix=\"$name_ns\"";
		}
		// if type is prefixed, create type prefix
		if($type_ns != '' && $type_ns == $this->namespaces['xsd']){
			// need to fix this. shouldn't default to xsd if no ns specified
		    // w/o checking against typemap
			$type_prefix = 'xsd';
		} elseif($type_ns){
			$type_prefix = 'ns'.rand(1000,9999);
			$xmlns .= " xmlns:$type_prefix=\"$type_ns\"";
		}
		// serialize attributes if present
		if($attributes){
			foreach($attributes as $k => $v){
				$atts .= " $k=\"$v\"";
			}
		}
        // serialize if an xsd built-in primitive type
        if($type != '' && isset($this->typemap[$this->XMLSchemaVersion][$type])){
        	return "<$name$xmlns xsi:type=\"xsd:$type\">$val</$name>\n";
        }
		// detect type and serialize
		switch(true) {
			case ($type == '' && is_null($val)):
				$xml .= "<$name$xmlns xsi:type=\"xsd:nil\"/>\n";
				break;
			case (is_bool($val) || $type == 'boolean'):
				if(!$val){
			    	$val = 0;
				}
				$xml .= "<$name$xmlns xsi:type=\"xsd:boolean\"$atts>$val</$name>\n";
				break;
			case (is_int($val) || is_long($val) || $type == 'int'):
				$xml .= "<$name$xmlns xsi:type=\"xsd:int\"$atts>$val</$name>\n";
				break;
			case (is_float($val)|| is_double($val) || $type == 'float'):
				$xml .= "<$name$xmlns xsi:type=\"xsd:float\"$atts>$val</$name>\n";
				break;
			case (is_string($val) || $type == 'string'):
				if($this->charencoding){
			    	$val = htmlspecialchars($val, ENT_QUOTES);
			    }
				$xml .= "<$name$xmlns xsi:type=\"xsd:string\"$atts>$val</$name>\n";
				break;
			case is_object($val):
				break;
			break;
			case (is_array($val) || $type):
				// detect if struct or array
                $keyList = array_keys($val);
				$valueType = 'arraySimple';
				foreach($keyList as $keyListValue){
					if(!is_int($keyListValue)){
						$valueType = 'arrayStruct';
					}
				}
                if($valueType=='arraySimple' || ereg('^ArrayOf',$type)){
					foreach($val as $v){
                    	if(is_object($v) && get_class($v) == 'soapval'){
                        	$tt = $v->type;
                        } else {
							$tt = gettype($v);
                        }
						$array_types[$tt] = 1;
						$xml .= $this->serialize_val($v,'item');
						if(is_array($v) && is_numeric(key($v))){
							$i += sizeof($v);
						} else {
							$i += 1;
						}
					}
					if(count($array_types) > 1){
						$array_typename = 'xsd:ur-type';
					} elseif(isset($this->typemap[$this->XMLSchemaVersion][$tt])) {
						$array_typename = 'xsd:'.$tt;
					} elseif($tt == 'array' || $tt == 'Array'){
						$array_typename = 'SOAP-ENC:Array';
					} else {
						$array_typename = $tt;
					}
					if($array_types['array']){
						$array_type = $i.",".$i;
					} else {
						$array_type = $i;
					}
					$xml = "<$name xsi:type=\"SOAP-ENC:Array\" SOAP-ENC:arrayType=\"".$array_typename."[$array_type]\"$atts>\n".$xml."</$name>\n";
				} else {
					// got a struct
					if($type && $type_prefix){
						$type_str = " xsi:type=\"$type_prefix:$type\"";
					}
					$xml .= "<$name$xmlns$type_str$atts>\n";
					foreach($val as $k => $v){
						$xml .= $this->serialize_val($v,$k);
					}
					$xml .= "</$name>\n";
				}
				break;
			default:
				$xml .= 'not detected, got '.gettype($val).' for '.$val;
				break;
		}
		return $xml;
	}

    /**
    * serialize message
    *
    * @param string body
    * @param string headers
    * @param array namespaces
    * @return string message
    * @access public
    */
    function serializeEnvelope($body,$headers=false,$namespaces=array()){
	// serialize namespaces
    $ns_string = '';
	foreach(array_merge($this->namespaces,$namespaces) as $k => $v){
		$ns_string .= "\n  xmlns:$k=\"$v\"";
	}
	// serialize headers
	if($headers){
		$headers = "<SOAP-ENV:Header>\n".$headers."</SOAP-ENV:Header>\n";
	}
	// serialize envelope
	return
	'<?xml version="1.0" encoding="'.$this->soap_defencoding .'"?'.">\n".
	'<SOAP-ENV:Envelope SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"'.$ns_string.">\n".
	$headers.
	"<SOAP-ENV:Body>\n".
		$body.
	"</SOAP-ENV:Body>\n".
	"</SOAP-ENV:Envelope>\n";
    }

    function formatDump($str){
		$str = htmlspecialchars($str);
		return nl2br($str);
    }

    /**
    * returns the local part of a prefixed string
    * returns the original string, if not prefixed
    *
    * @param string
    * @return string
    * @access public
    */
	function getLocalPart($str){
		if($sstr = strrchr($str,':')){
			// get unqualified name
			return substr( $sstr, 1 );
		} else {
			return $str;
		}
	}

	/**
    * returns the prefix part of a prefixed string
    * returns false, if not prefixed
    *
    * @param string
    * @return mixed
    * @access public
    */
	function getPrefix($str){
		if($pos = strrpos($str,':')){
			// get prefix
			return substr($str,0,$pos);
		}
		return false;
	}

    function varDump($data) {
		ob_start();
		var_dump($data);
		$ret_val = ob_get_contents();
		ob_end_clean();
		return $ret_val;
	}
}

// XML Schema Datatype Helper Functions

//xsd:dateTime helpers

/**
* convert unix timestamp to ISO 8601 compliant date string
*
* @param    string $timestamp Unix time stamp
* @access   public
*/
function timestamp_to_iso8601($timestamp,$utc=true){
	$datestr = date('Y-m-d\TH:i:sO',$timestamp);
	if($utc){
		$eregStr =
		'([0-9]{4})-'.	// centuries & years CCYY-
		'([0-9]{2})-'.	// months MM-
		'([0-9]{2})'.	// days DD
		'T'.			// separator T
		'([0-9]{2}):'.	// hours hh:
		'([0-9]{2}):'.	// minutes mm:
		'([0-9]{2})(\.[0-9]*)?'. // seconds ss.ss...
		'(Z|[+\-][0-9]{2}:?[0-9]{2})?'; // Z to indicate UTC, -/+HH:MM:SS.SS... for local tz's

		if(ereg($eregStr,$datestr,$regs)){
			return sprintf('%04d-%02d-%02dT%02d:%02d:%02dZ',$regs[1],$regs[2],$regs[3],$regs[4],$regs[5],$regs[6]);
		}
		return false;
	} else {
		return $datestr;
	}
}

/**
* convert ISO 8601 compliant date string to unix timestamp
*
* @param    string $datestr ISO 8601 compliant date string
* @access   public
*/
function iso8601_to_timestamp($datestr){
	$eregStr =
	'([0-9]{4})-'.	// centuries & years CCYY-
	'([0-9]{2})-'.	// months MM-
	'([0-9]{2})'.	// days DD
	'T'.			// separator T
	'([0-9]{2}):'.	// hours hh:
	'([0-9]{2}):'.	// minutes mm:
	'([0-9]{2})(\.[0-9]+)?'. // seconds ss.ss...
	'(Z|[+\-][0-9]{2}:?[0-9]{2})?'; // Z to indicate UTC, -/+HH:MM:SS.SS... for local tz's
	if(ereg($eregStr,$datestr,$regs)){
		// not utc
		if($regs[8] != 'Z'){
			$op = substr($regs[8],0,1);
			$h = substr($regs[8],1,2);
			$m = substr($regs[8],strlen($regs[8])-2,2);
			if($op == '-'){
				$regs[4] = $regs[4] + $h;
				$regs[5] = $regs[5] + $m;
			} elseif($op == '+'){
				$regs[4] = $regs[4] - $h;
				$regs[5] = $regs[5] - $m;
			}
		}
		return strtotime("$regs[1]-$regs[2]-$regs[3] $regs[4]:$regs[5]:$regs[6]Z");
	} else {
		return false;
	}
}


?><?php

/**
* soap_fault class, allows for creation of faults
* mainly used for returning faults from deployed functions
* in a server instance.
* @access public
*/
class soap_fault extends nusoap_base {

	var $faultcode;
	var $faultactor;
	var $faultstring;
	var $faultdetail;

	/**
	* constructor
    *
    * @param string $faultcode (client | server)
    * @param string $faultactor only used when msg routed between multiple actors
    * @param string $faultstring human readable error message
    * @param string $faultdetail
	*/
	function soap_fault($faultcode,$faultactor='',$faultstring='',$faultdetail=''){
		$this->faultcode = $faultcode;
		$this->faultactor = $faultactor;
		$this->faultstring = $faultstring;
		$this->faultdetail = $faultdetail;
	}

	/**
	* serialize a fault
	*
	* @access   public
	*/
	function serialize(){
		foreach($this->namespaces as $k => $v){
			$ns_string .= "\n  xmlns:$k=\"$v\"";
		}
		$return_msg =
			'<?xml version="1.0"?'.">\n".
			'<SOAP-ENV:Envelope SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"'.$ns_string.">\n".
				'<SOAP-ENV:Body>'.
				'<SOAP-ENV:Fault>'.
					'<faultcode>'.$this->faultcode.'</faultcode>'.
					'<faultactor>'.$this->faultactor.'</faultactor>'.
					'<faultstring>'.$this->faultstring.'</faultstring>'.
					'<detail>'.$this->serialize_val($this->faultdetail).'</detail>'.
				'</SOAP-ENV:Fault>'.
				'</SOAP-ENV:Body>'.
			'</SOAP-ENV:Envelope>';
		return $return_msg;
	}
}

?><?php

/**
* parses an XML Schema, allows access to it's data, other utility methods
* no validation... yet.
* very experimental and limited. As is discussed on XML-DEV, I'm one of the people
* that just doesn't have time to read the spec(s) thoroughly, and just have a couple of trusty
* tutorials I refer to :)
*
* @author   Dietrich Ayala <dietrich@ganx4.com>
* @access   public
*/
class XMLSchema extends nusoap_base  {
	
	// files
	var $schema = '';
	var $xml = '';
	// define internal arrays of bindings, ports, operations, messages, etc.
	var $complexTypes = array();

	// parser vars
	var $parser;
	var $position;
	var $depth = 0;
	var $depth_array = array();
    
	/**
	* constructor
	*
	* @param    string $schema schema document URI
	* @param    string $xml xml document URI
	* @access   public
	*/
	function XMLSchema($schema='',$xml=''){

		$this->debug('xmlschema class instantiated, inside constructor');
		// files
		$this->schema = $schema;
		$this->xml = $xml;

		// parse schema file
		if($schema != ''){
			$this->debug('initial schema file: '.$schema);
			$this->parseFile($schema);
		}

		// parse xml file
		if($xml != ''){
			$this->debug('initial xml file: '.$xml);
			$this->parseFile($xml);
		}

	}

    /**
    * parse an XML file
    *
    * @param string $xml, path/URL to XML file
    * @param string $type, (schema | xml)
	* @return boolean
    * @access public
    */
	function parseFile($xml,$type){
		// parse xml file
		if($xml != ""){
			$this->debug('parsing $xml');
			$xmlStr = @join("",@file($xml));
			if($xmlStr == ""){
				$this->setError('No file at the specified URL: '.$xml);
			return false;
			} else {
				$this->parseString($xmlStr,$type);
			return true;
			}
		}
	return false;
	}

	/**
	* parse an XML string
	*
	* @param    string $xml path or URL
    * @param string $type, (schema|xml)
	* @access   private
	*/
	function parseString($xml,$type){
		// parse xml string
		if($xml != ""){

	    	// Create an XML parser.
	    	$this->parser = xml_parser_create();
	    	// Set the options for parsing the XML data.
	    	xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);

	    	// Set the object for the parser.
	    	xml_set_object($this->parser, $this);

	    	// Set the element handlers for the parser.
			if($type == "schema"){
		    	xml_set_element_handler($this->parser, 'schemaStartElement','schemaEndElement');
		    	xml_set_character_data_handler($this->parser,'schemaCharacterData');
			} elseif($type == "xml"){
				xml_set_element_handler($this->parser, 'xmlStartElement"','xmlEndElement');
		    	xml_set_character_data_handler($this->parser,'xmlCharacterData');
			}

		    // Parse the XML file.
		    if(!xml_parse($this->parser,$xml,true)){
			// Display an error message.
				$errstr = sprintf('XML error on line %d: %s',
				xml_get_current_line_number($this->parser),
				xml_error_string(xml_get_error_code($this->parser))
				);
				$this->debug('XML parse error: '.$errstr);
				$this->setError('Parser error: '.$errstr);
	    	}
            
			xml_parser_free($this->parser);
		} else{
			$this->debug('no xml passed to parseString()!!');
			$this->setError('no xml passed to parseString()!!');
		}
	}

	/**
	* start-element handler
	*
	* @param    string $parser XML parser object
	* @param    string $name element name
	* @param    string $attrs associative array of attributes
	* @access   private
	*/
	function schemaStartElement($parser, $name, $attrs) {
		
		// position in the total number of elements, starting from 0
		$pos = $this->position++;
		$depth = $this->depth++;
		// set self as current value for this depth
		$this->depth_array[$depth] = $pos;

		// get element prefix
		if($prefix = $this->getPrefix($name)){
			// get unqualified name
			$name = $this->getLocalPart($name);
		} else {
        	$prefix = '';
        }
		
        // loop thru attributes, expanding, and registering namespace declarations
        if(count($attrs) > 0){
        	foreach($attrs as $k => $v){
                // if ns declarations, add to class level array of valid namespaces
				if(ereg("^xmlns",$k)){
                	$this->xdebug("$k: $v");
                	$this->xdebug("ns_prefix: ".$this->getPrefix($k));
                	if($ns_prefix = substr(strrchr($k,':'),1)){
					//if($ns_prefix = $this->getPrefix($k)){
						$this->namespaces[$ns_prefix] = $v;
					} else {
						$this->namespaces['ns'.(count($this->namespaces)+1)] = $v;
					}
					if($v == 'http://www.w3.org/2001/XMLSchema' || $v == 'http://www.w3.org/1999/XMLSchema'){
						$this->XMLSchemaVersion = $v;
						$this->namespaces['xsi'] = $v.'-instance';
					}
				}
                // expand each attribute
                $k = strpos($k,':') ? $this->expandQname($k) : $k;
                $v = strpos($v,':') ? $this->expandQname($v) : $v;
        		$eAttrs[$k] = $v;
        	}
        	$attrs = $eAttrs;
        } else {
        	$attrs = array();
        }
		// find status, register data
		switch($name){
			case 'all':
				$this->complexTypes[$this->currentComplexType]['compositor'] = 'all';
				$this->complexTypes[$this->currentComplexType]['phpType'] = 'struct';
			break;
			case 'attribute':
            	$this->xdebug("parsing attribute $attrs[name] $attrs[ref] of value: ".$attrs['http://schemas.xmlsoap.org/wsdl/:arrayType']);
                if(isset($attrs['name'])){
					$this->attributes[$attrs['name']] = $attrs;
					$aname = $attrs['name'];
				} elseif($attrs['ref']){
					$aname = $attrs['ref'];
                    $this->attributes[$attrs['ref']] = $attrs;
				}
                
				if($this->currentComplexType){
					$this->complexTypes[$this->currentComplexType]['attrs'][$aname] = $attrs;
				} elseif($this->currentElement){
					$this->elements[$this->currentElement]['attrs'][$aname] = $attrs;
				}
				// arrayType attribute
				if($this->getLocalPart($aname) == 'arrayType'){
                	$this->complexTypes[$this->currentComplexType]['phpType'] = 'array';
					$v = $attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'];
                    if(strpos($v,'[,]')){
                        $this->complexTypes[$this->currentComplexType]['multidimensional'] = true;
                    }
                    $v = substr($v,0,strpos($v,'[')); // clip the []
                    if(!strpos($v,':') && isset($this->typemap[$this->XMLSchemaVersion][$v])){
                        $v = $this->XMLSchemaVersion.':'.$v;
                    }
                    $this->complexTypes[$this->currentComplexType]['arrayType'] = $v;
				}
			break;
			case 'complexContent':

			break;
			case 'complexType':
				if($attrs['name']){
					$this->currentElement = false;
					$this->currentComplexType = $attrs['name'];
					$this->complexTypes[$this->currentComplexType] = $attrs;
					$this->complexTypes[$this->currentComplexType]['typeClass'] = 'complexType';
					if(isset($attrs['base']) && ereg(':Array$',$attrs['base'])){
						$this->complexTypes[$this->currentComplexType]['phpType'] = 'array';
					} else {
						$this->complexTypes[$this->currentComplexType]['phpType'] = 'struct';
					}
					$this->xdebug('processing complexType '.$attrs['name']);
				}
			break;
			case 'element':
				if(isset($attrs['type'])){
					$this->xdebug("processing element ".$attrs['name']);
					$this->currentElement = $attrs['name'];
					$this->elements[ $attrs['name'] ] = $attrs;
					$this->elements[ $attrs['name'] ]['typeClass'] = 'element';
					$ename = $attrs['name'];
				} elseif(isset($attrs['ref'])){
					$ename = $attrs['ref'];
				} else {
					$this->xdebug('adding complexType '.$attrs[name]);
					$this->currentComplexType = $attrs['name'];
					$this->complexTypes[ $attrs['name'] ] = $attrs;
					$this->complexTypes[ $attrs['name'] ]['element'] = 1;
					$this->complexTypes[$this->currentComplexType]['phpType'] = 'struct';
				}
				if($ename && $this->currentComplexType){
					$this->complexTypes[$this->currentComplexType]['elements'][$ename] = $attrs;
				}
			break;
			case 'restriction':
				$this->xdebug("in restriction for ct: $this->currentComplexType and ce: $this->currentElement");
				if($this->currentElement){
					$this->elements[$this->currentElement]['type'] = $attrs['base'];
				} elseif($this->currentComplexType){
					$this->complexTypes[$this->currentComplexType]['restrictionBase'] = $attrs['base'];
					if(strstr($attrs['base'],':') == ':Array'){
						$this->complexTypes[$this->currentComplexType]['phpType'] = 'array';
					}
				}
			break;
			case 'schema':
				$this->schema = $attrs;
				$this->schema['schemaVersion'] = $this->getNamespaceFromPrefix($prefix);
			break;
			case 'sequence':
				$this->complexTypes[$this->currentComplexType]['compositor'] = 'sequence';
			break;
			case 'simpleType':
				$this->currentElement = $attrs['name'];
				$this->elements[ $attrs['name'] ] = $attrs;
				$this->elements[ $attrs['name'] ]['typeClass'] = 'element';
			break;
		}
	}

	/**
	* end-element handler
	*
	* @param    string $parser XML parser object
	* @param    string $name element name
	* @access   private
	*/
	function schemaEndElement($parser, $name) {
		// position of current element is equal to the last value left in depth_array for my depth
		if(isset($this->depth_array[$this->depth])){
        	$pos = $this->depth_array[$this->depth];
        }
		// bring depth down a notch
		$this->depth--;
		// move on...
		if($name == 'complexType'){
			$this->currentComplexType = false;
			$this->currentElement = false;
		}
		if($name == 'element'){
			$this->currentElement = false;
		}
	}

	/**
	* element content handler
	*
	* @param    string $parser XML parser object
	* @param    string $data element content
	* @access   private
	*/
	function schemaCharacterData($parser, $data){
		$pos = $this->depth_array[$this->depth];
		$this->message[$pos]['cdata'] .= $data;
	}

	/**
	* serialize the schema
	*
	* @access   public
	*/
	function serializeSchema(){

		$schemaPrefix = $this->getPrefixFromNamespace($this->XMLSchemaVersion);
		// complex types
		foreach($this->complexTypes as $typeName => $attrs){
			$contentStr = "";
			// serialize child elements
			if(count($attrs['elements']) > 0){
				foreach($attrs['elements'] as $element => $eParts){
					$contentStr .= "<element ref=\"$element\"/>\n";
				}
			}
			// serialize attributes
			if(count($attrs['attrs']) > 0){
				foreach($attrs['attrs'] as $attr => $aParts){
					$contentStr .= "<attribute ref=\"$attr\"/>\n";
				}
			}

			// if restriction
			if($attrs['restrictionBase']){
				$contentStr = "<$schemaPrefix:restriction base=\"".$attrs["restrictionBase"]."\">\n".
				$contentStr."</$schemaPrefix:restriction>\n";
			}
			if($attrs['complexContent']){
				$contentStr = "<$schemaPrefix:complexContent>\n".
				$contentStr."</$schemaPrefix:complexContent>\n";
			} elseif($attrs['sequence']){
				$contentStr = "<$schemaPrefix:sequence>\n".
				$contentStr."</$schemaPrefix:sequence>\n";
			} elseif($attrs['all']){
				$contentStr = "<$schemaPrefix:all>\n".
				$contentStr."</$schemaPrefix:all>\n";
			}
			if($attrs['element']){
				if($contentStr != ''){
					$contentStr = "<$schemaPrefix:element name=\"$typeName\">\n"."<$schemaPrefix:complexType>\n".
					$contentStr."</$schemaPrefix:complexType>\n"."</$schemaPrefix:element>\n";
				} else {
					$contentStr = "<$schemaPrefix:element name=\"$typeName\">\n"."<$schemaPrefix:complexType/>\n".
					"</$schemaPrefix:element>\n";
				}
			} else {
				if($contentStr != ''){
					$contentStr = "<$schemaPrefix:complexType name=\"$typeName\">\n".
					$contentStr."</$schemaPrefix:complexType>\n";
				} else {
					$contentStr = "<$schemaPrefix:complexType name=\"$typeName\"/>\n";
				}
			}
			$xml .= $contentStr;
		}
		// elements
		if(count($this->elements) > 0){
			foreach($this->elements as $element => $eParts){
				$xml .= "<$schemaPrefix:element name=\"$element\" type=\"".$eParts['type']."\"/>\n";
			}
		}
		// attributes
		if(count($this->attributes) > 0){
			foreach($this->attributes as $attr => $aParts){
				$xml .= "<$schemaPrefix:attribute name=\"$attr\" type=\"".$aParts['type']."\"/>\n";
			}
		}
		$xml = "<$schemaPrefix:schema targetNamespace=\"".$this->schema["targetNamespace"]."\">\n".
		$xml."</$schemaPrefix:schema>\n";

		return $xml;
	}

	/**
	* expands a qualified name
	*
	* @param    string $string qname
	* @return	string expanded qname
	* @access   private
	*/
	function expandQname($qname){
		// get element prefix
		if(strpos($qname,':') && !ereg('^http://',$qname)){
			// get unqualified name
			$name = substr(strstr($qname,':'),1);
			// get ns prefix
			$prefix = substr($qname,0,strpos($qname,':'));
			if(isset($this->namespaces[$prefix])){
				return $this->namespaces[$prefix].':'.$name;
			} else {
				return $qname;
			}
		} else {
			return $qname;
		}
	}

	/**
	* adds debug data to the clas level debug string
	*
	* @param    string $string debug data
	* @access   private
	*/
	function xdebug($string){
		$this->debug(' xmlschema: '.$string);
	}

    /**
    * get the PHP type of a user defined type in the schema
    * PHP type is kind of a misnomer since it actually returns 'struct' for assoc. arrays
    * returns false if no type exists, or not w/ the given namespace
    * else returns a string that is either a native php type, or 'struct'
    *
    * @param string $type, name of defined type
    * @param string $ns, namespace of type
    * @return mixed
    * @access public
    */
	function getPHPType($type,$ns){
		global $typemap;
		if(isset($typemap[$ns][$type])){
			//print "found type '$type' and ns $ns in typemap<br>";
			return $typemap[$ns][$type];
		} elseif(isset($this->complexTypes[$type])){
			//print "getting type '$type' and ns $ns from complexTypes array<br>";
			return $this->complexTypes[$type]['phpType'];
		}
		return false;
	}

    /**
    * returns the local part of a prefixed string
    * returns the original string, if not prefixed
    *
    * @param string
    * @return string
    * @access public
    */
	function getLocalPart($str){
		if($sstr = strrchr($str,':')){
			// get unqualified name
			return substr( $sstr, 1 );
		} else {
			return $str;
		}
	}

	/**
    * returns the prefix part of a prefixed string
    * returns false, if not prefixed
    *
    * @param string
    * @return mixed
    * @access public
    */
	function getPrefix($str){
		if($pos = strrpos($str,':')){
			// get prefix
			return substr($str,0,$pos);
		}
		return false;
	}

	/**
    * pass it a prefix, it returns a namespace
    * or false if no prefixes registered for the given namespace
    *
    * @param string
    * @return mixed
    * @access public
    */
	function getNamespaceFromPrefix($prefix){
		if(isset($this->namespaces[$prefix])){
			return $this->namespaces[$prefix];
		}
		//$this->setError("No namespace registered for prefix '$prefix'");
		return false;
	}

	/**
    * returns the prefix for a given namespace
    * returns false if no namespace registered with the given prefix
    *
    * @param string
    * @return mixed
    * @access public
    */
	function getPrefixFromNamespace($ns){
		foreach($this->namespaces as $p => $n){
			if($ns == $n){
			    $this->usedNamespaces[$p] = $ns;
				return $p;
			}
		}
		return false;
	}

	/**
    * returns an array of information about a given type
    * returns false if no type exists by the given name
    *
	*	 typeDef = array(
	*	 'elements' => array(), // refs to elements array
	*	'restrictionBase' => '',
	*	'phpType' => '',
	*	'order' => '(sequence|all)',
	*	'attrs' => array() // refs to attributes array
	*	)
    *
    * @param string
    * @return mixed
    * @access public
    */
	function getTypeDef($type){
		if(isset($this->complexTypes[$type])){
			return $this->complexTypes[$type];
		} elseif(isset($this->elements[$type])){
			return $this->elements[$type];
		} elseif(isset($this->attributes[$type])){
			return $this->attributes[$type];
		}
		return false;
	}

	/**
    * returns a sample serialization of a given type, or false if no type by the given name
    *
    * @param string $type, name of type
    * @return mixed
    * @access public
    */
    function serializeTypeDef($type){
    	//print "in sTD() for type $type<br>";
	if($typeDef = $this->getTypeDef($type)){
		$str .= '<'.$type;
	    if(is_array($typeDef['attrs'])){
		foreach($attrs as $attName => $data){
		    $str .= " $attName=\"{type = ".$data['type']."}\"";
		}
	    }
	    $str .= " xmlns=\"".$this->schema['targetNamespace']."\"";
	    if(count($typeDef['elements']) > 0){
		$str .= ">\n";
		foreach($typeDef['elements'] as $element => $eData){
		    $str .= $this->serializeTypeDef($element);
		}
		$str .= "</$type>\n";
	    } elseif($typeDef['typeClass'] == 'element') {
		$str .= "></$type>\n";
	    } else {
		$str .= "/>\n";
	    }
			return $str;
	}
    	return false;
    }

    /**
    * returns HTML form elements that allow a user
    * to enter values for creating an instance of the given type.
    *
    * @param string $name, name for type instance
    * @param string $type, name of type
    * @return string
    * @access public
	*/
	function typeToForm($name,$type){
		// get typedef
		if($typeDef = $this->getTypeDef($type)){
			// if struct
			if($typeDef['phpType'] == 'struct'){
				$buffer .= '<table>';
				foreach($typeDef['elements'] as $child => $childDef){
					$buffer .= "
					<tr><td align='right'>$childDef[name] (type: ".$this->getLocalPart($childDef['type'])."):</td>
					<td><input type='text' name='parameters[".$name."][$childDef[name]]'></td></tr>";
				}
				$buffer .= '</table>';
			// if array
			} elseif($typeDef['phpType'] == 'array'){
				$buffer .= '<table>';
				for($i=0;$i < 3; $i++){
					$buffer .= "
					<tr><td align='right'>array item (type: $typeDef[arrayType]):</td>
					<td><input type='text' name='parameters[".$name."][]'></td></tr>";
				}
				$buffer .= '</table>';
			// if scalar
			} else {
				$buffer .= "<input type='text' name='parameters[$name]'>";
			}
		} else {
			$buffer .= "<input type='text' name='parameters[$name]'>";
		}
		return $buffer;
	}
}

?><?php

/**
* for creating serializable abstractions of native PHP types
* NOTE: this is only really used when WSDL is not available.
*
* @author   Dietrich Ayala <dietrich@ganx4.com>
* @version  v 0.6.2
* @access   public
*/
class soapval extends nusoap_base {
	/**
	* constructor
	*
	* @param    string $name optional name
	* @param    string $type optional type name
	* @param	mixed $value optional value
	* @param	string $namespace optional namespace of value
	* @param	string $type_namespace optional namespace of type
	* @param	array $attributes associative array of attributes to add to element serialization
	* @access   public
	*/
  	function soapval($name='soapval',$type=false,$value=-1,$element_ns=false,$type_ns=false,$attributes=false) {
		$this->name = $name;
		$this->value = $value;
		$this->type = $type;
		$this->element_ns = $element_ns;
		$this->type_ns = $type_ns;
		$this->attributes = $attributes;
    }

	/**
	* return serialized value
	*
	* @return	string XML data
	* @access   private
	*/
	function serialize() {
		return $this->serialize_val($this->value,$this->name,$this->type,$this->element_ns,$this->type_ns,$this->attributes);
    }

	/**
	* decodes a soapval object into a PHP native type
	*
	* @param	object $soapval optional SOAPx4 soapval object, else uses self
	* @return	mixed
	* @access   public
	*/
	function decode(){
		return $this->value;
	}
}

?><?php

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
	/**
	 * soap_transport_http::send()
	 * 
	 * @param $data
	 * @param integer $timeout
	 * @return 
	 **/
	function send($data, $timeout=0) {
	    flush();
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

		$credentials = '';
		if($this->username != '') {
			$this->debug('setting http auth credentials');
			$credentials = 'Authorization: Basic '.base64_encode("$this->username:$this->password").'\r\n';
		}

		if($this->proxyhost && $this->proxyport){
			$this-> outgoing_payload = "POST $this->url HTTP/1.0\r\n";
		} else {
			$this->outgoing_payload = "POST $this->path HTTP/1.0\r\n";
		}

		if($this->gzip){
			if(function_exists('gzdeflate') && $gzdata = gzdeflate($data)){
				$gzip = "Accept-Encoding: gzip, deflate\r\n";
				//set_socket_blocking($fp, 1); 
				//"Content-Encoding: deflate\r\n";
				//$data = $gzdata;
			}//
		}
		
		$this->outgoing_payload .=
			"User-Agent: $this->title/$this->version\r\n".
			//"User-Agent: Mozilla/4.0 (compatible; MSIE 5.5; Windows NT 5.0)\r\n".
			"Host: ".$this->host."\r\n".
			$credentials.
			"Content-Type: text/xml\r\nContent-Length: ".strlen($data)."\r\n".
			$gzip.
			"SOAPAction: \"$this->soapaction\""."\r\n\r\n".
			$data;
		
		// send
		if(!fputs($fp, $this->outgoing_payload, strlen($this->outgoing_payload))) {
			$this->setError('couldn\'t write message data to socket');
			$this->debug('Write error');
		}
		$this->debug('wrote data to socket');
		
		/* get response
	    $this->incoming_payload = '';
	    while ($data = fread($fp, 32768)) {
			$this->incoming_payload .= $data;
	    }*/
		//while(!feof($fp) && $t < $timeout) {
		while(!feof($fp)){
			$this->incoming_payload .= fgets($fp,32768);
			//$t = time();
		}
		
		/*if ($t>=$timeout){
			$this->setError('Operation timed out');
			return false;
		}*/
		
		//$s = socket_get_status($fp);
		// connection was closed
		if($this->incoming_payload == ''){
			$this->setError('no response from server');
			return false;
		}
		$this->debug('received incoming payload: '.strlen($this->incoming_payload));
		
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
		
		// remove 100
		if($this->gzip){
			$data = ereg_replace("^([^<]*)\r?\n\r?\n",'',$data);
		}
		//print '<pre>'.$data.'</pre>';
		// separate content from HTTP headers
        if(preg_match("/([^<]*?)\r?\n\r?\n(<.*>)/s",$data,$result)) {
			$this->debug('found proper separation of headers and document');
			$this->debug('getting rid of headers, stringlen: '.strlen($data));
			$clean_data = $result[2];
			$this->debug('cleaned data, stringlen: '.strlen($clean_data));
		} else {
			$this->setError('no proper separation of headers and document');
			return false;
		}
		if(strlen($clean_data) == 0){
			$this->debug('no data after headers!');
			$this->setError('no data present after HTTP headers');
			return false;
		}
		$this->debug('end of send()');
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

?><?php

/**
*
* soap_server allows the user to create a SOAP server
* that is capable of receiving messages and returning responses
*
* NOTE: WSDL functionality is experimental
*
* @author   Dietrich Ayala <dietrich@ganx4.com>
* @version  v 0.6.2
* @access   public
*/
class soap_server extends nusoap_base {

    // assoc array of operations => opData
    var $operations = array();
    var $responseHeaders = false;
	var $headers = '';
	var $request = '';
	var $charset_encoding = 'UTF-8';
	var $fault = false;
	var $result = 'successful';
	var $wsdl = false;
    var $debug_flag = false;
	/**
	* constructor
    * the optional parameter is a path to a WSDL file that you'd like to bind the server instance to.
	*
    * @param string $wsdl path or URL to a WSDL file
	* @access   public
	*/
	function soap_server($wsdl=false){

		// turn on debugging?
		global $debug;
		if(isset($debug)){
			$this->debug_flag = true;
		}

		$this->wsdl = false;

		// wsdl
		if($wsdl){
			$this->wsdl = new wsdl($wsdl);
			if($err = $this->wsdl->getError()){
				die('WSDL ERROR: '.$err);
			}
		}
	}

	/**
	* processes request and returns response
	*
	* @param    string $data usually is the value of $HTTP_RAW_POST_DATA
	* @access   public
	*/
	function service($data){
		// print wsdl
		if(isset($GLOBALS['QUERY_STRING']) && ereg('^wsdl',$GLOBALS['QUERY_STRING'])){
			header("Content-Type: text/xml\r\n");
			print $this->wsdl->serialize2();
		// print web interface
		} elseif($data == '' && $this->wsdl){
			print $this->webDescription();
		} else {
			// $response is the serialized response message
			$response = $this->parse_request($data);
			$this->debug('server sending...');
			$payload = $response;
            // add debug data if in debug mode
			if(isset($this->debug_flag)){
            	$payload .= "<!--\n".str_replace('--','- -',$this->debug_str)."\n-->";
            }
			// print headers
			if($this->fault){
				$header[] = "Status: 500 Internal Server Error\r\n";
			} else {
				$header[] = "Status: 200 OK\r\n";
			}
			$header[] = "Server: $this->title Server v$this->version\r\n";
			$header[] = "Connection: Close\r\n";
			$header[] = "Content-Type: text/xml; charset=$this->charset_encoding\r\n";
			$header[] = "Content-Length: ".strlen($payload)."\r\n\r\n";
			reset($header);
			foreach($header as $hdr){
				header($hdr);
			}
			$this->response = join("\n",$header).$payload;
			print $payload;
		}
	}

	/**
	* parses request and posts response
	*
	* @param    string $data XML string
	* @return	object SOAPx4 soapmsg object
	* @access   private
	*/
	function parse_request($data='') {
		$this->debug('entering parseRequest() on '.date("H:i Y-m-d"));
        $dump = '';
	// get headers
	if(function_exists('getallheaders')){
		$this->headers = getallheaders();
		foreach($this->headers as $k=>$v){
			$dump .= "$k: $v\r\n";
			$this->debug("$k: $v");
		}
		// get SOAPAction header
		if(isset($this->headers['SOAPAction'])){
			$this->SOAPAction = str_replace('"','',$this->headers['SOAPAction']);
		}
		// get the character encoding of the incoming request
		if(strpos($this->headers['Content-Type'],"=")){
			$enc = str_replace("\"","",substr(strstr($this->headers["Content-Type"],"="),1));
			if(eregi("^(ISO-8859-1|US-ASCII|UTF-8)$",$enc)){
				$this->xml_encoding = $enc;
			} else {
				$this->xml_encoding = 'us-ascii';
			}
		}
		$this->debug('got encoding: '.$this->charset_encoding);
	} elseif(is_array($_SERVER)){
		$this->headers['User-Agent'] = $_SERVER['HTTP_USER_AGENT'];
		$this->SOAPAction = isset($_SERVER['SOAPAction']) ? $_SERVER['SOAPAction'] : '';
	}
	$this->request = $dump."\r\n\r\n".$data;
	// parse response, get soap parser obj
	$parser = new soap_parser($data,$this->charset_encoding);
	// if fault occurred during message parsing
	if($err = $parser->getError()){
		// parser debug
		$this->debug("parser debug: \n".$parser->debug_str);
		$this->result = 'fault: error in msg parsing or eval: '.$err;
		$this->fault('Server',"error in msg parsing or eval:\n".$err);
		// return soapresp
		return $this->fault->serialize();
	// else successfully parsed request into soapval object
	} else {
		// get/set methodname
		$this->methodname = $parser->root_struct_name;
		$this->debug("method name: $this->methodname");
		// does method exist?
		if(!function_exists($this->methodname)){
			// "method not found" fault here
			$this->debug("method '$this->methodname' not found!");
			$this->debug("parser debug: \n".$parser->debug_str);
			$this->result = 'fault: method not found';
			$this->fault('Server',"method '$this->methodname' not defined in service '$this->service'");
			return $this->fault->serialize();
		}
		if($this->wsdl){
			if(!$this->opData = $this->wsdl->getOperationData($this->methodname)){
		    	$this->fault('Server',"Operation '$this->methodname' is not defined in the WSDL for this service");
			return $this->fault->serialize();
		    }
		}
		$this->debug("method '$this->methodname' exists");
		// evaluate message, getting back parameters
		$this->debug('calling parser->get_response()');
		$request_data = $parser->get_response();
		//$this->debug('Parsed response dump: $request_data');
		// parser debug
		$this->debug("parser debug: \n".$parser->debug_str);
		// verify that request parameters match the method's signature
		if($this->verify_method($this->methodname,$request_data)){
			// if there are parameters to pass
            $this->debug('params var dump '.$this->varDump($request_data));
			if($request_data){
				$this->debug("calling '$this->methodname' with params");
				if (! function_exists('call_user_func_array')) {
					$this->debug('calling method using eval()');
					$funcCall = $this->methodname.'(';
					foreach($request_data as $param) {
						$funcCall .= "\"$param\",";
					}
					$funcCall = substr($funcCall, 0, -1).')';
					$this->debug('function call:<br>'.$funcCall);
					eval("\$method_response = $funcCall;");
				} else {
					$this->debug('calling method using call_user_func_array()');
					$method_response = call_user_func_array("$this->methodname",$request_data);
				}
                $this->debug('response var dump'.$this->varDump($method_response));
			} else {
				// call method w/ no parameters
				$this->debug("calling $this->methodname w/ no params");
				$m = $this->methodname;
				$method_response = $m();
			}
			$this->debug("done calling method: $this->methodname, received $method_response of type".gettype($method_response));
			// if we got nothing back. this might be ok (echoVoid)
			if(isset($method_response) && $method_response != '' || is_bool($method_response)) {
				// if fault
				if(get_class($method_response) == 'soap_fault'){
					$this->debug('got a fault object from method');
					$this->fault = $method_response;
					return $method_response->serialize();
				// if return val is soapval object
				} elseif(get_class($method_response) == 'soapval'){
					$this->debug('got a soapval object from method');
					$return_val = $method_response->serialize();
				// returned other
				} else {
					$this->debug('got a(n) '.gettype($method_response).' from method');
					$this->debug('serializing return value');
					if($this->wsdl){
						if(sizeof($this->opData['output']['parts']) > 1){
					    	$opParams = $method_response;
					    } else {
					    	$opParams = array($method_response);
					    }
					    $return_val = $this->wsdl->serializeRPCParameters($this->methodname,'output',$opParams);
					} else {
					    $return_val = $this->serialize_val($method_response);
					}
				}
			}
			$this->debug("serializing response");
			$payload = '<'.$this->methodname."Response>\n".$return_val.'</'.$this->methodname."Response>\n";
			$this->result = "successful";
			if($this->wsdl){
				//if($this->debug_flag){
                	$this->debug("WSDL debug data:\n".$this->wsdl->debug_str);
                //	}
				// Added: In case we use a WSDL, return a serialized env. WITH the usedNamespaces.
				return $this->serializeEnvelope($payload,$this->responseHeaders,$this->wsdl->usedNamespaces);
			}
			return $this->serializeEnvelope($payload,$this->responseHeaders);
		} else {
			// debug
			$this->debug('ERROR: request not verified against method signature');
			$this->result = 'fault: request failed validation against method signature';
			// return fault
			$this->fault('Server',"Operation '$this->methodname' not defined in service.");
			return $this->fault->serialize();
		}
		}
	}

	/**
	* takes the value that was created by parsing the request
	* and compares to the method's signature, if available.
	*
	* @param	mixed
	* @return	boolean
	* @access   private
	*/
	function verify_method($operation,$request){
	    if(isset($this->operations[$operation])){
			return true;
		}
		return false;
	}

	/**
	* add a method to the dispatch map
	*
	* @param    string $methodname
	* @param    string $in array of input values
	* @param    string $out array of output values
	* @access   public
	*/
	function add_to_map($methodname,$in,$out){
			$this->operations[$methodname] = array('name' => $methodname,'in' => $in,'out' => $out);
	}

	/**
	* register a service with the server
	*
	* @param    string $methodname
	* @param    string $in assoc array of input values: key = param name, value = param type
	* @param    string $out assoc array of output values: key = param name, value = param type
	* @param	string $namespace
	* @param	string $soapaction
	* @param	string $style (rpc|literal)
	* @access   public
	*/
	function register($name,$in=false,$out=false,$namespace=false,$soapaction=false,$style=false,$use=false){
	    $this->operations[$name] = array(
	    'name' => $name,
	    'in' => $in,
	    'out' => $out,
	    'namespace' => $namespace,
	    'soapaction' => $soapaction,
	    'style' => $style);
        if($this->wsdl){
        	$this->wsdl->addOperation($name,$in,$out,$namespace,$soapaction,$style,$use);
	    }
		return true;
	}

	/**
	* create a fault. this also acts as a flag to the server that a fault has occured.
	*
	* @param	string faultcode
	* @param	string faultactor
	* @param	string faultstring
	* @param	string faultdetail
	* @access   public
	*/
	function fault($faultcode,$faultactor,$faultstring='',$faultdetail=''){
		$this->fault = new soap_fault($faultcode,$faultactor,$faultstring,$faultdetail);
	}

    /**
    * prints html description of services
    *
    * @access private
    */
    function webDescription(){
	$b .= '
	<html><head><title>NuSOAP: '.$this->wsdl->serviceName.'</title>
	<style type="text/css">
	    body    { font-family: arial; color: #000000; background-color: #ffffff; margin: 0px 0px 0px 0px; }
	    p       { font-family: arial; color: #000000; margin-top: 0px; margin-bottom: 12px; }
	    pre { background-color: silver; padding: 5px; font-family: Courier New; font-size: x-small; color: #000000;}
	    ul      { margin-top: 10px; margin-left: 20px; }
	    li      { list-style-type: none; margin-top: 10px; color: #000000; }
	    .content{
		margin-left: 0px; padding-bottom: 2em; }
	    .nav {
		padding-top: 10px; padding-bottom: 10px; padding-left: 15px; font-size: .70em;
		margin-top: 10px; margin-left: 0px; color: #000000;
		background-color: #ccccff; width: 20%; margin-left: 20px; margin-top: 20px; }
	    .title {
		font-family: arial; font-size: 26px; color: #ffffff;
		background-color: #999999; width: 105%; margin-left: 0px;
		padding-top: 10px; padding-bottom: 10px; padding-left: 15px;}
	    .hidden {
		position: absolute; visibility: hidden; z-index: 200; left: 250px; top: 100px;
		font-family: arial; overflow: hidden; width: 600;
		padding: 20px; font-size: 10px; background-color: #999999;
		layer-background-color:#FFFFFF; }
	    a,a:active  { color: charcoal; font-weight: bold; }
	    a:visited   { color: #666666; font-weight: bold; }
	    a:hover     { color: cc3300; font-weight: bold; }
	</style>
	<script language="JavaScript" type="text/javascript">
	<!--
	// POP-UP CAPTIONS...
	function lib_bwcheck(){ //Browsercheck (needed)
	    this.ver=navigator.appVersion
	    this.agent=navigator.userAgent
	    this.dom=document.getElementById?1:0
	    this.opera5=this.agent.indexOf("Opera 5")>-1
	    this.ie5=(this.ver.indexOf("MSIE 5")>-1 && this.dom && !this.opera5)?1:0;
	    this.ie6=(this.ver.indexOf("MSIE 6")>-1 && this.dom && !this.opera5)?1:0;
	    this.ie4=(document.all && !this.dom && !this.opera5)?1:0;
	    this.ie=this.ie4||this.ie5||this.ie6
	    this.mac=this.agent.indexOf("Mac")>-1
	    this.ns6=(this.dom && parseInt(this.ver) >= 5) ?1:0;
	    this.ns4=(document.layers && !this.dom)?1:0;
	    this.bw=(this.ie6 || this.ie5 || this.ie4 || this.ns4 || this.ns6 || this.opera5)
	    return this
	}
	var bw = new lib_bwcheck()
	//Makes crossbrowser object.
	function makeObj(obj){
	    this.evnt=bw.dom? document.getElementById(obj):bw.ie4?document.all[obj]:bw.ns4?document.layers[obj]:0;
	    if(!this.evnt) return false
	    this.css=bw.dom||bw.ie4?this.evnt.style:bw.ns4?this.evnt:0;
	    this.wref=bw.dom||bw.ie4?this.evnt:bw.ns4?this.css.document:0;
	    this.writeIt=b_writeIt;
	    return this
	}
	// A unit of measure that will be added when setting the position of a layer.
	//var px = bw.ns4||window.opera?"":"px";
	function b_writeIt(text){
	    if (bw.ns4){this.wref.write(text);this.wref.close()}
	    else this.wref.innerHTML = text
	}
	//Shows the messages
	var oDesc;
	function popup(divid){
	    if(oDesc = new makeObj(divid)){
		oDesc.css.visibility = "visible"
	    }
	}
	function popout(){ // Hides message
	    if(oDesc) oDesc.css.visibility = "hidden"
	}
	//-->
	</script>
	</head>
	<body>
	<div class=content>
	<br><br>
	<div class=title>'.$this->wsdl->serviceName.'</div>
	<div class=nav>
	<p>View the <a href="'.$PHP_SELF.'?wsdl">WSDL</a> for the service.
	Click on an operation name to view it&apos;s details.</p>
	<ul>';
	foreach($this->wsdl->getOperations() as $op => $data){
	    $b .= "<li><a href='#' onclick=\"popup('$op')\">$op</a></li>";
	    // create hidden div
	    $b .= "<div id='$op' class='hidden'>
	    <a href='#' onclick='popout()'><font color='#ffffff'>Close</font></a><br><br>";
	    foreach($data as $donnie => $marie){
		if($donnie == 'input' || $donnie == 'output'){
		    $b .= "<font color='white'>".ucfirst($donnie).':</font><br>';
		    foreach($marie as $captain => $tenille){
			if($captain == 'parts'){
			    $b .= "&nbsp;&nbsp;$captain:<br>";
                if(is_array($tenille)){
			    foreach($tenille as $joanie => $chachi){
					$b .= "&nbsp;&nbsp;&nbsp;&nbsp;$joanie: $chachi<br>";
			    }
        		}
			} else {
			    $b .= "&nbsp;&nbsp;$captain: $tenille<br>";
			}
		    }
		} else {
		    $b .= "<font color='white'>".ucfirst($donnie).":</font> $marie<br>";
		}
	    }
	}
	$b .= '<ul></div></div></body></html>';
	return $b;
    }

    /**
    * sets up wsdl object
    * this acts as a flag to enable internal WSDL generation
    * NOTE: NOT FUNCTIONAL
    *
    * @param string $serviceName, name of the service
    * @param string $namespace, tns namespace
    */
    function configureWSDL($serviceName,$namespace,$endpoint,$style='rpc'){
		$this->wsdl = new wsdl;
		$this->wsdl->serviceName = $serviceName;
        $this->wsdl->endpoint = $endpoint;
		$this->wsdl->namespaces['tns'] = $namespace;
		$this->wsdl->namespaces['soap'] = 'http://schemas.xmlsoap.org/wsdl/soap/';
		$this->wsdl->namespaces['wsdl'] = 'http://schemas.xmlsoap.org/wsdl/';
        $this->wsdl->bindings[$serviceName.'Binding'] = array(
        	'name'=>$serviceName.'Binding',
            'style'=>$style,
            'portType'=>$serviceName.'PortType');
        $this->wsdl->ports[$serviceName.'Port'] = array(
        	'binding'=>'tns:'.$serviceName.'Binding',
            'location'=>$endpoint,
            'bindingType'=>'http://schemas.xmlsoap.org/wsdl/soap/');
    }
}

?><?php

/**
* parses a WSDL file, allows access to it's data, other utility methods
*
* @author   Dietrich Ayala <dietrich@ganx4.com>
* @access   public
*/
class wsdl extends XMLSchema {
	var $wsdl;
	// define internal arrays of bindings, ports, operations, messages, etc.
    var $message = array();
	var $complexTypes = array();
	var $messages = array();
	var $currentMessage;
	var $currentOperation;
	var $portTypes = array();
	var $currentPortType;
	var $bindings = array();
	var $currentBinding;
	var $ports = array();
	var $currentPort;
	var $opData = array();
	var $status = '';
	var $documentation = false;
    var $endpoint = '';
	// array of wsdl docs to import
	var $import = array();
	// parser vars
	var $parser;
	var $position = 0;
	var $depth = 0;
	var $depth_array = array();

	/**
	* constructor
	*
	* @param    string $wsdl WSDL document URL
	* @access   public
	*/
	function wsdl($wsdl=''){
		$this->wsdl = $wsdl;

		// parse wsdl file
		if($wsdl != ""){
			$this->debug('initial wsdl file: '.$wsdl);
			$this->parseWSDL($wsdl);
		}

		// imports
		if(sizeof($this->import) > 0){
			foreach($this->import as $ns => $url){
				$this->debug('importing wsdl from '.$url);
				$this->parseWSDL($url);
			}
		}

	}

	/**
	* parses the wsdl document
	*
	* @param    string $wsdl path or URL
	* @access   private
	*/
	function parseWSDL($wsdl=''){
		if($wsdl == ''){
        	$this->debug('no wsdl passed to parseWSDL()!!');
			$this->setError('no wsdl passed to parseWSDL()!!');
			return false;
        }

        $this->debug('getting '.$wsdl);
	    if ($fp = @fopen($wsdl,'r')) {
        	$wsdl_string = '';
			while($data = fread($fp, 32768)) {
				$wsdl_string .= $data;
			}
			fclose($fp);
		} else {
			$this->setError('bad path to WSDL file.');
			return false;
		}
	    // Create an XML parser.
	    $this->parser = xml_parser_create();
	    // Set the options for parsing the XML data.
	    //xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	    xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
	    // Set the object for the parser.
	    xml_set_object($this->parser, $this);
	    // Set the element handlers for the parser.
	    xml_set_element_handler($this->parser, 'start_element','end_element');
	    xml_set_character_data_handler($this->parser,'character_data');

	    // Parse the XML file.
	    if(!xml_parse($this->parser,$wsdl_string,true)){
			// Display an error message.
			$errstr = sprintf(
            	'XML error on line %d: %s',
				xml_get_current_line_number($this->parser),
				xml_error_string(xml_get_error_code($this->parser))
				);
			$this->debug('XML parse error: '.$errstr);
			$this->setError('Parser error: '.$errstr);
			return false;
	    }

		xml_parser_free($this->parser);

		// add new data to operation data
		foreach($this->bindings as $binding => $bindingData){
			if(isset($bindingData['operations']) && is_array($bindingData['operations'])){
				foreach($bindingData['operations'] as $operation => $data){
					$this->debug('post-parse data gathering for '.$operation);
		    		$this->bindings[$binding]['operations'][$operation]['input'] = array_merge($this->bindings[$binding]['operations'][$operation]['input'],$this->portTypes[ $bindingData['portType'] ][$operation]['input']);
					$this->bindings[$binding]['operations'][$operation]['output'] = array_merge($this->bindings[$binding]['operations'][$operation]['output'],$this->portTypes[ $bindingData['portType'] ][$operation]['output']);
					$this->bindings[$binding]['operations'][$operation]['input']['parts'] = $this->messages[ $this->bindings[$binding]['operations'][$operation]['input']['message'] ];
					$this->bindings[$binding]['operations'][$operation]['output']['parts'] = $this->messages[ $this->bindings[$binding]['operations'][$operation]['output']['message'] ];
					if(!isset($this->bindings[$binding]['operations'][$operation]['style'])){
						$this->bindings[$binding]['operations'][$operation]['style'] = $bindingData['style'];
					}
					$this->bindings[$binding]['operations'][$operation]['transport'] = $bindingData['transport'];
					$this->bindings[$binding]['operations'][$operation]['documentation'] = isset($this->portTypes[ $bindingData['portType'] ][$operation]['documentation']) ? $this->portTypes[ $bindingData['portType'] ][$operation]['documentation'] : '';
					$this->bindings[$binding]['operations'][$operation]['endpoint'] = $bindingData['endpoint'];
				}
			}
		}
		return true;
	}

	/**
	* start-element handler
	*
	* @param    string $parser XML parser object
	* @param    string $name element name
	* @param    string $attrs associative array of attributes
	* @access   private
	*/
	function start_element($parser, $name, $attrs) {

		if($this->status == 'schema' || ereg('schema$',$name)){
			//$this->debug("startElement for $name ($attrs[name]). status = $this->status (".$this->getLocalPart($name).")");
			$this->status = 'schema';
			$this->schemaStartElement($parser,$name,$attrs);
		} else {
			// position in the total number of elements, starting from 0
			$pos = $this->position++;
			$depth = $this->depth++;
			// set self as current value for this depth
			$this->depth_array[$depth] = $pos;
			$this->message[$pos] = array('cdata'=>'');

			// get element prefix
			if(ereg(':',$name)){
				// get ns prefix
				$prefix = substr($name,0,strpos($name,':'));
                // get ns
                $namespace = isset($this->namespaces[$prefix]) ? $this->namespaces[$prefix] : $this->namespaces['tns'];
				// get unqualified name
				$name = substr(strstr($name,':'),1);
			}

            if(count($attrs) > 0){
        		foreach($attrs as $k => $v){
                    // if ns declarations, add to class level array of valid namespaces
					if(ereg("^xmlns",$k)){
						if($ns_prefix = substr(strrchr($k,':'),1)){
							$this->namespaces[$ns_prefix] = $v;
						} else {
							$this->namespaces['ns'.(count($this->namespaces)+1)] = $v;
						}
						if($v == 'http://www.w3.org/2001/XMLSchema'|| $v == 'http://www.w3.org/1999/XMLSchema'){
							$this->XMLSchemaVersion = $v;
							$this->namespaces['xsi'] = $v.'-instance';
						}
					}//
                    // expand each attribute
                	$k = strpos($k,':') ? $this->expandQname($k) : $k;
                	if($k != 'location' && $k != 'soapAction' && $k != 'namespace'){
                    	$v = strpos($v,':') ? $this->expandQname($v) : $v;
                    }
        			$eAttrs[$k] = $v;
        		}
        		$attrs = $eAttrs;
        	} else {
        		$attrs = array();
        	}

			// find status, register data
			switch($this->status){
				case 'message':
					if($name == 'part'){
						if($attrs['type']){
							$this->debug( "msg ".$this->currentMessage.": found part $attrs[name]: ".implode(',',$attrs));
							$this->messages[$this->currentMessage][$attrs['name']] = $attrs['type'];
						}
						if(isset($attrs['element'])){
							$this->messages[$this->currentMessage][$attrs['name']] = $attrs['element'];
						}
					}
				break;
				case 'portType':
					switch($name){
						case 'operation':
							$this->currentPortOperation = $attrs['name'];
							$this->debug("portType $this->currentPortType operation: $this->currentPortOperation");
							if(isset($attrs['parameterOrder'])){
                            	$this->portTypes[$this->currentPortType][$attrs['name']]['parameterOrder'] = $attrs['parameterOrder'];
                        	}
						break;
						case 'documentation':
							$this->documentation = true;
						break;
						// merge input/output data
						default:
							$m = isset($attrs['message']) ? $this->getLocalPart($attrs['message']) : '';
                            $this->portTypes[$this->currentPortType][$this->currentPortOperation][$name]['message'] = $m;
						break;
					}
				break;
				case 'binding':
					switch($name){
						case 'binding':
							// get ns prefix
							if(isset($attrs['style'])){
								$this->bindings[$this->currentBinding]['prefix'] = $prefix;
							}
							$this->bindings[$this->currentBinding] = array_merge($this->bindings[$this->currentBinding],$attrs);
						break;
						case 'header':
							$this->bindings[$this->currentBinding]['operations'][$this->currentOperation][$this->opStatus]['headers'][] = $attrs;
						break;
						case 'operation':
							if(isset($attrs['soapAction'])){
								$this->bindings[$this->currentBinding]['operations'][$this->currentOperation]['soapAction'] = $attrs['soapAction'];
							}
                            if(isset($attrs['style'])){
                            	$this->bindings[$this->currentBinding]['operations'][$this->currentOperation]['style'] = $attrs['style'];
                            }
							if(isset($attrs['name'])) {
								$this->currentOperation = $attrs['name'];
								$this->debug("current binding operation: $this->currentOperation");
								$this->bindings[$this->currentBinding]['operations'][$this->currentOperation]['name'] = $attrs['name'];
								$this->bindings[$this->currentBinding]['operations'][$this->currentOperation]['binding'] = $this->currentBinding;
								$this->bindings[$this->currentBinding]['operations'][$this->currentOperation]['endpoint'] = isset($this->bindings[$this->currentBinding]['endpoint']) ? $this->bindings[$this->currentBinding]['endpoint'] : '';
							}
						break;
						case 'input':
							$this->opStatus = 'input';
						break;
						case 'output':
							$this->opStatus = 'output';
						break;
						case 'body':
							if(isset($this->bindings[$this->currentBinding]['operations'][$this->currentOperation][$this->opStatus])){
                            	$this->bindings[$this->currentBinding]['operations'][$this->currentOperation][$this->opStatus]= array_merge($this->bindings[$this->currentBinding]['operations'][$this->currentOperation][$this->opStatus],$attrs);
                            } else {
                            	$this->bindings[$this->currentBinding]['operations'][$this->currentOperation][$this->opStatus] = $attrs;
                            }
						break;
					}
				break;
				case 'service':
					switch($name){
						case 'port':
							$this->currentPort = $attrs['name'];
							$this->debug('current port: '.$this->currentPort);
							$this->ports[$this->currentPort]['binding'] = $this->getLocalPart($attrs['binding']);

						break;
						case 'address':
							$this->ports[$this->currentPort]['location'] = $attrs['location'];
							$this->ports[$this->currentPort]['bindingType'] = $namespace;
                            $this->bindings[ $this->ports[$this->currentPort]['binding'] ]['bindingType'] = $namespace;
							$this->bindings[ $this->ports[$this->currentPort]['binding'] ]['endpoint'] = $attrs['location'];
						break;
					}
				break;
			}
			// set status
			switch($name){
				case "import":
					if(isset($attrs['location'])){
						$this->import[$attrs['namespace']] = $attrs['location'];
					}
				break;
				case 'types':
					$this->status = 'schema';
				break;
				case 'message':
					$this->status = 'message';
					$this->messages[$attrs['name']] = array();
					$this->currentMessage = $attrs['name'];
				break;
				case 'portType':
					$this->status = 'portType';
					$this->portTypes[$attrs['name']] = array();
					$this->currentPortType = $attrs['name'];
				break;
				case "binding":
					if(isset($attrs['name'])){
						// get binding name
						if(strpos($attrs['name'],':')){
							$this->currentBinding = $this->getLocalPart($attrs['name']);
						} else {
							$this->currentBinding = $attrs['name'];
						}
						$this->status = 'binding';
						$this->bindings[$this->currentBinding]['portType'] = $this->getLocalPart($attrs['type']);
						$this->debug("current binding: $this->currentBinding of portType: ".$attrs['type']);
					}
				break;
				case 'service':
					$this->serviceName = $attrs['name'];
					$this->status = 'service';
				break;
				case 'definitions':
					foreach ($attrs as $name=>$value) {
						$this->wsdl_info[$name]=$value;
					}
				break;
			}
		}
	}

	/**
	* end-element handler
	*
	* @param    string $parser XML parser object
	* @param    string $name element name
	* @access   private
	*/
	function end_element($parser, $name) {
	    // unset schema status
		if(ereg('types$',$name) || ereg('schema$',$name)){
			$this->status = "";
		}
		if($this->status == 'schema'){
			$this->schemaEndElement($parser, $name);
		} else {
			// bring depth down a notch
			$this->depth--;
		}
		// end documentation
		if($this->documentation){
			$this->portTypes[$this->currentPortType][$this->currentPortOperation]['documentation'] = $this->documentation;
			$this->documentation = false;
		}
	}

	/**
	* element content handler
	*
	* @param    string $parser XML parser object
	* @param    string $data element content
	* @access   private
	*/
	function character_data($parser, $data){
		$pos = isset($this->depth_array[$this->depth]) ? $this->depth_array[$this->depth] : 0;
		if(isset($this->message[$pos]['cdata'])){
        	$this->message[$pos]['cdata'] .= $data;
        }
		if($this->documentation){
			$this->documentation .= $data;
		}
	}


	function getBindingData($binding){
		if(is_array($this->bindings[$binding])){
			return $this->bindings[$binding];
		}
	}

	function getMessageData($operation,$portType,$msgType){
		$name = $this->opData[$operation][$msgType]['message'];
		$this->debug( "getting msgData for $name, using $operation,$portType,$msgType<br>" );
		return $this->messages[$name];
	}

    /**
    * returns an assoc array of operation names => operation data
    * NOTE: currently only supports multiple services of differing binding types
    * This method needs some work
    *
    * @param string $bindingType eg: soap, smtp, dime (only soap is currently supported)
    * @return array
    * @access public
    */
	function getOperations($bindingType = 'soap'){
		if($bindingType == 'soap'){
			$bindingType = 'http://schemas.xmlsoap.org/wsdl/soap/';
		}
		// loop thru ports
		foreach($this->ports as $port => $portData){
			// binding type of port matches parameter
			if($portData['bindingType'] == $bindingType){
				// get binding
				return $this->bindings[ $portData['binding'] ]['operations'];
			}
		}
		return array();
	}

    /**
    * returns an associative array of data necessary for calling an operation
    *
    * @param string $operation, name of operation
    * @param string $bindingType, type of binding eg: soap
	* @return array
    * @access public
    */
	function getOperationData($operation,$bindingType='soap'){
		if($bindingType == 'soap'){
			$bindingType = 'http://schemas.xmlsoap.org/wsdl/soap/';
		}
		// loop thru ports
		foreach($this->ports as $port => $portData){
			// binding type of port matches parameter
			if($portData['bindingType'] == $bindingType){
				// get binding
				foreach($this->bindings[ $portData['binding'] ]['operations'] as $bOperation => $opData){
					if($operation == $bOperation){
						return $opData;
					}
				}
			}
		}
	}

	/**
	* serialize the parsed wsdl
	*
    * @return string, serialization of WSDL
	* @access   public
	*/
	function serialize(){
		$xml = '<?xml version="1.0"?><definitions';
		foreach($this->namespaces as $k => $v){
			$xml .= " xmlns:$k=\"$v\"";
		}
		$xml .= '>';

		// imports
		if(sizeof($this->import) > 0){
			foreach($this->import as $ns => $url){
				$xml .= '<import location="'.$url.'" namespace="'.$ns.'" />';
			}
		}

		// types
		if($this->schema){
			$xml .= '<types>';
			$xml .= $this->serializeSchema();
			$xml .= '</types>';
		}

		// messages
		if(count($this->messages) >= 1){
			foreach($this->messages as $msgName => $msgParts){
				$xml .= '<message name="'.$msgName.'">';
				foreach($msgParts as $partName => $partType){
					$xml .= '<part name="'.$partName.'" type="'.$this->getPrefixFromNamespace($this->getPrefix($partType)).':'.$this->getLocalPart($partType).'" />';
				}
				$xml .= '</message>';
			}
		}
		// portTypes
		if(count($this->portTypes) >= 1){
			foreach($this->portTypes as $portTypeName => $portOperations){
				$xml .= '<portType name="'.$portTypeName.'">';
				foreach($portOperations as $portOperation => $portOpData){
					$xml .= '<operation name="'.$portOperation.'" parameterOrder="'.$portOpData['parameterOrder'].'">';
                    foreach($portOpData as $name => $attrs){
						if($name != 'parameterOrder'){
                        $xml .= '<'.$name;
						if(is_array($attrs)){
							foreach($attrs as $k => $v){
								$xml .= " $k=\"$v\"";
							}
						}
						$xml .= '/>';
                    	}
					}
					$xml .= '</operation>';
				}
				$xml .= '</portType>';
			}
		}
		// bindings
		if(count($this->bindings) >= 1){
			foreach($this->bindings as $bindingName => $attrs){
				$xml .= '<binding name="'.$msgName.'" type="'.$attrs['type'].'">';
				$xml .= "<soap:binding style=\"".$attrs['style'].'" transport="'.$attrs['transport'].'"/>';
				foreach($attrs["operations"] as $opName => $opParts){
					$xml .= '<operation name="'.$opName.'">';
					$xml .= '<soap:operation soapAction="'.$opParts['soapAction'].'"/>';
					$xml .= '<input><soap:body use="'.$opParts['input']['use'].'" namespace="'.$opParts['input']['namespace'].'" encodingStyle="'.$opParts['input']['encodingStyle'].'"/></input>';
					$xml .= '<output><soap:body use="'.$opParts['output']['use'].'" namespace="'.$opParts['output']['namespace'].'" encodingStyle="'.$opParts['output']['encodingStyle'].'"/></output>';
					$xml .= '</operation>';
				}
				$xml .= '</binding>';
			}
		}
		// services
		$xml .= '<service name="'.$this->serviceName.'">';
		if(count($this->ports) >= 1){
			foreach($this->ports as $pName => $attrs){
				$xml .= '<port name="'.$pName.'" binding="'.$attrs['binding'].'">';
				$xml .= '<soap:address location="'.$attrs['location'].'"/>';
				$xml .= '</port>';
			}
		}
		$xml .= '</service>';
		return $xml.'</definitions>';
	}

    /**
	* serialize the parsed wsdl
	*
    * @return string, serialization of WSDL
	* @access   public
	*/
	function serialize2(){
		$xml = '<?xml version="1.0"?><definitions';
		foreach($this->namespaces as $k => $v){
			$xml .= " xmlns:$k=\"$v\"";
		}
		$xml .= '>';

		// imports
		if(sizeof($this->import) > 0){
			foreach($this->import as $ns => $url){
				$xml .= '<import location="'.$url.'" namespace="'.$ns.'" />';
			}
		}

		// types
		if($this->schema){
			$xml .= '<types>';
			$xml .= $this->serializeSchema();
			$xml .= '</types>';
		}

		// messages
		if(count($this->messages) >= 1){
			foreach($this->messages as $msgName => $msgParts){
				$xml .= '<message name="'.$msgName.'">';
				foreach($msgParts as $partName => $partType){
                	//print 'serializing '.$partType.', sv: '.$this->XMLSchemaVersion.'<br>';
                	if(strpos(':',$partType)){
                    	$typePrefix = $this->getPrefixFromNamespace($this->getPrefix($partType));
                    } elseif(isset($this->typemap[$this->namespaces['xsd']][$partType])){
                    	print 'checking typemap: '.$this->XMLSchemaVersion.'<br>';
                        $typePrefix = 'xsd';
                    } else {
                        foreach($this->typemap as $ns => $types){
                          	if(isset($types[$partType])){
                                $typePrefix = $this->getPrefixFromNamespace($ns);
                            }
                        }
                        if(!isset($typePrefix)){
                   	    	die("$partType has no namespace!");
                    	}
                    }
					$xml .= '<part name="'.$partName.'" type="'.$typePrefix.':'.$this->getLocalPart($partType).'" />';
				}
				$xml .= '</message>';
			}
		}

		// bindings
		if(count($this->bindings) >= 1){
			foreach($this->bindings as $bindingName => $attrs){

                $binding_xml .= '<binding name="'.$msgName.'" type="'.$attrs['type'].'">';
				$binding_xml .= "<soap:binding style=\"".$attrs['style'].'" transport="'.$attrs['transport'].'"/>';
				$portType_xml .= '<portType name="'.$portTypeName.'">';
                foreach($attrs["operations"] as $opName => $opParts){
					$binding_xml .= '<operation name="'.$opName.'">';
					$binding_xml .= '<soap:operation soapAction="'.$opParts['soapAction'].'"/>';
					$binding_xml .= '<input><soap:body use="'.$opParts['input']['use'].'" namespace="'.$opParts['input']['namespace'].'" encodingStyle="'.$opParts['input']['encodingStyle'].'"/></input>';
					$binding_xml .= '<output><soap:body use="'.$opParts['output']['use'].'" namespace="'.$opParts['output']['namespace'].'" encodingStyle="'.$opParts['output']['encodingStyle'].'"/></output>';
					$binding_xml .= '</operation>';

                    $portType_xml .= '<operation name="'.$opParts['name'].'"';
                    if(isset($opParts['parameterOrder'])){
                    	$portType_xml .= ' parameterOrder="'.$opParts['parameterOrder'].'"';
                    }
                    $portType_xml .= '>';
                    $portType_xml .= '<input message="'.$opParts['input']['message'].'"/>';
                    $portType_xml .= '<output message="'.$opParts['output']['message'].'"/>';
                    $portType_xml .= '</operation>';
				}
                $portType_xml .= '</portType>';
				$binding_xml .= '</binding>';

			}
            $xml .= $portType_xml.$binding_xml;
		}
		// services
		$xml .= '<service name="'.$this->serviceName.'">';
		if(count($this->ports) >= 1){
			foreach($this->ports as $pName => $attrs){
				$xml .= '<port name="'.$pName.'" binding="'.$attrs['binding'].'">';
				$xml .= '<soap:address location="'.$attrs['location'].'"/>';
				$xml .= '</port>';
			}
		}
		$xml .= '</service>';
		return $xml.'</definitions>';
	}

	/**
	* serialize a PHP value according to a WSDL message definition
	*
    * TODO
	* - multi-ref serialization
	* - validate PHP values against type definitions, return errors if invalid
    *
	* @param	string type name
	* @param	mixed param value
	* @return	mixed new param or false if initial value didn't validate
	*/
	function serializeRPCParameters($operation,$direction,$parameters){
		if($direction != 'input' && $direction != 'output'){
	    	$this->setError('The value of the \$direction argument needs to be either "input" or "output"');
			return false;
	    }
		if(!$opData = $this->getOperationData($operation)){
        	$this->setError('Unable to retrieve WSDL data for operation: '.$operation);
			return false;
		}
		$this->debug( 'in serializeRPCParameters with xml schema version '.$this->XMLSchemaVersion);
		// set input params
        $xml = '';
		if(sizeof($opData[$direction]['parts']) > 0){
        	$this->debug('got '.count($opData[$direction]['parts']).' part(s)');
			foreach($opData[$direction]['parts'] as $name => $type){
            	if(isset($parameters[$name])){
                	$xml .= $this->serializeType($name,$type,$parameters[$name]);
                } else {
					$xml .= $this->serializeType($name,$type,array_shift($parameters));
                }
			}
		}
		return $xml;
	}

    /**
    * serializes a PHP value according a given type definition
    *
    * @param string $name, name of type
    * @param string $type, type of type, heh
    * @param mixed $value, a native PHP value
    * @return string serialization
    * @access public
    */
    function serializeType($name,$type,$value){
    	$contents = '';
    	$this->debug("in serializeType: $name, $type, $value");
		if(strpos($type,':')){
			$uqType = substr($type,strrpos($type,':')+1);
	    	$ns = substr($type,0,strrpos($type,':'));
	    	$this->debug("got a prefixed type: $uqType, $ns");
	    	if($ns == $this->XMLSchemaVersion){
	    		if($uqType == 'boolean' && !$value){
					$value = 0;
				} elseif($uqType == 'boolean'){
					$value = 1;
				}
				if($this->charencoding && $uqType == 'string' && gettype($value) == 'string'){
					$value = htmlspecialchars($value);
				}
				// it's a scalar
				return "<$name xsi:type=\"".$this->getPrefixFromNamespace($this->XMLSchemaVersion).":$uqType\">$value</$name>\n";
	    	}
		} else {
			$uqType = $type;
		}
		$typeDef = $this->getTypeDef($uqType);
        foreach($typeDef as $k => $v){
        	$this->debug("typedef, $k: $v");
        }
		$phpType = $typeDef['phpType'];
		$this->debug("serializeType: uqType: $uqType, ns: $ns, phptype: $phpType, arrayType: ".$typeDef['arrayType']);
		// if php type == struct, map value to the <all> element names
		if($phpType == 'struct'){
	    	$xml = "<$name xsi:type=\"".$this->getPrefixFromNamespace($ns).":$uqType\">\n";
	    	if(is_array($this->complexTypes[$uqType]['elements'])){
				foreach($this->complexTypes[$uqType]['elements'] as $eName => $attrs){
					// get value
					if(isset($value[$eName])){
						$v = $value[$eName];
					} elseif(is_array($value)) {
						$v = array_shift($value);
					}
					if(!isset($attrs['type'])){
						$xml .= $this->serializeType($eName,$attrs['name'],$v);
					} else {
						$this->debug("calling serialize_val() for $eName, $v, ".$this->getLocalPart($attrs['type']));
						$xml .= $this->serialize_val($v,$eName,$this->getLocalPart($attrs['type']),null,$this->getNamespaceFromPrefix($this->getPrefix($attrs['type'])));
					}
				}
	    	}
	    	$xml .= "</$name>\n";
		} elseif($phpType == 'array'){
			$rows = sizeof($value);
	    	if(isset($typeDef['multidimensional'])){
	    		$nv = array();
				foreach($value as $v){
					$cols = ','.sizeof($v);
		    		$nv = array_merge($nv,$v);
				}
				$value = $nv;
            } else {
            	$cols = '';
            }
			if(is_array($value) && sizeof($value) >= 1){
	    		foreach($value as $k => $v){
					if(strpos($typeDef['arrayType'],':')){
						$contents .= $this->serializeType('item',$typeDef['arrayType'],$v);
					} else {
						$contents .= $this->serialize_val($v,'item',$typeDef['arrayType'],null,$this->XMLSchemaVersion);
					}
	    		}
			}
			$xml = "<$name xsi:type=\"".$this->getPrefixFromNamespace('http://schemas.xmlsoap.org/soap/encoding/').':Array" '.
			$this->getPrefixFromNamespace('http://schemas.xmlsoap.org/soap/encoding/')
			.':arrayType="'
			.$this->getPrefixFromNamespace($this->getPrefix($typeDef['arrayType']))
			.":".$this->getLocalPart($typeDef['arrayType'])."[$rows$cols]\">\n"
			.$contents
			."</$name>\n";
		}
    	return $xml;
	}

    /**
	* register a service with the server
	*
	* @param    string $methodname
	* @param    string $in assoc array of input values: key = param name, value = param type
	* @param    string $out assoc array of output values: key = param name, value = param type
	* @param	string $namespace
	* @param	string $soapaction
	* @param	string $style (rpc|literal)
	* @access   public
	*/
	function addOperation($name,$in=false,$out=false,$namespace=false,$soapaction=false,$style='rpc',$use='encoded',$documentation=''){
        if($style == 'rpc' && $use == 'encoded'){
        	$encodingStyle = 'http://schemas.xmlsoap.org/soap/encoding/';
        } else {
        	$encodingStyle = '';
        }

		// get binding
        $this->bindings[ $this->serviceName.'Binding' ]['operations'][$name] =
        array(
        'name' => $name,
        'binding' => $this->serviceName.'Binding',
        'endpoint' => $this->endpoint,
        'soapAction' => $soapaction,
        'style' => $style,
        'input' => array(
            'use' => $use,
            'namespace' => $namespace,
            'encodingStyle' => $encodingStyle,
            'message' => $name.'Request',
            'parts' => $in),
        'output' => array(
            'use' => $use,
            'namespace' => $namespace,
            'encodingStyle' => $encodingStyle,
            'message' => $name.'Response',
            'parts' => $out),
        'namespace' => $namespace,
        'transport' => 'http://schemas.xmlsoap.org/soap/http',
        'documentation' => $documentation);
        // add portTypes
        // add messages
        if($in){
        	foreach($in as $pName => $pType){
        		$this->messages[$name.'Request'][$pName] = $pType;
        	}
        }
        if($out){
        	foreach($out as $pName => $pType){
        		$this->messages[$name.'Response'][$pName] = $pType;
        	}
        }
        return true;
	}
}

?><?php

/**
*
* soap_parser class parses SOAP XML messages into native PHP values
*
* @author   Dietrich Ayala <dietrich@ganx4.com>
* @version  v 0.6.2
* @access   public
*/
class soap_parser extends nusoap_base {

	var $xml = '';
	var $xml_encoding = '';
	var $method = '';
	var $root_struct = '';
	var $root_struct_name = '';
	var $root_header = '';
    var $document = '';
	// determines where in the message we are (envelope,header,body,method)
	var $status = '';
	var $position = 0;
	var $depth = 0;
	var $default_namespace = '';
	var $namespaces = array();
	var $message = array();
    var $parent = '';
	var $fault = false;
	var $fault_code = '';
	var $fault_str = '';
	var $fault_detail = '';
	var $depth_array = array();
	var $debug_flag = true;
	var $soapresponse = NULL;
	var $responseHeaders = '';
	// for multiref parsing:
	// array of id => pos
	var $ids = array();
	// array of id => hrefs => pos
	var $multirefs = array();

	/**
	* constructor
	*
	* @param    string $xml SOAP message
	* @param    string $encoding character encoding scheme of message
	* @access   public
	*/
	function soap_parser($xml,$encoding='UTF-8',$method=''){
		$this->xml = $xml;
		$this->xml_encoding = $encoding;
		$this->method = $method;

		// Check whether content has been read.
	if(!empty($xml)){
			$this->debug('Entering soap_parser()');
			// Create an XML parser.
			$this->parser = xml_parser_create($this->xml_encoding);
			// Set the options for parsing the XML data.
			//xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
			xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
			// Set the object for the parser.
			xml_set_object($this->parser, $this);
			// Set the element handlers for the parser.
			xml_set_element_handler($this->parser, 'start_element','end_element');
			xml_set_character_data_handler($this->parser,'character_data');

			// Parse the XML file.
			if(!xml_parse($this->parser,$xml,true)){
			    // Display an error message.
			    $err = sprintf('XML error on line %d: %s',
			    xml_get_current_line_number($this->parser),
			    xml_error_string(xml_get_error_code($this->parser)));
				$this->debug('parse error: '.$err);
				$this->errstr = $err;
		} else {
				$this->debug('parsed successfully, found root struct: '.$this->root_struct.' of name '.$this->root_struct_name);
				// get final value
				$this->soapresponse = $this->message[$this->root_struct]['result'];
				// get header value
				if($this->root_header != ""){
					$this->responseHeaders = $this->message[$this->root_header]['result'];
				}
			}
			xml_parser_free($this->parser);
	} else {
			$this->debug('xml was empty, didn\'t parse!');
			$this->errstr = 'xml was empty, didn\'t parse!';
		}
	}

	/**
	* start-element handler
	*
	* @param    string $parser XML parser object
	* @param    string $name element name
	* @param    string $attrs associative array of attributes
	* @access   private
	*/
	function start_element($parser, $name, $attrs) {
		// position in a total number of elements, starting from 0
		// update class level pos
		$pos = $this->position++;
		// and set mine
		$this->message[$pos] = array('pos' => $pos,'children'=>'','cdata'=>'');
		// depth = how many levels removed from root?
		// set mine as current global depth and increment global depth value
		$this->message[$pos]['depth'] = $this->depth++;

		// else add self as child to whoever the current parent is
		if($pos != 0){
			$this->message[$this->parent]['children'] .= '|'.$pos;
		}
		// set my parent
		$this->message[$pos]['parent'] = $this->parent;
		// set self as current parent
		$this->parent = $pos;
		// set self as current value for this depth
		$this->depth_array[$this->depth] = $pos;
		// get element prefix
		if(strpos($name,':')){
			// get ns prefix
			$prefix = substr($name,0,strpos($name,':'));
			// get unqualified name
			$name = substr(strstr($name,':'),1);
		}
		// set status
		if($name == 'Envelope'){
			$this->status = 'envelope';
		} elseif($name == 'Header'){
			$this->root_header = $pos;
			$this->status = 'header';
		} elseif($name == 'Body'){
			$this->status = 'body';
			$this->body_position = $pos;
		// set method
		} elseif($this->status == 'body' && $pos == ($this->body_position+1)){
			$this->status = 'method';
			$this->root_struct_name = $name;
			$this->root_struct = $pos;
			$this->message[$pos]['type'] = 'struct';
			$this->debug("found root struct $this->root_struct_name, pos $this->root_struct");
		}
		// set my status
		$this->message[$pos]['status'] = $this->status;
		// set name
		$this->message[$pos]['name'] = htmlspecialchars($name);
		// set attrs
		$this->message[$pos]['attrs'] = $attrs;

		// loop through atts, logging ns and type declarations
        $attstr = '';
		foreach($attrs as $key => $value){
        	$key_prefix = $this->getPrefix($key);
			$key_localpart = $this->getLocalPart($key);
			// if ns declarations, add to class level array of valid namespaces
            if($key_prefix == 'xmlns'){
				if(ereg('^http://www.w3.org/[0-9]{4}/XMLSchema$',$value)){
					$this->XMLSchemaVersion = $value;
					$this->namespaces['xsd'] = $this->XMLSchemaVersion;
					$this->namespaces['xsi'] = $this->XMLSchemaVersion.'-instance';
				}
                $this->namespaces[$key_localpart] = $value;
				// set method namespace
				if($name == $this->root_struct_name){
					$this->methodNamespace = $value;
				}
			// if it's a type declaration, set type
            } elseif($key_localpart == 'type'){
            	$value_prefix = $this->getPrefix($value);
                $value_localpart = $this->getLocalPart($value);
				$this->message[$pos]['type'] = $value_localpart;
				$this->message[$pos]['typePrefix'] = $value_prefix;
                if(isset($this->namespaces[$value_prefix])){
                	$this->message[$pos]['type_namespace'] = $this->namespaces[$value_prefix];
                }
				// should do something here with the namespace of specified type?
			} elseif($key_localpart == 'arrayType'){
				$this->message[$pos]['type'] = 'array';
				/* do arrayType ereg here
				[1]    arrayTypeValue    ::=    atype asize
				[2]    atype    ::=    QName rank*
				[3]    rank    ::=    '[' (',')* ']'
				[4]    asize    ::=    '[' length~ ']'
				[5]    length    ::=    nextDimension* Digit+
				[6]    nextDimension    ::=    Digit+ ','
				*/
				$expr = '([A-Za-z0-9_]+):([A-Za-z]+[A-Za-z0-9_]+)\[([0-9]+),?([0-9]*)\]';
				if(ereg($expr,$value,$regs)){
					$this->message[$pos]['typePrefix'] = $regs[1];
					$this->message[$pos]['arraySize'] = $regs[3];
					$this->message[$pos]['arrayCols'] = $regs[4];
				}
			}
			// log id
			if($key == 'id'){
				$this->ids[$value] = $pos;
			}
			// root
			if($key_localpart == 'root' && $value == 1){
				$this->status = 'method';
				$this->root_struct_name = $name;
				$this->root_struct = $pos;
				$this->debug("found root struct $this->root_struct_name, pos $pos");
			}
            // for doclit
            $attstr .= " $key=\"$value\"";
		}
        // get namespace - must be done after namespace atts are processed
		if(isset($prefix)){
			$this->message[$pos]['namespace'] = $this->namespaces[$prefix];
			$this->default_namespace = $this->namespaces[$prefix];
		} else {
			$this->message[$pos]['namespace'] = $this->default_namespace;
		}
        if($this->status == 'header'){
        	$this->responseHeaders .= "<$name$attstr>";
        } elseif($this->root_struct_name != ''){
        	$this->document .= "<$name$attstr>";
        }
	}

	/**
	* end-element handler
	*
	* @param    string $parser XML parser object
	* @param    string $name element name
	* @access   private
	*/
	function end_element($parser, $name) {
		// position of current element is equal to the last value left in depth_array for my depth
		$pos = $this->depth_array[$this->depth--];

        // get element prefix
		if(strpos($name,':')){
			// get ns prefix
			$prefix = substr($name,0,strpos($name,':'));
			// get unqualified name
			$name = substr(strstr($name,':'),1);
		}

		// build to native type
		if(isset($this->body_position) && $pos > $this->body_position){
			// deal w/ multirefs
			if(isset($this->message[$pos]['attrs']['href'])){
				// get id
				$id = substr($this->message[$pos]['attrs']['href'],1);
				// add placeholder to href array
				$this->multirefs[$id][$pos] = "placeholder";
				// add set a reference to it as the result value
				$this->message[$pos]['result'] =& $this->multirefs[$id][$pos];
            // build complex values
			} elseif($this->message[$pos]['children'] != ""){
				$this->message[$pos]['result'] = $this->buildVal($pos);
			} else {
            	$this->debug('adding data for scalar value '.$this->message[$pos]['name'].' of value '.$this->message[$pos]['cdata']);
				if(is_numeric($this->message[$pos]['cdata']) ){
                	if( strpos($this->message[$pos]['cdata'],'.') ){
                		$this->message[$pos]['result'] = doubleval($this->message[$pos]['cdata']);
                    } else {
                    	$this->message[$pos]['result'] = intval($this->message[$pos]['cdata']);
                    }
                } else {
                	$this->message[$pos]['result'] = $this->message[$pos]['cdata'];
                }
			}
		}

		// switch status
		if($pos == $this->root_struct){
			$this->status = 'body';
		} elseif($name == 'Body'){
			$this->status = 'header';
		 } elseif($name == 'Header'){
			$this->status = 'envelope';
		} elseif($name == 'Envelope'){
			// resolve hrefs/ids
			if(sizeof($this->multirefs) > 0){
				foreach($this->multirefs as $id => $hrefs){
					$this->debug('resolving multirefs for id: '.$id);
					foreach($hrefs as $refPos => $ref){
						$this->debug('resolving href at pos '.$refPos);
						$this->multirefs[$id][$refPos] = $this->buildval($this->ids[$id]);
					}
				}
			}
		}
		// set parent back to my parent
		$this->parent = $this->message[$pos]['parent'];
        // for doclit
        if($this->status == 'header'){
        	$this->responseHeaders .= "</$name>";
        } elseif($pos >= $this->root_struct){
        	$this->document .= "</$name>";
        }
	}

	/**
	* element content handler
	*
	* @param    string $parser XML parser object
	* @param    string $data element content
	* @access   private
	*/
	function character_data($parser, $data){
		$pos = $this->depth_array[$this->depth];
        $this->message[$pos]['cdata'] .= $data;
        // for doclit
        if($this->status == 'header'){
        	$this->responseHeaders .= $data;
        } else {
        	$this->document .= $data;
        }
	}

	/**
	* get the parsed message
	*
	* @return	mixed
	* @access   public
	*/
	function get_response(){
		return $this->soapresponse;
	}

	/**
	* get the parsed headers
	*
	* @return	string XML or empty if no headers
	* @access   public
	*/
	function getHeaders(){
	    return $this->responseHeaders;
	}

	/**
	* decodes entities
	*
	* @param    string $text string to translate
	* @access   private
	*/
	function decode_entities($text){
		foreach($this->entities as $entity => $encoded){
			$text = str_replace($encoded,$entity,$text);
		}
		return $text;
	}

	/**
	* builds response structures for compound values (arrays/structs)
	*
	* @param    string $pos position in node tree
	* @access   private
	*/
	function buildVal($pos){
		$this->debug('inside buildVal() for '.$this->message[$pos]['name']."(pos $pos) of type ".$this->message[$pos]['type']);
		// if there are children...
		if($this->message[$pos]['children'] != ''){
			$children = explode('|',$this->message[$pos]['children']);
			array_shift($children); // knock off empty
			// md array
			if(isset($this->message[$pos]['arrayCols']) && $this->message[$pos]['arrayCols'] != ''){
            	$r=0; // rowcount
            	$c=0; // colcount
            	foreach($children as $child_pos){
					$this->debug("got an MD array element: $r, $c");
					$params[$r][] = $this->message[$child_pos]['result'];
				    $c++;
				    if($c == $this->message[$pos]['arrayCols']){
				    	$c = 0;
						$r++;
				    }
                }
            // array
			} elseif($this->message[$pos]['type'] == 'array' || $this->message[$pos]['type'] == 'Array'){
                $this->debug('adding array '.$this->message[$pos]['name']);
                foreach($children as $child_pos){
                	$params[] = $this->message[$child_pos]['result'];
                }
            // apache Map type: java hashtable
            } elseif($this->message[$pos]['type'] == 'Map' && $this->message[$pos]['type_namespace'] == 'http://xml.apache.org/xml-soap'){
                foreach($children as $child_pos){
                	$kv = explode("|",$this->message[$child_pos]['children']);
                   	$params[$this->message[$kv[1]]['result']] = $this->message[$kv[2]]['result'];
                }
            // generic compound type
            //} elseif($this->message[$pos]['type'] == 'SOAPStruct' || $this->message[$pos]['type'] == 'struct') {
            } else {
            	foreach($children as $child_pos){
				    $params[$this->message[$child_pos]['name']] =& $this->message[$child_pos]['result'];
                }
			}
			return is_array($params) ? $params : array();
		} else {
        	$this->debug('no children');
            if(strpos($this->message[$pos]['cdata'],'&')){
		    	return  strtr($this->message[$pos]['cdata'],array_flip($this->entities));
            } else {
            	return $this->message[$pos]['cdata'];
            }
		}
	}
}

?><?php

/**
*
* soapclient higher level class for easy usage.
*
* usage:
*
* // instantiate client with server info
* $soapclient = new soapclient( string path [ ,boolean wsdl] );
*
* // call method, get results
* echo $soapclient->call( string methodname [ ,array parameters] );
*
* // bye bye client
* unset($soapclient);
*
* @author   Dietrich Ayala <dietrich@ganx4.com>
* @version  v 0.6.2
* @access   public
*/
class soapclient extends nusoap_base  {

	var $username = '';
	var $password = '';
	var $requestHeaders = false;
	var $responseHeaders;
	var $endpoint;
	var $error_str = false;
    var $proxyhost = '';
    var $proxyport = '';
    var $xml_encoding = '';
	var $gzip = false;
	var $timeout = 0;
	/**
	* fault related variables
	*
	* @var      fault
	* @var      faultcode
	* @var      faultstring
	* @var      faultdetail
	* @access   public
	*/
	var $fault, $faultcode, $faultstring, $faultdetail;

	/**
	* constructor
	*
	* @param    string $endpoint SOAP server or WSDL URL
	* @param    string $wsdl optional, set to true if using WSDL
	* @param	int $portName optional portName in WSDL document
	* @access   public
	*/
	function soapclient($endpoint,$wsdl=''){
		$this->endpoint = $endpoint;

		// make values
		if($wsdl != ''){
			$this->endpointType = 'wsdl';
			$this->wsdlFile = $this->endpoint;

			// instantiate wsdl object and parse wsdl file
			$this->debug('instantiating wsdl class with doc: '.$endpoint);
			$this->wsdl = & new wsdl($this->wsdlFile);
			// catch errors
			if($errstr = $this->wsdl->getError()){
				$this->debug('got wsdl error: '.$errstr);
				$this->debug("wsdl debug: \n".$this->wsdl->debug_str);
				$this->setError('wsdl error: '.$errstr);
			} elseif($this->operations = $this->wsdl->getOperations()){
				$this->debug( 'got '.count($this->operations).' operations from wsdl '.$this->wsdlFile);
			}
		}
	}

	/**
	* calls method, returns PHP native type
	*
	* @param    string $method SOAP server URL or path
	* @param    array $params array of parameters, can be associative or not
	* @param	string $namespace optional method namespace
	* @param	string $soapAction optional SOAPAction value
	* @param	boolean $headers optional array of soapval objects for headers
	* @return	mixed
	* @access   public
	*/
	function call($operation,$params=array(),$namespace='',$soapAction='',$headers=false){
		$this->operation = $operation;
		$this->fault = false;
		$this->error_str = '';
		$this->request = '';
		$this->response = '';
		$this->faultstring = '';
		$this->faultcode = '';
		$this->opData = array();
		// if wsdl, get operation data and process parameters
		if($this->endpointType == 'wsdl' && $opData = $this->getOperationData($operation)){

			$this->opData = $opData;
			$soapAction = $opData['soapAction'];
			$this->endpoint = $opData['endpoint'];
			$namespace = $opData['input']['namespace'];
			$style = $opData['style'];
			// add ns to ns array
			if($namespace != '' && !isset($this->wsdl->namespaces[$namespace])){
				$this->wsdl->namespaces['nu'] = $namespace;
			} else {
            	$namespace = 'http://testuri.org';
                $this->wsdl->namespaces['nu'] = $namespace;
            }
			// serialize payload
			if($style == 'rpc'){
				$payload = "<".$this->wsdl->getPrefixFromNamespace($namespace).":$operation>\n".
				$this->wsdl->serializeRPCParameters($operation,'input',$params).
				'</'.$this->wsdl->getPrefixFromNamespace($namespace).":$operation>\n";
			} elseif($opData['input']['use'] == 'literal') {
				$payload .= array_shift($params);
			}
			// serialize envelope
			$soapmsg = $this->serializeEnvelope($payload,$this->requestHeaders,$this->wsdl->usedNamespaces);
			$this->debug("wsdl debug: \n".$this->wsdl->debug_str);
		} elseif($this->endpointType == 'wsdl') {
			$this->setError( 'operation '.$operation.' not present.');
			$this->debug("operation '$operation' not present.");
			$this->debug("wsdl debug: \n".$this->wsdl->debug_str);
			return false;
		// no wsdl
		} else {
			// make message
			if(!$style){
				$style = 'rpc';
			}
            if($namespace == ''){
            	$namespace = 'http://testuri.org';
                $this->wsdl->namespaces['nu'] = $namespace;
            }
			// serialize envelope
			foreach($params as $k => $v){
				$payload .= $this->serialize_val($v,$k);
			}
			$payload = "<nu:$operation xmlns:nu=\"$namespace\">\n".$payload."</nu:$operation>\n";
			$soapmsg = $this->serializeEnvelope($payload,$this->requestHeaders);
		}
		$this->debug("endpoint: $this->endpoint, soapAction: $soapAction, namespace: $namespace");
		// send
		$this->debug('sending msg (len: '.strlen($soapmsg).") w/ soapaction '$soapAction'...");
		$return = $this->send($soapmsg,$soapAction,$this->timeout);
		if($errstr = $this->getError()){
			$this->debug('Error: '.$errstr);
			return false;
		} else {
			$this->return = $return;
			$this->debug('sent message successfully and got a(n) '.gettype($return).' back');
			
			// fault?
			if(is_array($return) && isset($return['faultcode'])){
				$this->debug('got fault');
				$this->setError($return['faultcode'].': '.$return['faultstring']);
				$this->fault = true;
				foreach($return as $k => $v){
					$this->$k = $v;
					$this->debug("$k = $v<br>");
				}
				return $return;
			} else {
				// array of return values
				if(is_array($return)){
					// multiple 'out' parameters
					if(sizeof($return) > 1){
						return $return;
					}
					// single 'out' parameter
					return array_shift($return);
				// nothing returned (ie, echoVoid)
				} else {
					return "";
				}
			}
		}
	}

	/**
	* get available data pertaining to an operation
	*
	* @param    string $operation operation name
	* @return	array array of data pertaining to the operation
	* @access   public
	*/
	function getOperationData($operation){
		if(isset($this->operations[$operation])){
			return $this->operations[$operation];
		}
	}

    /**
    * send the SOAP message
    *
    * Note: if the operation has multiple return values
    * the return value of this method will be an array
    * of those values.
    *
	* @param    string $msg a SOAPx4 soapmsg object
	* @param    string $soapaction SOAPAction value
	* @param    integer $timeout set timeout in seconds
	* @return	mixed native PHP types.
	* @access   private
	*/
	function send($msg, $soapaction = '', $timeout=0) {
		// detect transport
		switch(true){
			// http(s)
			case ereg('^http',$this->endpoint):
				$this->debug('transporting via HTTP');
				$http = new soap_transport_http($this->endpoint);
				$http->setSOAPAction($soapaction);
				if($this->proxyhost && $this->proxyport){
					$http->setProxy($this->proxyhost,$this->proxyport);
				}
                if($this->username != '' && $this->password != '') {
					$http->setCredentials($this->username,$this->password);
				}
				if($this->gzip){
					$http->gzip = true;
				}
				$this->debug('sending message, length: '.strlen($msg));
				if(ereg('^http:',$this->endpoint)){
					$response = $http->send($msg,$timeout);
                    $this->request = $http->outgoing_payload;
					$this->response = $http->incoming_payload;
				} elseif(ereg('^https',$this->endpoint)){
					if (!extension_loaded('curl')) {
				    	$this->setError('CURL Extension is required for HTTPS');
						return false;
					}
					$response = $http->sendHTTPS($msg,$timeout);
                    $this->request = $http->outgoing_payload;
					$this->response = $http->incoming_payload;
				}
				$this->debug("transport debug data...\n".$http->debug_str);
				if($err = $http->getError()){
					$this->setError('HTTP Error: '.$err);
					return false;
				}
				$this->debug('got response, length: '.strlen($response));
				return $this->parseResponse($response);
			break;
			default:
				$this->setError('no transport found, or selected transport is not yet supported!');
			return false;
			break;
		}
	}

	/**
	* processes SOAP message returned from server
	*
	* @param	string unprocessed response data from server
	* @return	mixed value of the message, decoded into a PHP type
	* @access   private
	*/
    function parseResponse($data) {
		$this->debug('Entering parseResponse(), about to create soap_parser instance');
		$parser = & new soap_parser($data,$this->xml_encoding,$this->operation);
		// if parse errors
		if($errstr = $parser->getError()){
			$this->setError( $errstr);
			// destroy the parser object
			unset($parser);
			return false;
		} else {
			// get SOAP headers
			$this->responseHeaders = $parser->getHeaders();
			// get decoded message
			$return = $parser->get_response();
			// add parser debug data to our debug
			$this->debug($parser->debug_str);
            // add document for doclit support
            $this->document = $parser->document;
			// destroy the parser object
			unset($parser);
			// return decode message
			return $return;
		}
	 }

	/**
	* set the SOAP headers
	*
	* @param	$headers string XML
	* @access   public
	*/
	function setHeaders($headers){
		$this->requestHeaders = $headers;
	}

	/**
	* get the response headers
	*
	* @return	mixed object SOAPx4 soapval object or empty if no headers
	* @access   public
	*/
	function getHeaders(){
	    if($this->responseHeaders != '') {
			return $this->responseHeaders;
	    }
	}

	/**
	* set proxy info here
	*
	* @param    string $proxyhost
	* @param    string $proxyport
	* @access   public
	*/
	function setHTTPProxy($proxyhost, $proxyport) {
		$this->proxyhost = $proxyhost;
		$this->proxyport = $proxyport;
	}

	/**
	* if authenticating, set user credentials here
	*
	* @param    string $username
	* @param    string $password
	* @access   public
	*/
	function setCredentials($username, $password) {
		$this->username = $username;
		$this->password = $password;
	}

	/**
	* dynamically creates proxy class, allowing user to directly call methods from wsdl
	*
	* @return   object soap_proxy object
	* @access   public
	*/
	function getProxy(){
		foreach($this->operations as $operation => $opData){
			if($operation != ''){
				// create param string
				if(sizeof($opData['input']['parts']) > 0){
					foreach($opData['input']['parts'] as $name => $type){
						$paramStr .= "\$$name,";
					}
					$paramStr = substr($paramStr,0,strlen($paramStr)-1);
				}
				$evalStr .= "function $operation ($paramStr){
					// load params into array
					\$params = array($paramStr);
					return \$this->call('$operation',\$params,'".$opData['namespace']."','".$opData['soapAction']."');
				}";
				unset($paramStr);
			}
		}
		$evalStr = 'class soap_proxy extends soapclient {
				'.$evalStr.'
			}';
		//print "proxy class:<pre>$evalStr</pre>";
		// eval the class
		eval($evalStr);
		// instantiate proxy object
		$proxy = new soap_proxy("");
		// transfer current wsdl data to the proxy thereby avoiding parsing the wsdl twice
		$proxy->endpointType = 'wsdl';
		$proxy->wsdlFile = $this->wsdlFile;
		$proxy->wsdl = $this->wsdl;
		$proxy->operations = $this->operations;
		return $proxy;
	}
}

?>