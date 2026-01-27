<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
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
  .scrollable-card-body {
    max-height: 600px;
    overflow-y: auto;
  }
  .btn-confirm {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
    color: white;
  }
  .btn-confirm:hover {
    background-color: #218838 !important;
    transform: translateY(-2px);
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container-fluid">
  <?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <i class="ri-checkbox-circle-fill me-2"></i><?= session()->getFlashdata('message') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="ri-error-warning-fill me-2"></i><?= session()->getFlashdata('error') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row">
    <div class="col-lg-7">
      <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="card-title mb-0">Detalle de Devolución: <strong><?= esc($envio->code ?? 'ENV-'.$envio->id) ?></strong></h5>
          <a href="<?= base_url('envios/devoluciones') ?>" class="btn btn-secondary btn-sm">
            <i class="ri-arrow-left-line"></i> Volver
          </a>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <h6 class="text-muted">Información Básica</h6>
              <hr>
              <div class="row mb-2">
                <div class="col-md-5 fw-bold">Sucursal Origen:</div>
                <div class="col-md-7"><?= esc($sucursalOrigen->nombre ?? 'N/A') ?></div>
              </div>
              <div class="row mb-2">
                <div class="col-md-5 fw-bold">Sucursal Destino:</div>
                <div class="col-md-7"><?= esc($sucursalDestino->nombre ?? 'N/A') ?></div>
              </div>
              <div class="row mb-2">
                <div class="col-md-5 fw-bold">Usuario Creador:</div>
                <div class="col-md-7"><?= esc($userCreador->usuario ?? 'N/A') ?></div>
              </div>
            </div>
            <div class="col-md-6">
              <h6 class="text-muted">Estado y Fecha</h6>
              <hr>
              <div class="row mb-2">
                <div class="col-md-4 fw-bold">Estado:</div>
                <div class="col-md-8">
                  <?php if ($envio->estado_id == 1): ?>
                    <span class="badge badge-warning">Pendiente</span>
                  <?php elseif ($envio->estado_id == 2): ?>
                    <span class="badge badge-info">En Tránsito</span>
                  <?php elseif ($envio->estado_id == 9): ?>
                    <span class="badge badge-success">Entregado</span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="row mb-2">
                <div class="col-md-4 fw-bold">Fecha:</div>
                <div class="col-md-8"><?= esc($envio->fecha_envio ? date('d/m/Y H:i', strtotime($envio->fecha_envio)) : 'Pendiente') ?></div>
              </div>
            </div>
          </div>

          <?php if ($envio->estado_id == 1): ?>
            <div class="row mt-4">
              <div class="col-12 text-center">
                <button type="button" class="btn btn-confirm btn-lg" data-bs-toggle="modal" data-bs-target="#confirmEnvioModal">
                  <i class="ri-check-double-line me-2"></i>Confirmar Envío
                </button>
                <p class="text-muted mt-2">Al confirmar, se restará el stock de los productos seleccionados.</p>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($envio->estado_id == 1): ?>
        <div class="card mt-4">
          <form action="<?= base_url('devoluciones/confirmarEnvio') ?>" method="post" id="addProductsForm">
            <input type="hidden" name="envio_id" value="<?= esc($envio->id) ?>">
            <div class="card-header">
              <h5 class="card-title mb-0">Productos en el Envío <span class="badge bg-secondary" id="itemsCountBadge">0</span></h5>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped table-bordered">
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

    <?php if ($envio->estado_id == 1): ?>
      <div class="col-lg-5">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">Productos Disponibles (Stock Sucursal)</h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <input type="text" class="form-control" id="productSearchCard" placeholder="Buscar producto...">
            </div>
            <div class="scrollable-card-body">
              <div class="row row-cols-1 row-cols-md-2 g-4" id="productCardsContainer">
                <?php if (empty($productos)): ?>
                  <div class="col-12">
                    <div class="alert alert-info text-center">No hay productos disponibles.</div>
                  </div>
                <?php else: ?>
                  <?php foreach ($productos as $producto):
                    if (($producto->stock ?? 0) > 0): ?>
                      <div class="col product-card"
                        data-producto-id="<?= (int)($producto->producto_id ?? 0) ?>"
                        data-stock-id="<?= esc($producto->id) ?>"
                        data-nombre="<?= esc($producto->producto_nombre) ?>"
                        data-stock="<?= (int)($producto->stock ?? 0) ?>">
                        <div class="card h-100">
                          <div class="card-body text-center">
                            <h6 class="card-title"><?= esc($producto->producto_nombre) ?></h6>
                            <p class="card-text mb-1">
                              <span class="fw-bold">Stock:</span> <?= (int)($producto->stock) ?>
                            </p>
                            <div class="input-group mt-2">
                              <input type="number" class="form-control form-control-sm quantity-input" value="1" min="1" max="<?= (int)($producto->stock) ?>">
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
            Historial de Devoluciones 
            <span class="badge bg-info"><?= count($transferencias ?? []) ?></span>
          </h5>
          <a href="<?= base_url('transferencias/envioPdf/' . $envio->id) ?>" target="_blank" class="btn btn-info btn-sm">
            <i class="ri-file-pdf-line"></i> Exportar PDF
          </a>
        </div>
        <div class="card-body">
          <!-- Debug info (remover después) -->
          <?php if (ENVIRONMENT === 'development'): ?>
            <div class="alert alert-info">
              <strong>Debug:</strong> Total transferencias: <?= count($transferencias ?? []) ?>
              <?php if (!empty($transferencias)): ?>
                <br>Primera transferencia: <?= json_encode($transferencias[0]) ?>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          
          <div class="table-responsive">
            <table class="table table-striped table-bordered table-sm">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Producto</th>
                  <th>Cantidad</th>
                  <th>Estado</th>
                  <th>Fecha</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($transferencias)): ?>
                  <tr>
                    <td colspan="5" class="text-center py-4">
                      <i class="ri-inbox-line display-4 text-muted"></i>
                      <p class="text-muted mb-0">No hay devoluciones registradas.</p>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php $counter = 1; ?>
                  <?php foreach ($transferencias as $transferencia): ?>
                    <tr>
                      <td><?= $counter++ ?></td>
                      <td>
                        <?= esc($transferencia->producto_nombre ?? 'Sin nombre') ?>
                        <small class="text-muted d-block">ID: <?= esc($transferencia->producto_id ?? 'N/A') ?></small>
                      </td>
                      <td><span class="badge bg-primary"><?= esc($transferencia->cantidad ?? 0) ?></span></td>
                      <td>
                        <?php 
                          $estadoNombre = $transferencia->estado_nombre ?? 'N/A';
                          $badgeClass = 'bg-secondary';
                          if (stripos($estadoNombre, 'entregado') !== false || stripos($estadoNombre, 'completado') !== false) {
                            $badgeClass = 'bg-success';
                          } elseif (stripos($estadoNombre, 'pendiente') !== false) {
                            $badgeClass = 'bg-warning';
                          } elseif (stripos($estadoNombre, 'transito') !== false) {
                            $badgeClass = 'bg-info';
                          }
                        ?>
                        <span class="badge <?= $badgeClass ?>"><?= ucfirst(esc($estadoNombre)) ?></span>
                      </td>
                      <td>
                        <?php 
                          $fecha = $transferencia->created_at ?? null;
                          echo $fecha ? esc(date('d/m/Y H:i', strtotime($fecha))) : 'N/A';
                        ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal de Confirmación -->
<div class="modal fade" id="confirmEnvioModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="ri-checkbox-circle-line text-success me-2"></i>Confirmar Envío
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <i class="ri-inbox-archive-line display-4 text-primary mb-3"></i>
        <h5>¿Confirmar este envío?</h5>
        <p class="text-muted">Esta acción restará el stock de los productos seleccionados.</p>
        <div class="alert alert-warning mt-3">
          <i class="ri-alert-line me-2"></i> Verifique los productos antes de confirmar.
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
          const stockId = card.dataset.stockId;
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

          const uniqueId = `p${productoId}_s${stockId}`;
          if (itemsTableBody.querySelector(`tr[data-id="${uniqueId}"]`)) {
            alert(`El producto "${nombreProducto}" ya fue añadido.`);
            return;
          }

          const safeIndex = `p${productoId}_s${stockId}`;

          const newRow = document.createElement('tr');
          newRow.setAttribute('data-id', uniqueId);
          newRow.innerHTML = `
            <td>
              ${nombreProducto}
              <input type="hidden" name="productos[${safeIndex}][producto_id]" value="${productoId}">
              <input type="hidden" name="productos[${safeIndex}][stock_sucursal_id]" value="${stockId}">
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
          alert('Debe agregar al menos un producto al envío antes de confirmar.');
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
