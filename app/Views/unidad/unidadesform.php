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
                            <li class="breadcrumb-item"><a href="/unidades">Unidades</a></li>
                            <li class="breadcrumb-item active"><?= esc($title) ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Formulario de Unidad</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= esc($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form action="<?= isset($unidad['id']) ? '/unidades/update/' . $unidad['id'] : '/unidades/create' ?>" method="post">
                            <?= csrf_field() ?> 

                            <div class="mb-3"> 
                                <label for="nombre" class="form-label">Nombre de la Unidad:</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" value="<?= old('nombre', $unidad['nombre'] ?? '') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción:</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= old('descripcion', $unidad['descripcion'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <?php
                                $userId = session()->get('id');
                                ?>
                                <input type="hidden" id="user_id" name="user_id" value="<?= $userId ?>">
                            </div>

                            <div class="hstack gap-2 justify-content-end">
                                <button type="submit" class="btn btn-success">Guardar Unidad</button>
                                <a href="/unidades" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>




<?= $this->endSection() ?>

<?= $this->section('scripts') ?><?= $this->endSection() ?>
