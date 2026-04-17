<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  <?php $isAgro = (($unidad['tipo'] ?? $tipo ?? 'lacteo') === 'agro'); ?>
  :root {
    --form-color:        <?= $isAgro ? '#16a34a' : '#0d6efd' ?>;
    --form-color-light:  <?= $isAgro ? '#f0fdf4' : '#eff6ff' ?>;
    --form-color-border: <?= $isAgro ? '#bbf7d0' : '#bfdbfe' ?>;
  }
  .form-card {
    border: 1px solid var(--form-color-border);
    border-radius: 12px;
    background: var(--form-color-light);
    padding: 24px;
  }
  .form-card .section-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--form-color);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .form-control:focus, .form-select:focus {
    border-color: var(--form-color);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--form-color) 15%, transparent);
  }
  .btn-guardar {
    background: var(--form-color);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 9px 28px;
    font-weight: 600;
    transition: filter 0.15s;
  }
  .btn-guardar:hover { filter: brightness(0.88); color: #fff; }
  .tipo-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
    background: var(--form-color-border);
    color: var(--form-color);
    border: 1px solid var(--form-color-border);
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
  $tipoActual = $unidad['tipo'] ?? $tipo ?? 'lacteo';
  $isAgro     = $tipoActual === 'agro';
  $isEdit     = !empty($unidad['id']);
  $action     = $isEdit
    ? base_url('unidades/update/' . $unidad['id'])
    : base_url('unidades/create');
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-sm-0"><?= esc($title) ?></h4>
        <ol class="breadcrumb m-0 mt-1">
          <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Inicio</a></li>
          <li class="breadcrumb-item"><a href="<?= base_url('unidades?tab=' . $tipoActual) ?>">Unidades</a></li>
          <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Nueva' ?></li>
        </ol>
      </div>
      <a href="<?= base_url('unidades?tab=' . $tipoActual) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="ri-arrow-left-line me-1"></i> Volver
      </a>
    </div>
  </div>
</div>

<div class="row justify-content-center">
  <div class="col-xl-6 col-lg-8">

    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="form-card">
      <div class="section-label">
        <i class="<?= $isAgro ? 'ri-leaf-line' : 'ri-drop-line' ?>"></i>
        <?= $isAgro ? 'Unidad Agropecuaria' : 'Unidad Lácteos' ?>
        <span class="tipo-badge ms-2">
          <i class="<?= $isAgro ? 'ri-leaf-line' : 'ri-drop-line' ?>"></i>
          <?= $isAgro ? 'Agropecuario' : 'Lácteos' ?>
        </span>
      </div>

      <form action="<?= $action ?>" method="post" id="formUnidad">
        <?= csrf_field() ?>
        <input type="hidden" name="tipo"    value="<?= esc($tipoActual) ?>">
        <input type="hidden" name="user_id" value="<?= session()->get('id') ?>">

        <div class="mb-3">
          <label for="nombre" class="form-label fw-semibold" style="font-size:0.82rem;">
            Nombre <span class="text-danger">*</span>
          </label>
          <input type="text" class="form-control" id="nombre" name="nombre"
                 value="<?= old('nombre', $unidad['nombre'] ?? '') ?>"
                 placeholder="<?= $isAgro ? 'Ej: Quintal (qq)' : 'Ej: Litros' ?>"
                 required maxlength="100" autofocus>
        </div>

        <div class="mb-4">
          <label for="descripcion" class="form-label fw-semibold" style="font-size:0.82rem;">
            Descripción <span class="text-muted fw-normal">(opcional)</span>
          </label>
          <textarea class="form-control" id="descripcion" name="descripcion"
                    rows="2" maxlength="255"
                    placeholder="Descripción breve de la unidad..."><?= old('descripcion', $unidad['descripcion'] ?? '') ?></textarea>
        </div>

        <div class="d-flex justify-content-end gap-2" style="border-top:1px solid var(--form-color-border); padding-top:16px;">
          <a href="<?= base_url('unidades?tab=' . $tipoActual) ?>" class="btn btn-outline-secondary">
            Cancelar
          </a>
          <button type="submit" class="btn btn-guardar">
            <i class="ri-save-3-line me-1"></i> <?= $isEdit ? 'Actualizar' : 'Registrar' ?>
          </button>
        </div>
      </form>
    </div>

  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.getElementById('formUnidad').addEventListener('submit', function (e) {
    const nombre = document.getElementById('nombre');
    if (!nombre.value.trim()) {
      nombre.classList.add('is-invalid');
      e.preventDefault();
    }
  });
  document.getElementById('nombre').addEventListener('input', function () {
    this.classList.remove('is-invalid');
  });
});
</script>
<?= $this->endSection() ?>
