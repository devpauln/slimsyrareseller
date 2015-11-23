<?php
	class reseller_api {
		private $reseller_api_soap_client;

		function __construct($reseller_id = RESELLER_ID, $api_key = API_KEY) {
			//set the login headers
			$authenticate = array();
			$authenticate['AuthenticateRequest'] = array();
			$authenticate['AuthenticateRequest']['ResellerID'] = $reseller_id;
			$authenticate['AuthenticateRequest']['APIKey'] = $api_key;

			//convert $authenticate to a soap variable
			$authenticate['AuthenticateRequest'] = new SoapVar($authenticate['AuthenticateRequest'], SOAP_ENC_OBJECT);
			$authenticate = new SoapVar($authenticate, SOAP_ENC_OBJECT);

			$header = new SoapHeader(SOAP_LOCATION, 'Authenticate', $authenticate, false);

			$this->reseller_api_soap_client = new SoapClient(WSDL_LOCATION, array('soap_version' => SOAP_1_2, 'cache_wsdl' => WSDL_CACHE_NONE));
			$this->reseller_api_soap_client->__setSoapHeaders(array($header));
		}

		function call($method, $data = null) {
			$prepared_data = $data != null ? array($data) : array();

			try {
				$response = $this->reseller_api_soap_client->__soapCall($method, $prepared_data);
			} catch (SoapFault $response) { }

			return $response;
		}
	}
?>