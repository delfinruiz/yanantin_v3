<?php

declare(strict_types=1);

namespace App\Cache;

use Illuminate\Cache\CacheManager as BaseCacheManager;

class TenantCacheManager extends BaseCacheManager
{
    public function __call($method, $parameters)
    {
        if ($method === 'tags') {
            throw new \BadMethodCallException('This cache store does not support tagging.');
        }

        $prefix = config('tenancy.cache.tag_base').tenant()->getTenantKey().'_';

        if (in_array($method, ['many', 'putMany'])) {
            if (isset($parameters[0]) && is_array($parameters[0])) {
                $prefixed = [];
                foreach ($parameters[0] as $key => $value) {
                    $prefixed[$prefix.$key] = $value;
                }
                $parameters[0] = $prefixed;
            }
        } elseif (in_array($method, [
            'get', 'pull', 'put', 'set', 'add', 'seal',
            'increment', 'decrement',
            'forever', 'forget', 'has', 'missing',
            'remember', 'rememberForever',
        ])) {
            if (isset($parameters[0]) && is_string($parameters[0])) {
                $parameters[0] = $prefix.$parameters[0];
            }
        }

        return $this->store()->$method(...$parameters);
    }
}
