<?php

namespace Gkite13\VtigerApiBundle;

use Gkite13\VtigerApiBundle\Exception\VtigerApiAuthFailedException;
use Gkite13\VtigerApiBundle\Exception\VtigerApiException;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class VtigerApi
{
    public const CACHE_KEY = 'vtiger_api_session_key';

    private string $api_uri;
    private string $user;
    private string $access_key;
    private CacheItemPoolInterface $cache;
    private HttpClientInterface $client;

    public function __construct(CacheItemPoolInterface $cache, HttpClientInterface $client, string $site_url, string $user, string $access_key)
    {
        $this->cache = $cache;
        $this->client = $client;
        $this->user = $user;
        $this->access_key = $access_key;
        $this->api_uri = $site_url.'/webservice.php';
    }

    protected function getSessionId()
    {
        $cacheItem = $this->cache->getItem(self::CACHE_KEY);

        if ($cacheItem->isHit()) {
            $sessionId = $cacheItem->get();
        } else {
            $sessionId = $this->auth();
            $cacheItem->set($sessionId);
            $cacheItem->expiresAfter(300);
            $this->cache->save($cacheItem);
        }

        return $sessionId;
    }

    private function auth()
    {
        $token = $this->getToken();

        $response = $this->client->request('POST', $this->api_uri, [
            'body' => [
                'operation' => 'login',
                'username' => $this->user,
                'accessKey' => $token,
            ],
        ]);
        $this->_checkResponseStatusCode($response);
        $loginResult = $this->_processResponse($response);
        if (true !== $loginResult->success) {
            throw new VtigerApiAuthFailedException(sprintf('%s %s', 'Login failed.', $loginResult->error->message));
        }

        return $loginResult->result->sessionName;
    }

    private function getToken(): string
    {
        try {
            $requestResult = $this->client->request('GET', $this->api_uri, [
                'query' => [
                    'operation' => 'getchallenge',
                    'username' => $this->user,
                ],
            ]);
            $this->_checkResponseStatusCode($requestResult);

            $content = $requestResult->getContent(false);
            $result = json_decode($content, true);
            if (!isset($result['success']) || !$result['success']) {
                throw new VtigerApiAuthFailedException("Failed to get sessionName. ".print_r($result, true));
            }
        } catch (\Exception $ex) {
            throw new VtigerApiAuthFailedException(sprintf('%s %s', "Failed to get sessionName.", $ex->getMessage()));
        }

        return md5(sprintf('%s%s', $result['result']['token'], $this->access_key));
    }

    public function retrieve(string $id): ?object
    {
        $sessionId = $this->getSessionId();

        try {
            $response = $this->client->request('GET', $this->api_uri, [
                'query' => [
                    'operation' => 'retrieve',
                    'sessionName' => $sessionId,
                    'id' => $id,
                ],
            ]);
        } catch (\Exception $ex) {
            throw new VtigerApiException(sprintf('%s %s', 'Retrieve request failed.', $ex->getMessage()));
        }

        return $this->_processResult($response);
    }

    public function query(string $query)
    {
        $sessionId = $this->getSessionId();

        try {
            $response = $this->client->request('GET', $this->api_uri, [
                'query' => [
                    'operation' => 'query',
                    'sessionName' => $sessionId,
                    'query' => $query,
                ],
            ]);
        } catch (\Exception $ex) {
            throw new VtigerApiException(sprintf('%s %s', 'Query request failed.', $ex->getMessage()));
        }

        return $this->_processResult($response);
    }

    protected function _checkResponseStatusCode(ResponseInterface $response)
    {
        if ($response->getStatusCode() !== 200) {
            throw new VtigerApiException("Response status code is wrong.");
        }
    }

    protected function _processResult(ResponseInterface $response)
    {
        $this->_checkResponseStatusCode($response);

        $data = $this->_processResponse($response);

        if (!isset($data->success)) {
            throw new VtigerApiException('Property `success` is not set');
        }

        if ($data->success == false) {
            $this->_processResponseError($data);
        }

        return $data->result;
    }

    protected function _processResponse(ResponseInterface $response): ?object
    {
        if (!empty($response->getContent())) {
            return json_decode($response->getContent());
        }

        return null;
    }

    protected function _processResponseError($processedData)
    {
        if (!isset($processedData->error)) {
            throw new VtigerApiException('Vtiger Api return an error, but no details');
        }
        throw new VtigerApiException(sprintf('%s %s', 'Vtiger Api return an error.', $processedData->error->message));
    }
}
