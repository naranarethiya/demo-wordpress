<?php

namespace GoDaddy\WordPress\MWC\Common\Auth\JWT;

use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\JwtAuthServiceException;
use GoDaddy\WordPress\MWC\Common\Auth\JWT\Contracts\JwtDecoderContract;
use GoDaddy\WordPress\MWC\Common\Auth\JWT\Contracts\KeySetProviderContract;
use GoDaddy\WordPress\MWC\Common\Auth\JWT\Contracts\TokenContract;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Common\Validation\Contracts\ValidationRuleContract;
use GoDaddy\WordPress\MWC\Common\Validation\Contracts\ValidatorContract;
use Throwable;

class JwtAuthFactory
{
    use CanGetNewInstanceTrait;

    /**
     * Gets the configured JwtAuthService for the given type.
     *
     * @param string $type
     * @return JwtAuthService
     * @throws JwtAuthServiceException
     */
    public function getServiceByType(string $type) : JwtAuthService
    {
        $serviceConfig = Configuration::get("auth.jwt.{$type}");

        $this->validateConfig($type, $serviceConfig);

        try {
            return $this->buildService($serviceConfig);
        } catch (Throwable $exception) {
            throw new JwtAuthServiceException("Error building the JWT authentication service: {$exception->getMessage()}");
        }
    }

    /**
     * Checks if the configuration is valid.
     *
     * @param string $type
     * @param array<string, mixed>|null $serviceConfig
     * @return void
     * @throws JwtAuthServiceException
     */
    protected function validateConfig(string $type, ?array $serviceConfig) : void
    {
        if (empty($serviceConfig)) {
            throw new JwtAuthServiceException("A JWT authentication service is not configured for type {$type}.");
        }

        $requiredConfigClasses = [
            'decoder'        => JwtDecoderContract::class,
            'keySetProvider' => KeySetProviderContract::class,
            'validator'      => ValidatorContract::class,
            'tokenObject'    => TokenContract::class,
        ];

        foreach ($requiredConfigClasses as $configKey => $contract) {
            if (empty(ArrayHelper::get($serviceConfig, $configKey))) {
                throw new JwtAuthServiceException("The JWT authentication service configuration for type {$type} is missing the {$configKey}.");
            }
        }
    }

    /**
     * Builds the JwtAuthService with its required dependencies.
     *
     * @param array<string, mixed> $serviceConfig
     * @return JwtAuthService
     * @throws JwtAuthServiceException
     */
    protected function buildService(array $serviceConfig) : JwtAuthService
    {
        $decoderClass = ArrayHelper::get($serviceConfig, 'decoder');
        /** @var JwtDecoderContract */
        $decoder = new $decoderClass();

        $keySetProviderClass = ArrayHelper::get($serviceConfig, 'keySetProvider');
        /** @var KeySetProviderContract */
        $keySetProvider = new $keySetProviderClass();

        $tokenClass = ArrayHelper::get($serviceConfig, 'tokenObject');
        /** @var TokenContract */
        $token = new $tokenClass();

        $validationRules = array_map(function (string $ruleClass) {
            if (! is_a($ruleClass, ValidationRuleContract::class, true)) {
                throw new JwtAuthServiceException("Invalid validation rule class provided for JWT auth service: {$ruleClass}");
            }

            return new $ruleClass();
        }, TypeHelper::arrayOfStrings(ArrayHelper::get($serviceConfig, 'validationRules')));

        $validatorClass = ArrayHelper::getStringValueForKey($serviceConfig, 'validator');

        if (! is_a($validatorClass, ValidatorContract::class, true)) {
            throw new JwtAuthServiceException("Invalid validator class provided for JWT auth service: {$validatorClass}");
        }

        $validator = (new $validatorClass())->setRules($validationRules);

        return new JwtAuthService($keySetProvider, $validator, $token, $decoder);
    }
}
