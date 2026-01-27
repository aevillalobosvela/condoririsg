<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Envíos - CONDORIRI</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome para íconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 0px; 
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 2fr 1fr; 
            gap: 20px;
            max-width: 1480px; 
            margin: 0 auto;
            padding: 0px; 
        }

        .main-content {
            grid-column: 1;
        }

        .sidebar-content {
            grid-column: 2;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        /* Card general style */
        .card {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
            margin-bottom: 20px;
            padding: 1.5rem;
        }

        /* KPI Cards */
        .kpi-row {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap; 
        }

        .kpi-card {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
            padding: 20px;
            flex: 1;
            min-width: 200px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .kpi-card.blue { background-color: #e0f7fa; border-left: 5px solid #00acc1; }
        .kpi-card.orange { background-color: #fff3e0; border-left: 5px solid #ff8f00; }
        .kpi-card.green { background-color: #e8f5e9; border-left: 5px solid #43a047; }


        .kpi-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        .kpi-value {
            font-size: 2.2rem;
            font-weight: bold;
            color: #343a40;
        }
        .kpi-sub {
            font-size: 0.8rem;
            color: #777;
            margin-top: 10px;
        }
        .kpi-sub .trend {
            font-weight: bold;
        }
        .kpi-sub .trend.up { color: #28a745; }
        .kpi-sub .trend.down { color: #dc3545; }

        /* Table Styles */
        .table-header {
            background-color: #007bff;
            color: white;
        }
        .table-responsive {
            border-radius: 0.5rem;
            overflow: hidden; 
        }
        .table thead th {
            background-color: #007bff;
            color: white;
            border-bottom: 2px solid #dee2e6;
        }
        .table tbody tr:nth-of-type(odd) {
            background-color: rgba(0,0,0,.03);
        }
        .table tbody tr:hover {
            background-color: rgba(0,0,0,.07);
        }
        .table td, .table th {
            padding: 0.75rem;
            vertical-align: middle;
        }
        .status-badge {
            padding: .35em .65em;
            border-radius: .25rem;
            font-size: .75em;
            font-weight: 700;
            color: #fff;
        }
        .status-badge.in-transit { background-color: #ffc107; color: #212529;} 
        .status-badge.delivered { background-color: #28a745; } 
        .status-badge.pending { background-color: #17a2b8; } 
        .status-badge.canceled { background-color: #dc3545; } 

        /* Formulario de Nuevo Envío */
        .form-section .form-label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-section .form-control,
        .form-section .form-select {
            border-radius: 0.3rem;
        }
        .product-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 0.5rem;
            background-color: #fcfcfc;
        }
        .product-item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 0.25rem;
        }
        .product-item .product-info {
            flex-grow: 1;
        }
        .product-item .product-name {
            font-weight: bold;
        }
        .product-item .product-sku {
            font-size: 0.85rem;
            color: #777;
        }
        .product-item .product-actions input {
            width: 60px; 
            text-align: center;
        }

        .transport-method .btn {
            border: 1px solid #ccc;
            border-radius: 0.5rem;
            padding: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex-grow: 1;
            font-size: 0.9rem;
            color: #555;
        }
        .transport-method .btn.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        .transport-method .btn i {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        /* Statistics Chart Placeholder */
        .chart-placeholder {
            background-color: #e9ecef;
            border-radius: 0.5rem;
            height: 200px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #6c757d;
            font-weight: bold;
        }

        /* Bottom KPIs */
        .bottom-kpi-row {
            display: flex;
            justify-content: space-around;
            gap: 20px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        .bottom-kpi-card {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
            padding: 20px;
            text-align: center;
            flex: 1;
            min-width: 180px;
        }
        .bottom-kpi-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .bottom-kpi-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #343a40;
            margin-top: 5px;
        }
        .bottom-kpi-sub {
            font-size: 0.8rem;
            color: #777;
        }

        /* Responsive adjustments */
        @media (max-width: 991.98px) { /* Medium devices (tablets) */
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            .sidebar-content {
                order: -1;
            }
            .kpi-row, .bottom-kpi-row {
                flex-direction: column;
            }
            .kpi-card, .bottom-kpi-card {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="main-content">
        <br>
        <h1 class="section-title">Gestión de Envíos</h1>

        <!-- Top KPIs -->
        <div class="kpi-row">
            <div class="kpi-card blue">
                <div class="kpi-label"><i class="fas fa-box-open"></i> Envíos Pendientes</div>
                <div class="kpi-value">
                <?php
                    $pendientes = 0;
                    if (!empty($transferencias)) {
                        foreach ($transferencias as $t) {
                            if ($t['estado_destino'] == 'pendiente') {
                                $pendientes++;
                            }
                        }
                    }
                    echo $pendientes;
                ?>
                </div>
                <div class="kpi-sub">
                <?php
                    $urgentes = 0; // Lógica para envíos urgentes, no disponible con la información actual
                    echo $urgentes;
                ?> urgentes
                </div>
            </div>
            <div class="kpi-card orange">
                <div class="kpi-label"><i class="fas fa-truck-moving"></i> Envíos en Tránsito</div>
                <div class="kpi-value">
                <?php
                    $enTransito = 0;
                    if (!empty($transferencias)) {
                        foreach ($transferencias as $t) {
                            if ($t['estado_origen'] == 'enviado' && $t['estado_destino'] == 'pendiente') {
                                $enTransito++;
                            }
                        }
                    }
                    echo $enTransito;
                ?>
                </div>
                <div class="kpi-sub">
                <?php
                    $lleganHoy = 0; // Lógica para envíos que llegan hoy, no disponible con la información actual
                    echo $lleganHoy;
                ?> llegan hoy
                </div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-label"><i class="fas fa-check-circle"></i> Envíos Completados</div>
                <div class="kpi-value">
                <?php
                    $completados = 0;
                    if (!empty($transferencias)) {
                        foreach ($transferencias as $t) {
                            if ($t['estado_destino'] == 'recibido') {
                                $completados++;
                            }
                        }
                    }
                    echo $completados;
                ?>
                </div>
                <div class="kpi-sub">
                    <span class="trend up"><i class="fas fa-arrow-trend-up"></i>
                    <?php
                        // Lógica para porcentaje vs anterior, no disponible con la información actual
                        $porcentajeAnterior = 'N/A';
                        echo $porcentajeAnterior;
                    ?>
                    </span> vs anterior
                </div>
            </div>
        </div>

        <!-- Recent Shipments Table -->
        <div class="card">
            <h2 class="section-title">Envíos Recientes</h2>
            <div class="mb-3 d-flex gap-2">
                <input type="text" class="form-control" placeholder="Buscar envío..." id="searchInput">
                <select class="form-select w-auto" id="statusFilter">
                    <option value="">Todos los estados</option>
                    <option value="en_transito">En tránsito</option>
                    <option value="entregado">Entregado</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="cancelado">Cancelado</option>
                </select>
                <button class="btn btn-primary" id="searchButton"><i class="fas fa-search"></i></button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="shipmentsTable">
                    <thead>
                        <tr>
                            <th>Código Envío</th>
                            <th>Sucursal Origen</th>
                            <th>Sucursal Destino</th>
                            <th>Fecha de Envío</th>
                            <th>Producto</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($transferencias)): ?>
                            <?php foreach ($transferencias as $transferencia): ?>
                                <?php
                                    $estado_clase = '';
                                    $estado_texto = '';
                                    
                                    if ($transferencia['estado_destino'] === 'recibido') {
                                        $estado_clase = 'delivered'; 
                                        $estado_texto = 'Recibido';
                                    } else if ($transferencia['estado_destino'] === 'pendiente') {
                                        $estado_clase = 'pending'; 
                                        $estado_texto = 'Pendiente';
                                    } else if ($transferencia['estado_origen'] === 'enviado' && $transferencia['estado_destino'] === 'pendiente') {
                                        $estado_clase = 'in-transit';
                                        $estado_texto = 'En Tránsito';
                                    } else if ($transferencia['estado_destino'] === 'cancelado') {
                                        $estado_clase = 'canceled';
                                        $estado_texto = 'Cancelado';
                                    } else {
                                        $estado_clase = 'pending';
                                        $estado_texto = 'Pendiente';
                                    }
                                ?>
                                <tr>
                                    <td>#<?= esc($transferencia['code']) ?></td>
                                    <td><?= esc($transferencia['sucursal_origen_nombre'] ?? $transferencia['sucursal_origen_id']) ?></td>
                                    <td><?= esc($transferencia['sucursal_destino_nombre'] ?? $transferencia['sucursal_destino_id']) ?></td>
                                    <td><?= esc($transferencia['fecha_transferencia']) ?></td>
                                    <td><?= esc($transferencia['producto_nombre'] ?? 'Producto ID: ' . $transferencia['producto_id']) ?> (x<?= esc($transferencia['cantidad']) ?>)</td>
                                    <td>
                                        <span class="status-badge <?= $estado_clase ?>"><?= $estado_texto ?></span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('transferencias/ver/' . $transferencia['id']) ?>" class="btn btn-sm btn-outline-primary" title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= base_url('transferencias/editar/' . $transferencia['id']) ?>" class="btn btn-sm btn-outline-info" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($transferencia['estado_destino'] !== 'recibido' && $transferencia['estado_destino'] !== 'cancelado'): ?>
                                            <form action="<?= base_url('transferencias/cancelar/' . $transferencia['id']) ?>" method="post" style="display:inline;">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Cancelar" onclick="return confirm('¿Está seguro de cancelar esta transferencia?');">
                                                    <i class="fas fa-times-circle"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($transferencia['estado_destino'] === 'pendiente'): ?>
                                            <form action="<?= base_url('transferencias/recibir/' . $transferencia['id']) ?>" method="post" style="display:inline;">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Marcar como Recibido" onclick="return confirm('¿Está seguro de marcar esta transferencia como recibida?');">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form action="<?= base_url('transferencias/eliminar/' . $transferencia['id']) ?>" method="post" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="_method" value="DELETE">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar esta transferencia?');">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No hay transferencias de productos registradas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <small>Mostrando <?= count($transferencias ?? []) ?> de <?= $pager->getTotal() ?? count($transferencias ?? []) ?> envíos</small>
                <nav>
                    <?php if (isset($pager) && $pager->getPageCount() > 1): ?>
                        <?= $pager->links() ?>
                    <?php else: ?>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item disabled"><a class="page-link" href="#">«</a></li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item disabled"><a class="page-link" href="#">»</a></li>
                        </ul>
                    <?php endif; ?>
                </nav>
            </div>
        </div>

        <!-- Shipping Statistics -->
        <div class="card">
            <h2 class="section-title">Estadísticas de Envíos</h2>
            <div class="chart-placeholder">
                [Placeholder para Gráfico de Línea]
            </div>
            <div class="bottom-kpi-row">
                <div class="bottom-kpi-card">
                    <div class="bottom-kpi-label">Tiempo promedio</div>
                    <div class="bottom-kpi-value">N/A</div>
                    <div class="bottom-kpi-sub">de almacén a sucursal</div>
                </div>
                <div class="bottom-kpi-card">
                    <div class="bottom-kpi-label">Tasa de entrega</div>
                    <div class="bottom-kpi-value">N/A</div>
                    <div class="bottom-kpi-sub">Sin incidencias</div>
                </div>
                <div class="bottom-kpi-card">
                    <div class="bottom-kpi-label">Costo promedio</div>
                    <div class="bottom-kpi-value">N/A</div>
                    <div class="bottom-kpi-sub">Por envío</div>
                </div>
            </div>
        </div>
    </div>

    <div class="sidebar-content">
        
        <div class="card">
            <h2 class="section-title">Crear Nuevo Envío</h2>
            
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger">
                    <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success">
                    <?= session()->getFlashdata('success') ?>
                </div>
            <?php endif; ?>
            <?php $validation = session()->getFlashdata('validation'); ?>

            <form action="<?= base_url('transferencias/guardar') ?>" method="post">
                <?= csrf_field() ?>

                <input type="hidden" name="code" value="<?= esc($transferencia['code'] ?? 'TR-' . date('Ymd-His')) ?>">
                <input type="hidden" name="estado_origen" value="enviado">
                <input type="hidden" name="estado_destino" value="pendiente">

                <div class="mb-3">
                    <label for="sucursal_origen_id" class="form-label">Sucursal Origen</label>
                    <select class="form-select <?= $validation && $validation->hasError('sucursal_origen_id') ? 'is-invalid' : '' ?>" id="sucursal_origen_id" name="sucursal_origen_id" required>
                        <option value="">Seleccionar sucursal de origen...</option>
                        <?php if (!empty($sucursales)): ?>
                            <?php foreach ($sucursales as $sucursal): ?>
                                <option value="<?= esc($sucursal['id']) ?>" <?= old('sucursal_origen_id') == $sucursal['id'] ? 'selected' : '' ?>>
                                    <?= esc($sucursal['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <?php if ($validation && $validation->hasError('sucursal_origen_id')): ?>
                        <div class="invalid-feedback">
                            <?= $validation->getError('sucursal_origen_id') ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="sucursal_destino_id" class="form-label">Sucursal Destino</label>
                    <select class="form-select <?= $validation && $validation->hasError('sucursal_destino_id') ? 'is-invalid' : '' ?>" id="sucursal_destino_id" name="sucursal_destino_id" required>
                        <option value="">Seleccionar sucursal de destino...</option>
                        <?php if (!empty($sucursales)): ?>
                            <?php foreach ($sucursales as $sucursal): ?>
                                <option value="<?= esc($sucursal['id']) ?>" <?= old('sucursal_destino_id') == $sucursal['id'] ? 'selected' : '' ?>>
                                    <?= esc($sucursal['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <?php if ($validation && $validation->hasError('sucursal_destino_id')): ?>
                        <div class="invalid-feedback">
                            <?= $validation->getError('sucursal_destino_id') ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="producto_id" class="form-label">Seleccionar Producto:</label>
                    <select class="form-select <?= $validation && $validation->hasError('producto_id') ? 'is-invalid' : '' ?>" id="producto_id" name="producto_id" required>
                        <option value="">Selecciona un producto...</option>
                        <?php if (!empty($productos)): ?>
                            <?php foreach ($productos as $producto): ?>
                                <option value="<?= esc($producto['id']) ?>" <?= old('producto_id') == $producto['id'] ? 'selected' : '' ?>>
                                    <?= esc($producto['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <?php if ($validation && $validation->hasError('producto_id')): ?>
                        <div class="invalid-feedback">
                            <?= $validation->getError('producto_id') ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="cantidad" class="form-label">Cantidad:</label>
                    <input type="number" class="form-control <?= $validation && $validation->hasError('cantidad') ? 'is-invalid' : '' ?>" id="cantidad" name="cantidad" value="<?= old('cantidad', 1) ?>" min="1" required>
                    <?php if ($validation && $validation->hasError('cantidad')): ?>
                        <div class="invalid-feedback">
                            <?= $validation->getError('cantidad') ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="nota" class="form-label">Nota:</label>
                    <textarea class="form-control <?= $validation && $validation->hasError('nota') ? 'is-invalid' : '' ?>" id="nota" name="nota" rows="2"><?= old('nota') ?></textarea>
                    <?php if ($validation && $validation->hasError('nota')): ?>
                        <div class="invalid-feedback">
                            <?= $validation->getError('nota') ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <label for="observaciones" class="form-label">Observaciones:</label>
                    <textarea class="form-control <?= $validation && $validation->hasError('observaciones') ? 'is-invalid' : '' ?>" id="observaciones" name="observaciones" rows="2"><?= old('observaciones') ?></textarea>
                    <?php if ($validation && $validation->hasError('observaciones')): ?>
                        <div class="invalid-feedback">
                            <?= $validation->getError('observaciones') ?>
                        </div>
                    <?php endif; ?>
                </div>

              <!-- Formulario de usuarios en el sidebar -->
<div class="mb-3">
    <label for="user_id" class="form-label">Usuario Origen:</label>
    <select class="form-select <?= $validation && $validation->hasError('user_id') ? 'is-invalid' : '' ?>" id="user_id" name="user_id" required>
        <option value="">Selecciona un usuario de origen...</option>
        <?php if (!empty($usuarios)): ?>
            <?php foreach ($usuarios as $usuario): ?>
                <?php 
                // Determinar qué campo usar para mostrar
                $displayName = '';
                if (isset($usuario['nombre'])) {
                    $displayName = $usuario['nombre'];
                } elseif (isset($usuario['username'])) {
                    $displayName = $usuario['username'];
                } elseif (isset($usuario['email'])) {
                    $displayName = $usuario['email'];
                } else {
                    $displayName = 'Usuario ' . $usuario['id'];
                }
                ?>
                <option value="<?= esc($usuario['id']) ?>" <?= old('user_id') == $usuario['id'] ? 'selected' : '' ?>>
                    <?= esc($displayName) ?>
                </option>
            <?php endforeach; ?>
        <?php else: ?>
            <option value="">No hay usuarios disponibles</option>
        <?php endif; ?>
    </select>
    <?php if ($validation && $validation->hasError('user_id')): ?>
        <div class="invalid-feedback">
            <?= $validation->getError('user_id') ?>
        </div>
    <?php endif; ?>
</div>

<div class="mb-3">
    <label for="user_des_id" class="form-label">Usuario Destino:</label>
    <select class="form-select <?= $validation && $validation->hasError('user_des_id') ? 'is-invalid' : '' ?>" id="user_des_id" name="user_des_id" required>
        <option value="">Selecciona un usuario de destino...</option>
        <?php if (!empty($usuarios)): ?>
            <?php foreach ($usuarios as $usuario): ?>
                <?php 
                // Determinar qué campo usar para mostrar
                $displayName = '';
                if (isset($usuario['nombre'])) {
                    $displayName = $usuario['nombre'];
                } elseif (isset($usuario['username'])) {
                    $displayName = $usuario['username'];
                } elseif (isset($usuario['email'])) {
                    $displayName = $usuario['email'];
                } else {
                    $displayName = 'Usuario ' . $usuario['id'];
                }
                ?>
                <option value="<?= esc($usuario['id']) ?>" <?= old('user_des_id') == $usuario['id'] ? 'selected' : '' ?>>
                    <?= esc($displayName) ?>
                </option>
            <?php endforeach; ?>
        <?php else: ?>
            <option value="">No hay usuarios disponibles</option>
        <?php endif; ?>
    </select>
    <?php if ($validation && $validation->hasError('user_des_id')): ?>
        <div class="invalid-feedback">
            <?= $validation->getError('user_des_id') ?>
        </div>
    <?php endif; ?>
</div>

                <hr class="my-4">

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Crear Envío</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle JS (para componentes como dropdowns, modales, etc.) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Funcionalidad básica de búsqueda y filtrado
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const searchButton = document.getElementById('searchButton');
        const tableRows = document.querySelectorAll('#shipmentsTable tbody tr');
        
        function filterTable() {
            const searchText = searchInput.value.toLowerCase();
            const statusValue = statusFilter.value;
            
            tableRows.forEach(row => {
                const code = row.cells[0].textContent.toLowerCase();
                const origin = row.cells[1].textContent.toLowerCase();
                const destination = row.cells[2].textContent.toLowerCase();
                const status = row.cells[5].textContent.toLowerCase();
                
                const matchesSearch = code.includes(searchText) || 
                                    origin.includes(searchText) || 
                                    destination.includes(searchText);
                
                const matchesStatus = statusValue === '' || 
                                    (statusValue === 'en_transito' && status.includes('tránsito')) ||
                                    (statusValue === 'entregado' && status.includes('recibido')) ||
                                    (statusValue === 'pendiente' && status.includes('pendiente')) ||
                                    (statusValue === 'cancelado' && status.includes('cancelado'));
                
                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        searchInput.addEventListener('input', filterTable);
        statusFilter.addEventListener('change', filterTable);
        searchButton.addEventListener('click', filterTable);
    });
</script>
</body>
</html>