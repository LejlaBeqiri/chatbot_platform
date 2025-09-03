<?php

namespace App\Http\Controllers\User\ApiKey;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiKey\StoreApiKeyRequest;
use App\Http\Requests\ApiKey\UpdateApiKeyRequest;
use App\Http\Resources\ApiKeyResource;
use App\Models\ApiKey;
use App\Values\ApiKeyProvidersValues;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class ApiKeyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $apiKeys = ApiKey::where('tenant_id', Auth::user()->tenant->id)->get();

        return $this->success(ApiKeyResource::collection($apiKeys));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreApiKeyRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $apiKey = ApiKey::create([
            'tenant_id' => Auth::user()->tenant->id,
            'provider'  => 'OPENAI',
            'key'       => Crypt::encryptString($validated['key']),
        ]);

        return $this->success(new ApiKeyResource($apiKey));
    }

    public function show(ApiKey $apiKey): JsonResponse
    {
        if ($apiKey->tenant_id !== Auth::user()->tenant->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $this->success(new ApiKeyResource($apiKey));
    }

    public function update(UpdateApiKeyRequest $request, $apiKey): JsonResponse
    {

        if ($apiKey->tenant_id !== Auth::user()->tenant->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validated();

        if (isset($validated['key'])) {
            $apiKey->key = Crypt::encryptString($validated['key']);
        }

        $apiKey->save();

        return $this->success(new ApiKeyResource($apiKey));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ApiKey $apiKey): JsonResponse
    {
        if ($apiKey->tenant_id !== Auth::user()->tenant->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $apiKey->delete();

        return $this->success(data: null, message: 'API Key deleted successfully');
    }

    public function generatePlatformAPIKey(): JsonResponse
    {
        DB::beginTransaction();
        try {
            $tenant = $this->authUser()->tenant;
            $tenant->apiKeys()
                ->where('provider', ApiKeyProvidersValues::PLATFORM)
                ->delete();

            $plainKey = 'plat_'.bin2hex(random_bytes(16));

            $encrypted = Crypt::encryptString($plainKey);
            $hash      = hash('sha256', $plainKey);
            $apiKey    = $tenant->apiKeys()->create([
                'key'      => $encrypted,
                'provider' => ApiKeyProvidersValues::PLATFORM,
                'key_hash' => $hash,
            ]);
            DB::commit();
        } catch (Exception $ex) {
            DB::rollback();

            return $this->error($ex->getMessage(), 500);
        }

        return $this->success(new ApiKeyResource($apiKey));
    }
}
