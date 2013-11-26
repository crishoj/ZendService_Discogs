<?php

namespace ZendService\Discogs;

use Zend\Http\Response as HttpResponse;
use Zend\Json\Exception\ExceptionInterface as JsonException;
use Zend\Json\Json;

/**
 * Representation of a response from Discogs.
 *
 * Provides:
 *
 * - method for testing if we have a successful call
 * - method for retrieving errors, if any
 * - method for retrieving the raw JSON
 * - method for retrieving the decoded response
 * - proxying to elements of the decoded response via property overloading
 */
class Response
{

    /**
     * Constructor
     *
     * Assigns the HttpResponse to a property, as well as the body
     * representation. It then attempts to decode the body as JSON.
     *
     * @param  HttpResponse $httpResponse
     * @throws Exception\DomainException if unable to decode JSON response
     */
    public function __construct(HttpResponse $httpResponse)
    {
        $this->httpResponse = $httpResponse;
        $this->rawBody      = $httpResponse->getBody();
        try {
            $jsonBody = Json::decode($this->rawBody, Json::TYPE_OBJECT);
            $this->jsonBody = $jsonBody;
        } catch (JsonException $e) {
            throw new Exception\DomainException(sprintf(
                'Unable to decode response from Discogs: %s',
                $e->getMessage()
            ), 0, $e);
        }
    }

    /**
     * Property overloading to JSON elements
     *
     * If a named property exists within the JSON response returned,
     * proxies to it. Otherwise, returns null.
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (null === $this->jsonBody) {
            return null;
        }
        if (!isset($this->jsonBody->{$name})) {
            return null;
        }
        return $this->jsonBody->{$name};
    }

    /**
     * Was the request successful?
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->httpResponse->isSuccess();
    }

    /**
     * Did an error occur in the request?
     *
     * @return bool
     */
    public function isError()
    {
        return !$this->httpResponse->isSuccess();
    }

    /**
     * Retrieve the error.
     *
     * @return string
     */
    public function getError()
    {
        if ($this->isError())
            return ($this->jsonBody && !empty($this->jsonBody->message))
                ? $this->jsonBody->message
                : $this->httpResponse->getReasonPhrase();
    }

    /**
     * Retrieve the raw response body
     *
     * @return string
     */
    public function getRawResponse()
    {
        return $this->rawBody;
    }

    /**
     * Retun the decoded response body
     *
     * @return array|\stdClass
     */
    public function toValue()
    {
        return $this->jsonBody;
    }

}
