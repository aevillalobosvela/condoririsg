<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

  
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0"><?= esc($title) ?></h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                            <li class="breadcrumb-item active"><?= esc($title) ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">Listado de Productos</h5>
                        <div class="flex-shrink-0">
                            <a href="<?= base_url('productos/register') ?>" class="btn btn-primary">
                                <i class="ri-add-line align-bottom me-1"></i> Nuevo Producto
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (session()->getFlashdata('message')): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?= session()->getFlashdata('message') ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (session()->getFlashdata('error')): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= session()->getFlashdata('error') ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($productos)): ?>
                            <div class="alert alert-info text-center" role="alert">
                                No hay productos registrados.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-nowrap align-middle table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nombre1</th>
                                            <th>Precio Crédito</th>
                                            <th>Precio Contado</th>
                                            <th>Stock</th>
                                            <th>Categoría ID</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($productos as $producto): ?>
                                            <tr>
                                                <td><?= esc($producto->nombre) ?></td>
                                                <td><?= number_format($producto->precio_credito, 2) ?></td>
                                                <td><?= number_format($producto->precio_contado, 2) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $producto->stock > 0 ? 'success' : 'danger' ?>">
                                                        <?= $producto->stock ?>
                                                    </span>
                                                </td>
                                                <td><?= $producto->categoria_id ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $producto->estado ? 'success' : 'danger' ?>">
                                                        <?= $producto->estado ? 'Activo' : 'Inactivo' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                       
                                                        <a href="<?= base_url('productos/edit/' . $producto->id) ?>" 
                                                            class="btn btn-soft-warning btn-sm" title="Editar">
                                                            <i class="ri-pencil-fill"></i>
                                                        </a>
                                                       
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

   
<?= $this->endSection() ?>

<?= $this->section('scripts') ?><?= $this->endSection() ?>