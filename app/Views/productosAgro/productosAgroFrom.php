<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  :root {
    --agro-green:        #16a34a;
    --agro-green-light:  #f0fdf4;
    --agro-green-border: #bbf7d0;
    --agro-blue:         #1d4ed8;
    --agro-blue-light:   #eff6ff;
    --agro-blue-border:  #bfdbfe;
  }

  .section-id {
    background: var(--agro-blue-light);
    border: 1px solid var(--agro-blue-border);
    border-radius: 10px;
    padding: 18px 20px;
  }
  .section-id .section-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--agro-blue);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .section-num {
    background: var(--agro-green-light);
    border: 1px solid var(--agro-green-border);
    border-radius: 10px;
    padding: 18px 20px;
  }
  .section-num .section-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--agro-green);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .form-label {
    font-size: 0.78rem;
    font-weight: 600;
    margin-bottom: 4px;
    color: #374151;
  }
  .form-control, .form-select {
    font-size: 0.88rem;
    border-radius: 7px;
  }
  .form-control:focus, .form-select:focus {
    border-color: var(--agro-green);
    box-shadow: 0 0 0 3px rgba(22,163,74,0.12);
  }
  .input-group-text {
    font-size: 0.82rem;
    background: #f8fafc;
    border-color: #d1d5db;
    color: #6b7280;
  }

  .btn-guardar {
    background: var(--agro-green);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 9px 28px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: background 0.15s;
  }
  .btn-guardar:hover { background: #15803d; color: #fff; }

  .btn-cancelar {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 9px 20px;
    font-weight: 500;
    font-size: 0.9rem;
    transition: background 0.15s;
  }
  .btn-cancelar:hover { background: #e2e8f0; color: #1e293b; }

  /* Indicador de campo requerido */
  .req { color: #dc2626; margin-left: 2px; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Breadcrumb -->
<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-sm-0"><?= esc($title) ?></h4>
        <ol class="breadcrumb m-0 mt-1">
          <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Inicio</a></li>
          <li class="breadcrumb-item"><a href="<?= base_url('productosagro') ?>">Productos Agro</a></li>
          <li class="breadcrumb-item active"><?= isset($producto) ? 'Editar' : 'Nuevo' ?></li>
        </ol>
      </div>
      <a href="<?= base_url('productosagro') ?>" class="btn btn-cancelar">
        <i class="ri-arrow-left-line me-1"></i> Volver
      </a>
    </div>
  </div>
</div>

<div class="row justify-content-center">
  <div class="col-xl-9 col-lg-11">

    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>



    <?php
      $action = isset($producto)
        ? base_url("productosagro/update/{$producto->id}")
        : base_url('productosagro/store');
    ?>

    <form action="<?= $action ?>" method="post" id="formProductoAgro" autocomplete="off">
      <?= csrf_field() ?>

      <!-- Layout 2 columnas -->
      <div class="row g-3">

        <!-- COLUMNA IZQUIERDA: Identificación -->
        <div class="col-md-5">
          <div class="section-id h-100">
            <div class="section-label">
              <i class="ri-price-tag-3-line"></i> Identificación
            </div>

            <div class="mb-3">
              <label for="categoria" class="form-label">Categoría <span class="req">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="ri-folder-3-line"></i></span>
                <input type="text" class="form-control" id="categoria" name="categoria"
                       value="<?= old('categoria', $base->categoria ?? $producto->categoria ?? '') ?>"
                       placeholder="Ej: SEMILLAS" required maxlength="255" tabindex="1">
              </div>
            </div>

            <div class="mb-3">
              <label for="producto" class="form-label">Nombre del Producto <span class="req">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="ri-leaf-line"></i></span>
                <input type="text" class="form-control" id="producto" name="producto"
                       value="<?= old('producto', $base->producto ?? $producto->producto ?? '') ?>"
                       placeholder="Ej: FERTILIZANTE NPK" required maxlength="255" tabindex="2">
              </div>
            </div>

            <div class="mb-0">
              <label for="descripcion" class="form-label">Descripción <span style="color:#9ca3af; font-weight:400;">(opcional)</span></label>
              <textarea class="form-control" id="descripcion" name="descripcion"
                        rows="3" placeholder="Detalles adicionales..."
                        tabindex="3"><?= old('descripcion', $base->descripcion ?? $producto->descripcion ?? '') ?></textarea>
            </div>
          </div>
        </div>

        <!-- COLUMNA DERECHA: Precios e inventario -->
        <div class="col-md-7">
          <div class="section-num h-100">
            <div class="section-label">
              <i class="ri-coins-line"></i> Precios e Inventario
            </div>

            <div class="row g-3 mb-3">
              <div class="col-6">
                <label for="precio_contado" class="form-label">Precio Contado (Bs) <span class="req">*</span></label>
                <div class="input-group">
                  <span class="input-group-text">Bs</span>
                  <input type="number" class="form-control text-end" id="precio_contado" name="precio_contado"
                         step="0.01" min="0"
                         value="<?= old('precio_contado', $base->precio_contado ?? $producto->precio_contado ?? '0.00') ?>"
                         placeholder="0.00" required tabindex="4">
                </div>
              </div>
              <div class="col-6">
                <label for="precio_credito" class="form-label">Precio Crédito (Bs) <span class="req">*</span></label>
                <div class="input-group">
                  <span class="input-group-text">Bs</span>
                  <input type="number" class="form-control text-end" id="precio_credito" name="precio_credito"
                         step="0.01" min="0"
                         value="<?= old('precio_credito', $base->precio_credito ?? $producto->precio_credito ?? '0.00') ?>"
                         placeholder="0.00" required tabindex="5">
                </div>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-6">
                <label for="unidad_id" class="form-label">Unidad <span class="req">*</span></label>
                <select class="form-select" id="unidad_id" name="unidad_id" required tabindex="6">
                  <option value="">Seleccione...</option>
                  <?php foreach ($unidades as $u): ?>
                    <option value="<?= $u['id'] ?>"
                      <?= old('unidad_id', $base->unidad_id ?? $producto->unidad_id ?? '') == $u['id'] ? 'selected' : '' ?>>
                      <?= esc($u['nombre']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php if (!isset($producto)): ?>
              <div class="col-6">
                <label for="cantidad" class="form-label">Cantidad Inicial <span class="req">*</span></label>
                <input type="number" class="form-control text-end" id="cantidad" name="cantidad"
                       value="<?= old('cantidad', 0) ?>"
                       min="0" step="1" required tabindex="7">
                <div class="form-text" style="font-size:0.72rem;">Stock con el que inicia el producto.</div>
              </div>
              <?php else: ?>
              <div class="col-6">
                <label class="form-label">Stock Actual</label>
                <div class="form-control text-end fw-bold"
                     style="background:#f0fdf4; color:var(--agro-green); border-color:var(--agro-green-border);">
                  <?= (int)$producto->cantidad_inve ?> und
                </div>
                <div class="form-text" style="font-size:0.72rem;">Se actualiza con las ventas.</div>
              </div>
              <?php endif; ?>
            </div>

            <?php if (isset($producto)): ?>
            <!-- bloque de stock ya integrado arriba -->
            <?php endif; ?>

            <!-- Botones al fondo de la sección -->
            <div class="d-flex justify-content-end gap-2 mt-auto pt-3" style="border-top:1px solid var(--agro-green-border);">
              <a href="<?= base_url('productosagro') ?>" class="btn btn-cancelar" tabindex="9">
                <i class="ri-close-line me-1"></i> Cancelar
              </a>
              <button type="submit" class="btn btn-guardar" tabindex="8">
                <i class="ri-save-3-line me-1"></i>
                <?= isset($producto) ? 'Actualizar' : 'Registrar' ?>
              </button>
            </div>

          </div>
        </div>

      </div><!-- /row -->
    </form>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {

  // Mayúsculas automáticas
  ['producto', 'categoria'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', () => { el.value = el.value.toUpperCase(); });
  });

  // Foco inicial
  document.getElementById('categoria')?.focus();

  // Validación visual en submit
  document.getElementById('formProductoAgro').addEventListener('submit', function (e) {
    let valid = true;
    this.querySelectorAll('[required]').forEach(f => {
      if (!f.value.trim()) { f.classList.add('is-invalid'); valid = false; }
      else f.classList.remove('is-invalid');
    });
    if (!valid) e.preventDefault();
  });

  // Limpiar is-invalid al escribir
  document.getElementById('formProductoAgro').querySelectorAll('[required]').forEach(f => {
    f.addEventListener('input', () => f.classList.remove('is-invalid'));
  });

  // Tab order: al presionar Enter en un campo avanza al siguiente tabindex
  document.getElementById('formProductoAgro').querySelectorAll('input, select, textarea').forEach(el => {
    el.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && el.tagName !== 'TEXTAREA') {
        e.preventDefault();
        const next = document.querySelector(`[tabindex="${parseInt(el.tabIndex) + 1}"]`);
        if (next) next.focus();
      }
    });
  });

});
</script>
<?= $this->endSection() ?>
