<?php

namespace AltDesign\AltCommerceStatamic\Support;

use AltDesign\AltCommerce\Support\Location;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VisitorLocator implements \AltDesign\AltCommerce\Contracts\VisitorLocator
{

    public function retrieve(): Location|null
    {
        $ip = request()->ip();
        if (empty($ip)) {
            return null;
        }

        $cacheKey = "alt-commerce-ip-lookup-$ip";
        if ($response = Cache::get($cacheKey)) {
            return $response;
        }

        try {
            $response = Http::get('http://ip-api.com/json/', [
                'fields' => 'status,countryCode,currency',
            ]);

            $content = json_decode($response->getBody()->getContents());
            if (!$response->ok() || $content->status !== 'success') {
                throw new \Exception('Invalid response from ip-api.com', [
                    'ip' => $ip,
                    'status' => $response->getStatusCode(),
                    'content' => $content,
                ]);
            }

            return Cache::remember($cacheKey, now()->addDay(), fn() => new Location(
                countryCode: $content->countryCode,
                currency: $content->currency
            ));

        } catch (\Throwable $e) {
            Log::error($e);
        }

        return null;

    }
}
