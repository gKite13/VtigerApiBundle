<?php

namespace Gkite13\VtigerApiBundle;

use Gkite13\VtigerApiBundle\Exception\VtigerApiAuthFailedException;
use Gkite13\VtigerApiBundle\Exception\VtigerApiException;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class VtigerApi
{
    private const DEFAULT_CACHE_KEY = 'vtiger_api_session_key';
    private const DEFAULT_CACHE_EXPIRE_TIME = 300;

    private string $api_uri;
    private string $user;
    private string $access_key;
    private CacheItemPoolInterface $cache;
    private HttpClientInterface $client;
    private ?string $cacheKey;
    private ?int $cacheExpireTime;

    public function __construct(CacheItemPoolInterface $cache, HttpClientInterface $client, string $site_url, string $user, string $access_key, ?string $cacheKey = null, ?int $cache_expire_time)
    {
        $this->cache = $cache;
        $this->client = $client;
        $this->user = $user;
        $this->access_key = $access_key;
        $this->api_uri = $site_url.'/webservice.php';
        $this->cacheKey = $cacheKey;
        $this->cacheExpireTime = $cache_expire_time;
    }

    protected function getCacheKey(): string
    {
        return null === $this->cacheKey ? self::DEFAULT_CACHE_KEY : $this->cacheKey;
    }

    protected function getCacheExpireTime(): int
    {
        return null === $this->cacheExpireTime ? self::DEFAULT_CACHE_EXPIRE_TIME : $this->cacheExpireTime;
    }

    protected function getSessionId()
    {
        $cacheItem = $this->cache->getItem($this->getCacheKey());

        if ($cacheItem->isHit()) {
            $sessionId = $cacheItem->get();
        } else {
            $sessionId = $this->auth();
            $cacheItem->set($sessionId);
            $cacheItem->expiresAfter($this->getCacheExpireTime());
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

    public function create(string $entityType, object $data)
    {
        $sessionId = $this->getSessionId();

        try {
            $response = $this->client->request('POST', $this->api_uri, [
                'body' => [
                    'operation' => 'create',
                    'sessionName' => $sessionId,
                    'elementType' => $entityType,
                    'element' => json_encode($data),
                ],
            ]);
        } catch (\Exception $ex) {
            throw new VtigerApiException(sprintf('%s %s', 'Create request failed.', $ex->getMessage()));
        }

        return $this->_processResult($response);
    }

    public function update(object $data)
    {
        $sessionId = $this->getSessionId();

        try {
            $response = $this->client->request('POST', $this->api_uri, [
                'body' => [
                    'operation' => 'update',
                    'sessionName' => $sessionId,
                    'element' => json_encode($data),
                ],
            ]);
        } catch (\Exception $ex) {
            throw new VtigerApiException(sprintf('%s %s', 'Update request failed.', $ex->getMessage()));
        }

        return $this->_processResult($response);
    }

    public function delete($id)
    {
        $sessionId = $this->getSessionId();

        try {
            $response = $this->client->request('POST', $this->api_uri, [
                'body' => [
                    'operation' => 'delete',
                    'sessionName' => $sessionId,
                    'id' => $id,
                ],
            ]);
        } catch (\Exception $ex) {
            throw new VtigerApiException(sprintf('%s %s', 'Update request failed.', $ex->getMessage()));
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
        $responseContent = $response->getContent();
        if (!empty($responseContent)) {
            return json_decode($responseContent);
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
