<?php

namespace App\Enums;

enum Role: string
{
    case Creator  = 'creator';
    case Consumer = 'consumer';
    case Admin    = 'admin';
}
