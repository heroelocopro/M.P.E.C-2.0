<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>{{ $institution_name }} {{ $period->name }} {{ $report['year'] }}</title>
    <style>
        @page {
            margin: 34px 38px 42px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            color: #202331;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            margin: 0;
        }

        .topbar {
            background: #171a2b;
            border-radius: 14px;
            color: #fff;
            padding: 22px 24px;
        }

        .brand {
            color: #aeb8ff;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        h1 {
            font-size: 23px;
            margin: 9px 0 4px;
        }

        .subtitle {
            color: #c6cae6;
            font-size: 10px;
        }

        .meta {
            border-collapse: collapse;
            margin: 20px 0;
            width: 100%;
        }

        .meta td {
            border-bottom: 1px solid #e3e5ed;
            padding: 9px 0;
        }

        .label {
            color: #73788f;
            font-size: 8px;
            text-transform: uppercase;
        }

        .value {
            color: #202331;
            font-size: 11px;
            font-weight: bold;
        }

        .summary {
            background: #eef0ff;
            border-radius: 12px;
            color: #283174;
            padding: 15px 18px;
        }

        .summary-label {
            font-size: 8px;
            letter-spacing: .7px;
            text-transform: uppercase;
        }

        .summary-value {
            font-size: 24px;
            font-weight: bold;
            margin-top: 4px;
        }

        h2 {
            color: #202331;
            font-size: 13px;
            margin: 24px 0 9px;
        }

        table.grades {
            border-collapse: collapse;
            width: 100%;
        }

        .grades th {
            background: #f1f2f7;
            color: #555a70;
            font-size: 8px;
            letter-spacing: .5px;
            padding: 10px 11px;
            text-align: left;
            text-transform: uppercase;
        }

        .grades td {
            border-bottom: 1px solid #e6e7ee;
            padding: 11px;
        }

        .grades td:last-child,
        .grades th:last-child {
            text-align: right;
        }

        .score {
            color: #2e3ea1;
            font-size: 12px;
            font-weight: bold;
        }

        .pass {
            color: #16805a;
        }

        .fail {
            color: #c0444a;
        }

        .footer {
            border-top: 1px solid #e3e5ed;
            color: #85899a;
            font-size: 8px;
            margin-top: 34px;
            padding-top: 10px;
        }
    </style>
</head>

<body>
    <header class="topbar">
        <div class="brand">{{ $institution_name }} · Comunidad educativa</div>
        <h1>Reporte final de notas</h1>
        <div class="subtitle">Resultado académico del {{ $period->name }} · Año lectivo {{ $report['year'] }}</div>
    </header>

    <table class="meta">
        <tr>
            <td>
                <div class="label">Estudiante</div>
                <div class="value">{{ $report['student'] }}</div>
            </td>
            <td>
                <div class="label">Curso</div>
                <div class="value">{{ $report['grade'] }}</div>
            </td>
        </tr>
    </table>

    <div class="summary">
        <div class="summary-label">Promedio general del periodo</div>
        <div class="summary-value">{{ number_format($report['average'], 2, ',', '.') }} <span style="font-size: 11px; font-weight: normal;">/ {{ number_format($gradingScale['maximum'], 2, ',', '.') }}</span></div>
    </div>

    <h2>Desempeño por asignatura</h2>
    <table class="grades">
        <thead>
            <tr>
                <th>Asignatura</th>
                <th>Actividades calificadas</th>
                <th>Nota final</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['subjects'] as $subject)
            <tr>
                <td>{{ $subject['name'] }}</td>
                <td>{{ $subject['activities'] }}</td>
                <td class="score {{ $subject['score'] >= $gradingScale['passing'] ? 'pass' : 'fail' }}">{{ number_format($subject['score'], 2, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="3">Aún no hay calificaciones registradas para este periodo.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Documento generado por Aula · Este reporte refleja las calificaciones registradas al momento de la descarga.</div>
</body>

</html>
