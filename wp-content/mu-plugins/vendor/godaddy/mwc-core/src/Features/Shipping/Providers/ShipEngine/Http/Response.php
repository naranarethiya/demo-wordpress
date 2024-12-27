<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\Http;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response as CommonResponse;

/**
 * ShipEngine response class.
 */
class Response extends CommonResponse
{
    /**
     * Returns true if getErrors() returns a non-empty array.
     *
     * @return bool
     */
    protected function hasErrors() : bool
    {
        return ! empty($this->getErrors());
    }

    /**
     * {@inheritDoc}
     */
    public function isError($response = null) : bool
    {
        return $this->hasErrors() || (bool) parent::isError();
    }

    /**
     * Gets the WordPress errors from the response.
     *
     * @return array<array{message: string, extensions: array}>
     */
    protected function getWordPressErrors() : array
    {
        $errors = [];
        if (is_wp_error($this->response)) {
            foreach ($this->response->get_error_messages() as $code => $message) {
                $errors[] = [
                    'message'    => $message,
                    'extensions' => [
                        'code' => $code,
                    ],
                ];
            }
        }

        return $errors;
    }

    /**
     * Gets the MWC API errors from the response.
     *
     * @return array<array{message: string, extensions: array}>
     */
    protected function getManagedWooCommerceErrors() : array
    {
        if (! $this->hasErrorStatusCode()) {
            return [];
        }

        if ($errors = $this->getManagedWooCommerceUnprocessableContentErrors()) {
            return $errors;
        }

        if ($errors = $this->getManagedWooCommerceBasicErrors()) {
            return $errors;
        }

        return [];
    }

    /**
     * Gets errors that are usually included in 422 Unprocessable Content responses.
     *
     * These errors have the following structure:
     *
     * ```json
     * {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "returnUrl": [
     *       "Invalid return url"
     *     ]
     *   }
     * }
     * ```
     *
     * @return array<array{message: string, extensions: array<string, string>}>
     */
    protected function getManagedWooCommerceUnprocessableContentErrors() : array
    {
        if (! ArrayHelper::has($this->body, ['message', 'errors'])) {
            return [];
        }

        $messages = [];

        foreach (ArrayHelper::wrap(ArrayHelper::get($this->body, 'errors')) as $index => $errors) {
            if (! is_string($index)) {
                continue;
            }

            $message = is_array($errors) ? array_shift($errors) : $errors;

            if (! $message = (string) StringHelper::ensureScalar($message)) {
                continue;
            }

            $messages[] = "{$index}: {$message}";
        }

        return [
            [
                'message'    => StringHelper::endWith(trim(ArrayHelper::get($this->body, 'message', '').' '.implode(', ', $messages)), '.'),
                'extensions' => [
                    'code' => ArrayHelper::get($this->body, 'code', ''),
                ],
            ],
        ];
    }

    /**
     * Gets errors from responses that include a message and a code only.
     *
     * These errors have the following structure:
     *
     * ```json
     * {
     *   "code": "UNAUTHORIZED",
     *   "message": "The token expired or is not valid."
     * }
     * ```
     *
     * @return array<array{message: string, extensions: array<string, string>}>
     */
    protected function getManagedWooCommerceBasicErrors() : array
    {
        if (! ArrayHelper::has($this->body, 'message')) {
            return [];
        }

        return [
            [
                'message'    => ArrayHelper::get($this->body, 'message', ''),
                'extensions' => [
                    'code' => ArrayHelper::get($this->body, 'code', ''),
                ],
            ],
        ];
    }

    /**
     * Gets the ShipEngine errors from the response.
     *
     * @return array<array{message: string, extensions: array}>
     */
    protected function getShipEngineErrors() : array
    {
        $errors = [];
        if ($this->hasErrorStatusCode() && ArrayHelper::has($this->body, 'errors')) {
            foreach ($this->body['errors'] as $error) {
                $errors[] = [
                    'message'    => TypeHelper::string(ArrayHelper::get($error, 'message'), ''),
                    'extensions' => [
                        'code' => ArrayHelper::get($error, 'error_code', ''),
                    ],
                ];
            }
        }

        return $errors;
    }

    /**
     * Gets the errors included in the response.
     *
     * @return array<array{message: string, extensions: array}>
     */
    public function getErrors() : array
    {
        if (! empty($this->getWordPressErrors())) {
            return $this->getWordPressErrors();
        }
        if (! empty($this->getManagedWooCommerceErrors())) {
            return $this->getManagedWooCommerceErrors();
        }
        if (! empty($this->getShipEngineErrors())) {
            return $this->getShipEngineErrors();
        }

        return [];
    }

    /**
     * Gets the first error message found.
     *
     * @return string|null
     */
    public function getErrorMessage() : ?string
    {
        $errors = $this->getErrors();

        return ArrayHelper::has($errors, '0.message') ? $errors[0]['message'] : null;
    }
}
