<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reportes</title>
</head>
<body>
    <h1>Reportes</h1>
    <p>Periodo: {{ $filters['from'] }} al {{ $filters['to'] }}</p>

    <table border="1">
        <tr>
            <th>Resumen</th>
            <th>Valor</th>
            <th>Detalle</th>
        </tr>
        @foreach ($overview as $card)
            <tr>
                <td>{{ $card['label'] }}</td>
                <td>{{ $card['value'] }}</td>
                <td>{{ $card['detail'] }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Asistencia por empleado</h2>
    <table border="1">
        <tr>
            <th>Empleado</th>
            <th>Usuario</th>
            <th>Inicio asistencia</th>
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
    <table border="1">
        <tr>
            <th>Repartidor</th>
            <th>Usuario</th>
            <th>Asignadas</th>
            <th>Completadas</th>
            <th>Canceladas</th>
            <th>Total entregado</th>
        </tr>
        @foreach ($details['delivery_users'] as $row)
            <tr>
                <td>{{ $row['name'] }}</td>
                <td>{{ $row['username'] }}</td>
                <td>{{ $row['assigned_count'] }}</td>
                <td>{{ $row['completed_count'] }}</td>
                <td>{{ $row['cancelled_count'] }}</td>
                <td>${{ $row['total'] }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Clientes</h2>
    <table border="1">
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
    <table border="1">
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
    <table border="1">
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
