<?php
/*
 *	$Id$
 *
 *	WSDL client sample.
 *
 *	Service: WSDL
 *	Payload: rpc/encoded
 *	Transport: http
 *	Authentication: none
 */
require_once('../lib/nusoap.php');
$proxyhost = isset($_POST['proxyhost']) ? $_POST['proxyhost'] : '';
$proxyport = isset($_POST['proxyport']) ? $_POST['proxyport'] : '';
$proxyusername = isset($_POST['proxyusername']) ? $_POST['proxyusername'] : '';
$proxypassword = isset($_POST['proxypassword']) ? $_POST['proxypassword'] : '';
$client = new soapclient("http://webservices.amazon.com/AWSECommerceService/AWSECommerceService.wsdl", true,
						$proxyhost, $proxyport, $proxyusername, $proxypassword);
$err = $client->getError();
if ($err) {
	echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
}

$client->soap_defencoding = 'UTF-8';

$itemSearchRequest = array(
	'BrowseNode' => '53',
	'ItemPage' => 1,
//	'ResponseGroup' => array('Request', 'Small'),
	'SearchIndex' => 'Books',
	'Sort' => 'salesrank'
);

echo 'You must set your own Amazon E-Commerce Services subscription id in the source code to run this client!'; exit();
$itemSearch = array(
	'SubscriptionId' => 'Your AWS subscription id',
//	'AssociateTag' => '',
//	'Validate' => '',
//	'XMLEscaping' => '',
//	'Shared' => $itemSearchRequest,
	'Request' => array($itemSearchRequest)
);

$result = $client->call('ItemSearch', array('body' => $itemSearch));
// Check for a fault
if ($client->fault) {
	echo '<h2>Fault</h2><pre>';
	print_r($result);
	echo '</pre>';
} else {
	// Check for errors
	$err = $client->getError();
	if ($err) {
		// Display the error
		echo '<h2>Error</h2><pre>' . $err . '</pre>';
	} else {
		// Display the result
		echo '<h2>Result</h2><pre>';
		print_r($result);
		echo '</pre>';
	}
}
echo '<h2>Request</h2><pre>' . htmlspecialchars($client->request, ENT_QUOTES) . '</pre>';
echo '<h2>Response</h2><pre>' . htmlspecialchars($client->response, ENT_QUOTES) . '</pre>';
echo '<h2>Debug</h2><pre>' . htmlspecialchars($client->debug_str, ENT_QUOTES) . '</pre>';
?>
