<?php

/**
 *
 * Biscolab Laravel Gestpay - GestpayResponse Class
 * web : robertobelotti.com, github.com/biscolab
 *
 * @package Biscolab\Gestpay
 * @author author: Roberto Belotti - info@robertobelotti.com
 * @license MIT License @ https://github.com/biscolab/laravel-gestpay/blob/master/LICENSE
 */

namespace Biscolab\Gestpay;

class GestpayResponse {

	/**
	 * The transaction_result
	 * boolean true | false
	 */
	protected $transaction_result	= false;

	/**
	 * The shop_transaction_id
	 * Transaction ID
	 */	
	protected $shop_transaction_id	= '';

	/**
	 * The error_code
	 * Error code
	 */		
	protected $error_code	= '';

	/**
	 * The error_description
	 * Error description
	 */		
	protected $error_description	= '';

	/*
	 * Custom info data
	 * 
	 */

	protected $custom_info = [];

	/**
	 * Create a GestpayResponse Object
	 *
	 * @param $transaction_result boolean The transaction_result
	 * @param $shop_transaction_id string The shop_transaction_id
	 * @param $error_code string The error_code
	 * @param $error_description string The error_description
	 */
	public function __construct($transaction_result, $shop_transaction_id, $error_code, $error_description, $custom_info) {
		$this->transaction_result	= $transaction_result;
		$this->shop_transaction_id	= $shop_transaction_id;
		$this->error_code			= $error_code;
		$this->error_description	= $error_description;
		$this->custom_info 			= $custom_info;
	}

	/**
	 * Get the transaction result
	 *
	 * @return $transaction_result boolean The transaction_result
	 */
	public function getTransactionResult() {
		return $this->transaction_result;
	}

	/**
	 * Get the transaction result
	 *
	 * @return $shop_transaction_id boolean The shop_transaction_id
	 */
	public function getShopTransactionId() {
		return $this->shop_transaction_id;
	}

	/**
	 * Get the error code
	 *
	 * @return $error_code boolean The error_code
	 */
	public function getErrorCode() {
		return $this->error_code;
	}

	/**
	 * Get the error description
	 *
	 * @return $error_description boolean The error_description
	 */
	public function getErrorDescription() {
		return $this->error_description;
	}	

	/**
	 * Get custom info parameters
	 * 
	 * @return $custom_info array The custom_info;
	 */

	public function getCustomInfo() {
		return $this->custom_info;
	}


}