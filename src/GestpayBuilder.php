<?php

/**
 *
 * Biscolab Laravel Gestpay - GestpayBuilder Class
 * web : robertobelotti.com, github.com/biscolab
 *
 * @package Biscolab\Gestpay
 * @author author: Roberto Belotti - info@robertobelotti.com
 * @license MIT License @ https://github.com/biscolab/laravel-gestpay/blob/master/LICENSE
 */

namespace Biscolab\Gestpay;

use Exception;
use Redirect;
use Biscolab\Gestpay\GestpayResponse;

class GestpayBuilder {

	/**
	 * The shopLogin
	 * please visit http://api.gestpay.it/#encrypt
	 */
	public $shopLogin;  //changed visibility for iFrame use

	/**
	 * The uicCode
	 * please visit http://api.gestpay.it/#encrypt
	 */	
	protected $uicCode;

	/**
	 * The test
	 * Indicates whether the software is in test mode
	 */	
	protected $test;

	/**
	 * The API Official request URI
	 */
    protected $api_prod_url = 'https://ecommS2S.sella.it/gestpay/GestPayWS/WsCryptDecrypt.asmx?wsdl';

	/**
	 * The API TEST request URI
	 */    
    protected $api_test_url = 'https://sandbox.gestpay.net/gestpay/GestPayWS/WsCryptDecrypt.asmx?wsdl';	
	
	/**
	 * PaymentPage Official URI
	 */
    protected $payment_page_prod_url = 'https://ecomm.sella.it/pagam/pagam.aspx';
	
	/**
	 * PaymentPage TEST URI
	 */    
    protected $payment_page_test_url = 'https://sandbox.gestpay.net/pagam/pagam.aspx';	

	
	protected $payment_redirect_url;

	/**
	 * encrypted string for iFrame use
	 * 
	 * @var string
	 */
	public $encrypted_string;
	
	protected $apikey;
	protected $payment_types = [];
	


	/**
	 * see http://api.gestpay.it/#introduction
	 *
	 * @param $shopLogin string Your shop login code
	 * @param $uicCode string Currency code - 242 is euro - see http://api.gestpay.it/#currency-codes
	 * @param $test boolean Indicates whether the software is in test mode
	 */
	public function __construct($shopLogin, $uicCode, $test = false)
	{
		$this->shopLogin	= $shopLogin;
		$this->uicCode		= $uicCode;
		$this->test			= $test;
		
		//setup the APIKEY if configured
		if(is_string(config("gestpay.apikey")) && strlen(config("gestpay.apikey")) > 0){
			$this->apikey = config("gestpay.apikey");
		}
	}
	
	/**
	 * Build and Encrypt XML string in order to Perform payment
	 *
	 * @param $amount the Transaction amount. Do not insert thousands separator. Decimals, max. 2 numbers, are optional and separator is the point (Mandatory)
	 * @param $shopTransactionId the Identifier attributed to merchant’s transaction (Mandatory)
	 * @param $customParameters array of custom payment parameters, default = [] - see http://docs.gestpay.it/gs/how-gestpay-works.html#configuration-of-fields--parameters
	 * @param $languageId the language ID (for future use), default = NULL - see http://api.gestpay.it/#language-codes
	 * @param mixed $shopLogin the shopLogin if you want to override the default config, default = NULL
	 *
	 * @return boolean | redirect on payment page
	 */
    public function pay($amount, $shopTransactionId, $customParameters = [], $languageId = null, $shopLogin = null)
	{

		$customInfo = http_build_query($customParameters, '', '*P1*');
		$customInfo = urldecode($customInfo); //revert chars like @...
		$encrData = ['amount' => $amount, 'shopTransactionId' => $shopTransactionId, 'customInfo' => $customInfo];

		if(!is_null($languageId)){
			$encrData["languageId"] = $languageId;
		}
		
		if(strlen($shopLogin) > 0){
			$this->shopLogin = $shopLogin;
		}
		
		//request url for a specific payment type
		if(is_array($this->payment_types) && count($this->payment_types) > 0){
			
			$type = "";
			
			foreach($this->payment_types as $value){
				$type .= "<paymentType>".$value."</paymentType>";
			}
			
			$encrData["paymentTypes"] = $type;
		}

        $res = $this->Encrypt($encrData);
		
        if ( false !== strpos($res, '<TransactionResult>OK</TransactionResult>') && preg_match('/<CryptDecryptString>([^<]+)<\/CryptDecryptString>/', $res, $match) ) {
        	$payment_page_url = ($this->test)? $this->payment_page_test_url : $this->payment_page_prod_url;
			
			$this->encrypted_string = $match[1];
			$this->payment_redirect_url = $payment_page_url.'?a=' . $this->shopLogin . '&b=' . $match[1];
            return Redirect::to($this->payment_redirect_url);
        } else {

			$xml = self::cleanXML($res);
			$errorMessage = $xml->Body->EncryptResponse->EncryptResult->GestPayCryptDecrypt->ErrorDescription;
            $errorCode = $xml->Body->EncryptResponse->EncryptResult->GestPayCryptDecrypt->ErrorCode;

            try{
                $numeric_errorcode = (int)"{$errorCode}";
            }catch(\Exception $e){
                $numeric_errorcode = 0;
            }
            
			throw new Exception($errorMessage . " (Code: {$errorCode})", $numeric_errorcode);
		}
	}

	/**
	 * http://api.gestpay.it/#encrypt
	 *
	 * @param array $data
	 *
	 * @return string Encrypted XML string
	 */
    public function Encrypt($data = [])
    {
    	$xml_data = '';

    	if(!isset($data['amount'])) {throw new Exception('Manca importo');}
    	if(!isset($data['shopTransactionId'])) {throw new Exception('Manca transazione');}

    	$data = array_merge(['shopLogin' => $this->shopLogin, 'uicCode' => $this->uicCode], $data);
		
		if(is_string($this->apikey) && strlen($this->apikey) > 0){
			$data["apikey"] = $this->apikey;
		}

    	foreach ($data as $key => $value) {
    		$xml_data.= '<'.$key.'>'.$value.'</'.$key.'>';
    	}

        $xml = file_get_contents( dirname(__FILE__) . '/../xml/encrypt.xml');

        $xml = str_replace('{request}', $xml_data, $xml);

        return $this->call($xml, 'Encrypt');
    }

	/**
	 * Decrypt SOAP response 
	 * http://api.gestpay.it/#decrypt
	 *
	 * @param string $CryptedString The SOAP response crypted string
	 *
	 * @return string XML SOAP API call
	 */
    function Decrypt($CryptedString)
    {
        $xml_data = '';
    	$data = ['shopLogin' => $this->shopLogin, 'CryptedString' => $CryptedString];
		
		if(is_string($this->apikey) && strlen($this->apikey) > 0){
			$data["apikey"] = $this->apikey;
		}
		
    	foreach ($data as $key => $value) {
    		$xml_data.= '<'.$key.'>'.$value.'</'.$key.'>';
    	}
    	$xml = file_get_contents( dirname(__FILE__) . '/../xml/decrypt.xml');
        $xml = str_replace('{request}', $xml_data, $xml);

        $res = $this->call($xml, 'Decrypt');

        return $res;
    }

	/**
	 * Decrypt SOAP response in order to checks whether the payment has been successful
	 *
	 * @param mixed $shopLogin the shopLogin if you want to override the default config, default = NULL
	 * @return array $result containing 'transaction_result' (boolean true|false) and 'shop_transaction_id'
	 */
    public function checkResponse($shopLogin = null)
    {

    	$b = request()->input('b');
		
		if(strlen($shopLogin) > 0){
			$this->shopLogin = $shopLogin;
		}

        $xml_response = $this->Decrypt($b);

        $xml = self::cleanXML($xml_response);

        $response = $xml->Body->DecryptResponse->DecryptResult->GestPayCryptDecrypt;

        $transaction_result 	= (strtolower($response->TransactionResult) == 'ok');
        $shop_transaction_id 	= (string)$response->ShopTransactionID;        
        $error_code 			= (string)$response->ErrorCode;        
        $error_description 		= (string)$response->ErrorDescription;        
		$custom_infoStr	 		= str_replace('*P1*', '&' , urldecode($response->CustomInfo));
		parse_str($custom_infoStr, $custom_info);

        $result = new GestpayResponse($transaction_result, $shop_transaction_id, $error_code, $error_description, $custom_info);
		$result->popolate_full_response($response);

        return $result;
    }

	/**
	 * perform GestPay API call
	 *
	 * @param string $xml The XML string to send
	 * @param string $op The function called - Default 'Encrypt'
	 *
	 * @return string The SOAP response
	 */
    public function call($xml, $op = 'Encrypt')
    {
        $header = array(
            "Content-type: text/xml; charset=utf-8\"",
            "Accept: text/xml",
            "Content-length: ".strlen($xml),
            "SOAPAction: \"https://ecomm.sella.it/".$op."\"",
        );

        $api_url = ($this->test)? $this->api_test_url : $this->api_prod_url;

        $soap = curl_init();
        curl_setopt($soap, CURLOPT_URL, $api_url );
        curl_setopt($soap, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($soap, CURLOPT_TIMEOUT,        10);
        curl_setopt($soap, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($soap, CURLOPT_POST,           true );
        curl_setopt($soap, CURLOPT_POSTFIELDS,     $xml);
        curl_setopt($soap, CURLOPT_HTTPHEADER,     $header);

        $xml_res = curl_exec($soap);

        curl_close($soap);

        return $xml_res;
    }

	/**
	 * Clean SOAM XML code 
	 *
	 * @param string $xml_response The XML string to "clean up"
	 *
	 * @return string 
	 */
    public static function cleanXML($xml_response){
        $clean_xml = str_ireplace(['SOAP-ENV:', 'SOAP:'], '', $xml_response);
        return simplexml_load_string($clean_xml);    	
    }

	
	/**
	 * return the payment url to redirect to
	 * @return string
	 */
	public function getPaymentRedirectUrl(){
		return $this->payment_redirect_url;
	}



	/**
	 * run-time setup of an APKIKEY
	 * 
	 * @param string $apikey
	 * @return $this
	 */
	public function apikey($apikey){
		$this->apikey = $apikey;
		return $this;
	}


	/**
	 * add a payment type for requesting a specific url
	 * 
	 * @param string $payment_type
	 * @return $this
	 */
	public function add_payment_type($payment_type){
		$this->payment_types[] = $payment_type;
		return $this;
	}
}
