<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Relatório de atendimentos</title>
    <style>
        @page { margin: 24px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 9px; }
        h1 { margin: 0 0 4px; font-size: 18px; }
        h2 { margin: 18px 0 6px; font-size: 12px; }
        .muted { color: #6b7280; }
        .summary { width: 100%; margin-top: 14px; border-spacing: 6px; }
        .summary td { padding: 9px; background: #f3f4f6; }
        .summary strong { display: block; margin-top: 3px; font-size: 12px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { padding: 6px 4px; background: #1f4e78; color: white; text-align: left; }
        table.data td { padding: 5px 4px; border-bottom: 1px solid #d1d5db; vertical-align: top; }
        .amount { text-align: right; white-space: nowrap; }
        .footer { margin-top: 16px; font-size: 8px; color: #6b7280; }
    </style>
</head>
<body>
    <h1>Relatório de atendimentos</h1>
    <div class="muted">
        Período: {{ $report['period']['date_from'] }} a {{ $report['period']['date_to'] }}
        · Gerado em {{ $generatedAt->format('d/m/Y H:i') }} por {{ $generatedBy->name }}
    </div>

    <table class="summary">
        <tr>
            <td>Atendimentos<strong>{{ $report['summary']['total_attendances'] }}</strong></td>
            <td>Pacientes únicos<strong>{{ $report['summary']['unique_patients'] }}</strong></td>
            <td>Total cobrado<strong>{{ $report['summary']['total_charged'] }}</strong></td>
            <td>Total recebido<strong>{{ $report['summary']['received_for_attendances'] }}</strong></td>
            <td>Saldo pendente<strong>{{ $report['summary']['total_pending'] }}</strong></td>
            <td>Caixa no período<strong>{{ $report['summary']['cash_received_in_period'] }}</strong></td>
        </tr>
    </table>

    <h2>Atendimentos</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Data</th>
                <th>Paciente</th>
                <th>Médico / especialidade</th>
                <th>Procedimentos</th>
                <th class="amount">Cobrado</th>
                <th class="amount">Pago</th>
                <th class="amount">Pendente</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($attendances as $attendance)
                @php($pending = max(0, (float) $attendance->total_amount - (float) $attendance->amount_paid))
                <tr>
                    <td>{{ $attendance->attendance_date->format('d/m/Y') }}</td>
                    <td>{{ $attendance->patient->name }}</td>
                    <td>{{ $attendance->doctor->user->name }}<br><span class="muted">{{ $attendance->doctor->speciality }}</span></td>
                    <td>
                        {{ $attendance->procedures->map(fn ($procedure) => $procedure->procedure.' ('.$procedure->pivot->price.')')->implode(', ') }}
                    </td>
                    <td class="amount">{{ $attendance->total_amount }}</td>
                    <td class="amount">{{ $attendance->amount_paid }}</td>
                    <td class="amount">{{ number_format($pending, 2, '.', '') }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Nenhum atendimento encontrado para os filtros informados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Resumo por médico</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Médico</th>
                <th>Especialidade</th>
                <th>Atendimentos</th>
                <th class="amount">Cobrado</th>
                <th class="amount">Recebido</th>
                <th class="amount">Pendente</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['by_doctor'] as $doctor)
                <tr>
                    <td>{{ $doctor->doctor }}</td>
                    <td>{{ $doctor->speciality }}</td>
                    <td>{{ $doctor->total_attendances }}</td>
                    <td class="amount">{{ $doctor->total_charged }}</td>
                    <td class="amount">{{ $doctor->total_received }}</td>
                    <td class="amount">{{ $doctor->total_pending }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Resumo por procedimento</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Procedimento</th>
                <th>Quantidade</th>
                <th class="amount">Total cobrado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['by_procedure'] as $procedure)
                <tr>
                    <td>{{ $procedure->procedure }}</td>
                    <td>{{ $procedure->usage_count }}</td>
                    <td class="amount">{{ $procedure->total_charged }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">Documento gerado automaticamente pelo Sistema de Gestão Hospitalar.</div>
</body>
</html>
