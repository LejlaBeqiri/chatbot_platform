<?php

namespace App\Values;

enum RoleValues: string
{
    case Admin  = 'admin';
    case Tenant = 'tenant';
}
