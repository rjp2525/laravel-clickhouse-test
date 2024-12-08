<!DOCTYPE html>
<html>
<head>
    <title>Daily Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1, h2 {
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Daily Report</h1>

    <h2>ClickHouse Data</h2>
    <p>Query Time: {{ $clickhouseTime }}</p>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Total Orders</th>
                <th>Top Location</th>
                <th>Products Sold</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($clickhouseData['orders'] as $order)
                <tr>
                    <td>{{ $order['date'] }}</td>
                    <td>{{ $order['order_count'] }}</td>
                    <td>
                        @foreach ($clickhouseData['topLocations'] as $location)
                            @if ($location['date'] === $order['date'])
                                {{ $location['country'] }}, {{ $location['state'] }}, {{ $location['city'] }}
                                @break
                            @endif
                        @endforeach
                    </td>
                    <td>
                        @foreach ($clickhouseData['productSales'] as $sale)
                            @if ($sale['date'] === $order['date'])
                                {{ $sale['sku'] }}: {{ $sale['total_quantity'] }}<br>
                            @endif
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
