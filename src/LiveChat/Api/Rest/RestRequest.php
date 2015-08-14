<?php

/**
 * RestRequest class file.
 */
namespace LiveChat\Api\Rest;

/**
 * RestRequest class.
 */
class RestRequest
{
    /**
     * @var string accept type header
     */
    const ACCEPT_TYPE = 'application/json';
    /**
     * @var integer request lenght
     */
    const REQUEST_LENGTH = 0;
    /**
     * @var string base API url
     */
    const BASE_API_URL = 'https://api.livechatinc.com/';

    private $url;
    private $method;
    private $requestBody;
    private $username;
    private $password;
    private $error;
    private $responseBody;
    private $responseInfo;

    /**
     * Setting base request data.
     *
     * @param string $path
     * @param string $method request method GET|POST|PUT|DELETE
     * @param array $requestBody request body
     * @param array $headers headers
     */
    public function __construct($path, $method, array $requestBody, array $headers = array())
    {
        $this->url = self::BASE_API_URL . $path;
        $this->headers = $headers;
        // valid method
        if (!in_array(strtoupper($method), array('GET', 'POST', 'PUT', 'DELETE'))) {
            throw new \InvalidArgumentException('Current method (' . $method . ') is an invalid REST method.');
        }
        $this->method = strtoupper($method);
        $this->buildRequestBody($requestBody);
    }

    /**
     * Set password
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Returns response body
     * @return mixed
     */
    public function getResponseBody()
    {
        return $this->responseBody;
    }

    /**
     * @return boolean
     * @see curl_getinfo()
     */
    public function getResponseInfo()
    {
        return $this->responseInfo;
    }

    /**
     * Set username
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * Retruns error.
     * @return string the error message or '' (the empty string) if no error occurred.
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Build request body.
     * @param array $data
     */
    private function buildRequestBody(array $data = array())
    {
        if (in_array($this->method, array('POST', 'PUT'))) {
            $this->requestBody = http_build_query($data, '', '&');
        }
    }

    /**
     * Execute request
     *
     * @throws \InvalidArgumentException
     */
    public function execute()
    {
        $ch = curl_init();
        $this->setAuth($ch);
        try {
            $methodName = 'execute' . ucfirst(strtolower($this->method));
            $this->{$methodName}($ch);
        } catch (\InvalidArgumentException $e) {
            curl_close($ch);
            throw $e;
        } catch (\Exception $e) {
            curl_close($ch);
            throw $e;
        }
    }

    /**
     * Execute request for GET method.
     * @param $ch cURL handle
     */
    private function executeGet(&$ch)
    {
        $this->doExecute($ch);
    }

    /**
     * Execute request for POST method.
     * @param $ch cURL handle
     */
    private function executePost(&$ch)
    {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestBody);
        curl_setopt($ch, CURLOPT_POST, 1);

        $this->doExecute($ch);
    }

    /**
     * Execute request for PUT method.
     * @param $ch cURL handle
     */
    private function executePut(&$ch)
    {
        // Prepare the data for HTTP PUT
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Length: ' . self::REQUEST_LENGTH));
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestBody);

        $this->doExecute($ch);
    }

    /**
     * Execute request for DELETE method.
     * @param $ch cURL handle
     */
    private function executeDelete(&$ch)
    {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

        $this->doExecute($ch);
    }

    /**
     * Execute request.
     * @param $curlHandle cURL handle
     */
    private function doExecute(&$curlHandle)
    { 
        $this->setCurlOpts($curlHandle);
        $this->responseBody = curl_exec($curlHandle);
        $this->responseInfo = curl_getinfo($curlHandle);
        $this->error = curl_error($curlHandle);

        curl_close($curlHandle);
    }

    /**
     * Set cURL options.
     * @param $curlHandle cURL handle
     */
    private function setCurlOpts(&$curlHandle)
    {
        $headers = array('Accept' => self::ACCEPT_TYPE);
        foreach ($this->headers as $key => $value) {
            $headers[] = "$key: $value";
        }

        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, 15);
        curl_setopt($curlHandle, CURLOPT_URL, $this->url);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * Set user authentication
     * @param $curlHandle cURL handle
     */
    private function setAuth(&$curlHandle)
    {
        if ($this->username !== null && $this->password !== null) {
            curl_setopt($curlHandle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curlHandle, CURLOPT_USERPWD, $this->username . ':' . $this->password);
        }
    }
}
