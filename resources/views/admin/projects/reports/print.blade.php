<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Project Reports</title>
    <style>
        body { color: #0f172a; font-family: Arial, sans-serif; margin: 32px; }
        h1 { margin: 0 0 4px; }
        p { color: #64748b; margin: 0 0 24px; }
        .kpis { display: grid; gap: 12px; grid-template-columns: repeat(4, 1fr); margin-bottom: 24px; }
        .kpis div { border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; }
        .kpis strong { display: block; font-size: 22px; }
        .kpis span { color: #64748b; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border-bottom: 1px solid #e2e8f0; font-size: 12px; padding: 10px; text-align: left; }
        th { color: #475569; text-transform: uppercase; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <button onclick="window.print()">Print</button>
    <h1>Project Reports</h1>
    <p>Generated {{ $generatedAt->format('d M Y H:i') }}</p>
    <section class="kpis">
        <div><strong>{{ number_format($kpis['total_projects']) }}</strong><span>Total Projects</span></div>
        <div><strong>{{ number_format($kpis['active_projects']) }}</strong><span>Active Projects</span></div>
        <div><strong>{{ $kpis['overall_completion'] }}</strong><span>Overall Completion</span></div>
        <div><strong>{{ $kpis['billable_hours'] }}</strong><span>Billable Hours</span></div>
    </section>
    <table>
        <thead>
            <tr>
                <th>Project</th>
                <th>Progress</th>
                <th>Owner</th>
                <th>Status</th>
                <th>Due Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($recentDelivery as $project)
                <tr>
                    <td>{{ $project->project_number }} - {{ $project->title }}</td>
                    <td>{{ $project->progress }}%</td>
                    <td>{{ $project->projectManager?->name ?: '-' }}</td>
                    <td>{{ str($project->status)->headline() }}</td>
                    <td>{{ $project->due_date?->format('d M Y') ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
