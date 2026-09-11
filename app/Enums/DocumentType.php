<?php

namespace App\Enums;

enum DocumentType: string
{
    case CivilRegistry = 'registro_civil';
    case IdentityCard = 'tarjeta_identidad';
    case CitizenshipCard = 'cedula_ciudadania';
    case ForeignerCard = 'cedula_extranjeria';
    case Passport = 'pasaporte';
    case TemporaryProtectionPermit = 'permiso_proteccion_temporal';

    public function label(): string
    {
        return match ($this) {
            self::CivilRegistry => 'Registro civil',
            self::IdentityCard => 'Tarjeta de identidad',
            self::CitizenshipCard => 'Cédula de ciudadanía',
            self::ForeignerCard => 'Cédula de extranjería',
            self::Passport => 'Pasaporte',
            self::TemporaryProtectionPermit => 'Permiso por protección temporal',
        };
    }
}
