<?php

namespace ZendService\Discogs;

use Traversable;
use Zend\Http;
use ZendOAuth as OAuth;
use Zend\Stdlib\ArrayUtils;

class Discogs
{
    const API_BASE_URI            = 'http://api.discogs.com';
    const OAUTH_REQUEST_TOKEN_URL = 'http://api.discogs.com/oauth/request_token';
    const OAUTH_AUTHORIZE_URL     = 'http://www.discogs.com/oauth/authorize';
    const OAUTH_ACCESS_TOKEN_URL  = 'http://api.discogs.com/oauth/access_token';

    protected $httpClient = null;
    protected $oauthConsumer = null;
    protected $options = array();

    public function __construct($options = null, OAuth\Consumer $consumer = null, Http\Client $httpClient = null)
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }
        if (!is_array($options)) {
            $options = array();
        }

        $this->options = $options;

        $accessToken = false;
        if (isset($options['accessToken']))
            $accessToken = $options['accessToken'];

        $oauthOptions = [];
        if (isset($options['oauthOptions']))
            $oauthOptions = $options['oauthOptions'];
        $oauthOptions['requestTokenUrl'] = static::OAUTH_REQUEST_TOKEN_URL;
        $oauthOptions['authorizeUrl']    = static::OAUTH_AUTHORIZE_URL;
        $oauthOptions['accessTokenUrl']  = static::OAUTH_ACCESS_TOKEN_URL;

        $httpClientOptions = array();
        if (isset($options['httpClientOptions']))
            $httpClientOptions = $options['httpClientOptions'];

        // If we have an OAuth access token, use the HTTP client it provides
        if ($accessToken && is_array($accessToken)
            && (isset($accessToken['token']) && isset($accessToken['secret']))
        ) {
            $token = new OAuth\Token\Access();
            $token->setToken($accessToken['token']);
            $token->setTokenSecret($accessToken['secret']);
            $accessToken = $token;
        }
        if ($accessToken && $accessToken instanceof OAuth\Token\Access) {
            $oauthOptions['token'] = $accessToken;
            $this->setHttpClient($accessToken->getHttpClient($oauthOptions, null, $httpClientOptions));
            return;
        }

        // See if we were passed an http client
        if (isset($options['httpClient']) && null === $httpClient) {
            $httpClient = $options['httpClient'];
        } elseif (isset($options['http_client']) && null === $httpClient) {
            $httpClient = $options['http_client'];
        }
        if ($httpClient instanceof Http\Client) {
            $this->httpClient = $httpClient;
        } else {
            $this->setHttpClient(new Http\Client(null, $httpClientOptions));
        }

        // Set the OAuth consumer
        if ($consumer === null) {
            $consumer = new OAuth\Consumer($oauthOptions);
        }
        $this->oauthConsumer = $consumer;
    }

    public function setHttpClient(Http\Client $client)
    {
        $this->httpClient = $client;
        return $this;
    }

    public function getHttpClient()
    {
        if (null === $this->httpClient) {
            $this->setHttpClient(new Http\Client());
        }
        return $this->httpClient;
    }

    public function isAuthorised()
    {
        if ($this->getHttpClient() instanceof OAuth\Client) {
            return true;
        }
        return false;
    }

    public function identity()
    {
        return new Response($this->get('/oauth/identity'));
    }

    public function profile($username)
    {
        return new Response($this->get('/users/'.$username));
    }

    public function label($id)
    {
        return new Response($this->get('/labels/'.$id));
    }

    /**
     * @param $params - see http://www.discogs.com/developers/resources/database/search-endpoint.html
     * @return SearchResponse
     */
    public function search($query, $params = [])
    {
        $params['q'] = $query;
        return new SearchResponse($this->get('/database/search', $params));
    }

    public function searchLabels($query, $params = [])
    {
        $params['type'] = 'label';
        return $this->search($query, $params);
    }

    /**
     * Call a remote REST web service URI
     *
     * @param  string $path The path to append to the URI
     * @param  Http\Client $client
     * @throws Client\Exception\UnexpectedValueException
     * @return void
     */
    protected function prepare($path, Http\Client $client)
    {
        $client->setUri(static::API_BASE_URI . $path);

        /**
         * Do this each time to ensure oauth calls do not inject new params
         */
        $client->resetParameters();
    }

    /**
     * Performs an HTTP GET request to the $path.
     *
     * @param string $path
     * @param array  $query Array of GET parameters
     * @throws Http\Client\Exception\ExceptionInterface
     * @return Http\Response
     */
    protected function get($path, array $query = array())
    {
        $client = $this->getHttpClient();
        $this->prepare($path, $client);
        $client->setParameterGet($query);
        $client->setMethod(Http\Request::METHOD_GET);
        $response = $client->send();
        return $response;
    }

    /**
     * Performs an HTTP POST request to $path.
     *
     * @param string $path
     * @param mixed $data Raw data to send
     * @throws Http\Client\Exception\ExceptionInterface
     * @return Http\Response
     */
    protected function post($path, $data = null)
    {
        $client = $this->getHttpClient();
        $this->prepare($path, $client);
        return $this->performPost(Http\Request::METHOD_POST, $data, $client);
    }

    /**
     * Perform a POST or PUT
     *
     * Performs a POST or PUT request. Any data provided is set in the HTTP
     * client. String data is pushed in as raw POST data; array or object data
     * is pushed in as POST parameters.
     *
     * @param mixed $method
     * @param mixed $data
     * @return Http\Response
     */
    protected function performPost($method, $data, Http\Client $client)
    {
        if (is_string($data)) {
            $client->setRawData($data);
        } elseif (is_array($data) || is_object($data)) {
            $client->setParameterPost((array) $data);
        }
        $client->setMethod($method);
        return $client->send();
    }

}