<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pay Slip</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin-bottom: 6px; }
        .meta { margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f5f5f5; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Pay Slip</h1>
    <div class="meta">
        <div><strong>Employee:</strong> {{ $payroll->employee?->full_name }}</div>
        <div><strong>Employee ID:</strong> {{ $payroll->employee_id }}</div>
        <div><strong>Period:</strong> {{ $month }}</div>
        <div><strong>Status:</strong> {{ strtoupper($payroll->status) }}</div>
    </div>

    <table>
        <tr>
            <th>Component</th>
            <th class="right">Amount</th>
        </tr>
        <tr>
            <td>Basic Salary</td>
            <td class="right">{{ number_format((float)$payroll->basic_salary, 2) }}</td>
        </tr>
        @foreach(($payroll->allowances ?? []) as $key => $value)
            <tr>
                <td>Allowance - {{ ucwords(str_replace('_', ' ', (string)$key)) }}</td>
                <td class="right">{{ number_format((float)$value, 2) }}</td>
            </tr>
        @endforeach
        <tr>
            <td>Overtime Pay</td>
            <td class="right">{{ number_format((float)$payroll->overtime_pay, 2) }}</td>
        </tr>
        @foreach(($payroll->deductions ?? []) as $key => $value)
            <tr>
                <td>Deduction - {{ ucwords(str_replace('_', ' ', (string)$key)) }}</td>
                <td class="right">-{{ number_format((float)$value, 2) }}</td>
            </tr>
        @endforeach
        <tr>
            <th>Net Salary</th>
            <th class="right">{{ number_format((float)$payroll->net_salary, 2) }}</th>
        </tr>
    </table>
</body>
</html>
