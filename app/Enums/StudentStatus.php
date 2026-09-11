<?php

namespace App\Enums;

enum StudentStatus: string
{
    case Active = 'activo';
    case Graduated = 'egresado';
    case Withdrawn = 'retirado';
    case Inactive = 'inactivo';
}
