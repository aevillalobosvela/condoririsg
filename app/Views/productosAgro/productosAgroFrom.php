<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  :root {
    --agro-green:        #16a34a;
    --agro-green-light:  #f0fdf4;
    --agro-green-border: #bbf7d0;
  }

  .form-panel {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px;
  }

  .form-divider {
    width: 1px;
    background: #e2e8f0;
    align-self: stretch;
    flex-shrink: 0;
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

  .req { color: #dc2626; margin-left: 2px; }

  .btn-unidad-rapida {
    background: var(--agro-green-light);
    border: 1px solid var(--agro-green-border);
    color: var(--agro-green);
    border-radius: 7px;
    padding: 0 10px;
    font-size: 1rem;
    line-height: 1;
    transition: background 0.15s;
    white-space: nowrap;
  }
  .btn-unidad-rapida:hover {
    background: var(--agro-green);
    color: #fff;
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

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
  <div class="col-xl-10 col-lg-12">

    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('errors')): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
          <?php foreach (session()->getFlashdata('errors') as $err): ?>
            <li><?= esc($err) ?></li>
          <?php endforeach; ?>
        </ul>
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

      <div class="form-panel">
        <div class="d-flex gap-0">

          <!-- COLUMNA IZQUIERDA: identificación + unidad + cantidad -->
          <div class="flex-fill pe-4" style="min-width:0;">

            <div class="mb-3">
              <label for="producto" class="form-label">Nombre del Producto <span class="req">*</span></label>
              <input type="text" class="form-control" id="producto" name="producto"
                     value="<?= old('producto', $base->producto ?? $producto->producto ?? '') ?>"
                     placeholder="Papa" required maxlength="255" tabindex="1">
            </div>

            <div class="mb-3">
              <label for="categoria" class="form-label">Categoría <span class="req">*</span></label>
              <select class="form-select" id="categoria" name="categoria" required tabindex="2">
                <?php
                  $cats = ['TUBERCULOS', 'HORTALIZAS', 'DESHIDRATADOS', 'CEREALES', 'LEGUMINOSAS'];
                  $catActual = old('categoria', $base->categoria ?? $producto->categoria ?? '');
                  foreach ($cats as $cat):
                ?>
                  <option value="<?= $cat ?>" <?= $catActual === $cat ? 'selected' : '' ?>><?= ucfirst(strtolower($cat)) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label for="descripcion" class="form-label">Descripción <span style="color:#9ca3af; font-weight:400;">(opcional)</span></label>
              <textarea class="form-control" id="descripcion" name="descripcion"
                        rows="2" placeholder="Detalles adicionales..."
                        tabindex="3"><?= old('descripcion', $base->descripcion ?? $producto->descripcion ?? '') ?></textarea>
            </div>

            <div class="row g-3">
              <div class="col-7">
                <label for="unidad_id" class="form-label">Unidad <span class="req">*</span></label>
                <div class="d-flex gap-2">
                  <select class="form-select" id="unidad_id" name="unidad_id" required tabindex="4">
                    <option value="">Seleccione...</option>
                    <?php foreach ($unidades as $u): ?>
                      <option value="<?= $u['id'] ?>"
                        <?= old('unidad_id', $base->unidad_id ?? $producto->unidad_id ?? '') == $u['id'] ? 'selected' : '' ?>>
                        <?= esc($u['nombre']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button type="button" class="btn-unidad-rapida" data-bs-toggle="modal" data-bs-target="#modalUnidadRapida" title="Agregar nueva unidad">
                    <i class="ri-add-line"></i>
                  </button>
                </div>
              </div>
              <div class="col-5">
                <?php if (!isset($producto)): ?>
                  <label for="cantidad" class="form-label">Cantidad Inicial <span class="req">*</span></label>
                  <input type="number" class="form-control text-end" id="cantidad" name="cantidad"
                         value="<?= old('cantidad', 0) ?>"
                         min="0" step="1" required tabindex="5">
                  <div class="form-text" style="font-size:0.72rem;">Stock inicial.</div>
                <?php else: ?>
                  <label class="form-label">Stock Actual</label>
                  <div class="form-control text-end fw-bold"
                       style="background:#f0fdf4; color:var(--agro-green); border-color:var(--agro-green-border);">
                    <?= (int)$producto->cantidad_inve ?> und
                  </div>
                  <div class="form-text" style="font-size:0.72rem;">Se actualiza con las ventas.</div>
                <?php endif; ?>
              </div>
            </div>

          </div>

          <!-- DIVISOR VERTICAL -->
          <div class="form-divider mx-1"></div>

          <!-- COLUMNA DERECHA: precios + botones -->
          <div class="flex-fill ps-4" style="min-width:0;">

            <div class="mb-3">
              <label for="precio_contado" class="form-label">Precio Contado (Bs) <span class="req">*</span></label>
              <div class="input-group">
                <span class="input-group-text">Bs</span>
                <input type="number" class="form-control text-end" id="precio_contado" name="precio_contado"
                       step="0.01" min="0"
                       value="<?= old('precio_contado', $base->precio_contado ?? $producto->precio_contado ?? '0.00') ?>"
                       placeholder="0.00" required tabindex="6">
              </div>
            </div>

            <div class="mb-3">
              <label for="precio_credito" class="form-label">Precio Crédito (Bs) <span class="req">*</span></label>
              <div class="input-group">
                <span class="input-group-text">Bs</span>
                <input type="number" class="form-control text-end" id="precio_credito" name="precio_credito"
                       step="0.01" min="0"
                       value="<?= old('precio_credito', $base->precio_credito ?? $producto->precio_credito ?? '0.00') ?>"
                       placeholder="0.00" required tabindex="7">
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3" style="border-top:1px solid #e2e8f0;">
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
      </div>

    </form>
  </div>
</div>

<!-- MODAL UNIDAD RÁPIDA -->
<div class="modal fade" id="modalUnidadRapida" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:360px;">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--agro-green-light); border-bottom:1px solid var(--agro-green-border);">
        <h6 class="modal-title" style="color:var(--agro-green);"><i class="ri-scales-line me-1"></i> Nueva Unidad Agro</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="unidadRapidaAlert" class="alert py-2 d-none"></div>
        <label for="nuevaUnidadNombre" class="form-label">Nombre <span class="req">*</span></label>
        <input type="text" class="form-control" id="nuevaUnidadNombre" placeholder="Ej: Quintal (qq)" maxlength="100">
        <div class="form-text" style="font-size:0.72rem;">Se guardará como unidad agropecuaria.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-sm btn-guardar" id="btnGuardarUnidadRapida">
          <i class="ri-save-3-line me-1"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {

  // Mayúsculas en nombre
  const prodEl = document.getElementById('producto');
  if (prodEl) {
    prodEl.addEventListener('input', () => { prodEl.value = prodEl.value.toUpperCase(); });
    prodEl.focus();
  }

  // Validación visual en submit
  document.getElementById('formProductoAgro').addEventListener('submit', function (e) {
    let valid = true;

    // Nombre
    const prod = document.getElementById('producto');
    if (!prod.value.trim() || prod.value.trim().length < 2) {
      setInvalid(prod, 'El nombre es obligatorio (mínimo 2 caracteres).');
      valid = false;
    } else clearInvalid(prod);

    // Categoría
    const cat = document.getElementById('categoria');
    if (!cat.value) {
      setInvalid(cat, 'Seleccione una categoría.');
      valid = false;
    } else clearInvalid(cat);

    // Unidad
    const unidad = document.getElementById('unidad_id');
    if (!unidad.value) {
      setInvalid(unidad, 'Seleccione una unidad de medida.');
      valid = false;
    } else clearInvalid(unidad);

    // Cantidad (solo en crear)
    const cantidad = document.getElementById('cantidad');
    if (cantidad) {
      const val = cantidad.value.trim();
      if (val === '' || isNaN(val) || parseInt(val) < 0 || !Number.isInteger(Number(val))) {
        setInvalid(cantidad, 'Ingrese una cantidad entera válida (0 o mayor).');
        valid = false;
      } else clearInvalid(cantidad);
    }

    // Precio contado
    const pc = document.getElementById('precio_contado');
    if (!pc.value || isNaN(pc.value) || parseFloat(pc.value) <= 0) {
      setInvalid(pc, 'El precio de contado debe ser mayor a 0.');
      valid = false;
    } else clearInvalid(pc);

    // Precio crédito
    const pcr = document.getElementById('precio_credito');
    if (!pcr.value || isNaN(pcr.value) || parseFloat(pcr.value) <= 0) {
      setInvalid(pcr, 'El precio de crédito debe ser mayor a 0.');
      valid = false;
    } else clearInvalid(pcr);

    if (!valid) e.preventDefault();
  });

  function setInvalid(el, msg) {
    el.classList.add('is-invalid');
    let fb = el.parentElement.querySelector('.invalid-feedback');
    if (!fb) {
      fb = document.createElement('div');
      fb.className = 'invalid-feedback';
      el.parentElement.appendChild(fb);
    }
    fb.textContent = msg;
  }

  function clearInvalid(el) {
    el.classList.remove('is-invalid');
    const fb = el.parentElement.querySelector('.invalid-feedback');
    if (fb) fb.textContent = '';
  }

  // Limpiar errores en tiempo real
  ['producto','categoria','unidad_id','precio_contado','precio_credito'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
      el.addEventListener('input', () => clearInvalid(el));
      el.addEventListener('change', () => clearInvalid(el));
    }
  });
  const cantidadEl = document.getElementById('cantidad');
  if (cantidadEl) cantidadEl.addEventListener('input', () => clearInvalid(cantidadEl));

  // Marcar campos con error del servidor al cargar
  <?php
    $serverErrors = session()->getFlashdata('errors') ?? [];
    $fieldMap = [
      'producto'       => 'producto',
      'categoria'      => 'categoria',
      'unidad_id'      => 'unidad_id',
      'cantidad'       => 'cantidad',
      'precio_contado' => 'precio_contado',
      'precio_credito' => 'precio_credito',
    ];
  ?>
  <?php foreach ($fieldMap as $field => $elId): ?>
    <?php if (isset($serverErrors[$field])): ?>
      (function(){
        const el = document.getElementById('<?= $elId ?>');
        if (el) setInvalid(el, '<?= esc($serverErrors[$field]) ?>');
      })();
    <?php endif; ?>
  <?php endforeach; ?>

  // Enter avanza tabindex
  document.getElementById('formProductoAgro').querySelectorAll('input, select, textarea').forEach(el => {
    el.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && el.tagName !== 'TEXTAREA') {
        e.preventDefault();
        const next = document.querySelector(`[tabindex="${parseInt(el.tabIndex) + 1}"]`);
        if (next) next.focus();
      }
    });
  });

  // Limpiar modal al abrir
  document.getElementById('modalUnidadRapida').addEventListener('show.bs.modal', function () {
    document.getElementById('nuevaUnidadNombre').value = '';
    const alert = document.getElementById('unidadRapidaAlert');
    alert.className = 'alert py-2 d-none';
    alert.textContent = '';
  });

  // Guardar unidad rápida
  document.getElementById('btnGuardarUnidadRapida').addEventListener('click', function () {
    const nombre  = document.getElementById('nuevaUnidadNombre').value.trim();
    const alertEl = document.getElementById('unidadRapidaAlert');
    const btn     = this;

    if (!nombre) {
      alertEl.className = 'alert alert-warning py-2';
      alertEl.textContent = 'Ingrese un nombre para la unidad.';
      return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';

    const formData = new FormData();
    formData.append('nombre', nombre);
    formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

    fetch('<?= base_url('productosagro/storeUnidadRapida') ?>', {
      method: 'POST',
      body: formData,
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        // Agregar opción al select y seleccionarla
        const select = document.getElementById('unidad_id');
        const opt = new Option(data.unidad.nombre, data.unidad.id, true, true);
        select.appendChild(opt);

        bootstrap.Modal.getInstance(document.getElementById('modalUnidadRapida')).hide();
      } else {
        alertEl.className = 'alert alert-danger py-2';
        alertEl.textContent = data.message || 'Error al guardar.';
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-save-3-line me-1"></i> Guardar';
      }
    })
    .catch(() => {
      alertEl.className = 'alert alert-danger py-2';
      alertEl.textContent = 'Error de conexión. Intente nuevamente.';
      btn.disabled = false;
      btn.innerHTML = '<i class="ri-save-3-line me-1"></i> Guardar';
    });
  });

  // Enter en el input del modal dispara el botón guardar
  document.getElementById('nuevaUnidadNombre').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') document.getElementById('btnGuardarUnidadRapida').click();
  });

});
</script>
<?= $this->endSection() ?>
