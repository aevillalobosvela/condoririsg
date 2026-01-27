<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= esc($title) ?></title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            color: #333;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #28a745;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header-title {
            font-size: 18pt;
            font-weight: bold;
            color: #28a745;
            text-align: center;
            text-transform: uppercase;
        }
        .meta-info {
            text-align: right;
            font-size: 8pt;
            color: #666;
            margin-top: 5px;
        }
        .filters-info {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 10px;
            margin-bottom: 20px;
            font-size: 9pt;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #28a745;
            color: white;
            font-weight: bold;
            padding: 8px;
            text-align: left;
            font-size: 9pt;
        }
        td {
            border-bottom: 1px solid #ddd;
            padding: 8px;
            vertical-align: top;
            font-size: 9pt;
        }
        .row-even {
            background-color: #f9f9f9;
        }
        .badge {
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: bold;
            color: white;
            display: inline-block;
        }
        .badge-pendiente { background-color: #ffc106; color: #333; }
        .badge-enviado { background-color: #17a2b8; }
        .badge-entregado { background-color: #28a745; }
        .badge-observado { background-color: #dc3545; }
        
        .products-table {
            width: 100%;
            margin: 0;
            border: none;
        }
        .products-table td {
            border: none;
            padding: 2px 0;
            font-size: 8.5pt;
            color: #555;
        }
        .products-header {
            font-weight: bold;
            font-size: 8.5pt;
            color: #28a745;
            border-bottom: 1px solid #eee;
            margin-bottom: 4px;
            display: block;
        }
        .no-records {
            text-align: center;
            padding: 20px;
            color: #777;
            font-style: italic;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8pt;
            color: #aaa;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="header-title"><?= esc($title) ?></div>
        <div class="meta-info">
            Generado el: <?= date('d/m/Y H:i') ?><br>
            Usuario: <?= session()->get('nombre') ?? 'Sistema' ?>
        </div>
    </div>

    <div class="filters-info">
        <strong>Filtros aplicados:</strong><br>
        <?php if ($fecha_inicio || $fecha_fin): ?>
            📅 Rango de Fechas: <?= $fecha_inicio ? date('d/m/Y', strtotime($fecha_inicio)) : 'Inicio' ?> 
            al <?= $fecha_fin ? date('d/m/Y', strtotime($fecha_fin)) : 'Actualidad' ?><br>
        <?php else: ?>
            📅 Rango de Fechas: Todo el historial<br>
        <?php endif; ?>
        📦 Tipo: <?= ucfirst($tipo) ?>
    </div>

    <?php if (empty($envios)): ?>
        <div class="no-records">
            No se encontraron registros que coincidan con los criterios de búsqueda.
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th width="15%">Código / Fecha</th>
                    <th width="20%">Ruta</th>
                    <th width="20%">Responsables</th>
                    <th width="10%">Estado</th>
                    <th width="35%">Detalle de Productos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($envios as $index => $envio): ?>
                    <tr class="<?= $index % 2 == 0 ? 'row-even' : '' ?>">
                        <td>
                            <strong><?= esc($envio['code']) ?></strong><br>
                            <small style="color: #666;">
                                Envío: <?= $envio['fecha_envio'] ? date('d/m/Y H:i', strtotime($envio['fecha_envio'])) : '—' ?>
                            </small>
                        </td>
                        <td>
                            <strong>De:</strong> <?= esc($envio['sucursal_origen_nombre'] ?? '—') ?><br>
                            <strong>A:</strong> <?= esc($envio['sucursal_destino_nombre'] ?? '—') ?>
                        </td>
                        <td>
                            <small>
                                <strong>Creado por:</strong><br> <?= esc($envio['creador_nombre'] ?? '—') ?><br>
                                <strong>Transporte:</strong><br> <?= esc($envio['transporte_nombre'] ?? '—') ?>
                            </small>
                        </td>
                        <td>
                            <?php 
                                $estadoClass = 'badge-enviado';
                                if ($envio['estado_id'] == 1) $estadoClass = 'badge-pendiente';
                                if ($envio['estado_id'] == 9) $estadoClass = 'badge-entregado';
                                if ($envio['estado_id'] == 11) $estadoClass = 'badge-observado';
                            ?>
                            <span class="badge <?= $estadoClass ?>">
                                <?= esc($envio['estado_nombre'] ?? 'Desconocido') ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($envio['productos'])): ?>
                                <div class="products-header">Cant. | Producto</div>
                                <table class="products-table">
                                    <?php foreach ($envio['productos'] as $prod): ?>
                                        <tr>
                                            <td width="15%" style="font-weight: bold;"><?= esc($prod['cantidad']) ?></td>
                                            <td><?= esc($prod['producto_nombre']) ?> <small style="color:#999;">(<?= esc($prod['producto_codigo']) ?>)</small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            <?php else: ?>
                                <span style="color: #999; font-style: italic; font-size: 8pt;">Sin productos registrados</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="footer">
        Reporte generado por el Sistema de Gestión de Inventarios - Página {PAGENO} de {nbpg}
    </div>

</body>
</html>
