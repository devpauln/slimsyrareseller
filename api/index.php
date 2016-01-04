<?php

require '../app/bootstrap.php';

use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

$reseller_api 	= new reseller_api();
$logger 		= new Logger('RESELLER API');
$logger->pushHandler(new StreamHandler('../logs/trans.log', Logger::DEBUG));

// Create new instances

$app = new \Slim\Slim();

$app->config(array(
	'debug' => true,
	'mode' 	=> 'development',
));

$app->get('/', function() use ($app, $logger){
	#$logger->addWarning('WARNING DOE!');
	#$logger->addError('WARNING DOE!');

	# Do Random Test (Debugging Purposes)
});

$app->get('/test', function(){
	# NULL
});

$app->post('/domaincheck', function() use($reseller_api, $app) {

	$response = $app->response();
	$response['Content-Type'] = 'application/json';
	
	$dom_name = $app->request->post('names');
	
	$request = array(
		'DomainNames' => $dom_name
	);
	
	//send the request
	$resp = $reseller_api->call('DomainCheck', $request);
	
	if (!is_soap_fault($resp)) {

		//Successfully checked the availability of the domains
		if (isset($resp->APIResponse->AvailabilityList)) {
			$arr_av_data = array();
			foreach($resp->APIResponse->AvailabilityList as $list){
				if($list->Available){
					array_push($arr_av_data, $list->Item);
				}
			}
			#print implode($arr_av_data,',');
			$response->body(json_encode($arr_av_data));
			return $response;
		} 
		else {
			$response->body(json_encode($resp->APIResponse->Errors));
			echo $response;
		}
	} else {
		//SoapFault
		$response->body(json_encode($resp->getMessage()));
		return $response;
	}
});

$app->post('/registeruser', function() use ($app, $reseller_api) {

	$response = $app->response();
	$response['Content-Type'] = 'application/json';

	$data = $app->request->post();

	$request = array(
		'FirstName' 	=> $data['first_name'],
		'LastName' 		=> $data['last_name'],
		'Address' 		=> $data['address'],
		'City' 			=> $data['city'],
		'Country' 		=> $data['country'],
		'State' 		=> $data['state'],
		'PostCode' 		=> $data['zip'],
		'CountryCode' 	=> '61',
		'Phone' 		=> $data['phone'],
		'Mobile' 		=> $data['mobile'],
		'Email' 		=> $data['email'],
		'AccountType' 	=> $data['type']
	);

	$resp = $reseller_api->call('ContactCreate', $request);

	if (!is_soap_fault($resp)) {
		//Successfully created the contact
		if (isset($resp->APIResponse->ContactDetails)) {
			$response->body(json_encode($resp->APIResponse->ContactDetails->ContactIdentifier));
			return $response;
		} else {
			$response->body(json_encode($resp->APIResponse->Errors));
			return $response;
		}
	} 
	else {
		// Soap Fault 
		$response->body(json_encode($resp->getMessage));
		return $response;
	}
});

$app->post('/contactinfo', function() use ($app, $reseller_api) {

	$response = $app->response();
	$response['Content-Type'] = 'application/json';

	//construct the request data
	$contact_id = $app->request->post('contact');

	$request = array(
		'ContactIdentifier' => $contact_id#'C-000982915-SN'
	);

	//send the request
	$resp = $reseller_api->call('ContactInfo', $request);

	if (!is_soap_fault($resp)) {
		//Successfully created the domain
		if (isset($resp->APIResponse->ContactDetails)) {
			$response->body(json_encode($resp->APIResponse->ContactDetails));
			return $response;
		} 
		else {
			$response->body(json_encode($resp->APIResponse->Errors));
			return $response;
		}
	} 
	else {
		//SoapFault
		$response->body(json_encode($resp->APIResponse->getMessage()));
		return $response;
	}
});

$app->post('/domaininfo', function() use ($app, $reseller_api){

	$response = $app->response();
	$response['Content-Type'] = 'application/json';

	$dom_name = $app->request->post('domain_name');

	$request = array(
		'DomainName' => $dom_name
	);

	$resp = $reseller_api->call('DomainInfo', $request);

	if (!is_soap_fault($resp)){
	//Successfully created the domain
		if (isset($resp->APIResponse->DomainDetails)) {
			$response->body(json_encode($resp->APIResponse->DomainDetails));
			echo $response;
		} 
		else {
			$response->body(json_encode($resp->APIResponse->Errors));
			return $response;
		}
	} else {
		//SoapFault
		$response->body(json_encode($resp->getMessage()));
		return $response;
	}
});

$app->post('/domaincreate', function() use ($app, $reseller_api) {

	$response = $app->response();
	$response['Content-Type'] = 'application/json';

	$data = $app->request->post();

	$request = array(
		'DomainName' => $data['dom_name'],
		'RegistrantContactIdentifier' => $data['registrant_contact_id'],
		'AdminContactIdentifier' => $data['admin_contact_id'],
		'BillingContactIdentifier' => $data['billing_contact_id'],
		'TechContactIdentifier' => $data['tech_contact_id'],
		'RegistrationPeriod' => 2
	);

	//send the request
	$resp = $reseller_api->call('DomainCreate', $request);

	if (!is_soap_fault($response)) {

		//Successfully created the domain
		if (isset($resp->APIResponse->DomainDetails)) {
			$response->body(json_encode($resp->APIResponse->DomainDetails));
			return $response;
		} 
		else {
			$response->body(json_encode($resp->APIResponse->Errors));
			return $response;
		}
	}
	else {
		// Soap Fault
		$response->body(json_encode($resp->getMessage()));
		return $response;
	}
});

$app->post('/createcloneregistrant', function() use ($app, $reseller_api){

	$response = $app->response();
	$response['Content-Type'] = 'application/json';

	$data = $app->request->post();

	$request = array('ContactIdentifier' => $data['c_id']);

	$resp = $reseller_api->call('ContactCloneToRegistrant', $request);

	//$response->body(json_encode($resp));
	//return $response;

	if(!is_soap_fault($response)){
		if(isset($resp->APIResponse->ContactDetails)){
			$response->body(json_encode($resp->APIResponse->ContactDetails->ContactIdentifier));
			return $response;
		}
		else{
			$response->body(json_encode($resp->APIResponse->Errors));
			return $response;
		}
	}
	else{
		$response->body(json_encode($resp->getMessage()));
		return $response;
	}
});

$app->notFound(function() use ($app) {
	echo '404';
});

$app->run();