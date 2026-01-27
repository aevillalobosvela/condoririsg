<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
    



    <div class="container-fluid">
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
                        <h5 class="card-title mb-0">Listado de Unidades</h5>
                        <div class="flex-shrink-0">
                            <a href="/unidades/register" class="btn btn-primary">
                                <i class="ri-add-line align-bottom me-1"></i> Crear Nueva Unidad
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (session()->getFlashdata('success')): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?= session()->getFlashdata('success') ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        <?php if (session()->getFlashdata('error')): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= session()->getFlashdata('error') ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($unidades)): ?>
                            <div class="alert alert-info text-center" role="alert">
                                No hay unidades registradas.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-nowrap align-middle table-sm">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Descripción</th>
                                            <th>Creado</th>
                                            <th>Actualizado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($unidades as $unidad): ?>
                                            <tr>
                                                <td><?= $unidad['id'] ?></td>
                                                <td><?= esc($unidad['nombre']) ?></td>
                                                <td><?= esc($unidad['descripcion']) ?></td>
                                                <td><?= $unidad['created_at'] ?></td>
                                                <td><?= $unidad['updated_at'] ?></td>
                                                <td>
                                                    <a href="/unidades/edit/<?= $unidad['id'] ?>" class="btn btn-sm btn-warning">
                                                        <i class="ri-edit-line"></i> Editar
                                                    </a>
                                                   
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
    </div>
    



<?= $this->endSection() ?>

<?= $this->section('scripts') ?><?= $this->endSection() ?>