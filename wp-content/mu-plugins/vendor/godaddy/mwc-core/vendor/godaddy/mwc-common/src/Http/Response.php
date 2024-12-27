<?php

namespace GoDaddy\WordPress\MWC\Common\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\HttpRepository;
use WP_Error;

/**
 * HTTP Response handler.
 */
class Response implements ResponseContract
{
    /** @var array response body */
    public $body;

    /** @var int response status code */
    public $status;

    /** @var object|array response object */
    public $response;

    /**
     * Response constructor.
     *
     * @param array|WP_Error|null $response
     */
    public function __construct($response = null)
    {
        if ($response) {
            $this->setInitialBody($response)
                ->setInitialResponse($response)
                ->setInitialStatus($response);
        }
    }

    /**
     * @deprecated
     *
     * @param array $parameters
     * @return $this
     */
    public function body(array $parameters) : Response
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '4.2.0');

        return $this->setBody($parameters);
    }

    /**
     * Sets the response body.
     *
     * @param array $value
     * @return $this
     */
    public function setBody(array $value) : ResponseContract
    {
        $this->body = $value;

        return $this;
    }

    /**
     * Sets the response as an error response.
     *
     * @see https://developer.wordpress.org/reference/functions/wp_send_json_error/#source
     *
     * @param array<string>|string $errors
     * @param int|null $responseCode
     * @return $this
     * @throws Exception
     */
    public function error($errors, ?int $responseCode = null) : Response
    {
        if ($responseCode) {
            $this->setStatus($responseCode);
        }

        foreach (ArrayHelper::wrap($errors) as $error) {
            $this->setBody(ArrayHelper::combine(ArrayHelper::wrap($this->body), [
                'code'    => $responseCode,
                'message' => $error,
            ]));
        }

        $this->setBody(ArrayHelper::combine(ArrayHelper::wrap($this->body), ['success' => false]));

        return $this;
    }

    /**
     * Gets the response body.
     *
     * @return array|null
     */
    public function getBody() : ?array
    {
        return $this->body;
    }

    /**
     * Gets the error message.
     *
     * @TODO: Will need to expand to handle non-wp/wc responses in the future when needed.
     *
     * @return string|null
     */
    public function getErrorMessage() : ?string
    {
        if (! $this->isError()) {
            return null;
        }

        if (is_callable([$this->response, 'get_error_message'])) {
            return $this->response->get_error_message();
        }

        if (is_array($this->response) && ! empty($this->response['body'])) {
            if (is_string($this->response['body']) && ! empty($decodedBody = json_decode($this->response['body'], true))) {
                foreach (['developerMessage', 'message', 'error'] as $key) {
                    if ($message = ArrayHelper::get($decodedBody, $key)) {
                        return $message;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Gets the response status code.
     *
     * @return int|null
     */
    public function getStatus() : ?int
    {
        return $this->status;
    }

    /**
     * Determines if the response is an error response.
     *
     * @TODO: Will need to expand to handle non-wp/wc responses in the future when needed.
     *
     * @param array|WP_Error|null $response
     * @return bool
     */
    public function isError($response = null) : bool
    {
        return $this->hasErrorStatusCode() || (bool) is_wp_error($response ?: $this->response);
    }

    /**
     * Determine if the response has error status code.
     *
     * @return bool
     */
    protected function hasErrorStatusCode() : bool
    {
        $statusCode = $this->getStatus();

        // checks if status code not within the OK and Redirect status codes range
        return $statusCode && ($statusCode < 200 || $statusCode >= 400);
    }

    /**
     * Determines if the response is a success response.
     *
     * @TODO: Will need to expand to handle non-wp/wc responses in the future when needed.
     *
     * @return bool
     */
    public function isSuccess() : bool
    {
        return ! $this->isError();
    }

    /**
     * Sends a response.
     *
     * @NOTE: This will send a standard WP or API response back from the calling entity.
     *
     * @param bool $killAfter
     */
    public function send(bool $killAfter = true)
    {
        wp_send_json($this->getBody(), $this->getStatus());

        if ($killAfter) {
            exit;
        }
    }

    /**
     * Sets the initial response body.
     *
     * @param $originalResponse
     * @return $this
     */
    private function setInitialBody($originalResponse) : Response
    {
        $body = ArrayHelper::get($originalResponse, 'body', '');

        $this->body = ArrayHelper::wrap(json_decode(is_string($body) ? $body : '', true));

        return $this;
    }

    /**
     * Sets the initial response object.
     *
     * @NOTE: This is separated because we may want special handling of responses in the platform later
     *
     * @param $originalResponse
     * @return $this
     */
    private function setInitialResponse($originalResponse) : Response
    {
        $this->response = $originalResponse;

        return $this;
    }

    /**
     * Sets the initial response code.
     *
     * @TODO: Consider throwing an exception or default code here if there is no code as something likely went wrong
     * @TODO: This is a good place to log a sentry error or some sort of broader error reporting
     *
     * @param array|WP_Error $originalResponse
     * @return $this
     */
    public function setInitialStatus($originalResponse) : Response
    {
        $code = HttpRepository::getResponseCode($originalResponse);

        if (is_numeric($code)) {
            $this->status = (int) $code;
        }

        return $this;
    }

    /**
     * @deprecated
     *
     * @param int|null $code
     * @return $this
     */
    public function status(int $code = 200) : Response
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '4.2.0');

        return $this->setStatus($code);
    }

    /**
     * Sets the response status code.
     *
     * @param int|null $value
     * @return $this
     */
    public function setStatus(int $value = 200) : ResponseContract
    {
        $this->status = $value;

        return $this;
    }

    /**
     * Sets the response as a successful response.
     *
     * @NOTE WordPress just sets a success key. This is better to standardize ourselves so its not WordPress-dependent.
     * @link https://developer.wordpress.org/reference/functions/wp_send_json_success/#source
     *
     * @param int|null $code
     * @return $this
     * @throws Exception
     */
    public function success(?int $code = null) : Response
    {
        if ($code) {
            $this->setStatus($code);
        }

        $this->setBody(ArrayHelper::combine(ArrayHelper::wrap($this->body), ['success' => true]));

        return $this;
    }
}
