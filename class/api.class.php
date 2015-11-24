<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class MEOWallet_API {

    protected $_endpoint = '{{ DOMAIN }}/{{ RESOURCE }}';
    protected $_domain = '';
    protected $_authToken = '';
    protected $_args = array();
    protected $_resource = '';
    protected $_url = '';

    /*
     * Initialize the and store the domain/token for making requests
     *
     * @param string $domain The subdomain like 'yoursite'.freshbooks.com
     * @param string $token The token found in your account settings area
     * @return null
     */

    public function __construct($domain, $authToken) {
        if (!extension_loaded('curl')) {
            die('CURL extension not found!');
        }
        $this->_domain = $domain;
        $this->_authToken = $authToken;
    }

    /*
     * Set the data/arguments we're about to request with
     *
     * @return null
     */

    public function post($resource, $data) {

        if (!$this->_domain || !$this->_authToken) {
            WC_MEOWALLET_GW::log('You need to call MEOWallet_API($domain, $authToken) with your domain and Authentication Token.');
        }

        $verify = false;
        $encode = true;

        $this->_resource = $resource;

        $url = str_replace('{{ DOMAIN }}', $this->_domain, $this->_endpoint);
        $this->_url = str_replace('{{ RESOURCE }}', $this->_resource, $url);

        $this->_type = 'POST';

        if ($resource == 'callback/verify') {
            $encode = false;
            $verify = true;
        }

        if ($encode)
            $this->_args = json_encode($data);
        else
            $this->_args = $data;


        return $this->request($encode, $verify);
    }

    public function get($resource, $data) {
        $this->_args = $data;
    }

    public function request($post = true, $verify = false) {



        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_URL, $this->_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($this->_type));
        if ($post)
            curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_args);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header());
        $response = curl_exec($ch);
        curl_close($ch);

        if ($verify) {
            if (0 == strcasecmp('true', $response)) {
                return true;
            }

            if (0 != strcasecmp('false', $response)) {
                return false;
            }
        } else {
            if (!$response) {
                WC_MEOWALLET_GW::log('CURL Error: ' . curl_error($response), curl_errno($ch));
            } else {
                $response = json_decode($response);
                WC_MEOWALLET_GW::log('API Response (' . $this->_resource . '): ' . print_r($response, true));
                return $response;
            }
        }
    }

    public function header() {
        return array(
            'Authorization: WalletPT ' . $this->_authToken,
            'Content-Type: application/json',
            'Content-Length: ' . strlen($this->_args)
        );
    }

}
