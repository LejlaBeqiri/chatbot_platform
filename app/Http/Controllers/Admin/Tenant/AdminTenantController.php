<?php

namespace App\Http\Controllers\Admin\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Http\Resources\TenantResource;
use App\Models\Domain;
use App\Models\Tenant;
use App\Models\User;
use App\Traits\ApiResponser;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminTenantController extends Controller
{
    use ApiResponser;

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $tenants = TenantResource::collection(Tenant::paginate($this->defaultPerPage));

        return $this->success($tenants->response()->getData(true));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTenantRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $userData = $validated['user'];
        unset($userData['password_confirmation']);
        $userData['password'] = Hash::make($userData['password']);

        // Generate domain from email
        $tenant_domain = Str::after($userData['email'], '@');

        DB::beginTransaction();
        try {
            $user = User::create($userData);

            $tenantData            = $validated['tenant'];
            $tenantData['user_id'] = $user->id;
            $tenantData['domain']  = $tenant_domain;

            // Check domain uniqueness
            $domainExists = Tenant::where('domain', $tenant_domain)->exists();
            if ($domainExists) {
                DB::rollBack();

                return $this->error('The domain has already been taken.');
            }

            $newTenant = Tenant::create($tenantData);
            DB::commit();

            

            return $this->success(new TenantResource($newTenant));
        } catch (Exception $ex) {
            DB::rollBack();

            return $this->error($ex->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Tenant $tenant): JsonResponse
    {
        return $this->success(new TenantResource($tenant->load('user')));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validated();

        // Check domain uniqueness if it's being updated
        if (isset($data['domain'])) {
            $domainExists = Tenant::where('domain', $data['domain'])
                ->where('id', '!=', $tenant->id)
                ->exists();

            if ($domainExists) {
                return $this->error('The domain has already been taken.');
            }
        }

        $userData   = Arr::except($data['user'] ?? [], ['id']);
        $tenantData = Arr::except($data, ['user']);

        DB::beginTransaction();

        try {
            $tenant->update($tenantData);

            if (! empty($userData)) {
                $tenant->user()->update($userData);
            }
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('Tenant Update Error: '.$ex->getMessage());

            return $this->error($ex->getMessage());
        }

        $tenant->load('user');

        return $this->success(new TenantResource($tenant));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tenant $tenant): JsonResponse
    {
        DB::beginTransaction();
        try {
            $tenant->knowledge_base()->delete();
            $tenant->chatbots()->delete();
            $tenant->apiKeys()->delete();
            $tenant->user()->delete();
            $tenant->delete();
            DB::commit();
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error('Tenant Deletion Error: '.$ex->getMessage());

            return $this->error($ex->getMessage());
        }

        return $this->success(data: null, message: 'Tenant Deleted Successfully!', code: 204);
    }
}
