<?php

namespace Birim\Laravel\Payone\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array sendRequest(array $requestData, array $requestCartItems = [])
 * @method static array getData()
 * @method static void setVersion(string $version)
 * @method static string getVersion()
 * @method static void setMode(string $mode)
 * @method static string getMode()
 * @method static void setEncoding(string $encoding)
 * @method static string getEncoding()
 * @method static void setIntegratorName(string $integratorName)
 * @method static string|null getIntegratorName()
 * @method static void setSubAccountId(int $subAccountId)
 * @method static int|null getSubAccountId()
 * @method static void setPortalId(int $portalId)
 * @method static int|null getPortalId()
 * @method static void setMerchantId(int $merchantId)
 * @method static int|null getMerchantId()
 * @method static void setKey(string $key)
 * @method static string|null getKey()
 */
class Payone extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Payone';
    }
}