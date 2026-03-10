<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  /* Alertas suaves */
  .alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
  }
  .alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
  }

  /* Botones */
  .btn-success {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
  }
  .btn-success:hover {
    background-color: #218838 !important;
    border-color: #1e7e34 !important;
  }
  .btn-secondary {
    background-color: #6c757d !important;
    border-color: #6c757d !important;
  }

  /* Tarjeta de formulario */
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

  /* Inputs */
  .form-control:focus,
  .form-select:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
  }

  /* Texto pequeño */
  .form-text {
    color: #6c757d;
    font-size: 0.875rem;
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$userId = session()->get('id');
$userSucursal = session()->get('sucursal_id');
?>

<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-sm-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-sm-0"><?= esc($title) ?></h4>
        <div class="page-title-right">
          <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="<?= base_url('inventarios') ?>">Inventario</a></li>
            <li class="breadcrumb-item active"><?= isset($inventario) ? 'Editar' : 'Nuevo' ?></li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-8">
      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?= session()->getFlashdata('error') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <ul class="mb-0">
            <?php foreach (session()->getFlashdata('errors') as $error): ?>
              <li><?= esc($error) ?></li>
            <?php endforeach; ?>
          </ul>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?= session()->getFlashdata('success') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <form 
        action="<?= isset($inventario->id) ? base_url('inventarios/update/' . $inventario->id) : base_url('inventarios/create') ?>" 
        method="post" 
        class="needs-validation" 
        novalidate
      >
        <?= csrf_field() ?>
        <?php if (isset($inventario->id)): ?>
          <input type="hidden" name="id" value="<?= esc($inventario->id) ?>">
        <?php endif; ?>

        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">Información Básica</h5>
          </div>
          <div class="card-body">
            <!-- Nombre (Materia prima) -->
            <div class="mb-3">
              <label for="nombre" class="form-label">Materia Prima *</label>
              <select class="form-select" id="nombre" name="nombre" required>
                <option value="" disabled <?= empty(old('nombre', $inventario->nombre ?? '')) ? 'selected' : '' ?>>Seleccione la materia prima</option>
                <?php if (isset($materiasPrimas) && !empty($materiasPrimas)): ?>
                  <?php foreach ($materiasPrimas as $mp): ?>
                    <?php $nombreMp = is_object($mp) ? $mp->nombre : $mp['nombre']; ?>
                    <option value="<?= esc($nombreMp) ?>" <?= (old('nombre', $inventario->nombre ?? '') === $nombreMp) ? 'selected' : '' ?>>
                      <?= esc($nombreMp) ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
              <div class="invalid-feedback">Seleccione la materia prima.</div>
            </div>

            <!-- Código -->
            <div class="mb-3">
              <label for="code" class="form-label">Código</label>
              <?php if (isset($auto_generate_code) && $auto_generate_code): ?>
                <input type="text" class="form-control" value="Se generará automáticamente" disabled>
                <div class="form-text">El código se generará automáticamente con el formato: <code>INV-DD-MM-YYYY-HH</code></div>
              <?php else: ?>
                <input type="text" class="form-control" name="code" id="code" 
                       value="<?= old('code', $inventario->code ?? '') ?>" readonly>
                <div class="form-text">El código no puede ser modificado.</div>
              <?php endif; ?>
            </div>

            <!-- Descripción -->
            <div class="mb-3">
              <label for="descripcion" class="form-label">Descripción</label>
              <textarea class="form-control" id="descripcion" name="descripcion" 
                        rows="3" placeholder="Descripción opcional del inventario"><?= old('descripcion', $inventario->descripcion ?? '') ?></textarea>
            </div>

            <!-- Cantidad y Turno -->
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="stock" class="form-label">Cantidad *</label>
                  <input type="number" class="form-control" id="stock" name="stock"
                         value="<?= old('stock', $inventario->stock ?? '0') ?>" required min="0" step="any"
                         inputmode="decimal" placeholder="0.00">
                  <div class="form-text">Puede ingresar decimales (use punto como separador).</div>
                  <div class="invalid-feedback">Por favor ingrese la cantidad.</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="turno" class="form-label">Turno *</label>
                  <select class="form-select" id="turno" name="turno" required>
                    <option value="" disabled <?= empty(old('turno', $inventario->turno ?? '')) ? 'selected' : '' ?>>Seleccione el turno</option>
                    <option value="AM" <?= (old('turno', $inventario->turno ?? '') === 'AM') ? 'selected' : '' ?>>AM</option>
                    <option value="PM" <?= (old('turno', $inventario->turno ?? '') === 'PM') ? 'selected' : '' ?>>PM</option>
                  </select>
                  <div class="invalid-feedback">Seleccione el turno.</div>
                </div>
              </div>
            </div>
          
          

            <!-- Campos ocultos -->
            <input type="hidden" name="estado" value="1">
            <input type="hidden" name="sucursal_id" value="<?= esc($userSucursal) ?>">
            <input type="hidden" name="user_id" value="<?= esc($userId) ?>">
          </div>
        </div>

        <!-- Botones -->
        <div class="d-flex justify-content-between mt-4">
          <a href="<?= base_url('inventarios') ?>" class="btn btn-secondary">
            <i class="ri-arrow-go-back-line me-1"></i> Cancelar
          </a>
          <button type="submit" class="btn btn-success">
            <i class="ri-save-3-line me-1"></i>
            <?= isset($inventario->id) ? 'Actualizar' : 'Crear' ?> Inventario
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
      form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  });
</script>

<?= $this->endSection() ?>