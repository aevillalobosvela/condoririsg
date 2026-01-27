<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Mi Perfil<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
    .profile-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 0 20px rgba(0,0,0,0.05);
    }
    .profile-header {
        background: linear-gradient(135deg, #28a745 0%, #218838 100%);
        color: white;
        padding: 30px;
        border-radius: 15px 15px 0 0;
        text-align: center;
    }
    .profile-avatar {
        width: 100px;
        height: 100px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        margin: 0 auto 15px;
        border: 4px solid rgba(255,255,255,0.3);
    }
    .form-control:read-only {
        background-color: #f8f9fa;
        cursor: not-allowed;
        opacity: 0.8;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card profile-card animate__animated animate__fadeInUp">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="ri-user-line"></i>
                    </div>
                    <h2 class="mb-0"><?= esc($usuario['nombre']) ?> <?= esc($usuario['apellidos']) ?></h2>
                    <p class="mb-0 opacity-75"><?= esc($usuario['rol_nombre']) ?></p>
                </div>
                
                <div class="card-body p-4">
                    <?php if (session()->getFlashdata('message')): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="ri-checkbox-circle-line me-2"></i>
                            <?= session()->getFlashdata('message') ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="ri-error-warning-line me-2"></i>
                            <?= session()->getFlashdata('error') ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('validation')): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach (session()->getFlashdata('validation')->getErrors() as $error): ?>
                                    <li><?= esc($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="<?= base_url('perfil/update') ?>" method="POST">
                        <h5 class="mb-3 text-muted"><i class="ri-lock-line me-2"></i>Datos Personales (No editables)</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Nombre</label>
                                <input type="text" class="form-control" value="<?= esc($usuario['nombre']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellidos</label>
                                <input type="text" class="form-control" value="<?= esc($usuario['apellidos']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cédula de Identidad</label>
                                <input type="text" class="form-control" value="<?= esc($usuario['ci']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Sucursal</label>
                                <input type="text" class="form-control" value="<?= esc($usuario['sucursal_nombre']) ?>" readonly>
                            </div>
                        </div>

                        <h5 class="mb-3 text-primary"><i class="ri-pencil-line me-2"></i>Datos Editables</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="usuario" class="form-label">Nombre de Usuario</label>
                                <input type="text" class="form-control" id="usuario" name="usuario" value="<?= esc($usuario['usuario']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="correo" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="correo" name="correo" value="<?= esc($usuario['correo']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="celular" class="form-label">Celular</label>
                                <input type="text" class="form-control" id="celular" name="celular" value="<?= esc($usuario['celular']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="direccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="direccion" name="direccion" value="<?= esc($usuario['direccion']) ?>">
                            </div>
                            
                            <div class="col-12">
                                <hr class="my-4">
                                <h5 class="mb-3 text-warning"><i class="ri-key-2-line me-2"></i>Cambiar Contraseña</h5>
                                <p class="text-muted small">Deje en blanco si no desea cambiar su contraseña.</p>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="password" class="form-label">Nueva Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" minlength="8">
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8">
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="ri-save-line me-2"></i>Guardar Cambios
                            </button>
                            <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
