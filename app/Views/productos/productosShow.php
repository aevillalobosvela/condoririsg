<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
    .producto-img {
        max-width: 300px;
        max-height: 300px;
        object-fit: contain;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border: 2px solid #e9ecef;
    }
    .info-card {
        border-left: 4px solid #0d6efd;
    }
    .calidad-card {
        border-left: 4px solid #20c997;
    }
    .produccion-card {
        border-left: 4px solid #fd7e14;
    }
    .badge-estado {
        font-size: 0.875em;
    }
    .calculation-result {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 5px;
        padding: 10px;
        margin-top: 10px;
    }
    .result-item {
        margin-bottom: 5px;
        font-size: 14px;
    }
    .result-highlight {
        font-weight: bold;
        color: #198754;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= esc($title) ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/productos">Productos</a></li>
                    <li class="breadcrumb-item active"><?= esc($title) ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <?php if (session()->getFlashdata('message')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="ri-check-line align-middle me-1"></i>
                        <strong>¡Éxito!</strong> <?= session()->getFlashdata('message') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="hstack gap-2 justify-content-end mb-4">
                    <a href="<?= site_url('productos/edit/' . $producto['id']) ?>" class="btn btn-warning">
                        <i class="ri-pencil-fill align-bottom me-1"></i> Editar
                    </a>
                    <a href="<?= site_url('productos') ?>" class="btn btn-secondary">
                        <i class="ri-arrow-left-line align-bottom me-1"></i> Volver a Productos
                    </a>
                </div>

                <div class="row g-4">
                    <!-- Columna Izquierda - Información Básica -->
                    <div class="col-lg-6">
                        <div class="card info-card h-100">
                            <div class="card-header bg-transparent border-bottom">
                                <h5 class="card-title mb-0 text-primary">
                                    <i class="ri-information-fill me-2"></i>Información Básica
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Imagen del Producto -->
                                <?php if (!empty($producto['imagen'])): ?>
                                <div class="text-center mb-4">
                                    <img src="<?= base_url('uploads/' . $producto['imagen']) ?>" 
                                         alt="Imagen del producto" 
                                         class="producto-img">
                                </div>
                                <?php endif; ?>

                                <div class="table-responsive">
                                    <table class="table table-borderless table-sm">
                                        <tbody>
                                            <tr>
                                                <td class="fw-bold" style="width: 40%">ID:</td>
                                                <td><span class="badge bg-dark">#<?= esc($producto['id']) ?></span></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Producto:</td>
                                                <td>
                                                    <span class="badge bg-primary"><?= esc($producto['nombre']) ?></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Descripción:</td>
                                                <td><?= esc($producto['descripcion'] ?: '<span class="text-muted">N/A</span>') ?></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Precio Crédito:</td>
                                                <td>
                                                    <span class="badge bg-success fs-6">
                                                        Bs. <?= number_format($producto['precio_credito'], 2) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Precio Contado:</td>
                                                <td>
                                                    <span class="badge bg-info fs-6">
                                                        Bs. <?= number_format($producto['precio_contado'], 2) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Categoría:</td>
                                                <td>
                                                    <span class="badge bg-secondary"><?= esc($producto['categoria_nombre'] ?: 'N/A') ?></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Unidad:</td>
                                                <td><?= esc($producto['unidad_nombre'] ?: 'N/A') ?></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Estado:</td>
                                                <td>
                                                    <span class="badge bg-<?= $producto['estado'] ? 'success' : 'danger' ?>">
                                                        <?= $producto['estado'] ? 'Activo' : 'Inactivo' ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha - Información de Producción -->
                    <div class="col-lg-6">
                        <div class="card produccion-card h-100">
                            <div class="card-header bg-transparent border-bottom">
                                <h5 class="card-title mb-0 text-warning">
                                    <i class="ri-calculator-line me-2"></i>Información de Producción
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-borderless table-sm">
                                        <tbody>
                                            <tr>
                                                <td class="fw-bold" style="width: 40%">Inventario:</td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?= esc($producto['inventario_nombre'] ?: 'N/A') ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Litros Disponibles:</td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?= number_format($producto['inventario_stock'] ?? 0, 2) ?> litros
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Litros Utilizados:</td>
                                                <td>
                                                    <span class="badge bg-warning text-dark">
                                                        <?= number_format($producto['cantidad_litros_leche'] ?? $producto['cantidad_produccion'], 2) ?> litros
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Litros por Unidad:</td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?= number_format($producto['cantidad_unidad'], 2) ?> litros/unidad
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Stock Actual:</td>
                                                <td>
                                                    <span class="badge bg-<?= $producto['stock'] > 0 ? 'success' : 'danger' ?> fs-6">
                                                        <?= esc($producto['stock']) ?> unidades
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Reserva de Leche:</td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?= number_format($producto['reserva'], 2) ?> litros
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Resultados del Cálculo (Similar al form) -->
                                <div class="calculation-result mt-3">
                                    <h6 class="text-primary mb-3"><i class="ri-calculator-fill me-2"></i>Resumen de Producción</h6>
                                    <div class="result-item">
                                        <span>Cantidad de productos producidos:</span>
                                        <span class="result-highlight"><?= esc($producto['stock']) ?> unidades</span>
                                    </div>
                                    <div class="result-item">
                                        <span>Litros utilizados en producción:</span>
                                        <span class="result-highlight">
                                            <?= number_format(($producto['stock'] * $producto['cantidad_unidad']), 2) ?> litros
                                        </span>
                                    </div>
                                    <div class="result-item">
                                        <span>Reserva de leche sobrante:</span>
                                        <span class="result-highlight"><?= number_format($producto['reserva'], 2) ?> litros</span>
                                    </div>
                                    <?php if ($producto['cantidad_litros_leche'] > 0): ?>
                                    <div class="result-item">
                                        <span>Eficiencia de producción:</span>
                                        <?php 
                                        $litrosUtilizados = $producto['stock'] * $producto['cantidad_unidad'];
                                        $eficiencia = ($litrosUtilizados / $producto['cantidad_litros_leche']) * 100;
                                        $color = $eficiencia >= 90 ? 'success' : ($eficiencia >= 70 ? 'warning' : 'danger');
                                        ?>
                                        <span class="badge bg-<?= $color ?>"><?= number_format($eficiencia, 1) ?>%</span>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="ri-information-line me-1"></i>
                                        Creado por: <?= esc($producto['usuario_nombre'] ?: 'N/A') ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Control de Calidad -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card calidad-card">
                            <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0 text-success">
                                    <i class="ri-microscope-line me-2"></i>Control de Calidad
                                </h5>
                                <span class="badge bg-<?= $producto['fecha_vencimiento'] && strtotime($producto['fecha_vencimiento']) < time() ? 'danger' : 'success' ?>">
                                    <?= $producto['fecha_vencimiento'] ? 'Vence: ' . date('d/m/Y', strtotime($producto['fecha_vencimiento'])) : 'Sin vencimiento' ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-borderless table-sm">
                                            <tbody>
                                                <tr>
                                                    <td class="fw-bold" style="width: 40%">Fecha Vencimiento:</td>
                                                    <td>
                                                        <?php if ($producto['fecha_vencimiento']): ?>
                                                            <?php 
                                                            $hoy = date('Y-m-d');
                                                            $vencimiento = $producto['fecha_vencimiento'];
                                                            $dias = floor((strtotime($vencimiento) - strtotime($hoy)) / (60 * 60 * 24));
                                                            $color = $dias < 0 ? 'danger' : ($dias < 7 ? 'warning' : 'success');
                                                            ?>
                                                            <span class="badge bg-<?= $color ?>">
                                                                <?= date('d/m/Y', strtotime($vencimiento)) ?>
                                                                <?= $dias < 0 ? '(Vencido)' : "($dias días restantes)" ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">N/A</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold">Porosidad:</td>
                                                    <td>
                                                        <?php if ($producto['porocidad']): ?>
                                                            <span class="badge bg-info"><?= esc($producto['porocidad']) ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">N/A</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold">PH:</td>
                                                    <td>
                                                        <?php if ($producto['ph']): ?>
                                                            <span class="badge bg-primary"><?= esc($producto['ph']) ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">N/A</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold">Acidez:</td>
                                                    <td><?= esc($producto['acides'] ?: '<span class="text-muted">N/A</span>') ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold">Consistencia:</td>
                                                    <td><?= esc($producto['consistencia'] ?: '<span class="text-muted">N/A</span>') ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-borderless table-sm">
                                            <tbody>
                                                <tr>
                                                    <td class="fw-bold" style="width: 40%">Color:</td>
                                                    <td><?= esc($producto['color'] ?: '<span class="text-muted">N/A</span>') ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold">Olor:</td>
                                                    <td><?= esc($producto['olor'] ?: '<span class="text-muted">N/A</span>') ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold">Textura:</td>
                                                    <td><?= esc($producto['textura'] ?: '<span class="text-muted">N/A</span>') ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold">Observaciones:</td>
                                                    <td>
                                                        <?php if ($producto['observaciones']): ?>
                                                            <div class="alert alert-light border small">
                                                                <?= nl2br(esc($producto['observaciones'])) ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">N/A</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de Auditoría -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-transparent border-bottom">
                                <h6 class="card-title mb-0 text-muted">
                                    <i class="ri-history-line me-2"></i>Información de Auditoría
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <div class="border-end">
                                            <h6 class="text-muted mb-1">Creado el</h6>
                                            <p class="mb-0 fw-bold"><?= date('d/m/Y H:i', strtotime($producto['created_at'])) ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="border-end">
                                            <h6 class="text-muted mb-1">Actualizado el</h6>
                                            <p class="mb-0 fw-bold"><?= date('d/m/Y H:i', strtotime($producto['updated_at'])) ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div>
                                            <h6 class="text-muted mb-1">Última Actualización</h6>
                                            <p class="mb-0 fw-bold">
                                                <?php
                                                $diferencia = time() - strtotime($producto['updated_at']);
                                                if ($diferencia < 60) {
                                                    echo 'Hace unos segundos';
                                                } elseif ($diferencia < 3600) {
                                                    echo 'Hace ' . floor($diferencia / 60) . ' minutos';
                                                } elseif ($diferencia < 86400) {
                                                    echo 'Hace ' . floor($diferencia / 3600) . ' horas';
                                                } else {
                                                    echo 'Hace ' . floor($diferencia / 86400) . ' días';
                                                }
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Funcionalidad adicional si es necesaria
        console.log('Página de detalles del producto cargada');
        
        // Puedes agregar más interactividad aquí si lo necesitas
    });
</script>
<?= $this->endSection() ?>