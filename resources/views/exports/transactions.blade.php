<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 5px; }
        p { color: #666; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f0f0f0; padding: 8px; text-align: left; border: 1px solid #ddd; font-size: 11px; }
        td { padding: 6px 8px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>FinTrack - Transactions Export</h1>
    <p>User: {{ $user->name }} | Period: {{ $month }}</p>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Category</th>
                <th>Description</th>
                <th>Amount</th>
                <th>Currency</th>
                <th>Wallet</th>
                <th>Merchant</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr>
                <td>{{ $row['date'] }}</td>
                <td>{{ $row['type'] }}</td>
                <td>{{ $row['category'] }}</td>
                <td>{{ $row['description'] }}</td>
                <td>{{ $row['amount'] }}</td>
                <td>{{ $row['currency'] }}</td>
                <td>{{ $row['wallet'] }}</td>
                <td>{{ $row['merchant'] }}</td>
                <td>{{ $row['notes'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
