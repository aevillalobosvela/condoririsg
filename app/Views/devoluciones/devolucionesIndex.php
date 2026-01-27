<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">📦 Gestión de Devoluciones</h3>
        <a href="<?= base_url('envios/registerDevoluciones') ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nueva Devolución
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="example1" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Sucursal Origen</th>
                        <th>Sucursal Destino</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $envios as $envio): ?>
                        <tr>
                            <td><strong><?= esc($envio['code'] ?? 'DEV-'.$envio['id']) ?></strong></td>
                            <td><?= esc($envio['sucursal_origen_nombre']) ?></td>
                            <td><?= esc($envio['sucursal_destino_nombre']) ?></td>
                            <td><?= esc($envio['producto_nombre']) ?></td>
                            <td><?= esc($envio['cantidad']) ?></td>
                            <td>
                                <?php if ($envio['estado_id'] == 1): ?>
                                    <span class="badge badge-warning">Pendiente</span>
                                <?php elseif ($envio['estado_id'] == 2): ?>
                                    <span class="badge badge-success">Enviado/Confirmado</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Desconocido</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($envio['fecha_envio'])) ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?= base_url('envios/devoluciones/show/' . $envio['id']) ?>" class="btn btn-info btn-sm" title="Ver Detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($envio['estado_id'] == 2): ?>
                                        <a href="<?= base_url('envios/reciboDevolucion/' . $envio['id']) ?>" class="btn btn-secondary btn-sm" target="_blank" title="Imprimir Recibo">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
