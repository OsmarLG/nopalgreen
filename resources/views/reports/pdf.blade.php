<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reportes {{ $filters['from'] }} - {{ $filters['to'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #1f2937;
            margin: 24px;
        }

        h1, h2 {
            margin: 0 0 10px;
        }

        h2 {
            margin-top: 28px;
            color: #2c5d1f;
        }

        p {
            margin: 0 0 12px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin: 18px 0 24px;
        }

        .card {
            border: 1px solid #d6d3d1;
            border-radius: 12px;
            padding: 14px;
        }

        .card strong {
            display: block;
            font-size: 20px;
            margin-top: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 1px solid #d6d3d1;
            padding: 8px;
            font-size: 12px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f5faed;
            color: #2c5d1f;
        }

        .hint {
            color: #57534e;
            font-size: 12px;
            margin-top: 16px;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Imprimir / Guardar PDF</button>
        <p class="hint">Usa la impresora del navegador y selecciona "Guardar como PDF".</p>
    </div>

    <h1>Reportes</h1>
    <p>Periodo: {{ $filters['from'] }} al {{ $filters['to'] }}</p>

    <div class="cards">
        @foreach ($overview as $card)
            <div class="card">
                <div>{{ $card['label'] }}</div>
                <strong>{{ str_contains(strtolower($card['label']), 'balance') ? '$'.$card['value'] : $card['value'] }}</strong>
                <div>{{ $card['detail'] }}</div>
            </div>
        @endforeach
    </div>

    <h2>Asistencia por empleado</h2>
    <table>
        <tr>
            <th>Empleado</th>
            <th>Usuario</th>
            <th>Inicio</th>
            <th>Asistencias</th>
            <th>Retardos</th>
            <th>Faltas</th>
            <th>Faltas por retardos</th>
            <th>Dispositivos</th>
        </tr>
        @foreach ($details['employees'] as $row)
            <tr>
                <td>{{ $row['name'] }}</td>
                <td>{{ $row['username'] }}</td>
                <td>{{ $row['attendance_starts_at'] }}</td>
                <td>{{ $row['attendances'] }}</td>
                <td>{{ $row['tardies'] }}</td>
                <td>{{ $row['absences'] }}</td>
                <td>{{ $row['absence_equivalents'] }}</td>
                <td>{{ $row['devices_count'] }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Repartidores</h2>
    <table>
        <tr>
            <th>Repartidor</th>
            <th>Asignadas</th>
            <th>Completadas</th>
            <th>Canceladas</th>
            <th>Total</th>
        </tr>
        @foreach ($details['delivery_users'] as $row)
            <tr>
                <td>{{ $row['name'] }}</td>
                <td>{{ $row['assigned_count'] }}</td>
                <td>{{ $row['completed_count'] }}</td>
                <td>{{ $row['cancelled_count'] }}</td>
                <td>${{ $row['total'] }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Clientes</h2>
    <table>
        <tr>
            <th>Cliente</th>
            <th>Ventas</th>
            <th>Descuento</th>
            <th>Total</th>
            <th>Ultima venta</th>
        </tr>
        @foreach ($details['customers'] as $row)
            <tr>
                <td>{{ $row['name'] }}</td>
                <td>{{ $row['sales_count'] }}</td>
                <td>${{ $row['discount_total'] }}</td>
                <td>${{ $row['total'] }}</td>
                <td>{{ $row['last_sale_at'] }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Productos</h2>
    <table>
        <tr>
            <th>Producto</th>
            <th>Vendido</th>
            <th>Total venta</th>
            <th>Comprado</th>
            <th>Total compra</th>
            <th>Producido</th>
        </tr>
        @foreach ($details['products'] as $row)
            <tr>
                <td>{{ $row['name'] }}</td>
                <td>{{ $row['sold_quantity'] }}</td>
                <td>${{ $row['sales_total'] }}</td>
                <td>{{ $row['purchased_quantity'] }}</td>
                <td>${{ $row['purchase_total'] }}</td>
                <td>{{ $row['produced_quantity'] }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Finanzas recientes</h2>
    <table>
        <tr>
            <th>Folio</th>
            <th>Concepto</th>
            <th>Fuente</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th>Monto</th>
            <th>Fecha</th>
        </tr>
        @foreach ($finances['recent'] as $row)
            <tr>
                <td>{{ $row['folio'] }}</td>
                <td>{{ $row['concept'] }}</td>
                <td>{{ $row['source'] }}</td>
                <td>{{ $row['transaction_type'] }}</td>
                <td>{{ $row['status'] }}</td>
                <td>${{ $row['amount'] }}</td>
                <td>{{ $row['occurred_at'] }}</td>
            </tr>
        @endforeach
    </table>
</body>
</html>
