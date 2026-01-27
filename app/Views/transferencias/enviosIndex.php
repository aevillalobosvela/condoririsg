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
            /* background-color: #f8f9fa; */
           
            padding: 0px;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 2fr 1fr; /* Columna principal (tabla/estadísticas) y columna lateral (formulario) */
            gap: 20px;
            max-width: 1180px;
            margin: 0 auto;
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
            flex-wrap: wrap; /* Permite que los KPIs se envuelvan en pantallas pequeñas */
        }

        .kpi-card {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
            padding: 20px;
            flex: 1;
            min-width: 200px; /* Ancho mínimo para cada KPI */
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
            overflow: hidden; /* Para que los bordes redondeados se apliquen a la tabla */
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
        .status-badge.in-transit { background-color: #ffc107; color: #212529;} /* warning */
        .status-badge.delivered { background-color: #28a745; } /* success */
        .status-badge.pending { background-color: #17a2b8; } /* info */
        .status-badge.canceled { background-color: #dc3545; } /* danger */

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
            width: 60px; /* Ancho para campos de stock */
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
                grid-template-columns: 1fr; /* Una columna para pantallas más pequeñas */
            }
            .sidebar-content {
                order: -1; /* Mueve el formulario arriba en móviles */
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
    <div class="main-content"> <br>
        <h1 class="section-title">Gestión de Envíos</h1>

        <!-- Top KPIs -->
        <div class="kpi-row">
            <div class="kpi-card blue">
                <div class="kpi-label"><i class="fas fa-box-open"></i> Envíos Pendientes</div>
                <div class="kpi-value">12</div>
                <div class="kpi-sub">4 urgentes</div>
            </div>
            <div class="kpi-card orange">
                <div class="kpi-label"><i class="fas fa-truck-moving"></i> Envíos en Tránsito</div>
                <div class="kpi-value">8</div>
                <div class="kpi-sub">En camino, **llegan hoy**</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-label"><i class="fas fa-check-circle"></i> Envíos Completados</div>
                <div class="kpi-value">45</div>
                <div class="kpi-sub">
                    <span class="trend up"><i class="fas fa-arrow-trend-up"></i> 12%</span> vs anterior
                </div>
            </div>
        </div>

        <!-- Recent Shipments Table -->
        <div class="card">
            <h2 class="section-title">Envíos Recientes</h2>
            <div class="mb-3 d-flex gap-2">
                <input type="text" class="form-control" placeholder="Buscar envío...">
                <select class="form-select w-auto">
                    <option>Todos los estados</option>
                    <option>En tránsito</option>
                    <option>Entregado</option>
                    <option>Pendiente</option>
                    <option>Cancelado</option>
                </select>
                <button class="btn btn-primary"><i class="fas fa-search"></i></button>
            </div>
             <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID Envío</th>
                            <th>Destino</th>
                            <th>Fecha</th>
                            <th>Productos</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($transferencias)): ?>
                            <?php foreach ($transferencias as $transferencia): ?>
                                <tr>
                                    <td>#TRF-<?= esc($transferencia['id']) ?></td>
                                    <td><?= esc($transferencia['sucursal_destino_nombre'] ?? 'N/A') ?></td>
                                    <td><?= esc($transferencia['fecha_transferencia'] ?? 'N/A') ?></td>
                                    <td><?= esc($transferencia['producto_nombre'] ?? 'N/A') ?> (x<?= esc($transferencia['cantidad']) ?>)</td>
                                    <td>
                                        <?php
                                            $estado_texto = '';
                                            $estado_clase = '';
                                            if ($transferencia['estado'] === true || $transferencia['estado'] == '1') {
                                                $estado_texto = 'Activa';
                                                $estado_clase = 'delivered'; // Puedes mapear a Delivered para Activa
                                            } else {
                                                $estado_texto = 'Inactiva';
                                                $estado_clase = 'canceled'; // O a Canceled para Inactiva
                                            }
                                            // En un sistema real, mapearías los estados más complejos: Pendiente, En Camino, Entregado, etc.
                                        ?>
                                        <span class="status-badge <?= $estado_clase ?>"><?= $estado_texto ?></span>
                                    </td>
                                    <td>
                                        <!-- Botones de acción para la tabla -->
                                        <a href="<?= base_url('transferencias/edit/' . $transferencia['id']) ?>" class="btn btn-sm btn-outline-info" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="<?= base_url('transferencias/delete/' . $transferencia['id']) ?>" method="post" style="display:inline;">
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
                                <td colspan="6" class="text-center">No hay transferencias de productos registradas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <small>Mostrando 5 de 45 envíos</small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item disabled"><a class="page-link" href="#">«</a></li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item"><a class="page-link" href="#">»</a></li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Shipping Statistics -->
        <div class="card">
            <h2 class="section-title">Estadísticas de Envíos</h2>
            <div class="chart-placeholder">
                            </div>
            <div class="bottom-kpi-row">
                <div class="bottom-kpi-card">
                    <div class="bottom-kpi-label">Tiempo promedio</div>
                    <div class="bottom-kpi-value">2.3 días</div>
                    <div class="bottom-kpi-sub">de almacén a sucursal</div>
                </div>
                <div class="bottom-kpi-card">
                    <div class="bottom-kpi-label">Tasa de entrega</div>
                    <div class="bottom-kpi-value">98.2%</div>
                    <div class="bottom-kpi-sub">Sin incidencias</div>
                </div>
                <div class="bottom-kpi-card">
                    <div class="bottom-kpi-label">Costo promedio</div>
                    <div class="bottom-kpi-value">Bs. 450</div>
                    <div class="bottom-kpi-sub">Por envío</div>
                </div>
            </div>
        </div>
    </div>

    <div class="sidebar-content">
        <!-- Create New Shipping Form -->
        <div class="card">
            <h2 class="section-title">Crear Nuevo Envío</h2>
            <div class="mb-3">
                <label for="sucursalDestino" class="form-label">Sucursal Destino</label>
                <select class="form-select" id="sucursalDestino">
                    <option>Seleccionar sucursal...</option>
                    <option>Sucursal La Paz</option>
                    <option>Sucursal El Alto</option>
                    <option>Sucursal Cochabamba</option>
                    <option>Sucursal Santa Cruz</option>
                    <option>Sucursal Oruro</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="fechaEnvio" class="form-label">Fecha de Envío</label>
                <input type="date" class="form-control" id="fechaEnvio" value="2025-08-20">
            </div>

            <h3 class="section-title mt-4">Productos</h3>
            <div class="products-list">
                <div class="product-item">
                    <img src="https://placehold.co/50x50/cccccc/ffffff?text=Prod1" alt="Monitor LED">
                    <div class="product-info">
                        <div class="product-name">Monitor LED</div>
                        <div class="product-sku">SKU: MON-24-LED</div>
                    </div>
                    <div class="product-actions d-flex align-items-center gap-2">
                        <input type="number" class="form-control" value="24" min="1">
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <div class="product-item">
                    <img src="https://placehold.co/50x50/cccccc/ffffff?text=Prod2" alt="Teclado Mecánico RGB">
                    <div class="product-info">
                        <div class="product-name">Teclado Mecánico RGB</div>
                        <div class="product-sku">SKU: TEC-MEC-RGB</div>
                    </div>
                    <div class="product-actions d-flex align-items-center gap-2">
                        <input type="number" class="form-control" value="16" min="1">
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <div class="product-item">
                    <img src="https://placehold.co/50x50/cccccc/ffffff?text=Prod3" alt="Mouse Inalámbrico">
                    <div class="product-info">
                        <div class="product-name">Mouse Inalámbrico</div>
                        <div class="product-sku">SKU: MOU-INAL-01</div>
                    </div>
                    <div class="product-actions d-flex align-items-center gap-2">
                        <input type="number" class="form-control" value="82" min="1">
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></button>
                    </div>
                </div>
            </div>
            <button class="btn btn-outline-secondary w-100 mt-2 mb-4">Agregar más productos</button>

            <h3 class="section-title mt-4">Método de Transporte</h3>
            <div class="transport-method d-flex gap-3 mb-4">
                <button class="btn btn-light active">
                    <i class="fas fa-truck-moving"></i>
                    Camión Propio
                </button>
                <button class="btn btn-light">
                    <i class="fas fa-shipping-fast"></i>
                    Servicio Externo
                </button>
                <button class="btn btn-light">
                    <i class="fas fa-hand-holding"></i>
                    Recogida
                </button>
            </div>

            <div class="mb-4">
                <label for="notasAdicionales" class="form-label">Notas Adicionales</label>
                <textarea class="form-control" id="notasAdicionales" rows="3" placeholder="Instrucciones especiales para este envío..."></textarea>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    Total productos: <strong>16</strong><br>
                    Unidades: <strong>24.5 kg</strong>
                </div>
                <div>
                    <button class="btn btn-secondary me-2">Guardar borrador</button>
                    <button class="btn btn-primary">Crear Envío</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle JS (para componentes como dropdowns, modales, etc.) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
