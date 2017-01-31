<?php

namespace MCSDK\Authentication;


class FakeDiscoveryOptions
{
    private $_operatorUrls;
    private $_clientId;
    private $_clientSecret;
    private $_clientName;
    private $_subId;

    public function __construct()
    {
    }

    /**
     * @return mixed
     */
    public function getOperatorUrls()
    {
        return $this->_operatorUrls;
    }

    /**
     * @param mixed $operatorUrls
     */
    public function setOperatorUrls($operatorUrls)
    {
        $this->_operatorUrls = $operatorUrls;
    }

    /**
     * @return mixed
     */
    public function getClientId()
    {
        return $this->_clientId;
    }

    /**
     * @param mixed $clientId
     */
    public function setClientId($clientId)
    {
        $this->_clientId = $clientId;
    }

    /**
     * @return mixed
     */
    public function getClientSecret()
    {
        return $this->_clientSecret;
    }

    /**
     * @param mixed $clientSecret
     */
    public function setClientSecret($clientSecret)
    {
        $this->_clientSecret = $clientSecret;
    }

    /**
     * @return mixed
     */
    public function getClientName()
    {
        return $this->_clientName;
    }

    /**
     * @param mixed $clientName
     */
    public function setClientName($clientName)
    {
        $this->_clientName = $clientName;
    }

    /**
     * @param mixed $subId
     */
    public function setSubId($subId)
    {
        $this->_subId = $subId;
    }

    public function getJson(){
        $json = "{
	        \"response\": {
		    \"apis\": {
			    \"operatorid\": {
				    %s
			    }
		    },
		    \"client_secret\": \"%s\",
		    \"client_id\": \"%s\",
		    \"client_name\": \"%s\"
	        },
	    \"subscriber_id\": \"%s\"
        }";

        return sprintf($json, $this->_operatorUrls->getJson(), $this->_clientSecret, $this->_clientId, $this->_clientName, $this->_subId);
    }
}