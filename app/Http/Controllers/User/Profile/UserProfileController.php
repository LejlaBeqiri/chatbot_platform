<?php

namespace App\Http\Controllers\User\Profile;

use App\Http\Controllers\Controller;
use App\Http\Resources\TenantResource;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'domain' => [
                'sometimes',
                'string',
                'unique:tenants,domain,'.$this->authUser()->tenant->id,
            ],
        ]);

        $tenant         = $this->authUser()->tenant;
        $tenant->domain = $data['domain'];
        $tenant->save();

        return $this->success(new TenantResource($this->authUser()->tenant->refresh()));
    }
}
