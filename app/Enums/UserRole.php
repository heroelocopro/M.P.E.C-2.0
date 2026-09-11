<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'admin';
    case Teacher = 'profesor';
    case Student = 'estudiante';
    case Guardian = 'acudiente';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrador',
            self::Teacher => 'Profesor',
            self::Student => 'Estudiante',
            self::Guardian => 'Acudiente',
        };
    }
}
