<?php

class HttpRequestMessage {
    private $_headers;
    private $_method;
    private $_requestUri;
    private $_version;

    public function __construct($headers, $method, $requestUri, $version) {
        $this->$_headers = $headers;
        $this->$_method = $method;
        $this->$_requestUri = $requestUri;
        $this->$_version = $version;
    }
}