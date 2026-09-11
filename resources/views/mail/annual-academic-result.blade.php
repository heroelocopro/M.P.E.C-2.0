{{ $messageTitle }}

@if ($average !== null)
Promedio final: {{ number_format($average, 2, ',', '.') }}
@else
No hay promedio final disponible.
@endif

Adjuntamos el reporte académico completo del año {{ $academicYear }}.
