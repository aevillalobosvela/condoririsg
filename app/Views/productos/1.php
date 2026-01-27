<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
    .form-check-input:checked[type=checkbox] {
        background-color: #28a745;
        border-color: #28a745;
    }

    .form-check-input:not(:checked)[type=checkbox] {
        background-color: #dc3545;
        border-color: #dc3545;
    }

    .img-preview {
        max-width: 200px;
        max-height: 200px;
        object-fit: contain;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .toggle-fields.disabled {
        pointer-events: none;
        opacity: 0.6;
        filter: grayscale(100%);
        transition: opacity 0.3s ease-in-out, filter 0.3s ease-in-out;
    }

    .toggle-fields.enabled {
        pointer-events: auto;
        opacity: 1;
        filter: grayscale(0%);
        transition: opacity 0.3s ease-in-out, filter 0.3s ease-in-out;
    }

    .calculation-result {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 5px;
        padding: 10px;
        margin-top: 10px;
    }

    .result-item {
        margin-bottom: 5px;
        font-size: 14px;
    }

    .result-highlight {
        font-weight: bold;
        color: #198754;
    }

    .stock-info {
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }

    .stock-available {
        color: #198754;
        font-weight: bold;
    }

    .stock-warning {
        color: #dc3545;
        font-weight: bold;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= esc($title) ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/productos">Productos</a></li>
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
                        <strong>¡Error de validación!</strong> Se encontraron errores en el formulario. Por favor, revise los campos resaltados en rojo y corrija la información.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="<?= isset($producto->id) ? base_url('productos/update/' . $producto->id) : base_url('productos/create') ?>"
                    method="post"
                    enctype="multipart/form-data"
                    id="productoForm">
                    <?= csrf_field() ?>

                    <?php if (isset($producto->id)): ?>
                        <input type="hidden" name="id" value="<?= esc($producto->id) ?>">
                    <?php endif; ?>

                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="card card-body h-100">
                                <h5 class="card-title text-primary"><i class="ri-information-fill me-2"></i>Información Básica</h5>
                                <hr>

                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Producto *</label>
                                    <select class="form-select" id="nombre" name="nombre" required>
                                        <option value="" disabled <?= empty(old('nombre', $producto->nombre ?? '')) ? 'selected' : '' ?>>Seleccione la materia prima</option>
                                        <option value="QUESO" <?= (old('nombre', $producto->nombre ?? '') === 'QUESO') ? 'selected' : '' ?>>QUESO</option>
                                        <option value="YOGURD" <?= (old('nombre', $producto->nombre ?? '') === 'YOGURD') ? 'selected' : '' ?>>YOGURD</option>
                                        <option value="QUESILLO" <?= (old('nombre', $producto->nombre ?? '') === 'QUESILLO') ? 'selected' : '' ?>>QUESILLO</option>
                                    </select>
                                    <div class="invalid-feedback">Seleccione la materia prima.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="descripcion" class="form-label">Descripción</label>
                                    <textarea class="form-control <?= (session('validation') && session('validation')->hasError('descripcion')) ? 'is-invalid' : '' ?>" id="descripcion" name="descripcion"
                                        rows="3"><?= old('descripcion', $producto->descripcion ?? '') ?></textarea>
                                    <?php if (session('validation') && session('validation')->hasError('descripcion')): ?>
                                        <div class="invalid-feedback">
                                            <?= session('validation')->getError('descripcion') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="precio_credito" class="form-label">Precio Crédito *</label>
                                            <input type="number" step="0.01" class="form-control <?= (session('validation') && session('validation')->hasError('precio_credito')) ? 'is-invalid' : '' ?>" id="precio_credito" name="precio_credito"
                                                value="<?= old('precio_credito', $producto->precio_credito ?? '') ?>" required>
                                            <?php if (session('validation') && session('validation')->hasError('precio_credito')): ?>
                                                <div class="invalid-feedback">
                                                    <?= session('validation')->getError('precio_credito') ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="precio_contado" class="form-label">Precio Contado *</label>
                                            <input type="number" step="0.01" class="form-control <?= (session('validation') && session('validation')->hasError('precio_contado')) ? 'is-invalid' : '' ?>" id="precio_contado" name="precio_contado"
                                                value="<?= old('precio_contado', $producto->precio_contado ?? '') ?>" required>
                                            <?php if (session('validation') && session('validation')->hasError('precio_contado')): ?>
                                                <div class="invalid-feedback">
                                                    <?= session('validation')->getError('precio_contado') ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sección de cálculo automático -->
                                <div class="alert alert-info">
                                    <h6 class="alert-heading"><i class="ri-calculator-line me-2"></i>Cálculo Automático de Producción</h6>
                                    <small class="mb-0">Complete los campos de litros y litros por unidad para calcular automáticamente la cantidad de productos.</small>
                                </div>

                                <!-- Selección de Inventario -->
                                <div class="mb-3">
                                    <label for="inventario_id" class="form-label">Cantidad de leche litros*</label>
                                    <select class="form-select <?= (session('validation') && session('validation')->hasError('inventario_id')) ? 'is-invalid' : '' ?>"
                                        id="inventario_id" name="inventario_id" required>
                                        <option value="">Seleccione un inventario</option>
                                        <?php foreach ($inventarios as $inventario): ?>
                                            <option value="<?= esc($inventario->id) ?>"
                                                data-stock="<?= esc($inventario->stock) ?>"
                                                <?= (old('inventario_id', $producto->inventario_id ?? '') == $inventario->id) ? 'selected' : '' ?>>
                                                <?= esc($inventario->nombre) ?> - Stock: <?= esc($inventario->stock) ?> litros
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (session('validation') && session('validation')->hasError('inventario_id')): ?>
                                        <div class="invalid-feedback">
                                            <?= session('validation')->getError('inventario_id') ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="stock-info">
                                        Stock disponible: <span id="stock-disponible" class="stock-available">0</span> litros
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cantidad_produccion" class="form-label">Cantidad de Litros de Leche *</label>
                                            <input type="number" step="0.01"
                                                class="form-control <?= (session('validation') && session('validation')->hasError('cantidad_produccion')) ? 'is-invalid' : '' ?>"
                                                id="cantidad_produccion" name="cantidad_litros_leche"
                                                value="<?= old('cantidad_produccion', $producto->cantidad_produccion ?? '') ?>"
                                                required min="0.01">
                                            <?php if (session('validation') && session('validation')->hasError('cantidad_produccion')): ?>
                                                <div class="invalid-feedback">
                                                    <?= session('validation')->getError('cantidad_produccion') ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="stock-info">
                                                Máximo permitido: <span id="max-litros" class="stock-available">0</span> litros
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cantidad_unidad" class="form-label">Litros por Unidad *</label>
                                            <input type="number" step="0.01"
                                                class="form-control <?= (session('validation') && session('validation')->hasError('cantidad_unidad')) ? 'is-invalid' : '' ?>"
                                                id="cantidad_unidad" name="cantidad_unidad"
                                                value="<?= old('cantidad_unidad', $producto->cantidad_unidad ?? '') ?>"
                                                required min="0.01">
                                            <?php if (session('validation') && session('validation')->hasError('cantidad_unidad')): ?>
                                                <div class="invalid-feedback">
                                                    <?= session('validation')->getError('cantidad_unidad') ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Resultados del cálculo -->
                                <div id="calculationResult" class="calculation-result" style="display: none;">
                                    <h6 class="text-primary mb-3"><i class="ri-calculator-fill me-2"></i>Resultados del Cálculo</h6>
                                    <div class="result-item">
                                        <span>Cantidad de productos calculada:</span>
                                        <span id="calculatedStock" class="result-highlight">0</span> unidades
                                    </div>
                                    <div class="result-item">
                                        <span>Litros utilizados:</span>
                                        <span id="litrosUtilizados" class="result-highlight">0</span> litros
                                    </div>
                                    <div class="result-item">
                                        <span>Reserva de leche:</span>
                                        <span id="reservaLeche" class="result-highlight">0</span> litros
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="stock" class="form-label">Cantidad de productos *</label>
                                    <input type="number" class="form-control <?= (session('validation') && session('validation')->hasError('stock')) ? 'is-invalid' : '' ?>"
                                        id="stock" name="stock"
                                        value="<?= old('stock', $producto->stock ?? '') ?>" required min="0" readonly>
                                    <?php if (session('validation') && session('validation')->hasError('stock')): ?>
                                        <div class="invalid-feedback">
                                            <?= session('validation')->getError('stock') ?>
                                        </div>
                                    <?php endif; ?>
                                    <small class="form-text text-muted">Este campo se calcula automáticamente</small>
                                </div>

                                <div class="mb-3">
                                    <label for="reserva" class="form-label">Reserva de Leche (litros)</label>
                                    <input type="number" step="0.01" class="form-control <?= (session('validation') && session('validation')->hasError('reserva')) ? 'is-invalid' : '' ?>"
                                        id="reserva" name="reserva"
                                        value="<?= old('reserva', $producto->reserva ?? '') ?>" readonly>
                                    <?php if (session('validation') && session('validation')->hasError('reserva')): ?>
                                        <div class="invalid-feedback">
                                            <?= session('validation')->getError('reserva') ?>
                                        </div>
                                    <?php endif; ?>
                                    <small class="form-text text-muted">Litros sobrantes después de la producción</small>
                                </div>

                                <div class="mb-3">
                                    <label for="categoria_id" class="form-label">Categoría *</label>
                                    <select class="form-select <?= (session('validation') && session('validation')->hasError('categoria_id')) ? 'is-invalid' : '' ?>" id="categoria_id" name="categoria_id" required>
                                        <option value="">Seleccione una categoría</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?= esc($categoria['id']) ?>" <?= (old('categoria_id', $producto->categoria_id ?? '') == $categoria['id']) ? 'selected' : '' ?>>
                                                <?= esc($categoria['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (session('validation') && session('validation')->hasError('categoria_id')): ?>
                                        <div class="invalid-feedback">
                                            <?= session('validation')->getError('categoria_id') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="unidad_id" class="form-label">Unidad *</label>
                                    <select class="form-select <?= (session('validation') && session('validation')->hasError('unidad_id')) ? 'is-invalid' : '' ?>" id="unidad_id" name="unidad_id" required>
                                        <option value="">Seleccione una unidad</option>
                                        <?php foreach ($unidades as $unidad): ?>
                                            <option value="<?= esc($unidad['id']) ?>" <?= (old('unidad_id', $producto->unidad_id ?? '') == $unidad['id']) ? 'selected' : '' ?>>
                                                <?= esc($unidad['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (session('validation') && session('validation')->hasError('unidad_id')): ?>
                                        <div class="invalid-feedback">
                                            <?= session('validation')->getError('unidad_id') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="estado" name="estado" value="1" <?= (old('estado', $producto->estado ?? '1') == '1') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="estado">Estado Activo</label>
                                </div>

                                <div>
                                    <input type="hidden" id="user_id" name="user_id" value="<?= esc(session()->get('id')) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="card card-body h-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="card-title text-info"><i class="ri-microscope-line me-2"></i>Control de Calidad</h5>
                                    <div class="form-check form-switch form-switch-lg">
                                        <input class="form-check-input" type="checkbox" id="toggleCalidadBtn" checked>
                                        <label class="form-check-label" for="toggleCalidadBtn"></label>
                                    </div>
                                </div>
                                <hr>

                                <div id="calidadFields" class="toggle-fields enabled">

                                    <div class="card card-body h-100">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="card-title text-info"><i class="ri-microscope-line me-2"></i>Control de Calidad</h5>
                                            <div class="form-check form-switch form-switch-lg">
                                                <input class="form-check-input" type="checkbox" id="toggleCalidadBtn" checked>
                                                <label class="form-check-label" for="toggleCalidadBtn"></label>
                                            </div>
                                        </div>
                                        <hr>

                                        <div id="calidadFields" class="toggle-fields enabled">

                                            <div class="mb-3">
                                                <label for="imagen" class="form-label">Imagen del Producto</label>
                                                <input type="file" class="form-control <?= (session('validation') && session('validation')->hasError('imagen')) ? 'is-invalid' : '' ?>" id="imagen" name="imagen" accept="image/*">
                                                <?php if (session('validation') && session('validation')->hasError('imagen')): ?>
                                                    <div class="invalid-feedback">
                                                        <?= session('validation')->getError('imagen') ?>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="mt-3">
                                                    <img id="imagen-preview" src="<?= (!empty($producto->imagen)) ? base_url('uploads/' . $producto->imagen) : 'https://placehold.co/200x200/cccccc/333333?text=Imagen+del+Producto' ?>" alt="Vista previa de la imagen" class="img-fluid img-preview">
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="fecha_vencimiento" class="form-label">Fecha de Vencimiento</label>
                                                <input type="date" class="form-control <?= (session('validation') && session('validation')->hasError('fecha_vencimiento')) ? 'is-invalid' : '' ?>" id="fecha_vencimiento" name="fecha_vencimiento"
                                                    value="<?= old('fecha_vencimiento', $producto->fecha_vencimiento ?? '') ?>">
                                                <?php if (session('validation') && session('validation')->hasError('fecha_vencimiento')): ?>
                                                    <div class="invalid-feedback">
                                                        <?= session('validation')->getError('fecha_vencimiento') ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="porocidad" class="form-label">Porosidad</label>
                                                        <input type="text" class="form-control <?= (session('validation') && session('validation')->hasError('porocidad')) ? 'is-invalid' : '' ?>" id="porocidad" name="porocidad"
                                                            value="<?= old('porocidad', $producto->porocidad ?? '') ?>">
                                                        <?php if (session('validation') && session('validation')->hasError('porocidad')): ?>
                                                            <div class="invalid-feedback">
                                                                <?= session('validation')->getError('porocidad') ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="ph" class="form-label">PH</label>
                                                        <input type="text" class="form-control <?= (session('validation') && session('validation')->hasError('ph')) ? 'is-invalid' : '' ?>" id="ph" name="ph"
                                                            value="<?= old('ph', $producto->ph ?? '') ?>">
                                                        <?php if (session('validation') && session('validation')->hasError('ph')): ?>
                                                            <div class="invalid-feedback">
                                                                <?= session('validation')->getError('ph') ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="acides" class="form-label">Acidez</label>
                                                        <input type="text" class="form-control <?= (session('validation') && session('validation')->hasError('acides')) ? 'is-invalid' : '' ?>" id="acides" name="acides"
                                                            value="<?= old('acides', $producto->acides ?? '') ?>">
                                                        <?php if (session('validation') && session('validation')->hasError('acides')): ?>
                                                            <div class="invalid-feedback">
                                                                <?= session('validation')->getError('acides') ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="consistencia" class="form-label">Consistencia</label>
                                                        <input type="text" class="form-control <?= (session('validation') && session('validation')->hasError('consistencia')) ? 'is-invalid' : '' ?>" id="consistencia" name="consistencia"
                                                            value="<?= old('consistencia', $producto->consistencia ?? '') ?>">
                                                        <?php if (session('validation') && session('validation')->hasError('consistencia')): ?>
                                                            <div class="invalid-feedback">
                                                                <?= session('validation')->getError('consistencia') ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="color" class="form-label">Color</label>
                                                        <input type="text" class="form-control <?= (session('validation') && session('validation')->hasError('color')) ? 'is-invalid' : '' ?>" id="color" name="color"
                                                            value="<?= old('color', $producto->color ?? '') ?>">
                                                        <?php if (session('validation') && session('validation')->hasError('color')): ?>
                                                            <div class="invalid-feedback">
                                                                <?= session('validation')->getError('color') ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="olor" class="form-label">Olor</label>
                                                        <input type="text" class="form-control <?= (session('validation') && session('validation')->hasError('olor')) ? 'is-invalid' : '' ?>" id="olor" name="olor"
                                                            value="<?= old('olor', $producto->olor ?? '') ?>">
                                                        <?php if (session('validation') && session('validation')->hasError('olor')): ?>
                                                            <div class="invalid-feedback">
                                                                <?= session('validation')->getError('olor') ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="textura" class="form-label">Textura</label>
                                                <input type="text" class="form-control <?= (session('validation') && session('validation')->hasError('textura')) ? 'is-invalid' : '' ?>" id="textura" name="textura"
                                                    value="<?= old('textura', $producto->textura ?? '') ?>">
                                                <?php if (session('validation') && session('validation')->hasError('textura')): ?>
                                                    <div class="invalid-feedback">
                                                        <?= session('validation')->getError('textura') ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="mb-3">
                                                <label for="observaciones" class="form-label">Observaciones</label>
                                                <textarea class="form-control <?= (session('validation') && session('validation')->hasError('observaciones')) ? 'is-invalid' : '' ?>" id="observaciones" name="observaciones"
                                                    rows="3"><?= old('observaciones', $producto->observaciones ?? '') ?></textarea>
                                                <?php if (session('validation') && session('validation')->hasError('observaciones')): ?>
                                                    <div class="invalid-feedback">
                                                        <?= session('validation')->getError('observaciones') ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hstack gap-2 justify-content-end mt-4">
                        <a href="<?= base_url('productos') ?>" class="btn btn-secondary">
                            <i class="ri-arrow-left-line align-bottom me-1"></i> Volver a Productos
                        </a>

                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line align-bottom me-1"></i>
                            <?= isset($producto->id) ? 'Actualizar' : 'Crear' ?> Producto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const calidadFields = document.getElementById('calidadFields');
        const toggleBtn = document.getElementById('toggleCalidadBtn');
        const form = document.getElementById('productoForm');

        // Campos de cálculo
        const inventarioSelect = document.getElementById('inventario_id');
        const cantidadLitrosInput = document.getElementById('cantidad_produccion');
        const litrosPorUnidadInput = document.getElementById('cantidad_unidad');
        const stockInput = document.getElementById('stock');
        const reservaInput = document.getElementById('reserva');
        const calculationResult = document.getElementById('calculationResult');
        const stockDisponibleSpan = document.getElementById('stock-disponible');
        const maxLitrosSpan = document.getElementById('max-litros');

        let stockMaximo = 0;

        // Función para actualizar el stock disponible
        function actualizarStockDisponible() {
            const selectedOption = inventarioSelect.options[inventarioSelect.selectedIndex];
            if (selectedOption && selectedOption.value !== '') {
                stockMaximo = parseFloat(selectedOption.getAttribute('data-stock')) || 0;
                stockDisponibleSpan.textContent = stockMaximo.toFixed(2);
                maxLitrosSpan.textContent = stockMaximo.toFixed(2);

                // Actualizar el máximo permitido en el input
                cantidadLitrosInput.setAttribute('max', stockMaximo);

                // Si hay un valor actual, validarlo
                if (cantidadLitrosInput.value) {
                    validarCantidadProduccion();
                }
            } else {
                stockMaximo = 0;
                stockDisponibleSpan.textContent = '0';
                maxLitrosSpan.textContent = '0';
                cantidadLitrosInput.removeAttribute('max');
            }
        }

        // Función para validar la cantidad de producción
        function validarCantidadProduccion() {
            const cantidadLitros = parseFloat(cantidadLitrosInput.value) || 0;

            if (cantidadLitros > stockMaximo) {
                cantidadLitrosInput.classList.add('is-invalid');
                cantidadLitrosInput.nextElementSibling.innerHTML =
                    `<div class="invalid-feedback d-block">La cantidad no puede ser mayor al stock disponible (${stockMaximo} litros)</div>`;
                return false;
            } else if (cantidadLitros <= 0) {
                cantidadLitrosInput.classList.add('is-invalid');
                cantidadLitrosInput.nextElementSibling.innerHTML =
                    `<div class="invalid-feedback d-block">La cantidad debe ser mayor a 0</div>`;
                return false;
            } else {
                cantidadLitrosInput.classList.remove('is-invalid');
                cantidadLitrosInput.nextElementSibling.innerHTML = '';
                return true;
            }
        }

        // Función para calcular la producción
        function calcularProduccion() {
            if (!validarCantidadProduccion()) {
                calculationResult.style.display = 'none';
                stockInput.value = '';
                reservaInput.value = '';
                return;
            }

            const cantidadLitros = parseFloat(cantidadLitrosInput.value) || 0;
            const litrosPorUnidad = parseFloat(litrosPorUnidadInput.value) || 0;

            if (cantidadLitros > 0 && litrosPorUnidad > 0) {
                // Calcular cantidad de productos (división entera)
                const cantidadProductos = Math.floor(cantidadLitros / litrosPorUnidad);

                // Calcular litros utilizados
                const litrosUtilizados = cantidadProductos * litrosPorUnidad;

                // Calcular reserva (litros sobrantes)
                const reserva = cantidadLitros - litrosUtilizados;

                // Actualizar campos
                stockInput.value = cantidadProductos;
                reservaInput.value = reserva.toFixed(2);

                // Mostrar resultados
                document.getElementById('calculatedStock').textContent = cantidadProductos;
                document.getElementById('litrosUtilizados').textContent = litrosUtilizados.toFixed(2);
                document.getElementById('reservaLeche').textContent = reserva.toFixed(2);
                calculationResult.style.display = 'block';
            } else {
                // Limpiar campos si no hay datos válidos
                stockInput.value = '';
                reservaInput.value = '';
                calculationResult.style.display = 'none';
            }
        }

        // Event listeners
        inventarioSelect.addEventListener('change', function() {
            actualizarStockDisponible();
            calcularProduccion();
        });

        cantidadLitrosInput.addEventListener('input', function() {
            validarCantidadProduccion();
            calcularProduccion();
        });

        litrosPorUnidadInput.addEventListener('input', calcularProduccion);

        // Función para manejar el estado de los campos de calidad
        function toggleCalidadFields() {
            if (toggleBtn.checked) {
                calidadFields.classList.remove('disabled');
                calidadFields.classList.add('enabled');
            } else {
                calidadFields.classList.remove('enabled');
                calidadFields.classList.add('disabled');
            }
        }

        // Preview de la imagen
        const imagenInput = document.getElementById('imagen');
        const imagenPreview = document.getElementById('imagen-preview');
        if (imagenInput) {
            imagenInput.addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagenPreview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Validar el formulario antes de enviar
        form.addEventListener('submit', function(e) {
            // Eliminar campos de calidad si están deshabilitados
            if (!toggleBtn.checked) {
                const fields = calidadFields.querySelectorAll('input, select, textarea');
                fields.forEach(field => {
                    field.removeAttribute('required');
                });
            }

            // Validar que se haya seleccionado un inventario
            if (!inventarioSelect.value) {
                e.preventDefault();
                alert('Por favor, seleccione un inventario.');
                inventarioSelect.focus();
                return;
            }

            // Validar la cantidad de producción
            if (!validarCantidadProduccion()) {
                e.preventDefault();
                cantidadLitrosInput.focus();
                return;
            }

            // Validar cálculos
            const cantidadLitros = parseFloat(cantidadLitrosInput.value) || 0;
            const litrosPorUnidad = parseFloat(litrosPorUnidadInput.value) || 0;

            if (cantidadLitros <= 0 || litrosPorUnidad <= 0) {
                e.preventDefault();
                alert('Por favor, ingrese valores válidos para la cantidad de litros y litros por unidad.');
                return;
            }

            if (cantidadLitros < litrosPorUnidad) {
                e.preventDefault();
                alert('La cantidad de litros debe ser mayor o igual a los litros por unidad.');
                return;
            }
        });

        // Inicializar al cargar la página
        actualizarStockDisponible();
        toggleCalidadFields();

        // Si hay un inventario pre-seleccionado (desde el parámetro $inventarioId)
        if (inventarioSelect.value) {
            actualizarStockDisponible();
        }

        // Calcular producción al cargar la página si hay valores
        if (cantidadLitrosInput.value && litrosPorUnidadInput.value) {
            calcularProduccion();
        }

        // Manejar el cambio del switch
        toggleBtn.addEventListener('change', toggleCalidadFields);
    });
</script>
<?= $this->endSection() ?>