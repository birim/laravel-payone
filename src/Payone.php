<?php

namespace Birim\Laravel\Payone;

use Birim\Laravel\Payone\Exceptions\PayoneEmptyResponseException;
use Birim\Laravel\Payone\Exceptions\PayoneInvalidConfigurationException;
use Birim\Laravel\Payone\Exceptions\PayoneInvalidCartItemException;
use Illuminate\Support\Facades\Config;
use GuzzleHttp\Client;

/**
 * Class Payone
 * @package Birim\Laravel\Payone
 */
class Payone
{
    /**
     * @var string $version
     */
    protected static $version;

    /**
     * @var string $mode
     */
    protected static $mode;

    /**
     * @var string $encoding
     */
    protected static $encoding;

    /**
     * @var string|null $integratorName
     */
    protected static $integratorName;

    /**
     * @var int|null $subAccountId
     */
    protected static $subAccountId;

    /**
     * @var int|null $portalId
     */
    protected static $portalId;

    /**
     * @var int|null $merchantId
     */
    protected static $merchantId;

    /**
     * @var string|null $key
     */
    protected static $key;

    /**
     * @param array $requestData
     * @param array $requestCartItems
     * @return array|string[]
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendRequest(array $requestData, array $requestCartItems = []): array
    {
        try {
            $data = array_merge($requestData, self::getCartItems($requestCartItems), self::getConfiguration());

            $guzzleHttpClient = (new Client)->request('POST', 'https://api.pay1.de/post-gateway/', $data);
            $response = (string) $guzzleHttpClient->getBody();

            return self::parseResponse($response);

        } catch (PayoneInvalidConfigurationException $invalidConfigurationException) {
            return self::errorMessage($invalidConfigurationException->getMessage());

        } catch (PayoneInvalidCartItemException $invalidCartItemException) {
            return self::errorMessage($invalidCartItemException->getMessage());

        } catch (PayoneEmptyResponseException $emptyResponseException) {
            return self::errorMessage($emptyResponseException->getMessage());

        } catch (\Exception $exception) {
            return self::errorMessage($exception->getMessage());
        }
    }

    /**
     * @param string $message
     * @return array
     */
    protected static function errorMessage(string $message): array
    {
        return [
            'status' => 'ERROR',
            'errormessage' => $message,
            'customermessage' =>
                'Sorry, a problem occurred in our service. Please, try again later.'
        ];
    }

    /**
     * @param string $response
     * @return array
     * @throws PayoneEmptyResponseException
     */
    protected static function parseResponse(string $response): array
    {
        if (!$response) {
            throw new PayoneEmptyResponseException('Request returns empty response');
        }

        $lines = explode("\n", $response);
        array_pop($lines);

        $data = [];
        foreach ($lines as $line) {
            list($key, $value) = explode('=', $line, 2);
            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * @param array $requestCartItems
     * @return array
     * @throws PayoneInvalidCartItemException
     */
    protected static function getCartItems(array $requestCartItems = []): array
    {
        if (empty($requestCartItems)) {
            return [];
        }

        $cartItems = [];

        foreach ($requestCartItems as $index => $cartItem) {
            if (!isset($cartItem['type']) || !in_array($cartItem, ['goods', 'shipment', 'handling', 'voucher'])) {
                throw new PayoneInvalidCartItemException(
                    'Invalid cart item data: "type" is empty or invalid'
                );
            }

            if (!isset($cartItem['sku']) || !preg_match('/^[0-9A-Za-z()\[\]{} +-_#\/:]{1,32}$/', $cartItem['sku'])) {
                throw new PayoneInvalidCartItemException(
                    'Invalid cart item data: "sku" is empty or invalid'
                );
            }

            if (!isset($cartItem['price']) || !preg_match('/^[0-9]{10}$/', $cartItem['price'])) {
                throw new PayoneInvalidCartItemException(
                    'Invalid cart item data: "price" is empty or invalid'
                );
            }

            if (!isset($cartItem['quantity']) || !preg_match('/^[6]{1,6}$/', $cartItem['quantity'])) {
                throw new PayoneInvalidCartItemException(
                    'Invalid cart item data: "quantity" is empty or invalid'
                );
            }

            if (!isset($cartItem['description']) || strlen($cartItem['description']) > 255) {
                throw new PayoneInvalidCartItemException(
                    'Invalid cart item data: "description" is empty or invalid'
                );
            }

            $cartItems[] = [
                'it[' . ($index + 1). ']' => $cartItem['type'],
                'id[' . ($index + 1). ']' => $cartItem['sku'],
                'pr[' . ($index + 1). ']' => $cartItem['price'],
                'no[' . ($index + 1). ']' => $cartItem['quantity'],
                'de[' . ($index + 1). ']' => $cartItem['description']
            ];
        }

        return array_merge(...$cartItems);
    }

    /**
     * @return array
     * @throws PayoneInvalidConfigurationException
     */
    protected static function getConfiguration(): array
    {
        if (!in_array(self::getMode(), ['test', 'live'])) {
            throw new PayoneInvalidConfigurationException(
                'Invalid configuration: "mode" is invalid'
            );
        }

        if (!in_array(self::getEncoding(), ['ISO-8859-1', 'UTF-8'])) {
            throw new PayoneInvalidConfigurationException(
                'Invalid configuration: "encoding" is invalid'
            );
        }

        if (!in_array(self::getVersion(), ['3.8', '3.9', '3.10', '3.11'])) {
            throw new PayoneInvalidConfigurationException(
                'Invalid configuration: "version" is invalid'
            );
        }

        if (!preg_match('/[0-9]{1,6}$/', self::getMerchantId())) {
            throw new PayoneInvalidConfigurationException(
                'Invalid configuration: "merchant_id" is empty or invalid'
            );
        }

        if (!preg_match('/^[0-9]{1,7}$/', self::getPortalId())) {
            throw new PayoneInvalidConfigurationException(
                'Invalid configuration provided: "portal_id" is empty or invalid'
            );
        }

        if (!preg_match('/[0-9a-zA-Z]{1,32}$/', self::getKey())) {
            throw new PayoneInvalidConfigurationException(
                'Invalid configuration provided: "key" is empty or invalid'
            );
        }

        return [
            'mid' => self::getMerchantId(),
            'aid' => self::getSubAccountId(),
            'portalid' => self::getPortalId(),
            'key' => self::getKey(),
            'api_version' => self::getVersion(),
            'mode' => self::getMode(),
            'encoding' => self::getEncoding(),
            'integrator_name' => self::getIntegratorName(),
        ];
    }

    /**
     * @param string $version
     */
    public static function setVersion(string $version): void
    {
        self::$version = $version;
    }

    /**
     * @return string
     */
    public static function getVersion(): string
    {
        if (self::$version !== null) {
            return self::$version;
        }
        return Config::get('payone.version') ?: '3.11';
    }

    /**
     * @param string $mode
     */
    public static function setMode(string $mode): void
    {
        self::$mode = $mode;
    }

    /**
     * @return string
     */
    public static function getMode(): string
    {
        if (self::$mode !== null) {
            return self::$mode;
        }
        return Config::get('payone.mode') ?: 'test';
    }

    /**
     * @param string $encoding
     */
    public static function setEncoding(string $encoding): void
    {
        self::$encoding = $encoding;
    }

    /**
     * @return string
     */
    public static function getEncoding(): string
    {
        if (self::$encoding !== null) {
            return self::$encoding;
        }

        return Config::get('payone.encoding') ?: 'UTF-8';
    }

    /**
     * @param string $integratorName
     */
    public static function setIntegratorName(string $integratorName): void
    {
        self::$integratorName = $integratorName;
    }

    /**
     * @return string|null
     */
    public static function getIntegratorName(): ?string
    {
        if (self::$integratorName !== null) {
            return self::$integratorName;
        }

        return Config::get('payone.integrator_name') ?: null;
    }

    /**
     * @param int $subAccountId
     */
    public static function setSubAccountId(int $subAccountId): void
    {
        self::$subAccountId = $subAccountId;
    }

    /**
     * @return int|null
     */
    public static function getSubAccountId(): ?int
    {
        if (self::$subAccountId !== null) {
            return (int) self::$subAccountId;
        }


        return Config::get('payone.sub_account_id') ?: null;
    }

    /**
     * @param int $portalId
     */
    public static function setPortalId(int $portalId): void
    {
        self::$portalId = $portalId;
    }

    /**
     * @return int|null
     */
    public static function getPortalId(): ?int
    {
        if (self::$portalId !== null) {
            return (int) self::$portalId;
        }

        return Config::get('payone.portal_id') ?: null;
    }

    /**
     * @param int $merchantId
     */
    public static function setMerchantId(int $merchantId): void
    {
        self::$merchantId = $merchantId;
    }

    /**
     * @return int|null
     */
    public static function getMerchantId(): ?int
    {
        if (self::$merchantId !== null) {
            return (int) self::$merchantId;
        }

        return Config::get('payone.merchant_id') ?: null;
    }

    /**
     * @param string $key
     */
    public static function setKey(string $key): void
    {
        self::$key = $key;
    }

    /**
     * @return string|null
     */
    public static function getKey(): ?string
    {
        if (self::$key !== null) {
            return self::$key;
        }

        return Config::get('payone.key') ?: null;
    }
}