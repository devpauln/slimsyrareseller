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

	$response_slim = $app->response();
	$response_slim['Content-Type'] = 'application/json';

	$dom_name = $app->request->post('names');
	
	$request = array(
		'DomainNames' => $dom_name
	);

	//send the request
	$response = $reseller_api->call('DomainCheck', $request);

	if (!is_soap_fault($response)) {

		//Successfully checked the availability of the domains
		if (isset($response->APIResponse->AvailabilityList)) {
			$arr_av_data = array();
			foreach($response->APIResponse->AvailabilityList as $list){
				if($list->Available){
					array_push($arr_av_data, $list->Item);
				}
			}
			#print implode($arr_av_data,',');
			$response_slim->body(json_encode($arr_av_data));
			return $response_slim;
		} else {
			echo 'The following error(s) occurred:<br />';

			foreach ($response->APIResponse->Errors as $error) {
				echo $error->Item . ' - ' . $error->Message . '<br />';
			}
		}
	} else {

		//SoapFault
		echo 'Error occurred while sending request: ' . $response->getMessage();
	}
});

$app->post('/registeruser', function() use ($app, $reseller_api) {


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

	$response = $reseller_api->call('ContactCreate', $request);

	if (!is_soap_fault($response)) {

	//Successfully created the contact
	if (isset($response->APIResponse->ContactDetails)) {
		echo 'Contact ID: ' . $response->APIResponse->ContactDetails->ContactIdentifier;
	} else {
		echo 'The following error(s) occurred:<br />';

		foreach ($response->APIResponse->Errors as $error) {
			echo $error->Item . ' - ' . $error->Message . '<br />';
		}
	}
	} else {

		//SoapFault
		echo 'Error occurred while sending request: ' . $response->getMessage();
	}
});

$app->post('/contactinfo', function() use ($app, $reseller_api) {
	//construct the request data

	$contact_id = $app->request->post('contact');

	$request = array(
		'ContactIdentifier' => $contact_id#'C-000982915-SN'
	);
	//send the request
	$response = $reseller_api->call('ContactInfo', $request);

	if (!is_soap_fault($response)) {

		//Successfully created the domain
		if (isset($response->APIResponse->ContactDetails)) {
			echo 'Contacts name is ' . $response->APIResponse->ContactDetails->FirstName . ' ' . $response->APIResponse->ContactDetails->LastName;
		} else {
			echo 'The following error(s) occurred:<br />';

			foreach ($response->APIResponse->Errors as $error) {
				echo $error->Item . ' - ' . $error->Message . '<br />';
			}
		}
	} else {

		//SoapFault
		echo 'Error occurred while sending request: ' . $response->getMessage();
	}
});

$app->post('/domaininfo', function() use ($app, $reseller_api){
	$dom_name = $app->request->post('domain_name');

	$request = array(
		'DomainName' => $dom_name
	);

	$response = $reseller_api->call('DomainInfo', $request);

	if (!is_soap_fault($response)){
	//Successfully created the domain
		if (isset($response->APIResponse->DomainDetails)) {
			echo $response->APIResponse->DomainDetails->DomainName . ' expires ' . $response->APIResponse->DomainDetails->Expiry;
		} else {
			echo 'The following error(s) occurred:<br />';

			foreach ($response->APIResponse->Errors as $error) {
				echo $error->Item . ' - ' . $error->Message . '<br />';
			}
		}
	} else {
		//SoapFault
		echo 'Error occurred while sending request: ' . $response->getMessage();
	}
});

$app->post('/domaincreate', function() use ($app, $reseller_api) {

	$data = $app->request->post();

	$request = array(
		'DomainName' => $data['dom_name'],
		'RegistrantContactIdentifier' => $data['registrant_contact_id'],
		'AdminContactIdentifier' => $data['admin_contact_id'],
		'BillingContactIdentifier' => $data['billing_contact_id'],
		'TechContactIdentifier' => $data['tech_contact_id'],
		'RegistrationPeriod' => 1
	);

	//send the request
	$response = $reseller_api->call('DomainCreate', $request);

	if (!is_soap_fault($response)) {

		//Successfully created the domain
		if (isset($response->APIResponse->DomainDetails)) {
			echo 'Domain successfully created';
		} else {
			echo 'The following error(s) occurred:<br />';

			foreach ($response->APIResponse->Errors as $error) {
				echo $error->Item . ' - ' . $error->Message . '<br />';
			}
		}
	} else {

		//SoapFault
		echo 'Error occurred while sending request: ' . $response->getMessage();
	}
});

$app->notFound(function() use ($app) {
	echo '404';
});

$app->run();