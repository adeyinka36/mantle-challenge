<?php
declare(strict_types=1);

namespace App\Enum;

enum CustomerType: string
{
    case Regular = 'regular';
    case Vip = 'vip';
    case Staff = 'staff';
    case Trade = 'trade';
    case Student = 'student';
}
