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

  .result-highlight {
    font-weight: 600;
    color: #28a745;
  }

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

                  <!-- Nombre (Producto) -->
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
                  <div class="mb-3">
                    <label class="form-label">Inventario de Leche *</label>
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
                          value="<?= esc($inventario_seleccionado->nombre) ?> - Stock total: <?= esc(number_format($stockTotal, 2)) ?> L" readonly>
                        <span class="input-group-text"><i class="ri-check-line text-success"></i></span>
                      </div>
                      <!-- ✅ Atributo data-stock para JS -->
                      <div class="stock-info">
                        Límite máximo: <span id="stock-disponible" class="stock-available" data-stock="<?= esc($stockTotal) ?>"><?= esc(number_format($stockTotal, 2)) ?></span> L
                      </div>
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
                        <label for="cantidad_produccion" class="form-label">Litros de Leche a Utilizar *</label>
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
                    <h6 class="text-primary mb-3"><i class="ri-calculator-fill me-2"></i> Resultados</h6>
                    <div class="result-item">
                      <span>Productos calculados:</span>
                      <span id="calculatedStock" class="result-highlight">0</span> unidades
                    </div>
                    <div class="result-item">
                      <span>Litros utilizados:</span>
                      <span id="litrosUtilizados" class="result-highlight">0.00</span> L
                    </div>
                    <div class="result-item">
                      <span>Reserva final:</span>
                      <span id="reservaLeche" class="result-highlight">0.00</span> L
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
                    <label for="reserva" class="form-label">Reserva Final de Leche (L)</label>
                    <input type="number" step="0.01" class="form-control <?= (session('validation') && session('validation')->hasError('reserva')) ? 'is-invalid' : '' ?>"
                      id="reserva" name="reserva" value="<?= old('reserva', $producto->reserva ?? '') ?>" readonly>
                    <?php if (session('validation') && session('validation')->hasError('reserva')): ?>
                      <div class="invalid-feedback"><?= session('validation')->getError('reserva') ?></div>
                    <?php endif; ?>
                    <small class="form-text text-muted">Stock total − litros realmente utilizados</small>
                  </div>
                  <?php else: ?>
                  <!-- En modo edición, mostrar stock actual como información -->
                  <div class="alert alert-warning">
                    <h6 class="alert-heading"><i class="ri-information-line me-2"></i> Información del Producto</h6>
                    <div class="row">
                      <div class="col-md-6">
                        <strong>Stock Actual:</strong> <?= esc($producto->stock_inve ?? 0) ?> unidades
                      </div>
                      <div class="col-md-6">
                        <strong>Stock Original:</strong> <?= esc($producto->stock ?? 0) ?> unidades
                      </div>
                    </div>
                    <small class="text-muted">Los valores de stock no pueden modificarse en productos existentes</small>
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
                  <!-- Categoría y Unidad -->
                  <div class="row">
                    <div class="col-md-6">
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
                      <!-- ✅ Inicia DESACTIVADO -->
                      <input class="form-check-input" type="checkbox" id="toggleCalidadBtn">
                      <label class="form-check-label" for="toggleCalidadBtn"></label>
                    </div>
                  </div>
                  <hr>
                  <div id="calidadFields" class="toggle-fields disabled">
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
              <!-- ✅ Corregido: ir a productos, no inventarios -->
              <a href="<?= base_url('inventarios') ?>" class="btn btn-secondary">
                <i class="ri-arrow-left-line align-bottom me-1"></i> Cancelar
              </a>
              <button type="submit" class="btn btn-success">
                <i class="ri-save-line align-bottom me-1"></i>
                <?= isset($producto->id) ? 'Actualizar' : 'Crear' ?> Producto
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
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Verificar si estamos en modo edición
    const isEditMode = <?= isset($producto->id) ? 'true' : 'false' ?>;
    
    // Solo ejecutar lógica de cálculo en modo creación
    if (!isEditMode) {
      const inventarioSelect = document.getElementById('inventario_id');
      const cantidadProduccionInput = document.getElementById('cantidad_produccion');
      const cantidadUnidadInput = document.getElementById('cantidad_unidad');
      const stockInput = document.getElementById('stock');
      const reservaInput = document.getElementById('reserva');
      const calculationResultDiv = document.getElementById('calculationResult');

      const stockDisponibleSpan = document.getElementById('stock-disponible');
      const maxLitrosSpan = document.getElementById('max-litros');
      const calculatedStockSpan = document.getElementById('calculatedStock');
      const litrosUtilizadosSpan = document.getElementById('litrosUtilizados');
      const reservaLecheSpan = document.getElementById('reservaLeche');
      const productoForm = document.getElementById('productoForm');

      // ✅ Obtener stock total desde data-stock
      function getStockTotal() {
        const stockStr = stockDisponibleSpan.getAttribute('data-stock');
        return parseFloat(stockStr) || 0;
      }

      function calcularProduccion() {
        const litrosAUsar = parseFloat(cantidadProduccionInput.value) || 0;
        const litrosPorUnidad = parseFloat(cantidadUnidadInput.value) || 0;
        const stockTotal = getStockTotal();

        if (litrosAUsar > stockTotal) {
          cantidadProduccionInput.classList.add('is-invalid');
        } else {
          cantidadProduccionInput.classList.remove('is-invalid');
        }

        let productos = 0;
        let litrosUtilizados = 0;
        let reservaFinal = stockTotal;

        if (litrosAUsar > 0 && litrosPorUnidad > 0) {
          productos = Math.floor(litrosAUsar / litrosPorUnidad); // ✅ Entero
          litrosUtilizados = productos * litrosPorUnidad;
          reservaFinal = stockTotal - litrosUtilizados; // ✅ Reserva = stock - utilizados
        }

        stockInput.value = productos;
        reservaInput.value = reservaFinal.toFixed(2);

        calculatedStockSpan.textContent = productos;
        litrosUtilizadosSpan.textContent = litrosUtilizados.toFixed(2);
        reservaLecheSpan.textContent = reservaFinal.toFixed(2);

        calculationResultDiv.style.display = (litrosAUsar > 0 || litrosPorUnidad > 0) ? 'block' : 'none';

        cantidadProduccionInput.setAttribute('max', stockTotal);
      }

      function actualizarStockInfo() {
        const stockTotal = getStockTotal();
        stockDisponibleSpan.textContent = stockTotal.toFixed(2);
        maxLitrosSpan.textContent = stockTotal.toFixed(2);
        calcularProduccion();
      }

      if (inventarioSelect && inventarioSelect.tagName === 'SELECT') {
        inventarioSelect.addEventListener('change', function() {
          const selectedOption = inventarioSelect.options[inventarioSelect.selectedIndex];
          const stock = selectedOption ? (selectedOption.dataset.stock || 0) : 0;
          stockDisponibleSpan.setAttribute('data-stock', stock);
          actualizarStockInfo();
        });
      }

      cantidadProduccionInput.addEventListener('input', calcularProduccion);
      cantidadUnidadInput.addEventListener('input', calcularProduccion);

      // ✅ Inicializar después de que el DOM esté listo
      actualizarStockInfo();

      productoForm.addEventListener('submit', function(e) {
        calcularProduccion();
        const litrosAUsar = parseFloat(cantidadProduccionInput.value) || 0;
        const litrosPorUnidad = parseFloat(cantidadUnidadInput.value) || 0;
        const stockTotal = getStockTotal();

        if (litrosAUsar > stockTotal) {
          alert('La cantidad de litros a usar no puede exceder el stock total del inventario.');
          e.preventDefault();
          return;
        }

        if (litrosAUsar > 0 && litrosPorUnidad <= 0) {
          alert('Debe especificar los litros por unidad.');
          e.preventDefault();
          return;
        }

        if (!toggleCalidadBtn.checked) {
          const inputs = calidadFieldsDiv.querySelectorAll('input:not([type="file"]), textarea, select');
          inputs.forEach(el => el.value = '');
        }
      });
    }

    // Lógica del toggle de calidad (funciona en ambos modos)
    const toggleCalidadBtn = document.getElementById('toggleCalidadBtn');
    const calidadFieldsDiv = document.getElementById('calidadFields');

    if (toggleCalidadBtn && calidadFieldsDiv) {
      toggleCalidadBtn.addEventListener('change', function() {
        calidadFieldsDiv.classList.toggle('disabled', !this.checked);
      });
    }
  });
</script>
<?= $this->endSection() ?>