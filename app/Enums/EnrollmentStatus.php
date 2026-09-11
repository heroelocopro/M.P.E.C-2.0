<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Active = 'activa';
    case Completed = 'completada';
    case Withdrawn = 'retirada';
    case Cancelled = 'cancelada';
}
