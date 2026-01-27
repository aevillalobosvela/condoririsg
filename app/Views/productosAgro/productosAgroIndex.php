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
                            <li class="breadcrumb-item"><a href="/dashboard">Inicio</a></li>
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
                        <h5 class="card-title mb-0">Listado de Productos Agropecuarios</h5>
                        <div class="flex-shrink-0">
                            <a href="<?= base_url('productosagro/create') ?>" class="btn btn-primary">
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
                                            <th>Code</th>
                                            <th>Producto</th>
                                            <th>Descripcion</th>
                                            <th>Precio Crédito</th>
                                            <th>Precio Contado</th>
                                            <th>cantidad</th>
                                            <th>Categoría</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($productos as $producto): ?>
                                            <tr>
                                               <td><?= esc($producto->code) ?></td>
                                                <td><?= esc($producto->producto) ?></td>
                                                <td><?= esc($producto->descripcion) ?></td>
                                                <td><?= number_format($producto->precio_credito, 2) ?></td>
                                                <td><?= number_format($producto->precio_contado, 2) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $producto->cantidad > 0 ? 'success' : 'danger' ?>">
                                                        <?= $producto->cantidad ?>
                                                    </span>
                                                </td>
                                                <td><?= $producto->categoria ?></td>
                                                
                                                <td>
                                                    <div class="btn-group" role="group">
                                                       
                                                        <a href="<?= base_url('productosagro/edit/' . $producto->id) ?>" 
                                                            class="btn btn-soft-warning btn-sm" title="Editar">
                                                            <i class="">Editar</i>
                                                        </a>
                                                       
                                                    </div>
                                                    <div class="btn-group" role="group">
                                                       
                                                        <a href="<?= base_url('productosagro/delete/' . $producto->id) ?>" 
                                                            class="btn btn-soft-danger btn-sm" title="Eliminiar">
                                                            <i class="">Eliminar</i>
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