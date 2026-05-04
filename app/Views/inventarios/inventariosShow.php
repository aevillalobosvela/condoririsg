<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  /* Colores personalizados (Se mantienen) */
  .bg-soft-activo { background-color: #d4edda !important; color: #155724 !important; }
  .bg-soft-inactivo { background-color: #f1f3f5 !important; color: #6c757d !important; }
  .bg-soft-stock { background-color: #d4edda !important; color: #155724 !important; }
  .bg-soft-no-stock { background-color: #f8d7da !important; color: #721c24 !important; }
  .alert-success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
  .alert-danger { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
  .alert-info { background-color: #f8fdfa; border-color: #e0f0e9; color: #28a745; }
  .btn-primary { background-color: #28a745 !important; border-color: #28a745 !important; }
  .btn-primary:hover { background-color: #218838 !important; border-color: #1e7e34 !important; }
  .btn-secondary { background-color: #6c757d !important; border-color: #6c757d !important; }
  .btn-soft-warning { background-color: #fff3cd !important; color: #856404 !important; border: 1px solid #ffeaa7; }
  .btn-soft-danger  { background-color: #f8d7da !important; color: #721c24 !important; border: 1px solid #f5c6cb; }
  .btn-soft-danger:hover { background-color: #f1aeb5 !important; color: #721c24 !important; }

  /* Tarjetas */
  .card { border: 1px solid #e0f0e9; box-shadow: 0 0.125rem 0.25rem rgba(40, 167, 69, 0.08); }
  .card-header { background-color: #f8fdfa; border-bottom: 1px solid #e0f0e9; font-weight: 600; color: #28a745; }
  hr { border-top-color: #e0f0e9; }

  /* Estilos para jerarquía de productos */
  .product-tree ul { list-style: none; padding-left: 20px; margin: 0; }
  .product-tree li { margin: 8px 0; padding: 8px; border-radius: 4px; background-color: #f8fdfa; border-left: 3px solid #28a745; position: relative; }
  .product-tree li li { background-color: #f1f8f5; border-left-color: #6c757d; }
  .product-actions { float: right; margin-top: -6px; display: flex; gap: 4px; }
  
  /* Ajuste responsivo para el árbol */
  @media (max-width: 768px) {
    .product-actions { 
      float: none; 
      display: block; 
      margin-top: 5px; 
    }
    .product-actions .btn-sm { margin-right: 5px; margin-bottom: 5px; }
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between mb-3">
                <h4 class="mb-sm-0"><?= esc($title) ?></h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="<?= base_url('inventarios') ?>">Inventarios</a></li>
                        <li class="breadcrumb-item active"><?= esc($title) ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <?php


    use BcMath\Number;
    
    // Verificar si es SUERO LECHE o SUERO QUESERIA
    $nombreInve = strtoupper($inventario->nombre);
    $isSuero = (strpos($nombreInve, 'SUERO LECHE') !== false || strpos($nombreInve, 'SUERO QUESERIA') !== false);
    ?>

    <?php if (session()->getFlashdata('message')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= esc(session()->getFlashdata('message')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Detalles del Inventario</h5>
                    <a href="<?= base_url('inventarios') ?>" class="btn btn-secondary btn-sm">
                        <i class="ri-arrow-left-line align-bottom me-1"></i> Volver
                    </a>
                </div>
                <div class="card-body">
                    <h6 class="text-muted mb-2">Información Principal</h6>
                    <hr class="mt-2 mb-3">
                    <dl class="row mb-4">
                        <dt class="col-sm-4 fw-bold">Nombre:</dt>
                        <dd class="col-sm-8"><?= esc($inventario->nombre) ?></dd>

                        <dt class="col-sm-4 fw-bold">Código:</dt>
                        <dd class="col-sm-8"><?= esc($inventario->code) ?></dd>

                        <dt class="col-sm-4 fw-bold">Descripción:</dt>
                        <dd class="col-sm-8"><?= esc($inventario->descripcion ?: 'N/A') ?></dd>

                        <dt class="col-sm-4 fw-bold">Cantidad Litros:</dt>
                        <dd class="col-sm-8">
                            <span class="badge <?= $inventario->stock > 0 ? 'bg-soft-stock' : 'bg-soft-no-stock' ?>">
                                <?= esc($inventario->stock) ?>
                            </span>
                        </dd>
                        
                        <dt class="col-sm-4 fw-bold">Reserva:</dt>
                        <dd class="col-sm-8"><?= esc($inventario->reserva ?: 'N/A') ?></dd>
                    </dl>

                    <?php if ($puedeEditarCantidad): ?>
                    <div class="alert alert-warning py-2 px-3 mb-2" style="font-size:0.85rem;">
                        <i class="ri-edit-line me-1"></i>
                        Puede corregir o eliminar este inventario porque es el último que registró hoy y aún no tiene productos asociados.
                    </div>
                    <form method="post" action="<?= base_url('inventarios/update-cantidad/' . $inventario->id) ?>">
                        <?= csrf_field() ?>
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text"><i class="ri-edit-line"></i>&nbsp;Cantidad</span>
                            <input type="number" step="0.01" min="0" name="stock"
                                   class="form-control"
                                   value="<?= esc($inventario->stock) ?>"
                                   required>
                            <button type="submit" class="btn btn-warning">
                                <i class="ri-save-line me-1"></i> Guardar
                            </button>
                        </div>
                    </form>
                    <button type="button"
                            class="btn btn-soft-danger btn-sm w-100"
                            data-bs-toggle="modal"
                            data-bs-target="#modalConfirmarEliminar">
                        <i class="ri-delete-bin-line me-1"></i> Eliminar este inventario
                    </button>
                    <?php elseif ($esUltimoDelUsuario && $esDehoy && $tieneProductos): ?>
                    <div class="alert alert-secondary py-2 px-3" style="font-size:0.82rem;">
                        <i class="ri-lock-line me-1"></i>
                        La cantidad no puede editarse ni eliminarse porque ya existen productos registrados bajo este inventario.
                    </div>
                    <?php endif; ?>

                    <h6 class="text-muted mb-2">Fechas</h6>
                    <hr class="mt-2 mb-3">
                    <dl class="row">
                        <dt class="col-sm-4 fw-bold">Creado el:</dt>
                        <dd class="col-sm-8"><?= esc(date('d/m/Y H:i', strtotime($inventario->created_at))) ?></dd>
                        
                        <dt class="col-sm-4 fw-bold">Actualizado el:</dt>
                        <dd class="col-sm-8"><?= esc(date('d/m/Y H:i', strtotime($inventario->updated_at))) ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <?php if (!$isSuero): ?>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0"><i class="ri-ruler-line align-bottom me-1"></i> Control de Calidad</h5>
                    <?php if (!empty($inventario->fecha_calidad)): ?>
                        <div class="btn-group">
                            <a href="<?= base_url('inventarios/control-calidad-pdf/' . $inventario->id) ?>" class="btn btn-danger btn-sm">
                                <i class="ri-file-pdf-line align-bottom me-1"></i> PDF
                            </a>
                            <a href="<?= base_url('inventarios/control-calidad-excel/' . $inventario->id) ?>" class="btn btn-success btn-sm">
                                <i class="ri-file-excel-2-line align-bottom me-1"></i> Excel
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($inventario->fecha_calidad)): ?>
                        <div class="alert alert-soft-warning text-center" role="alert">
                            ⚠️ **Aún no se ha registrado el Control de Calidad** para este inventario.
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <h6 class="text-muted mt-3 mb-2">Valores Medidos</h6>
                            <hr class="mt-2 mb-3">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2"><strong>Grasa (%):</strong> <span class="badge bg-primary-subtle text-primary"><?= esc($inventario->grasa ?? 'N/A') ?></span></li>
                                    <li class="mb-2"><strong>SNG:</strong> <span class="badge bg-primary-subtle text-primary"><?= esc($inventario->sng ?? 'N/A') ?></span></li>
                                    <li class="mb-2"><strong>Densidad:</strong> <span class="badge bg-primary-subtle text-primary"><?= esc($inventario->densidad ?? 'N/A') ?></span></li>
                                    <li class="mb-2"><strong>Lactosa (%):</strong> <span class="badge bg-primary-subtle text-primary"><?= esc($inventario->lactosa ?? 'N/A') ?></span></li>
                                    <li class="mb-2"><strong>Sólidos Totales (%):</strong> <span class="badge bg-primary-subtle text-primary"><?= esc($inventario->solidos ?? 'N/A') ?></span></li>
                                    <li class="mb-2"><strong>Proteína (%):</strong> <span class="badge bg-primary-subtle text-primary"><?= esc($inventario->proteina ?? 'N/A') ?></span></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2"><strong>Agua Agregada (%):</strong> <span class="badge bg-danger-subtle text-danger"><?= esc($inventario->agua ?? 'N/A') ?></span></li>
                                    <li class="mb-2"><strong>Temperatura (°C):</strong> <span class="badge bg-secondary-subtle text-secondary"><?= esc($inventario->temperatura ?? 'N/A') ?></span></li>
                                    <li class="mb-2"><strong>Punto de Congelación:</strong> <span class="badge bg-secondary-subtle text-secondary"><?= esc($inventario->congelacion ?? 'N/A') ?></span></li>
                                    <li class="mb-2"><strong>pH:</strong> <span class="badge bg-success-subtle text-success"><?= esc($inventario->ph ?? 'N/A') ?></span></li>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($puedeEditarCalidad): ?>
                    <hr class="mt-3">
                    <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:0.85rem;">
                        <i class="ri-edit-line me-1"></i>
                        Puede editar los datos de calidad de este inventario.
                    </div>
                    <form method="post" action="<?= base_url('inventarios/update-calidad/' . $inventario->id) ?>">
                        <?= csrf_field() ?>
                        <div class="row g-2">
                            <div class="col-6 col-md-4">
                                <label class="form-label form-label-sm">Grasa (%)</label>
                                <input type="number" step="0.01" name="grasa" class="form-control form-control-sm" value="<?= esc($inventario->grasa ?? '') ?>" required>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label form-label-sm">SNG</label>
                                <input type="number" step="0.01" name="sng" class="form-control form-control-sm" value="<?= esc($inventario->sng ?? '') ?>" required>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label form-label-sm">Densidad</label>
                                <input type="number" step="0.001" name="densidad" class="form-control form-control-sm" value="<?= esc($inventario->densidad ?? '') ?>" required>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label form-label-sm">Lactosa (%)</label>
                                <input type="number" step="0.01" name="lactosa" class="form-control form-control-sm" value="<?= esc($inventario->lactosa ?? '') ?>" required>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label form-label-sm">Sólidos (%)</label>
                                <input type="number" step="0.01" name="solidos" class="form-control form-control-sm" value="<?= esc($inventario->solidos ?? '') ?>" required>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label form-label-sm">Proteína (%)</label>
                                <input type="number" step="0.01" name="proteina" class="form-control form-control-sm" value="<?= esc($inventario->proteina ?? '') ?>" required>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label form-label-sm">Agua (%)</label>
                                <input type="number" step="0.01" name="agua" class="form-control form-control-sm" value="<?= esc($inventario->agua ?? '') ?>" required>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label form-label-sm">Temperatura (°C)</label>
                                <input type="number" step="0.01" name="temperatura" class="form-control form-control-sm" value="<?= esc($inventario->temperatura ?? '') ?>" required>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label form-label-sm">Congelación</label>
                                <input type="number" step="0.001" name="congelacion" class="form-control form-control-sm" value="<?= esc($inventario->congelacion ?? '') ?>" required>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label form-label-sm">pH</label>
                                <input type="number" step="0.01" min="0" max="14" name="ph" class="form-control form-control-sm" value="<?= esc($inventario->ph ?? '') ?>" required>
                            </div>
                            <div class="col-12 mt-2">
                                <button type="submit" class="btn btn-warning btn-sm">
                                    <i class="ri-save-line me-1"></i> Guardar datos de calidad
                                </button>
                            </div>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="row mt-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Productos del Inventario (Árbol)</h5>
                    <div class="flex-shrink-0">
                        <?php if ($isSuero): ?>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarSuero">
                                <i class="ri-add-circle-line align-bottom me-1"></i> Agregar Producto
                            </button>
                        <?php else: ?>
                            <a href="<?= base_url('productos/register/' . $inventario->id) ?>" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Nuevo Producto
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($productos)): ?>
                        <div class="alert alert-info text-center" role="alert">
                            No hay productos registrados en este inventario.
                        </div>
                    <?php else: ?>
                        <div class="product-tree">
                            <?= renderProductTree($productos) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="modalMerma" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formMerma" method="POST"> <?= csrf_field() ?>
        <input type="hidden" name="producto_id" id="merma_producto_id">
        <div class="modal-header">
          <h5 class="modal-title">Registrar Merma: <span id="merma_producto_nombre"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="merma_cantidad" class="form-label">Cantidad de merma</label>
            <input type="number" class="form-control" id="merma_cantidad" name="cantidad" min="1" required>
          </div>
          <div class="mb-3">
            <label for="merma_observacion" class="form-label">Observación Tecnica (opcional)</label>
            <textarea class="form-control" id="merma_observacion" name="observacion" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger">Aplicar Merma</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalAgregar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formAgregar" method="POST"> <?= csrf_field() ?>
        <input type="hidden" name="producto_id" id="agregar_producto_id">
        <div class="modal-header">
          <h5 class="modal-title">Agregar Stock: <span id="agregar_producto_nombre"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="agregar_cantidad" class="form-label">Cantidad a agregar</label>
            <input type="number" class="form-control" id="agregar_cantidad" name="cantidad" min="1" required>
          </div>
          <div class="mb-3">
            <label for="agregar_observacion" class="form-label">Observación Tecnica (opcional)</label>
            <textarea class="form-control" id="agregar_observacion" name="observacion" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success">Agregar Stock</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalSubproducto" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formSubproducto" method="POST" action="<?= base_url('productos/subproducto') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="parent_id" id="subproducto_parent_id">
        <input type="hidden" name="inventario_id" id="subproducto_inventario_id">

        <div class="modal-header">
          <h5 class="modal-title">Nuevo Subproducto de: <span id="subproducto_parent_nombre"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="subproducto_nombre" class="form-label">Producto *</label>
            <input type="text" class="form-control" name="nombre" required maxlength="255" placeholder="Ingrese el nombre del subproducto">
          </div>

          <div class="mb-3">
            <label for="subproducto_descripcion" class="form-label">Descripción</label>
            <textarea class="form-control" name="descripcion" maxlength="500" placeholder="Descripción opcional"></textarea>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Precio Crédito *</label>
                <input type="number" step="0.01" class="form-control" name="precio_credito" required min="0">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Precio Contado *</label>
                <input type="number" step="0.01" class="form-control" name="precio_contado" required min="0">
              </div>
            </div>
          </div>
 
          <div class="mb-3">
            <label class="form-label">Cantidad por Unidad *</label>
            <input type="number" step="0.01" class="form-control" name="cantidad_unidad" required min="0.01" value="1">
          </div>
          <div class="mb-3">
            <label class="form-label">Total SubProducto</label>
            <input type="number" class="form-control" name="stock" required min="0" value="0">
          </div>


          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Categoría *</label>
                <?= form_dropdown('categoria_id', $categoriasSelect ?? [], '', 'class="form-control" required') ?>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Unidad *</label>
                <?= form_dropdown('unidad_id', $unidadesSelect ?? [], '', 'class="form-control" required') ?>
              </div>
            </div>
          </div>
        </div>
        <div >
          <?php $userId = session()->get('id'); ?>
          <input type="hidden" id="user_id" name="user_id" value="<?= $userId ?>">
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-info">Crear Subproducto</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalAgregarSuero" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formAgregarSuero" method="POST" action="<?= base_url('productos/createSuero') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="inventario_id" value="<?= esc($inventario->id) ?>">
        <input type="hidden" id="suero_reserva_disponible" value="<?= esc($inventario->reserva) ?>">
        <div class="modal-header">
          <h5 class="modal-title">Agregar Producto (Desde Reserva)</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            
          <div class="alert alert-info">
             Reserva disponible: <strong><?= esc($inventario->reserva) ?></strong>
          </div>

          <div class="mb-3">
             <label class="form-label">Nombre del Producto *</label>
             <input type="text" class="form-control" name="nombre" required>
          </div>
          
          <div class="mb-3">
             <label class="form-label">Descripción</label>
             <textarea class="form-control" name="descripcion" rows="2"></textarea>
          </div>

          <div class="row">
            <div class="col-md-6">
               <div class="mb-3">
                   <label class="form-label">Precio Crédito *</label>
                   <input type="number" step="0.01" class="form-control" name="precio_credito" required>
               </div>
            </div>
            <div class="col-md-6">
               <div class="mb-3">
                   <label class="form-label">Precio Contado *</label>
                   <input type="number" step="0.01" class="form-control" name="precio_contado" required>
               </div>
            </div>
          </div>
          
          <div class="row">
             <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Cantidad por Unidad (Deduce Reserva) *</label>
                    <input type="number" step="0.01" class="form-control" name="cantidad_unidad" id="suero_cantidad_unidad" required max="<?= esc($inventario->reserva) ?>" min="0.01">
                </div>
             </div>
             <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Total Producto (Stock) *</label>
                    <input type="number" class="form-control" name="stock" required min="1" value="0">
                </div>
             </div>
          </div>

          <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Categoría *</label>
                    <?= form_dropdown('categoria_id', $categoriasSelect ?? [], '', 'class="form-control" required') ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Unidad *</label>
                    <?= form_dropdown('unidad_id', $unidadesSelect ?? [], '', 'class="form-control" required') ?>
                </div>
            </div>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Registrar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal confirmación eliminar inventario -->
<?php if ($puedeEditarCantidad): ?>
<div class="modal fade" id="modalConfirmarEliminar" tabindex="-1" aria-labelledby="modalConfirmarEliminarLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-danger">
      <div class="modal-header" style="background-color:#f8d7da; border-bottom:1px solid #f5c6cb;">
        <h5 class="modal-title text-danger" id="modalConfirmarEliminarLabel">
          <i class="ri-delete-bin-line me-1"></i> Confirmar eliminación
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p class="mb-1">Está a punto de eliminar el inventario:</p>
        <p class="fw-bold mb-1"><?= esc($inventario->nombre) ?> — <span class="text-muted"><?= esc($inventario->code) ?></span></p>
        <p class="mb-3 text-muted" style="font-size:0.88rem;">Cantidad: <?= esc($inventario->stock) ?> L &nbsp;|&nbsp; Turno: <?= esc($inventario->turno) ?></p>
        <div class="alert alert-warning py-2 px-3 mb-0" style="font-size:0.85rem;">
          <i class="ri-information-line me-1"></i>
          Esta acción no se puede deshacer. Solo es posible porque es su último inventario registrado hoy y no tiene productos asociados.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
          <i class="ri-close-line me-1"></i> Cancelar
        </button>
        <form method="post" action="<?= base_url('inventarios/deleteUltimo') ?>" class="d-inline">
          <?= csrf_field() ?>
          <input type="hidden" name="inventario_id" value="<?= esc($inventario->id) ?>">
          <button type="submit" class="btn btn-danger btn-sm">
            <i class="ri-delete-bin-line me-1"></i> Sí, eliminar
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>

  <?php
  function renderProductTree($productos, $nivel = 0)
  {
    $html = '';
    foreach ($productos as $producto) {
      $espacio = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $nivel);
      $stockClass = $producto->stock_inve > 0 ? 'bg-soft-stock' : 'bg-soft-no-stock';
      $estadoClass = $producto->estado ? 'bg-soft-activo' : 'bg-soft-inactivo';
      $estadoText = $producto->estado ? 'Activo' : 'Inactivo';

      $html .= '<li>';
      $html .= '<div class="product-info">';
      $html .= "<strong>{$espacio}" . esc($producto->nombre) . '</strong>';

      if (!empty($producto->descripcion)) {
        $html .= ' — ' . esc($producto->descripcion);
      }

      $html .= '</div>';

      // Mostrar info resumida: precios, stock, estado
      $html .= '<small class="text-muted d-block mt-1">';
      $html .= 'Crédito: ' . number_format($producto->precio_credito, 2) . ' | ';
      $html .= 'Contado: ' . number_format($producto->precio_contado, 2) . ' | ';
      $html .= 'Stock: <span class="badge ' . $stockClass . '">' . esc($producto->stock_inve) . '</span> | ';
      // $html .= 'Estado: <span class="badge ' . $estadoClass . '">' . $estadoText . '</span>';
      $html .= 'Categoria: '. number_format($producto->categoria_id) .' | ';
      $html .= 'Merma:'. number_format($producto->merma). ' | ' ;
      $html .= 'Observacion:'. $producto->observacion. ' | ';
      $html .=  'Agregado:'. number_format($producto->agrega). ' | ';
      $html .= '</small>';
      // Botones de acción
      $html .= '<div class="product-actions">';
      $html .= '<a href="' . base_url('productos/edit/' . $producto->id) . '" class="btn btn-soft-warning btn-sm" title="Editar">';
      $html .= '<i class="ri-pencil-fill"></i>';
      $html .= '</a> ';

      $html .= '<button type="button" class="btn btn-danger btn-sm" 
                  data-bs-toggle="modal" data-bs-target="#modalMerma"
                  data-producto-id="' . $producto->id . '"
                  data-producto-nombre="' . esc($producto->nombre) . '">';
      $html .= '<i class="ri-subtract-line"></i>';
      $html .= '</button> ';

      $html .= '<button type="button" class="btn btn-success btn-sm" 
                  data-bs-toggle="modal" data-bs-target="#modalAgregar"
                  data-producto-id="' . $producto->id . '"
                  data-producto-nombre="' . esc($producto->nombre) . '">';
      $html .= '<i class="ri-add-line"></i>';
      $html .= '</button> ';

      // ✅ Botón para agregar subproducto
      $html .= '<button type="button" class="btn btn-info btn-sm ms-1"
                  data-bs-toggle="modal" data-bs-target="#modalSubproducto"
                  data-parent-id="' . $producto->id . '"
                  data-parent-nombre="' . esc($producto->nombre) . '"
                  data-parent-stock="' . $producto->stock_inve . '"
                  title="Agregar subproducto">';
      $html .= '<i class="ri-node-tree"></i>';
      $html .= '</button>';
      $html .= '</div>';

      // Subproductos
      if (!empty($producto->subproductos)) {
        $html .= '<ul>' . renderProductTree($producto->subproductos, $nivel + 1) . '</ul>';
      }

      $html .= '</li>';
    }
    return $html;
  }
  ?>
</script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // --- Lógica de Modals de Productos (se mantiene) ---
    const modalMerma = document.getElementById('modalMerma');
    const modalAgregar = document.getElementById('modalAgregar');
    const modalSubproducto = document.getElementById('modalSubproducto');

    modalMerma.addEventListener('show.bs.modal', function(event) {
      const button = event.relatedTarget;
      const productoId = button.getAttribute('data-producto-id');
      const productoNombre = button.getAttribute('data-producto-nombre');
      document.getElementById('merma_producto_id').value = productoId;
      document.getElementById('merma_producto_nombre').textContent = productoNombre;
      document.getElementById('formMerma').action = "<?= base_url('productos/merma') ?>/" + productoId;
    });
    modalMerma.addEventListener('hidden.bs.modal', function() {
      document.getElementById('formMerma').reset();
    });

    modalAgregar.addEventListener('show.bs.modal', function(event) {
      const button = event.relatedTarget;
      const productoId = button.getAttribute('data-producto-id');
      const productoNombre = button.getAttribute('data-producto-nombre');
      document.getElementById('agregar_producto_id').value = productoId;
      document.getElementById('agregar_producto_nombre').textContent = productoNombre;
      document.getElementById('formAgregar').action = "<?= base_url('productos/agregar') ?>/" + productoId;
    });
    modalAgregar.addEventListener('hidden.bs.modal', function() {
      document.getElementById('formAgregar').reset();
    });

    modalSubproducto.addEventListener('show.bs.modal', function(event) {
      const button = event.relatedTarget;
      const parentId = button.getAttribute('data-parent-id');
      const parentNombre = button.getAttribute('data-parent-nombre');
      document.getElementById('subproducto_parent_id').value = parentId;
      document.getElementById('subproducto_parent_nombre').textContent = parentNombre;
      document.getElementById('subproducto_inventario_id').value = "<?= esc($inventario->id) ?>";
      document.getElementById('formSubproducto').action = "<?= base_url('productos/subproducto') ?>";
    });
    modalSubproducto.addEventListener('hidden.bs.modal', function() {
      document.getElementById('formSubproducto').reset();
    });

    // --- Lógica del Modal Control de Calidad (Nueva/Ajustada) ---
    const modalCalidad = document.getElementById('modalCalidad');
    const formCalidad = document.getElementById('formCalidad');
    const baseUrlCalidad = '<?= base_url('inventarios/guardar-calidad') ?>';

    if (modalCalidad) {
        modalCalidad.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const inventarioId = button.getAttribute('data-id');
            const inventarioCode = button.getAttribute('data-code');
            
            // 1. Actualizar título
            modalCalidad.querySelector('#modalCodigoInventario').textContent = inventarioCode;
            
            // 2. Actualizar el action del formulario con el ID para el POST
            formCalidad.action = `${baseUrlCalidad}/${inventarioId}`;

            // 3. Opcional: Si tienes un endpoint para cargar datos de calidad existentes, 
            // este es el lugar para hacer una llamada AJAX y llenar los inputs. 
            // Por ahora, solo limpiamos el formulario.
            // formCalidad.reset(); 
        });
        
        modalCalidad.addEventListener('hidden.bs.modal', function() {
             formCalidad.reset();
        });
    }

  });
</script>
<?= $this->endSection() ?>