<?php

/**
 *
 * Biscolab Laravel Gestpay - GestpayServiceProvider Class
 * web : robertobelotti.com, github.com/biscolab
 * 
 * more info @ http://api.gestpay.it/#introduction
 *
 * @package Biscolab\Gestpay
 * @author author: Roberto Belotti - info@robertobelotti.com
 * @license MIT License @ https://github.com/biscolab/laravel-gestpay/blob/master/LICENSE
 */

return [

	/**
	 * Your shop login code
	 */
    'shopLogin'	=> '',

	/**
	 * Currency code
	 * 242 is euro
	 * see http://api.gestpay.it/#currency-codes
	 */
    'uicCode' 	=> '242',

	/**
	 * Indicates whether the software is in test mode
	 */
    'test'		=> env("GESTPAY_TEST", false),
	
	/**
	 * APIKEY for authentication (instead of IP-source based auth)
	 */
	'apikey' => env("GESTPAY_APIKEY", null),
];