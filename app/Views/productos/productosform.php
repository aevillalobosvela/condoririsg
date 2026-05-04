<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  /* Switch personalizado */
  .form-check-input:checked[type=checkbox] {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
  }

  /* Imagen preview */
  .img-preview {
    max-width: 200px;
    max-height: 200px;
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(40, 167, 69, 0.1);
  }

  /* Campos de calidad toggle */
  .toggle-fields.disabled {
    pointer-events: none;
    opacity: 0.5;
    filter: grayscale(100%);
    transition: opacity 0.3s ease-in-out, filter 0.3s ease-in-out;
  }

  /* Resultados de cálculo */
  .calculation-result {
    background-color: #f8fdfa;
    border: 1px solid #e0f0e9;
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
    display: none;
  }
  .calculation-result.alerta-alta {
    background-color: #fff3cd;
    border-color: #ffc107;
  }
  .calculation-result.alerta-critica {
    background-color: #f8d7da;
    border-color: #dc3545;
  }

  .result-highlight {
    font-weight: 600;
    color: #28a745;
  }
  .result-highlight.text-warning { color: #856404 !important; }
  .result-highlight.text-danger  { color: #721c24 !important; }

  /* Barra de uso de reserva */
  .reserva-bar-wrap {
    background: #e9ecef;
    border-radius: 6px;
    height: 10px;
    overflow: hidden;
    margin-top: 6px;
  }
  .reserva-bar {
    height: 100%;
    border-radius: 6px;
    transition: width 0.3s, background-color 0.3s;
    background-color: #28a745;
  }
  .reserva-bar.warn  { background-color: #ffc107; }
  .reserva-bar.crit  { background-color: #dc3545; }

  /* Stock info */
  .stock-info {
    font-size: 0.875rem;
    margin-top: 0.25rem;
  }

  .stock-available {
    color: #28a745;
    font-weight: 600;
  }

  /* Tarjetas */
  .card {
    border: 1px solid #e0f0e9;
    box-shadow: 0 0.125rem 0.25rem rgba(40, 167, 69, 0.08);
  }

  .card-title {
    font-weight: 600;
  }

  .card-title.text-primary {
    color: #28a745 !important;
  }

  .card-title.text-info {
    color: #17a2b8 !important;
  }

  /* Alertas */
  .alert-info {
    background-color: #f8fdfa;
    border-color: #e0f0e9;
    color: #28a745;
  }

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
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-sm-flex align-items-center justify-content-between">
        <h4 class="mb-sm-0"><?= esc($title) ?></h4>
        <div class="page-title-right">
          <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="<?= base_url('productos') ?>">Productos</a></li>
            <li class="breadcrumb-item active"><?= esc($title) ?></li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-body">
          <?php if (session()->getFlashdata('message')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <i class="ri-check-line align-middle me-1"></i>
              <strong>¡Éxito!</strong> <?= session()->getFlashdata('message') ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="ri-error-warning-line align-middle me-1"></i>
              <strong>¡Error!</strong> <?= session()->getFlashdata('error') ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <?php if (session('validation') && session('validation')->getErrors()): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="ri-close-circle-line align-middle me-1"></i>
              <strong>¡Error de validación!</strong> Revise los campos resaltados.
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <form
            action="<?= isset($producto->id) ? base_url('productos/update/' . $producto->id) : base_url('productos/create') ?>"
            method="post"
            enctype="multipart/form-data"
            id="productoForm">
              <input type="hidden" name="user_id" value="<?= esc($userId) ?>">
            <?= csrf_field() ?>
            <?php if (isset($producto->id)): ?>
              <input type="hidden" name="id" value="<?= esc($producto->id) ?>">
            <?php endif; ?>

            <div class="row g-4">
              <!-- Información Básica -->
              <div class="col-lg-6">
                <div class="card card-body h-100">
                  <h5 class="card-title text-primary"><i class="ri-information-fill me-2"></i> Información Básica</h5>
                  <hr>

                  <!-- Nombre y Unidad -->
                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="nombre" class="form-label">Producto *</label>
                        <input
                          type="text"
                          class="form-control <?= validation_show_error('nombre') ? 'is-invalid' : '' ?>"
                          id="nombre"
                          name="nombre"
                          value="<?= old('nombre', $producto->nombre ?? '') ?>"
                          required
                          placeholder="Ingrese el nombre del producto">
                        <div class="invalid-feedback">
                          <?= validation_show_error('nombre') ?: 'El nombre del producto es obligatorio.' ?>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="unidad_id" class="form-label">Unidad *</label>
                        <select class="form-select <?= (session('validation') && session('validation')->hasError('unidad_id')) ? 'is-invalid' : '' ?>"
                          id="unidad_id" name="unidad_id" required>
                          <option value="">Seleccione</option>
                          <?php foreach ($unidades as $unidad): ?>
                            <option value="<?= esc($unidad['id']) ?>" <?= (old('unidad_id', $producto->unidad_id ?? '') == $unidad['id']) ? 'selected' : '' ?>>
                              <?= esc($unidad['nombre']) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                        <?php if (session('validation') && session('validation')->hasError('unidad_id')): ?>
                          <div class="invalid-feedback"><?= session('validation')->getError('unidad_id') ?></div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>

                  <!-- Categoría -->
                  <div class="mb-3">
                    <label for="categoria_id" class="form-label">Categoría *</label>
                    <select class="form-select <?= (session('validation') && session('validation')->hasError('categoria_id')) ? 'is-invalid' : '' ?>"
                      id="categoria_id" name="categoria_id" required>
                      <option value="">Seleccione</option>
                      <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= esc($categoria['id']) ?>" <?= (old('categoria_id', $producto->categoria_id ?? '') == $categoria['id']) ? 'selected' : '' ?>>
                          <?= esc($categoria['nombre']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <?php if (session('validation') && session('validation')->hasError('categoria_id')): ?>
                      <div class="invalid-feedback"><?= session('validation')->getError('categoria_id') ?></div>
                    <?php endif; ?>
                  </div>

                  <!-- Descripción -->
                  <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea class="form-control <?= (session('validation') && session('validation')->hasError('descripcion')) ? 'is-invalid' : '' ?>"
                      id="descripcion" name="descripcion" rows="3"><?= old('descripcion', $producto->descripcion ?? '') ?></textarea>
                    <?php if (session('validation') && session('validation')->hasError('descripcion')): ?>
                      <div class="invalid-feedback"><?= session('validation')->getError('descripcion') ?></div>
                    <?php endif; ?>
                  </div>

                  <!-- Precios -->
                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="precio_credito" class="form-label">Precio Crédito *</label>
                        <input type="number" step="0.01" class="form-control <?= (session('validation') && session('validation')->hasError('precio_credito')) ? 'is-invalid' : '' ?>"
                          id="precio_credito" name="precio_credito" value="<?= old('precio_credito', $producto->precio_credito ?? '') ?>" required>
                        <?php if (session('validation') && session('validation')->hasError('precio_credito')): ?>
                          <div class="invalid-feedback"><?= session('validation')->getError('precio_credito') ?></div>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="precio_contado" class="form-label">Precio Contado *</label>
                        <input type="number" step="0.01" class="form-control <?= (session('validation') && session('validation')->hasError('precio_contado')) ? 'is-invalid' : '' ?>"
                          id="precio_contado" name="precio_contado" value="<?= old('precio_contado', $producto->precio_contado ?? '') ?>" required>
                        <?php if (session('validation') && session('validation')->hasError('precio_contado')): ?>
                          <div class="invalid-feedback"><?= session('validation')->getError('precio_contado') ?></div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>

                  <!-- Cálculo Automático - Solo en modo creación -->
                  <?php if (!isset($producto->id)): ?>
                  <div class="alert alert-info">
                    <h6 class="alert-heading"><i class="ri-calculator-line me-2"></i> Cálculo Automático de Producción</h6>
                    <small>Complete los campos para calcular automáticamente la cantidad de productos.</small>
                  </div>
                  <?php endif; ?>

                  <!-- Inventario - Solo en modo creación -->
                  <?php if (!isset($producto->id)): ?>
                  <?php 
                    $materiaPrimaNombre = 'Leche'; // Valor por defecto
                    if ($inventario_seleccionado && !empty($inventario_seleccionado->nombre)) {
                      $materiaPrimaNombre = $inventario_seleccionado->nombre;
                    }
                  ?>
                  <div class="mb-3">
                    <label class="form-label">Inventario de <?= esc($materiaPrimaNombre) ?> *</label>
                    <?php if ($inventario_seleccionado): ?>
                      <?php
                      $stockTotal = $inventario_seleccionado->reserva ?? 0;
                      ?>
                      <div class="alert alert-info">
                        <i class="ri-information-line me-2"></i>
                        <strong>Inventario:</strong> <?= esc($inventario_seleccionado->code) ?>
                      </div>
                      <input type="hidden" name="inventario_id" value="<?= esc($inventario_seleccionado->id) ?>">
                      <div class="input-group">
                        <input type="text" class="form-control"
                          value="<?= esc($inventario_seleccionado->nombre) ?> — Código: <?= esc($inventario_seleccionado->code) ?>" readonly>
                        <span class="input-group-text"><i class="ri-check-line text-success"></i></span>
                      </div>
                      <div class="mt-2 p-2 rounded" style="background:#f0fdf4; border:1px solid #bbf7d0;">
                        <div class="d-flex justify-content-between align-items-center">
                          <span style="font-size:0.82rem;">Reserva disponible:</span>
                          <strong id="reserva-label" style="font-size:0.9rem;"><?= esc(number_format($stockTotal, 2)) ?> L</strong>
                        </div>
                        <div class="reserva-bar-wrap mt-1">
                          <div class="reserva-bar" id="reservaBar" style="width:0%"></div>
                        </div>
                        <div id="reservaBarTexto" style="font-size:0.75rem; color:#6c757d; margin-top:3px;">Ingrese los litros a utilizar para ver el impacto.</div>
                      </div>
                      <div id="stock-disponible" data-stock="<?= esc($stockTotal) ?>" style="display:none;"></div>
                    <?php else: ?>
                      <select class="form-select <?= (session('validation') && session('validation')->hasError('inventario_id')) ? 'is-invalid' : '' ?>"
                        id="inventario_id" name="inventario_id" required>
                        <option value="">Seleccione un inventario</option>
                        <?php foreach ($inventarios as $inventario): ?>
                          <option value="<?= esc($inventario->id) ?>" data-stock="<?= esc($inventario->stock) ?>"
                            <?= (old('inventario_id', $producto->inventario_id ?? '') == $inventario->id) ? 'selected' : '' ?>>
                            <?= esc($inventario->nombre) ?> - Stock: <?= esc(number_format($inventario->stock, 2)) ?> L
                          </option>
                        <?php endforeach; ?>
                      </select>
                      <div class="stock-info">
                        Límite máximo: <span id="stock-disponible" class="stock-available" data-stock="0">0.00</span> L
                      </div>
                    <?php endif; ?>
                    <?php if (session('validation') && session('validation')->hasError('inventario_id')): ?>
                      <div class="invalid-feedback"><?= session('validation')->getError('inventario_id') ?></div>
                    <?php endif; ?>
                  </div>
                  <?php else: ?>
                  <!-- En modo edición, mostrar inventario como solo lectura -->
                  <div class="mb-3">
                    <label class="form-label">Inventario Asignado</label>
                    <input type="text" class="form-control" value="<?= esc($inventario_seleccionado->nombre ?? 'No asignado') ?>" readonly>
                    <input type="hidden" name="inventario_id" value="<?= esc($producto->inventario_id) ?>">
                    <small class="form-text text-muted">El inventario no puede modificarse en productos existentes</small>
                  </div>
                  <?php endif; ?>

                  <!-- Cantidad de litros y litros por unidad - Solo en modo creación -->
                  <?php if (!isset($producto->id)): ?>
                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="cantidad_produccion" class="form-label">Litros de <?= esc($materiaPrimaNombre) ?> a Utilizar *</label>
                        <input type="number" step="0.01" class="form-control <?= (session('validation') && session('validation')->hasError('cantidad_produccion')) ? 'is-invalid' : '' ?>"
                          id="cantidad_produccion" name="cantidad_produccion" value="<?= old('cantidad_produccion', $producto->cantidad_produccion ?? '') ?>" required min="0.01">
                        <?php if (session('validation') && session('validation')->hasError('cantidad_produccion')): ?>
                          <div class="invalid-feedback"><?= session('validation')->getError('cantidad_produccion') ?></div>
                        <?php endif; ?>
                        <div class="stock-info">
                          Máximo permitido: <span id="max-litros" class="stock-available">0.00</span> L
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="cantidad_unidad" class="form-label">Litros por Unidad *</label>
                        <input type="number" step="0.01" class="form-control <?= (session('validation') && session('validation')->hasError('cantidad_unidad')) ? 'is-invalid' : '' ?>"
                          id="cantidad_unidad" name="cantidad_unidad" value="<?= old('cantidad_unidad', $producto->cantidad_unidad ?? '') ?>" required min="0.01">
                        <?php if (session('validation') && session('validation')->hasError('cantidad_unidad')): ?>
                          <div class="invalid-feedback"><?= session('validation')->getError('cantidad_unidad') ?></div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                  <?php endif; ?>

                  <!-- Resultados - Solo en modo creación -->
                  <?php if (!isset($producto->id)): ?>
                  <div id="calculationResult" class="calculation-result">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <h6 class="mb-0"><i class="ri-calculator-fill me-1"></i> Resumen del cálculo</h6>
                      <span id="resultBadge" class="badge"></span>
                    </div>
                    <div class="row g-2" style="font-size:0.88rem;">
                      <div class="col-6">
                        <div class="p-2 rounded" style="background:rgba(0,0,0,0.04);">
                          <div class="text-muted" style="font-size:0.75rem;">Unidades a producir</div>
                          <div class="fw-bold fs-5" id="calculatedStock">0</div>
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="p-2 rounded" style="background:rgba(0,0,0,0.04);">
                          <div class="text-muted" style="font-size:0.75rem;">Litros realmente usados</div>
                          <div class="fw-bold" id="litrosUtilizados">0.00 L</div>
                        </div>
                      </div>
                      <div class="col-12">
                        <div class="p-2 rounded" style="background:rgba(0,0,0,0.04);">
                          <div class="text-muted" style="font-size:0.75rem;">Reserva que quedará en el inventario</div>
                          <div class="fw-bold" id="reservaLeche">0.00 L</div>
                        </div>
                      </div>
                    </div>
                    <div id="alertaCero" class="alert alert-danger py-2 mt-2 mb-0" style="display:none; font-size:0.82rem;">
                      <i class="ri-error-warning-line me-1"></i>
                      Con estos valores se producirían <strong>0 unidades</strong>. Revise los litros a utilizar y los litros por unidad.
                    </div>
                  </div>
                  <?php endif; ?>

                  <!-- Stock y Reserva - Solo en modo creación -->
                  <?php if (!isset($producto->id)): ?>
                  <div class="mb-3">
                    <label for="stock" class="form-label">Cantidad de Productos *</label>
                    <input type="number" class="form-control <?= (session('validation') && session('validation')->hasError('stock')) ? 'is-invalid' : '' ?>"
                      id="stock" name="stock" value="<?= old('stock', $producto->stock ?? '') ?>" required min="0" readonly>
                    <?php if (session('validation') && session('validation')->hasError('stock')): ?>
                      <div class="invalid-feedback"><?= session('validation')->getError('stock') ?></div>
                    <?php endif; ?>
                    <small class="form-text text-muted">Calculado automáticamente (siempre entero)</small>
                  </div>

                  <div class="mb-3">
                    <label for="reserva" class="form-label">Reserva Final de <?= esc($materiaPrimaNombre) ?> (L)</label>
                    <input type="number" step="0.01" class="form-control <?= (session('validation') && session('validation')->hasError('reserva')) ? 'is-invalid' : '' ?>"
                      id="reserva" name="reserva" value="<?= old('reserva', $producto->reserva ?? '') ?>" readonly>
                    <?php if (session('validation') && session('validation')->hasError('reserva')): ?>
                      <div class="invalid-feedback"><?= session('validation')->getError('reserva') ?></div>
                    <?php endif; ?>
                    <small class="form-text text-muted">Stock total − litros realmente utilizados</small>
                  </div>
                  <?php else: ?>
                  <!-- En modo edición: panel informativo claro -->
                  <div class="rounded p-3 mt-2" style="background:#fff8e1; border:1px solid #ffe082;">
                    <div class="fw-bold mb-2" style="color:#7c5c00;"><i class="ri-shield-check-line me-1"></i> Campos protegidos</div>
                    <div class="row g-2" style="font-size:0.85rem;">
                      <div class="col-6">
                        <div class="text-muted">Stock original</div>
                        <div class="fw-bold"><?= esc($producto->stock ?? 0) ?> unidades</div>
                      </div>
                      <div class="col-6">
                        <div class="text-muted">Stock disponible actual</div>
                        <div class="fw-bold"><?= esc($producto->stock_inve ?? 0) ?> unidades</div>
                      </div>
                    </div>
                    <div class="mt-2" style="font-size:0.78rem; color:#7c5c00;">
                      El inventario, el stock original y el stock disponible no pueden modificarse aquí.
                      Solo puede actualizar nombre, precios, categoría, unidad y datos de calidad.
                    </div>
                  </div>
                  <?php endif; ?>
                  <!-- <div class="row">
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="suero_lacteo" class="form-label">Suero de lacteo (Opcional) </label>
                          <input type="number" step="0.01" class="form-control"
                          id="suero_lacteo" name="suero_lacteo" value="<?= old('suero_lacteo', $producto->suero_lacteo ?? '') ?>" >
                       
                      
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="suero_queseria" class="form-label">Suero de queseria(Opcional)</label>
                          <input type="number" step="0.01" class="form-control' ?>"
                          id="suero_queseria" name="suero_queseria" value="<?= old('suero_queseria', $producto->suero_queseria ?? '') ?>" >
                       
                      </div>
                    </div>
                  </div> -->

                  <input type="hidden" name="estado" value="1">

                  <input type="hidden" name="user_id" value="<?= esc(session()->get('id')) ?>">
                </div>
              </div>

              <!-- Control de Calidad -->
              <div class="col-lg-6">
                <div class="card card-body h-100">
                  <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title text-info"><i class="ri-microscope-line me-2"></i> Control de Calidad</h5>
                    <div class="form-check form-switch form-switch-lg">
                      <?php 
                        // Determinar si el toggle debe estar activado
                        $tieneCalidad = !empty($producto->imagen) && $producto->imagen !== 'jpg' 
                                     || !empty($producto->ph) 
                                     || !empty($producto->porocidad)
                                     || !empty($producto->acides)
                                     || !empty($producto->consistencia)
                                     || !empty($producto->color)
                                     || !empty($producto->olor)
                                     || !empty($producto->textura)
                                     || !empty($producto->fecha_vencimiento);
                      ?>
                      <input class="form-check-input" type="checkbox" id="toggleCalidadBtn" <?= $tieneCalidad ? 'checked' : '' ?>>
                      <label class="form-check-label" for="toggleCalidadBtn"></label>
                    </div>
                  </div>
                  <hr>
                  <div id="calidadFields" class="toggle-fields <?= $tieneCalidad ? '' : 'disabled' ?>">
                    <div class="mb-3">
                      <label for="imagen" class="form-label">Imagen del Producto</label>
                      <input type="file" class="form-control <?= (session('validation') && session('validation')->hasError('imagen')) ? 'is-invalid' : '' ?>"
                        id="imagen" name="imagen" accept="image/*">
                      <?php if (session('validation') && session('validation')->hasError('imagen')): ?>
                        <div class="invalid-feedback"><?= session('validation')->getError('imagen') ?></div>
                      <?php endif; ?>
                      <div class="mt-3">
                        <img id="imagen-preview"
                          src="<?= (!empty($producto->imagen)) ? base_url( $producto->imagen) : 'https://placehold.co/200x200/e0f0e9/28a745?text=Producto' ?>"
                          alt="Vista previa" class="img-fluid img-preview">
                      </div>
                    </div>

                    <div class="mb-3">
                      <label for="fecha_vencimiento" class="form-label">Fecha de Vencimiento</label>
                      <input type="date" class="form-control <?= (session('validation') && session('validation')->hasError('fecha_vencimiento')) ? 'is-invalid' : '' ?>"
                        id="fecha_vencimiento" name="fecha_vencimiento" value="<?= old('fecha_vencimiento', $producto->fecha_vencimiento ?? '') ?>">
                      <?php if (session('validation') && session('validation')->hasError('fecha_vencimiento')): ?>
                        <div class="invalid-feedback"><?= session('validation')->getError('fecha_vencimiento') ?></div>
                      <?php endif; ?>
                    </div>

                    <!-- Campos de calidad -->
                    <div class="row">
                      <div class="col-md-6">
                        <div class="mb-3">
                          <label for="porocidad" class="form-label">Porosidad</label>
                          <input type="text" class="form-control <?= (session('validation') && session('validation')->hasError('porocidad')) ? 'is-invalid' : '' ?>"
                            id="porocidad" name="porocidad" value="<?= old('porocidad', $producto->porocidad ?? '') ?>">
                          <?php if (session('validation') && session('validation')->hasError('porocidad')): ?>
                            <div class="invalid-feedback"><?= session('validation')->getError('porocidad') ?></div>
                          <?php endif; ?>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="mb-3">
                          <label for="ph" class="form-label">PH</label>
                          <input type="text" class="form-control <?= (session('validation') && session('validation')->hasError('ph')) ? 'is-invalid' : '' ?>"
                            id="ph" name="ph" value="<?= old('ph', $producto->ph ?? '') ?>">
                          <?php if (session('validation') && session('validation')->hasError('ph')): ?>
                            <div class="invalid-feedback"><?= session('validation')->getError('ph') ?></div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-md-6">
                        <div class="mb-3">
                          <label for="acides" class="form-label">Acidez</label>
                          <input type="text" class="form-control <?= (session('validation') && session('validation')->hasError('acides')) ? 'is-invalid' : '' ?>"
                            id="acides" name="acides" value="<?= old('acides', $producto->acides ?? '') ?>">
                          <?php if (session('validation') && session('validation')->hasError('acides')): ?>
                            <div class="invalid-feedback"><?= session('validation')->getError('acides') ?></div>
                          <?php endif; ?>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="mb-3">
                          <label for="consistencia" class="form-label">Consistencia</label>
                          <input type="text" class="form-control <?= (session('validation') && session('validation')->hasError('consistencia')) ? 'is-invalid' : '' ?>"
                            id="consistencia" name="consistencia" value="<?= old('consistencia', $producto->consistencia ?? '') ?>">
                          <?php if (session('validation') && session('validation')->hasError('consistencia')): ?>
                            <div class="invalid-feedback"><?= session('validation')->getError('consistencia') ?></div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-md-6">
                        <div class="mb-3">
                          <label for="color" class="form-label">Color</label>
                          <input type="text" class="form-control <?= (session('validation') && session('validation')->hasError('color')) ? 'is-invalid' : '' ?>"
                            id="color" name="color" value="<?= old('color', $producto->color ?? '') ?>">
                          <?php if (session('validation') && session('validation')->hasError('color')): ?>
                            <div class="invalid-feedback"><?= session('validation')->getError('color') ?></div>
                          <?php endif; ?>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="mb-3">
                          <label for="olor" class="form-label">Olor</label>
                          <input type="text" class="form-control <?= (session('validation') && session('validation')->hasError('olor')) ? 'is-invalid' : '' ?>"
                            id="olor" name="olor" value="<?= old('olor', $producto->olor ?? '') ?>">
                          <?php if (session('validation') && session('validation')->hasError('olor')): ?>
                            <div class="invalid-feedback"><?= session('validation')->getError('olor') ?></div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>

                    <div class="mb-3">
                      <label for="textura" class="form-label">Textura</label>
                      <input type="text" class="form-control <?= (session('validation') && session('validation')->hasError('textura')) ? 'is-invalid' : '' ?>"
                        id="textura" name="textura" value="<?= old('textura', $producto->textura ?? '') ?>">
                      <?php if (session('validation') && session('validation')->hasError('textura')): ?>
                        <div class="invalid-feedback"><?= session('validation')->getError('textura') ?></div>
                      <?php endif; ?>
                    </div>

                    <div class="mb-3">
                      <label for="observaciones" class="form-label">Observaciones</label>
                      <textarea class="form-control <?= (session('validation') && session('validation')->hasError('observaciones')) ? 'is-invalid' : '' ?>"
                        id="observaciones" name="observaciones" rows="3"><?= old('observaciones', $producto->observaciones ?? '') ?></textarea>
                      <?php if (session('validation') && session('validation')->hasError('observaciones')): ?>
                        <div class="invalid-feedback"><?= session('validation')->getError('observaciones') ?></div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Botones -->
            <div class="hstack gap-2 justify-content-end mt-4">
              <a href="<?= base_url('inventarios') ?>" class="btn btn-secondary">
                <i class="ri-arrow-left-line align-bottom me-1"></i> Cancelar
              </a>
              <?php if (!isset($producto->id)): ?>
              <button type="button" class="btn btn-success" id="btnAbrirConfirmacion" disabled>
                <i class="ri-save-line align-bottom me-1"></i> Crear Producto
              </button>
              <?php else: ?>
              <button type="submit" class="btn btn-success">
                <i class="ri-save-line align-bottom me-1"></i> Actualizar Producto
              </button>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if (!isset($producto->id)): ?>
<!-- MODAL DE CONFIRMACIÓN DE CREACIÓN -->
<div class="modal fade" id="modalConfirmarCreacion" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background:#f0fdf4; border-bottom:1px solid #bbf7d0;">
        <h5 class="modal-title" style="color:#16a34a;"><i class="ri-checkbox-circle-line me-1"></i> Confirmar creación de producto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted mb-3" style="font-size:0.88rem;">Revise el resumen antes de guardar. Esta acción descontará litros de la reserva del inventario.</p>
        <table class="table table-sm table-borderless mb-0" style="font-size:0.88rem;">
          <tbody>
            <tr><td class="text-muted">Producto</td><td class="fw-bold" id="conf_nombre">—</td></tr>
            <tr><td class="text-muted">Categoría</td><td id="conf_categoria">—</td></tr>
            <tr><td class="text-muted">Precios</td><td id="conf_precios">—</td></tr>
            <tr><td class="text-muted">Litros a usar</td><td id="conf_litros">—</td></tr>
            <tr><td class="text-muted">Litros por unidad</td><td id="conf_litros_unidad">—</td></tr>
            <tr><td class="text-muted">Unidades a producir</td><td class="fw-bold fs-5" id="conf_stock">—</td></tr>
            <tr><td class="text-muted">Reserva restante</td><td id="conf_reserva">—</td></tr>
          </tbody>
        </table>
        <div id="conf_alerta_cero" class="alert alert-danger py-2 mt-3 mb-0" style="display:none; font-size:0.82rem;">
          <i class="ri-error-warning-line me-1"></i> El cálculo produce <strong>0 unidades</strong>. No se puede guardar.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Revisar</button>
        <button type="button" class="btn btn-success" id="btnConfirmarGuardar">
          <i class="ri-check-line me-1"></i> Sí, crear producto
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const isEditMode = <?= isset($producto->id) ? 'true' : 'false' ?>;

    if (!isEditMode) {
      const inventarioSelect    = document.getElementById('inventario_id');
      const cantidadProduccion  = document.getElementById('cantidad_produccion');
      const cantidadUnidad      = document.getElementById('cantidad_unidad');
      const stockInput          = document.getElementById('stock');
      const reservaInput        = document.getElementById('reserva');
      const resultDiv           = document.getElementById('calculationResult');
      const stockDisponible     = document.getElementById('stock-disponible');
      const maxLitrosSpan       = document.getElementById('max-litros');
      const calcStock           = document.getElementById('calculatedStock');
      const calcLitros          = document.getElementById('litrosUtilizados');
      const calcReserva         = document.getElementById('reservaLeche');
      const reservaBar          = document.getElementById('reservaBar');
      const reservaBarTexto     = document.getElementById('reservaBarTexto');
      const alertaCero          = document.getElementById('alertaCero');
      const resultBadge         = document.getElementById('resultBadge');
      const btnAbrir            = document.getElementById('btnAbrirConfirmacion');
      const productoForm        = document.getElementById('productoForm');

      function getStockTotal() {
        return parseFloat(stockDisponible.getAttribute('data-stock')) || 0;
      }

      function calcular() {
        const litros      = parseFloat(cantidadProduccion.value) || 0;
        const porUnidad   = parseFloat(cantidadUnidad.value) || 0;
        const stockTotal  = getStockTotal();

        // Validar exceso
        if (litros > stockTotal) {
          cantidadProduccion.classList.add('is-invalid');
        } else {
          cantidadProduccion.classList.remove('is-invalid');
        }

        let unidades = 0, litrosUsados = 0, reservaFinal = stockTotal;
        if (litros > 0 && porUnidad > 0) {
          unidades      = Math.floor(litros / porUnidad);
          litrosUsados  = unidades * porUnidad;
          reservaFinal  = stockTotal - litrosUsados;
        }

        stockInput.value  = unidades;
        reservaInput.value = reservaFinal.toFixed(2);

        // Actualizar panel de resultados
        calcStock.textContent   = unidades;
        calcLitros.textContent  = litrosUsados.toFixed(2) + ' L';
        calcReserva.textContent = reservaFinal.toFixed(2) + ' L';

        // Barra de uso
        const pct = stockTotal > 0 ? Math.min((litrosUsados / stockTotal) * 100, 100) : 0;
        reservaBar.style.width = pct.toFixed(1) + '%';

        if (pct >= 100) {
          reservaBar.className = 'reserva-bar crit';
          reservaBarTexto.textContent = 'Se usará toda la reserva disponible.';
          reservaBarTexto.style.color = '#dc3545';
        } else if (pct >= 90) {
          reservaBar.className = 'reserva-bar warn';
          reservaBarTexto.textContent = 'Se usará el ' + pct.toFixed(0) + '% de la reserva. Quedarán ' + reservaFinal.toFixed(2) + ' L.';
          reservaBarTexto.style.color = '#856404';
        } else if (pct > 0) {
          reservaBar.className = 'reserva-bar';
          reservaBarTexto.textContent = 'Se usará el ' + pct.toFixed(0) + '% de la reserva. Quedarán ' + reservaFinal.toFixed(2) + ' L.';
          reservaBarTexto.style.color = '#6c757d';
        } else {
          reservaBar.className = 'reserva-bar';
          reservaBarTexto.textContent = 'Ingrese los litros a utilizar para ver el impacto.';
          reservaBarTexto.style.color = '#6c757d';
        }

        // Colorear panel de resultados
        resultDiv.className = 'calculation-result' + (pct >= 100 ? ' alerta-critica' : pct >= 90 ? ' alerta-alta' : '');

        // Badge
        if (unidades === 0 && (litros > 0 || porUnidad > 0)) {
          resultBadge.textContent = '0 unidades — revise los valores';
          resultBadge.className = 'badge bg-danger';
        } else if (pct >= 90) {
          resultBadge.textContent = 'Uso alto de reserva';
          resultBadge.className = 'badge bg-warning text-dark';
        } else if (unidades > 0) {
          resultBadge.textContent = 'Listo para guardar';
          resultBadge.className = 'badge bg-success';
        } else {
          resultBadge.textContent = '';
          resultBadge.className = 'badge';
        }

        // Alerta de cero unidades
        alertaCero.style.display = (unidades === 0 && (litros > 0 || porUnidad > 0)) ? 'block' : 'none';

        // Mostrar panel
        resultDiv.style.display = (litros > 0 || porUnidad > 0) ? 'block' : 'none';

        // Habilitar botón solo si hay unidades válidas y litros no exceden reserva
        btnAbrir.disabled = (unidades <= 0 || litros > stockTotal);
      }

      function actualizarStockInfo() {
        const stockTotal = getStockTotal();
        if (maxLitrosSpan) maxLitrosSpan.textContent = stockTotal.toFixed(2);
        calcular();
      }

      if (inventarioSelect && inventarioSelect.tagName === 'SELECT') {
        inventarioSelect.addEventListener('change', function() {
          const opt = inventarioSelect.options[inventarioSelect.selectedIndex];
          stockDisponible.setAttribute('data-stock', opt ? (opt.dataset.stock || 0) : 0);
          actualizarStockInfo();
        });
      }

      cantidadProduccion.addEventListener('input', calcular);
      cantidadUnidad.addEventListener('input', calcular);
      actualizarStockInfo();

      // Abrir modal de confirmación
      btnAbrir.addEventListener('click', function() {
        const litros    = parseFloat(cantidadProduccion.value) || 0;
        const porUnidad = parseFloat(cantidadUnidad.value) || 0;
        const unidades  = parseInt(stockInput.value) || 0;
        const reserva   = parseFloat(reservaInput.value) || 0;

        const nombreEl    = document.getElementById('nombre');
        const categoriaEl = document.getElementById('categoria_id');
        const pcEl        = document.getElementById('precio_contado');
        const pcrEl       = document.getElementById('precio_credito');

        document.getElementById('conf_nombre').textContent         = nombreEl.value.trim() || '—';
        document.getElementById('conf_categoria').textContent      = categoriaEl.options[categoriaEl.selectedIndex]?.text || '—';
        document.getElementById('conf_precios').textContent        = 'Contado: Bs. ' + parseFloat(pcEl.value || 0).toFixed(2) + ' — Crédito: Bs. ' + parseFloat(pcrEl.value || 0).toFixed(2);
        document.getElementById('conf_litros').textContent         = litros.toFixed(2) + ' L';
        document.getElementById('conf_litros_unidad').textContent  = porUnidad.toFixed(2) + ' L/unidad';
        document.getElementById('conf_stock').textContent          = unidades + ' unidades';
        document.getElementById('conf_reserva').textContent        = reserva.toFixed(2) + ' L quedarán en el inventario';
        document.getElementById('conf_alerta_cero').style.display  = unidades <= 0 ? 'block' : 'none';
        document.getElementById('btnConfirmarGuardar').disabled    = unidades <= 0;

        new bootstrap.Modal(document.getElementById('modalConfirmarCreacion')).show();
      });

      // Confirmar y enviar
      document.getElementById('btnConfirmarGuardar').addEventListener('click', function() {
        productoForm.submit();
      });
    }

    // Toggle calidad (ambos modos)
    const toggleCalidad = document.getElementById('toggleCalidadBtn');
    const calidadFields = document.getElementById('calidadFields');
    if (toggleCalidad && calidadFields) {
      toggleCalidad.addEventListener('change', function() {
        calidadFields.classList.toggle('disabled', !this.checked);
      });
    }

    // Preview imagen
    const imagenInput   = document.getElementById('imagen');
    const imagenPreview = document.getElementById('imagen-preview');
    if (imagenInput && imagenPreview) {
      imagenInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
          const reader = new FileReader();
          reader.onload = e => { imagenPreview.src = e.target.result; };
          reader.readAsDataURL(file);
        }
      });
    }
  });
</script>
<?= $this->endSection() ?>