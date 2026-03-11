<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Inventarios</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
            font-size: 10pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            text-align: center;
        }
        h1, h2 {
            text-align: center;
            margin-bottom: 5px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header img {
            height: 80px;
        }
        .header-content {
            display: inline-block;
            vertical-align: top;
            width: 70%;
        }
        .logo-left, .logo-right {
            width: 15%;
            display: inline-block;
            vertical-align: top;
        }
        .logo-left {
            text-align: left;
        }
        .logo-right {
            text-align: right;
        }
        .totals-table {
            width: 50%;
            margin-top: 20px;
            margin-left: auto;
            margin-right: auto;
        }
        .totals-table th, .totals-table td {
            text-align: right;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo-left">
            <img src="<?= FCPATH ?>assets/img/uto.jpeg" alt="Logo UTO">
        </div>
        <div class="header-content">
            <h1>UNIVERSIDAD TÉCNICA DE ORURO</h1>
            <h2>DIRECCIÓN DE PLANIFICACIÓN Y DESARROLLO INSTITUCIONAL</h2>
            <p>Telf.: 5281745 | Interno: 120; FAX 5242215; Casilla 49</p>
            <p>Email: dpdi@uto.edu.bo; Internet: www.uto.edu.bo</p>
        </div>
        <div class="logo-right">
            <img src="<?= FCPATH ?>assets/img/condoriri.jpeg" alt="Logo DPDI">
        </div>
    </div>
    
    <h2>Reporte de Inventarios</h2>
    <p><strong>Filtros aplicados:</strong></p>
    <ul>
        <li><strong>Nombre/Código:</strong> <?= $filtros['nombre'] ?: 'Todos' ?></li>
        <li><strong>Fecha de Inicio:</strong> <?= $filtros['fecha_inicio'] ?: 'N/A' ?></li>
        <li><strong>Fecha de Fin:</strong> <?= $filtros['fecha_fin'] ?: 'N/A' ?></li>
    </ul>

    <?php 
    $grandTotalStock = 0;
    $grandTotalCredito = 0;
    $grandTotalContado = 0;
    $productNameTotalsCredito = [];
    $productNameTotalsContado = [];
    $productNameTotalsStock = [];
    ?>

    <?php foreach ($inventarios as $inventario): ?>
        <h3>Inventario: <?= $inventario->nombre ?> (Código: <?= $inventario->code ?>)</h3>
        <p><strong>Descripción:</strong> <?= $inventario->descripcion ?></p>
        <p><strong>Stock General:</strong> <?= $inventario->stock ?></p>
        <p><strong>Turno:</strong> <?= $inventario->turno ?></p>
        <p><strong>Estado:</strong> <?= $inventario->estado ? 'Activo' : 'Inactivo' ?></p>

        <?php 
        $productos = $productoModel->where('inventario_id', $inventario->id)->findAll();
        ?>

        <?php if (!empty($productos)): ?>
            <h4>Productos en este inventario:</h4>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Stock</th>
                        <th>P. Crédito</th>
                        <th>P. Contado</th>
                        <th>Cant. Prod.</th>
                        <!-- <th>Porosidad</th>
                        <th>pH</th>
                        <th>Acidez</th>
                        <th>Consistencia</th>
                        <th>Color</th>
                        <th>Olor</th>
                        <th>Textura</th> -->
                        <th>Vencimiento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $producto): ?>
                        <tr>
                            <td><?= $producto->nombre ?></td>
                            <td><?= $producto->descripcion ?></td>
                            <td><?= $producto->stock ?></td>
                            <td>Bs<?= number_format($producto->precio_credito, 2) ?></td>
                            <td>Bs<?= number_format($producto->precio_contado, 2) ?></td>
                            <td><?= $producto->cantidad_produccion ?></td>
                            <!-- <td><?=  $producto->porocidad ?></td>
                            <td><?= $producto->ph ?></td>
                            <td><?= $producto->acides ?></td>
                            <td><?= $producto->consistencia ?></td>
                            <td><?= $producto->color ?></td>
                            <td><?= $producto->olor ?></td>
                            <td><?= $producto->textura ?></td> -->
                            <td><?= $producto->fecha_vencimiento ? date('d/m/Y', strtotime($producto->fecha_vencimiento)) : 'N/A' ?></td>
                        </tr>
                        <?php 
                        $grandTotalStock += $producto->stock;
                        $grandTotalCredito += ($producto->stock * $producto->precio_credito);
                        $grandTotalContado += ($producto->stock * $producto->precio_contado);
                        $productName = $producto->nombre;
                        if (!isset($productNameTotalsCredito[$productName])) {
                            $productNameTotalsCredito[$productName] = 0;
                            $productNameTotalsContado[$productName] = 0;
                            $productNameTotalsStock[$productName] = 0;
                        }
                        $productNameTotalsCredito[$productName] += ($producto->stock * $producto->precio_credito);
                        $productNameTotalsContado[$productName] += ($producto->stock * $producto->precio_contado);
                        $productNameTotalsStock[$productName] += $producto->stock;
                        ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No hay productos asociados a este inventario.</p>
        <?php endif; ?>
    <?php endforeach; ?>

    <h3>Resumen y Totales</h3>
    <table class="totals-table">
        <tr>
            <th>Cantidad Total de Litros producidos:</th>
            <td><?= $grandTotalStock ?> Litros</td>
        </tr>
        <tr>
            <th>Valor Total (Precio Crédito):</th>
            <td>Bs<?= number_format($grandTotalCredito, 2) ?></td>
        </tr>
        <tr>
            <th>Valor Total (Precio Contado):</th>
            <td>Bs<?= number_format($grandTotalContado, 2) ?></td>
        </tr>
    </table>

    <br>

    <h3>Totales por tipo de producto</h3>
    <table>
        <thead>
            <tr>
                <th>Nombre del Producto</th>
                <th>Stock Total</th>
                <th>Valor Total Crédito</th>
                <th>Valor Total Contado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($productNameTotalsStock as $nombre => $stock): ?>
                <tr>
                    <td><?= $nombre ?></td>
                    <td><?= $stock ?></td>
                    <td>Bs<?= number_format($productNameTotalsCredito[$nombre], 2) ?></td>
                    <td>Bs<?= number_format($productNameTotalsContado[$nombre], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>
