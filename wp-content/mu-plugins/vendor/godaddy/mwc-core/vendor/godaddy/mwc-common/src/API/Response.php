<?php

namespace GoDaddy\WordPress\MWC\Common\API;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response as HttpResponse;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class Response extends HttpResponse
{
    use CanGetNewInstanceTrait;

    /** @var array<string|int, mixed> response body */
    public $body = [];

    /** @var int response status code */
    public $status = 200;

    /**
     * Adds the given error to the body of the response.
     *
     * @param string $message
     * @param string|null $code
     * @return $this
     */
    public function addError(string $message, ?string $code = null)
    {
        $errors = ArrayHelper::wrap(ArrayHelper::get($this->body, 'errors'));

        try {
            $this->body['errors'] = ArrayHelper::combine($errors, [$this->formatError($message, $code)]);
        } catch (Exception $exception) {
            // ignore exception that is not possible when both parameters to ArrayHelper::combine() are arrays {wvega 2022-07-21}
        }

        return $this;
    }

    /**
     * Formats the given message and code using the standard error format.
     *
     * @param string $message
     * @param string|null $code
     * @return array<string, mixed>
     */
    protected function formatError(string $message, ?string $code = null) : array
    {
        $error = ['message' => $message];

        if ($code) {
            $error['extensions']['code'] = $code;
        }

        return $error;
    }
}
