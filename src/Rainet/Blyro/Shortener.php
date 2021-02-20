<?php

namespace Rainet\Blyro;

use Rainet\Exception\CurlException;
use Rainet\Exception\ResponseException;

class Shortener
{
    const APICURL_ENDPOINT = 'https://bly.ro/api/links';
    const APICURL_TIMEOUT = 30;
    const APICURL_VERIFY_SSL = false;
    const APICURL_VERIFY_HOST = false;

    protected $apiUID = null;
    protected $apiKey = null;

    protected $curlErrno = false;
    protected $curlError = false;
    protected $curlInfo = null;

    public function __construct(string $apiUID, string $apiKey)
    {
        if ( ! function_exists('curl_init')) {

            throw new CurlException('cURL is not available.');
        }

        $this->apiUID = $apiUID;
        $this->apiKey = $apiKey;
    }

    public function create(string $longUrl)
    {
        $params = [
            'UID' => $this->apiUID,
            'KEY' => $this->apiKey,
            'URL' => $longUrl,
        ];

        $result = $this->_apiCall(self::APICURL_ENDPOINT, $params, 'POST');

        if ($result) {
            return $result->link;
        }

        return null;
    }

    private function _apiCall(string $url, ?array $params = null, string $method = 'GET')
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::APICURL_TIMEOUT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, self::APICURL_VERIFY_SSL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, self::APICURL_VERIFY_HOST);
        curl_setopt($ch, CURLOPT_ENCODING , '');

        switch (strtoupper($method)) {
            case 'GET':
                curl_setopt($ch, CURLOPT_HTTPGET, true);
            break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            break;
            case 'PUT':
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            break;
        }

        $headers = [];

        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Accept: application/json';

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $this->curlErrno = curl_errno($ch);
            $this->curlError = curl_error($ch);
            curl_close($ch);
            
            return false;
        }

        $this->curlInfo = curl_getinfo($ch);
        curl_close($ch);

        return $this->response($response);
    }

    protected function response(?string $response)
    {
        $response = json_decode($response);

        if ( (isset($response->success)) && ($response->success === 1) ) {
            return $response;
        } else {
            throw new ResponseException((isset($response->error)) ? $response->error : 'API Request Failed!');
        }

        return false;
    }
}