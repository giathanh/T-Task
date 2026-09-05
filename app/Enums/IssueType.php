<?php

namespace App\Enums;

enum IssueType: string
{
    case Task = 'task';
    case Bug = 'bug';
}
