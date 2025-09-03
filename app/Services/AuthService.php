<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Values\ApiKeyProvidersValues;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthService
{
    /**
     * Determine and return the Tenant making this request via:
     * 1) X-API-KEY header (secret server-to-server key)
     * 2) Origin header (JS/browser)
     *
     * @throws UnauthorizedHttpException
     */
    public function tenantFromRequest(Request $request): Tenant
    {
        // Try bearer / API key first
        $apiKeyHeader = $request->header('X-API-KEY')
            ?? $request->bearerToken();

        if (! empty($apiKeyHeader)) {

            return $this->tenantFromApiKey($apiKeyHeader);
        }

        // Fallback to Origin header
        $origin = $request->header('Origin');
        if (empty($origin)) {

            throw new UnauthorizedHttpException('Origin', 'No API key or Origin header provided.');
        }

        return $this->tenantFromOrigin($origin);
    }

    /**
     * Lookup Tenant by matching decrypted secret API key
     */
    protected function tenantFromApiKey(string $incoming): Tenant
    {
        $h      = hash('sha256', $incoming);
        $tenant = Tenant::whereHas('apiKeys', function ($q) use ($h) {
            $q->where('provider', ApiKeyProvidersValues::PLATFORM)
                ->where('key_hash', $h);
        })->first();

        throw_if(! $tenant, new UnauthorizedHttpException('ApiKey', 'Invalid API key.'));

        return $tenant;
    }

    /**
     * Lookup Tenant by Origin domain whitelist
     */
    protected function tenantFromOrigin(string $origin): Tenant
    {
        $parsed = parse_url($origin);
        $domain = strtolower($parsed['host'] ?? $origin);


        $tenant = Tenant::where('domain', $domain)
            ->with('user')
            ->first();
            
        if (! $tenant) {
            throw new UnauthorizedHttpException('Origin', 'Unauthorized domain: '.$domain);
        }

        return $tenant;
    }
}
