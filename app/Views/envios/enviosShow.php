<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  /* Colores personalizados */
  .bg-soft-enviado {
    background-color: #d4edda !important;
    color: #155724 !important;
  }

  .bg-soft-pendiente {
    background-color: #fff3cd !important;
    color: #856404 !important;
  }

  /* Productos */
  .product-card {
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid #e0f0e9;
    border-radius: 8px;
  }

  .product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.1);
  }

  /* Precios */
  .price-text {
    font-size: 1.1rem;
    font-weight: 600;
    color: #28a745;
  }

  .credit-price-text {
    font-size: 0.9rem;
    color: #6c757d;
  }

  /* Scroll */
  .scrollable-card-body {
    max-height: 600px;
    overflow-y: auto;
  }

  /* Botón de confirmación */
  .btn-confirm {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
    color: white;
  }

  .btn-confirm:hover {
    background-color: #218838 !important;
    border-color: #1e7e34 !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
  }

  /* Tarjetas */
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

  /* Alertas */
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

  .alert-info {
    background-color: #f8fdfa;
    border-color: #e0f0e9;
    color: #28a745;
  }

  .alert-warning {
    background-color: #fff3cd;
    border-color: #ffeaa7;
    color: #856404;
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
            <li class="breadcrumb-item"><a href="<?= base_url('envios') ?>">Envíos</a></li>
            <li class="breadcrumb-item active"><?= esc($title) ?></li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="ri-checkbox-circle-fill me-2"></i><?= session()->getFlashdata('message') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="ri-error-warning-fill me-2"></i><?= session()->getFlashdata('error') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="row">
    <div class="col-lg-7">
      <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="card-title mb-0">Detalles del <?= esc($tipoLabel ?? 'Envío') ?></h5>
          <div class="flex-shrink-0">
            <?php if ($envio->estado_id != 2): ?>
              <a href="<?= base_url('envios/edit/' . $envio->id) ?>" class="btn btn-warning btn-sm">
                <i class="ri-pencil-fill align-bottom me-1"></i> Editar
              </a>
            <?php endif; ?>
            <a href="<?= base_url('envios') ?>" class="btn btn-secondary btn-sm">
              <i class="ri-arrow-left-line align-bottom me-1"></i> Volver
            </a>
          </div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-4">
                <h6 class="text-muted">Información Básica</h6>
                <hr class="mt-2 mb-3">
                <div class="row mb-2">
                  <div class="col-md-4 fw-bold">Código:</div>
                  <div class="col-md-8"><?= esc($envio->code) ?></div>
                </div>
                <div class="row mb-2">
                  <div class="col-md-4 fw-bold">Sucursal Origen:</div>
                  <div class="col-md-8"><?= esc($sucursalOrigen->nombre ?? 'N/A') ?></div>
                </div>
                <div class="row mb-2">
                  <div class="col-md-4 fw-bold">Sucursal Destino:</div>
                  <div class="col-md-8"><?= esc($sucursalDestino->nombre ?? 'N/A') ?></div>
                </div>
                <div class="row mb-2">
                  <div class="col-md-4 fw-bold">Observación Origen:</div>
                  <div class="col-md-8"><?= esc($envio->observacion_origen ?: 'N/A') ?></div>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-4">
                <h6 class="text-muted">Información Adicional</h6>
                <hr class="mt-2 mb-3">
                <div class="row mb-2">
                  <div class="col-md-4 fw-bold">Estado:</div>
                  <div class="col-md-8">
                    <span class="badge <?= $envio->estado_id == 2 || $envio->estado_id == 9 ? 'bg-soft-enviado' : 'bg-soft-pendiente' ?>">
                      <?= $envio->estado_id == 9 ? 'Enviado' : 'Entregado' ?>
                    </span>
                  </div>
                </div>
                <div class="row mb-2">
                  <div class="col-md-4 fw-bold">Fecha de <?= esc($tipoLabel ?? 'Envío') ?>:</div>
                  <div class="col-md-8"><?= esc($envio->fecha_envio ? date('d/m/Y H:i', strtotime($envio->fecha_envio)) : 'N/A') ?></div>
                </div>
              </div>
            </div>
          </div>

          <?php if ($envio->estado_id != 9 && $envio->estado_id != 2): ?>
            <div class="row mt-4">
              <div class="col-12 text-center">
                <button type="button" class="btn btn-confirm btn-lg" data-bs-toggle="modal" data-bs-target="#confirmEnvioModal">
                  <i class="ri-check-double-line align-bottom me-2"></i>Confirmar <?= esc($tipoAccion) ?>
                </button>
                <p class="text-muted mt-2">
                  Al confirmar la<?= ($tipoAccion === 'devolución' ? '' : 'l') ?> <?= strtolower($tipoAccion) ?>, el estado cambiará a "Enviado" y no podrá ser modificado.
                </p>
              </div>
            </div>
          <?php else: ?>
            <div class="alert alert-info mt-3">
              <i class="ri-information-line me-2"></i>
              <strong><?= esc($tipoAccion) ?> confirmada:</strong> Esta <?= strtolower($tipoAccion) ?> ya ha sido confirmada.
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($envio->estado_id != 9 && $envio->estado_id != 2): ?>
        <div class="card mt-4">
          <form action="<?= base_url('transferencias/confirmarEnvio') ?>" method="post" id="addProductsForm">
            <input type="hidden" name="envio_id" value="<?= esc($envio->id) ?>">
            <div class="card-header d-flex align-items-center justify-content-between">
              <h5 class="card-title mb-0">Productos en la<?= ($tipoAccion === 'Devolución' ? '' : 'l') ?> <?= esc($tipoAccion) ?> <span class="badge bg-secondary" id="itemsCountBadge">0</span></h5>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped table-bordered table-nowrap align-middle table-sm">
                  <thead class="table-light">
                    <tr>
                      <th>Producto</th>
                      <th>Cantidad</th>
                      <th>Observación</th>
                      <th>Acción</th>
                    </tr>
                  </thead>
                  <tbody id="itemsTableBody">
                    <tr id="emptyRow" class="text-center">
                      <td colspan="4" class="py-3 text-muted">Añada productos de la lista de disponibles.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </form>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($envio->estado_id != 9 && $envio->estado_id != 2): ?>
      <div class="col-lg-5">
        <div class="card">
          <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">Productos Disponibles</h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label for="productSearchCard" class="form-label">Buscar producto</label>
              <input type="text" class="form-control" id="productSearchCard" placeholder="Buscar por nombre o código...">
            </div>
            <div class="scrollable-card-body">
              <div class="row row-cols-1 row-cols-md-2 g-4" id="productCardsContainer">
                <?php if (empty($productos)): ?>
                  <div class="col-12">
                    <div class="alert alert-info text-center" role="alert">
                      No hay productos disponibles.
                    </div>
                  </div>
                <?php else: ?>
                  <?php foreach ($productos as $producto):
                    if (($producto->stock_inve ?? 0) > 0): ?>
                      <div class="col product-card"
                        data-producto-id="<?= (int)($producto->id ?? 0) ?>"
                        data-inventario-id="<?= esc($producto->inventario_id) ?>"
                        data-nombre="<?= esc($producto->nombre) ?>"
                        data-stock="<?= (int)($producto->stock_inve ?? 0) ?>"
                        data-precio-contado="<?= esc($producto->precio_contado ?? '0.00') ?>"
                        data-precio-credito="<?= esc($producto->precio_credito ?? '0.00') ?>">
                        <div class="card h-100">
                          <div class="card-body text-center">
                            <h6 class="card-title"><?= esc($producto->nombre) ?></h6>
                            <p class="card-text mb-1">
                              <span class="fw-bold">Lote:</span> <?= esc($producto->inventario_id) ?><br>
                              <span class="fw-bold">Stock:</span> <?= (int)($producto->stock_inve) ?>
                            </p>
                            <p class="card-text mt-2 mb-0 price-text">Bs. <?= number_format($producto->precio_contado ?? 0, 2) ?></p>
                            <div class="input-group mt-2">
                              <input type="number" class="form-control form-control-sm quantity-input" value="1" min="1" max="<?= (int)($producto->stock_inve) ?>">
                              <button type="button" class="btn btn-success btn-sm addItemBtn">
                                <i class="ri-add-line"></i>
                              </button>
                            </div>
                          </div>
                        </div>
                      </div>
                  <?php endif;
                  endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="row mt-4">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="card-title mb-0">
            <?= ($envio->sucursal_origen_id == 2) ? 'Historial de Devoluciones' : 'Historial de Transferencias' ?>
          </h5>
          <div>
            <a href="<?= base_url('transferencias/envioPdf/' . $envio->id) ?>" target="_blank" class="btn btn-info btn-sm">
              <i class="ri-file-pdf-line align-bottom me-1"></i> Generar Envio
            </a>
            <!-- <a href="<?= base_url('envios/exportarExcelEnvio/' . $envio->id) ?>" class="btn btn-success btn-sm">
              <i class="ri-file-excel-line align-bottom me-1"></i> Resumen de Productos
            </a> -->
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped table-bordered table-sm">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Producto</th>
                  <th>Cantidad</th>
                  <th>Cantidad Acep.</th>
                  <th>Precio Contado</th>
                  <th>Estado</th>
                  <th>Observación Destino</th>
                  <th>Fecha</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $transferencias_filtradas = array_filter($transferencias, function ($t) use ($envio) {
                  return isset($t->envio_id) && $t->envio_id == $envio->id;
                });
                ?>
                <?php if (empty($transferencias_filtradas)): ?>
                  <tr>
                    <td colspan="8" class="text-center py-4">
                      <i class="ri-inbox-line display-4 text-muted mb-2"></i>
                      <p class="text-muted">No hay registros para esta <?= strtolower($tipoAccion) ?>.</p>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php $counter = 1; ?>
                  <?php foreach ($transferencias_filtradas as $transferencia): ?>
                    <?php 
                      $cantidad = $transferencia->cantidad ?? 0;
                      $cantidad_acep = $transferencia->cantidad_acep ?? 0;
                      $highlight = ($cantidad != $cantidad_acep) ? 'background-color: #fff3cd;' : '';
                    ?>
                    <tr style="<?= $highlight ?>">
                      <td><?= $counter++ ?></td>
                      <td><?= esc($transferencia->producto_nombre) ?></td>
                      <td><span class="badge bg-primary rounded-pill"><?= esc($cantidad) ?></span></td>
                      <td>
                        <?php if ($cantidad_acep > 0): ?>
                          <span class="badge bg-success rounded-pill"><?= esc($cantidad_acep) ?></span>
                        <?php else: ?>
                          <span class="text-muted">-</span>
                        <?php endif; ?>
                      </td>
                      <td>Bs. <?= number_format($transferencia->precio_contado ?? 0, 2) ?></td>
                      <td>
                        <?php $estado = strtolower($transferencia->estado_nombre ?? ''); ?>
                        <span class="badge <?= $estado == 'enviado' || $estado == 'recibido' ? 'bg-soft-enviado' : 'bg-soft-pendiente' ?>">
                          <?= ucfirst($transferencia->estado_nombre ?? 'N/A') ?>
                        </span>
                      </td>
                      <td><?= esc($transferencia->observacion_destino ?? '-') ?></td>
                      <td><?= esc($transferencia->created_at ? date('d/m/Y H:i', strtotime($transferencia->created_at)) : 'N/A') ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <?php if (!empty($transferencias_filtradas)): ?>
            <div class="row mt-4">
              <div class="col-md-6">
                <div class="card bg-light">
                  <div class="card-body">
                    <h6 class="card-title">Resumen del Historial</h6>
                    <div class="row">
                      <div class="col-6"><strong>Total Productos Diferentes:</strong></div>
                      <div class="col-6"><?= count($transferencias_filtradas) ?></div>
                    </div>
                    <div class="row">
                      <div class="col-6"><strong>Total Cantidad Enviada:</strong></div>
                      <div class="col-6"><?= array_sum(array_column($transferencias_filtradas, 'cantidad')) ?></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="confirmEnvioModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="ri-checkbox-circle-line text-success me-2"></i>Confirmar <?= esc($tipoAccion) ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <i class="ri-inbox-archive-line display-4 text-primary mb-3"></i>
        <h5>¿Confirmar la<?= ($tipoAccion === 'Devolución' ? '' : ' el') ?> <?= strtolower($tipoAccion) ?>?</h5>
        <p class="text-muted">Esta acción es irreversible.</p>
        <div class="alert alert-warning mt-3">
          <i class="ri-alert-line me-2"></i> Verifique los productos antes de confirmar la<?= ($tipoAccion === 'Devolución' ? '' : ' el') ?> <?= strtolower($tipoAccion) ?>.
        </div>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="ri-close-line me-1"></i> Cancelar
        </button>
        <button type="button" class="btn btn-success" id="confirmModalBtn">
          <i class="ri-check-line me-1"></i> Sí, Confirmar
        </button>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const productSearchInput = document.getElementById('productSearchCard');
    const productCardsContainer = document.getElementById('productCardsContainer');
    const productCards = Array.from(productCardsContainer?.querySelectorAll('.product-card') || []);
    const itemsTableBody = document.getElementById('itemsTableBody');
    const itemsCountBadge = document.getElementById('itemsCountBadge');
    const emptyRow = document.getElementById('emptyRow');

    function updateItemsTableDisplay() {
      const rowCount = itemsTableBody.querySelectorAll('tr[data-id]').length;
      if (itemsCountBadge) itemsCountBadge.textContent = rowCount;
      if (emptyRow) {
        emptyRow.style.display = rowCount > 0 ? 'none' : 'table-row';
      }
    }

    updateItemsTableDisplay();

    if (productSearchInput) {
      productSearchInput.addEventListener('input', function(e) {
        const searchText = e.target.value.toLowerCase();
        productCards.forEach(card => {
          const nombre = card.dataset.nombre.toLowerCase();
          card.style.display = nombre.includes(searchText) ? 'block' : 'none';
        });
      });
    }

    if (productCardsContainer) {
      productCardsContainer.addEventListener('click', function(e) {
        const addItemBtn = e.target.closest('.addItemBtn');
        if (addItemBtn) {
          const card = addItemBtn.closest('.product-card');
          const productoId = parseInt(card.dataset.productoId);
          const inventarioId = card.dataset.inventarioId;
          const nombreProducto = card.dataset.nombre;
          const availableStock = parseInt(card.dataset.stock);
          const quantityInput = card.querySelector('.quantity-input');
          const quantity = parseInt(quantityInput.value);

          if (isNaN(productoId) || productoId <= 0) {
            alert('ID de producto inválido.');
            return;
          }
          if (isNaN(quantity) || quantity <= 0) {
            alert('Ingrese una cantidad válida mayor que 0.');
            return;
          }
          if (isNaN(availableStock) || quantity > availableStock) {
            alert(`Stock insuficiente (${availableStock}).`);
            return;
          }

          const uniqueId = `p${productoId}_i${inventarioId}`;
          if (itemsTableBody.querySelector(`tr[data-id="${uniqueId}"]`)) {
            alert(`El producto "${nombreProducto}" (lote ${inventarioId}) ya fue añadido.`);
            return;
          }

          const safeIndex = `p${productoId}_i${inventarioId}`;

          const newRow = document.createElement('tr');
          newRow.setAttribute('data-id', uniqueId);
          newRow.innerHTML = `
            <td>
              ${nombreProducto} <small class="text-muted">(Lote: ${inventarioId})</small>
              <input type="hidden" name="productos[${safeIndex}][producto_id]" value="${productoId}">
              <input type="hidden" name="productos[${safeIndex}][inventario_id]" value="${inventarioId}">
            </td>
            <td>
              <input type="number" name="productos[${safeIndex}][cantidad]" class="form-control" value="${quantity}" min="1" max="${availableStock}" required>
            </td>
            <td>
              <textarea name="productos[${safeIndex}][observacion_origen]" class="form-control" rows="1" placeholder="Observación opcional"></textarea>
            </td>
            <td>
              <button type="button" class="btn btn-sm btn-danger removeItemBtn"><i class="ri-delete-bin-fill"></i></button>
            </td>
          `;
          itemsTableBody.appendChild(newRow);
          quantityInput.value = 1;
          updateItemsTableDisplay();
        }
      });
    }

    if (itemsTableBody) {
      itemsTableBody.addEventListener('click', function(e) {
        if (e.target.closest('.removeItemBtn')) {
          e.target.closest('tr').remove();
          updateItemsTableDisplay();
        }
      });
    }

    document.getElementById('confirmModalBtn')?.addEventListener('click', function() {
      const form = document.getElementById('addProductsForm');
      if (form) {
        const products = form.querySelectorAll('tbody tr[data-id]');
        if (products.length === 0) {
          alert('Debe agregar al menos un producto a la<?= ($tipoAccion === 'devolución' ? '' : 'l') ?> <?= strtolower($tipoAccion) ?> antes de confirmar.');
          var modal = bootstrap.Modal.getInstance(document.getElementById('confirmEnvioModal'));
          if (modal) modal.hide();
          return;
        }

        this.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Procesando...';
        this.disabled = true;
        form.submit();
      }
    });
  });
</script>
<?= $this->endSection() ?>