<?php
$userId = session()->get('id');
?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  .card {
    border: 1px solid #e0f0e9;
    box-shadow: 0 0.125rem 0.25rem rgba(40, 167, 69, 0.08);
  }
  .card-header {
    background-color: #f8fdfa;
    border-bottom: 1px solid #e0f0e9;
    font-weight: 600;
    color: #28a745;
  }
  .alert-info {
    background-color: #f8fdfa;
    border-color: #e0f0e9;
    color: #28a745;
  }
  .btn-primary {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
  }
  .btn-secondary {
    background-color: #6c757d !important;
    border-color: #6c757d !important;
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-sm-flex align-items-center justify-content-between">
        <h4 class="mb-sm-0"><?= isset($usuario) ? 'Editar Usuario' : 'Registrar Nueva Cuenta' ?></h4>
        <div class="page-title-right">
          <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="<?= base_url('usuarios') ?>">Usuarios</a></li>
            <li class="breadcrumb-item active"><?= isset($usuario) ? 'Editar' : 'Registrar' ?></li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0"><?= isset($usuario) ? 'Editar Usuario' : 'Registro de Nuevo Usuario' ?></h5>
        </div>
        <div class="card-body">
          <?php if (isset($validation)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?= $validation->listErrors() ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?= session()->getFlashdata('error') ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <form action="<?= isset($usuario) ? base_url('usuarios/update') : base_url('usuarios/create') ?>" method="post">
            <?= csrf_field() ?>

            <?php if (isset($usuario)): ?>
              <input type="hidden" name="id" value="<?= esc($usuario['id']) ?>">
            <?php endif; ?>

            <!-- Información personal -->
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="nombre" class="form-label">Nombre *</label>
                <input type="text" class="form-control" id="nombre" name="nombre" required
                  value="<?= old('nombre', $usuario['nombre'] ?? '') ?>">
              </div>
              <div class="col-md-6 mb-3">
                <label for="apellidos" class="form-label">Apellidos *</label>
                <input type="text" class="form-control" id="apellidos" name="apellidos" required
                  value="<?= old('apellidos', $usuario['apellidos'] ?? '') ?>">
              </div>
            </div>

            <!-- CI (clave para generar contraseña) -->
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="ci" class="form-label">Cédula de Identidad (CI) *</label>
                <input type="text" class="form-control" id="ci" name="ci" required
                  value="<?= old('ci', $usuario['ci'] ?? '') ?>"
                  placeholder="Ej: 1234567">
              </div>
              <div class="col-md-6 mb-3">
                <label for="correo" class="form-label">Correo Electrónico *</label>
                <input type="email" class="form-control" id="correo" name="correo" required
                  value="<?= old('correo', $usuario['correo'] ?? '') ?>"
                  placeholder="usuario@ejemplo.com">
              </div>
            </div>

            <!-- Contacto -->
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="celular" class="form-label">Celular (opcional)</label>
                <input type="text" class="form-control" id="celular" name="celular"
                  value="<?= old('celular', $usuario['celular'] ?? '') ?>"
                  placeholder="Ej: 70012345">
              </div>
              <div class="col-md-6 mb-3">
                <label for="direccion" class="form-label">Dirección (opcional)</label>
                <textarea class="form-control" id="direccion" name="direccion" rows="2"
                  placeholder="Dirección completa"><?= old('direccion', $usuario['direccion'] ?? '') ?></textarea>
              </div>
            </div>

            <!-- Rol y Sucursal -->
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="rol_id" class="form-label">Rol *</label>
                <select class="form-control" id="rol_id" name="rol_id" required>
                  <option value="">Seleccione un Rol</option>
                  <?php foreach ($roles as $rol): ?>
                    <option value="<?= esc($rol->id) ?>" <?= old('rol_id', $usuario['rol_id'] ?? '') == $rol->id ? 'selected' : '' ?>>
                      <?= esc($rol->nombre) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label for="sucursal_id" class="form-label">Sucursal *</label>
                <select class="form-control" id="sucursal_id" name="sucursal_id" required>
                  <option value="">Seleccione una Sucursal</option>
                  <?php foreach ($sucursales as $sucursal): ?>
                    <option value="<?= esc($sucursal['id']) ?>" <?= old('sucursal_id', $usuario['sucursal_id'] ?? '') == $sucursal['id'] ? 'selected' : '' ?>>
                      <?= esc($sucursal['nombre']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <!-- Solo en edición: contraseña opcional -->
            <?php if (isset($usuario)): ?>
              <div class="row">
                <div class="col-md-12 mb-3">
                  <label for="password" class="form-label">Nueva Contraseña (opcional)</label>
                  <input type="password" class="form-control" id="password" name="password"
                    placeholder="Deje en blanco para no cambiar">
                </div>
              </div>
            <?php else: ?>
              <!-- Registro: mensaje informativo -->
              <div class="alert alert-info">
                <i class="ri-information-line me-2"></i>
                <strong>¡Atención!</strong> El <strong>nombre de usuario</strong> y la <strong>contraseña</strong> se generarán automáticamente:
                <ul class="mb-0 mt-2">
                  <li><strong>Usuario:</strong> primer nombre + iniciales de segundo nombre y apellidos (ej: <code>johnapg</code>)</li>
                  <li><strong>Contraseña:</strong> CI + iniciales de nombre y apellidos (ej: <code>123456jpg</code>)</li>
                </ul>
              </div>
            <?php endif; ?>

            <!-- Botones -->
            <div class="d-flex justify-content-between mt-4">
              <a href="<?= base_url('usuarios') ?>" class="btn btn-secondary">  
                <i class="ri-arrow-left-line me-1"></i> Cancelar
              </a>
              <button type="submit" class="btn btn-success">
                <i class="ri-save-line me-1"></i>
                <?= isset($usuario) ? 'Actualizar Usuario' : 'Registrar Usuario' ?>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->endSection() ?>