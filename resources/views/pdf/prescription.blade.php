<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Prescription #{{ $prescription->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #6b7280; }
        .section { margin-top: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #f9fafb; }
    </style>
</head>
<body>
    <h1>Prescription #{{ $prescription->id }}</h1>
    <p class="muted">{{ $hospitalName !== '' ? $hospitalName : 'Hospital' }} &mdash; {{ optional($prescription->created_at)->format('d M Y') }}</p>

    <div class="section">
        <strong>Patient:</strong> {{ $patientName !== '' ? $patientName : 'Patient' }}<br>
        <strong>Doctor:</strong> {{ $doctorName !== '' ? $doctorName : 'Doctor' }}<br>
        @if(filled($prescription->diagnosis))
            <strong>Diagnosis:</strong> {{ $prescription->diagnosis }}<br>
        @endif
    </div>

    <div class="section">
        <strong>Medicines</strong>
        <table>
            <thead>
                <tr>
                    <th>Medicine</th>
                    <th>Dosage</th>
                    <th>Frequency</th>
                    <th>Duration</th>
                    <th>When</th>
                    <th>Instructions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($medications as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['dosage'] }}</td>
                        <td>{{ $row['frequency'] }}</td>
                        <td>{{ $row['duration'] }}</td>
                        <td>{{ $row['when_to_take'] }}</td>
                        <td>{{ $row['special_instruction'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No medicines listed.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(filled($prescription->clinical_notes))
        <div class="section">
            <strong>Clinical notes</strong>
            <p>{{ $prescription->clinical_notes }}</p>
        </div>
    @endif
</body>
</html>
