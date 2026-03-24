<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
<?= esc($title) ?>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <div>
                <h4 class="mb-sm-0"><?= esc($title) ?></h4>
                <ol class="breadcrumb m-0 mt-2">
                    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('productosagro') ?>">Productos Agro</a></li>
                    <li class="breadcrumb-item active"><?= esc($title) ?></li>
                </ol>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('productosagro') ?>" class="btn btn-outline-secondary">
                    <i class="ri-arrow-left-line"></i> Volver
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8 col-lg-10 mx-auto">
        <div class="card shadow-sm">
            <div class="card-header bg-gradient-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="ri-seedling-line me-2"></i>
                    <?= isset($producto) ? 'Editar Producto' : 'Registrar Nuevo Producto' ?>
                </h5>
            </div>
            <div class="card-body">
                <?php
                
                $action = isset($producto)
                    ? base_url("productosagro/update/{$producto->id}")
                    : base_url('productosagro/store');
                ?>

                <form action="<?= $action ?>" method="post" id="formProductoAgro">
                    <?= csrf_field() ?>


                    <!-- Grupo: Identificación -->
                    <div class="row mb-4">
                      
                        <div class="col-md-12">
                            <label for="categoria" class="form-label fw-bold">Categoría <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ri-folder-3-line"></i></span>
                                <input type="text" class="form-control" id="categoria" name="categoria"
                                       value="<?= old('categoria', $producto->categoria ?? '') ?>"
                                       placeholder="Semillas, Fertilizantes, etc." required maxlength="255">
                            </div>
                        </div>
                    </div>

                    <!-- Nombre -->
                    <div class="mb-4">
                        <label for="producto" class="form-label fw-bold">Nombre del Producto <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ri-leaf-line"></i></span>
                            <input type="text" class="form-control form-control-lg" id="producto" name="producto"
                                   value="<?= old('producto', $producto->producto ?? '') ?>"
                                   placeholder="Ej: Fertilizante NPK 15-15-15" required maxlength="255">
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div class="mb-4">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                                  placeholder="Detalles adicionales (opcional)"><?= old('descripcion', $producto->descripcion ?? '') ?></textarea>
                    </div>

                    <!-- Precios -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="precio_contado" class="form-label fw-bold">Precio Contado (Bs) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Bs</span>
                                <input type="number" 
                                       class="form-control text-end" 
                                       id="precio_contado" 
                                       name="precio_contado"
                                       step="0.01"
                                       min="0"
                                       value="<?= old('precio_contado', $producto->precio_contado ?? '0.00') ?>"
                                       placeholder="0.00" 
                                       required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="precio_credito" class="form-label fw-bold">Precio Crédito (Bs) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Bs</span>
                                <input type="number" 
                                       class="form-control text-end" 
                                       id="precio_credito" 
                                       name="precio_credito"
                                       step="0.01"
                                       min="0"
                                       value="<?= old('precio_credito', $producto->precio_credito ?? '0.00') ?>"
                                       placeholder="0.00" 
                                       required>
                            </div>
                        </div>
                    </div>

                    <!-- Unidad, Inventario -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="unidad_id" class="form-label fw-bold">Unidad <span class="text-danger">*</span></label>
                            <select class="form-select" id="unidad_id" name="unidad_id" required>
                                <option value="">Seleccione...</option>
                                <?php // La variable $unidades debe ser inyectada desde el método create del controlador ?>
                                <?php foreach ($unidades as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= old('unidad_id', $producto->unidad_id ?? '') == $u['id'] ? 'selected' : '' ?>>
                                        <?= esc($u['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="cantidad" class="form-label">Cantidad en Inventario <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="cantidad" name="cantidad"
                                   value="<?= old('cantidad', $producto->cantidad ?? 0) ?>"
                                   min="0" step="1" required>
                        </div>
                    </div>

                    
                   

                    <!-- Botones -->
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <a href="<?= base_url('productosagro') ?>" class="btn btn-secondary px-4">
                            <i class="ri-close-line me-1"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-success px-4">
                            <i class="ri-save-3-line me-1"></i>
                            <?= isset($producto) ? 'Actualizar' : 'Registrar' ?>
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
    // ✅ Mayúsculas automáticas
    ['producto', 'categoria'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', () => {
                el.value = el.value.toUpperCase();
            });
        }
    });

    // ✅ Validación visual
    const form = document.getElementById('formProductoAgro');
    if (form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            // Si falla la validación del navegador, prevenimos el envío.
            if (!isValid) {
                e.preventDefault();
                // Opcional: mostrar un mensaje de error más profesional
                // alert('Por favor complete todos los campos obligatorios.');
            }
        });
    }

    // ✅ Foco inicial
    document.getElementById('producto')?.focus();
});
</script>
<?= $this->endSection() ?>