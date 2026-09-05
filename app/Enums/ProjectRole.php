<?php

namespace App\Enums;

enum ProjectRole: string
{
    case Admin = 'admin';
    case Leader = 'leader';
    case Member = 'member';
}
