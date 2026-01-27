<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
 <?php
    $currentPath = $_SERVER['REQUEST_URI'];
   
    
     $userId = session()->get('id'); 
      $userSucursalName = session()->get('sucursal_id'); 
   

  ?>

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0"><?= esc($title) ?></h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="/sucursales">Sucursales</a></li>
                            <li class="breadcrumb-item active"><?= isset($sucursal['id']) ? 'Editar' : 'Crear' ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Formulario de Sucursal</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= esc($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="<?= isset($sucursal['id']) ? '/sucursales/update/' . $sucursal['id'] : '/sucursales/create' ?>" method="post">
                            <?= csrf_field() ?> 

                            <div class="row g-3">
                                <div class="col-md-12"> 
                                    <label for="nombre" class="form-label">Nombre de la Sucursal:</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?= old('nombre', $sucursal['nombre'] ?? '') ?>" placeholder="Ej. Sucursal Central" required>
                                </div>

                                <div class="col-md-12">
                                    <label for="descripcion" class="form-label">Descripción:</label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3" placeholder="Descripción breve de la sucursal"><?= old('descripcion', $sucursal['descripcion'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="col-md-12">
                                    <label for="direccion" class="form-label">Dirección:</label>
                                    <textarea class="form-control" id="direccion" name="direccion" rows="3" placeholder="Dirección completa de la sucursal"><?= old('direccion', $sucursal['direccion'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="col-md-12">
                                    <label for="telefono" class="form-label">Teléfono:</label>
                                    <input type="text" class="form-control" id="telefono" name="telefono" value="<?= old('telefono', $sucursal['telefono'] ?? '') ?>" placeholder="Ej. 123-456-7890">
                                </div>
                                
                                <div class="col-md-12">
                                    <input type="hidden" id="user_id" name="user_id" value="<?= $userId ?>">
                                </div>
                            </div>
                            
                            <div class="mt-4 pt-2 text-center">
                                <button type="submit" class="btn btn-success btn-label">
                                    <i class="ri-save-line label-icon align-middle fs-16 me-2"></i>
                                    Guardar Sucursal
                                </button>
                                <a href="/sucursales" class="btn btn-secondary btn-label ms-2">
                                    <i class="ri-close-line label-icon align-middle fs-16 me-2"></i>
                                    Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
   
<?= $this->endSection() ?>

<?= $this->section('scripts') ?><?= $this->endSection() ?>