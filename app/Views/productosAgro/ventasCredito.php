<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  :root {
    --primary-green: #28a745;
    --primary-green-light: #f8fdfa;
    --primary-green-border: #e0f0e9;
    --primary-green-hover: #218838;
  }

  .header-top {
    background-color: var(--primary-green-light);
    padding: 12px 24px;
    border-bottom: 1px solid var(--primary-green-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .btn-success {
    background-color: var(--primary-green) !important;
    border-color: var(--primary-green) !important;
  }
  .btn-success:hover {
    background-color: var(--primary-green-hover) !important;
    border-color: var(--primary-green-hover) !important;
  }

  .pos-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
    padding: 24px;
    max-width: 1400px;
    margin: 0 auto;
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.08);
  }

  .section-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary-green);
    margin-bottom: 20px;
    padding-bottom: 8px;
    border-bottom: 2px solid var(--primary-green-border);
  }

  .table-sales thead th {
    background-color: var(--primary-green-light);
    border-bottom: 2px solid var(--primary-green-border);
    font-weight: 600;
  }

  .total-summary-card {
    background-color: var(--primary-green-light);
    border-radius: 12px;
    padding: 24px;
    border: 1px solid var(--primary-green-border);
  }

  .popular-products-section,
  .sales-of-day-card {
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.08);
    padding: 24px;
    margin-top: 24px;
  }

  /* Modal */
  .modal-backdrop {
    background-color: rgba(0,0,0,0.5);
  }
  .modal-content {
    border: none;
    border-radius: 12px;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
  }
  .modal-header {
    background-color: var(--primary-green-light);
    border-bottom: 1px solid var(--primary-green-border);
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
  }
  .modal-title {
    color: var(--primary-green);
    font-weight: 600;
  }

  /* Alertas */
  .alert {
    border-radius: 8px;
  }

  @media (max-width: 991.98px) {
    .pos-container {
      grid-template-columns: 1fr;
      padding: 16px;
    }
    .summary-sidebar {
      order: -1;
    }
    .header-top {
      flex-direction: column;
      gap: 12px;
      text-align: center;
    }
    .header-top .nav-buttons {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 8px;
    }
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
  <!-- Mensajes flash -->
  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?= session()->getFlashdata('success') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?= session()->getFlashdata('error') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Header Top -->
  <div class="header-top mb-4">
    <div class="left-info">
      <strong>Punto de Venta</strong> <br>
      Sucursal Central - <?= date('d \d\e F, Y') ?>
    </div>
  </div>

  <!-- POS Container -->
  <div class="pos-container">
    <!-- Main Sales Content -->
    <div class="main-sales-content">
      <h2 class="section-title">NUEVA VENTA A CREDITO</h2>

      <!-- Búsqueda por DIP -->
      <div class="mb-4">
        <label for="dipSearch" class="form-label mb-2">Buscar por CI o Nombre:</label>
        <div class="input-group">
          <input type="text" class="form-control" id="dipSearch" placeholder="Ingrese CI o nombre (mín. 3 caracteres)" autocomplete="off">
          <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalNuevoExterno">
            <i class="ri-user-add-line"></i> Nuevo externo
          </button>
        </div>
        <div id="personalInfo" class="mt-3"></div>
        <div id="panelSaldoCredito" style="display:none; background:#fffbeb; border-left:4px solid #f97316; border-radius:4px; padding:8px 12px; margin-top:8px; font-size:12px;">
          <div style="font-weight:600; color:#92400e; margin-bottom:4px;">CRÉDITO PERIODO <span id="saldoPeriodo" style="font-weight:400;"></span></div>
          <div style="display:flex; justify-content:space-between;"><span>Saldo anterior:</span><span id="saldoAnteriorVal">Bs. 0.00</span></div>
          <div style="display:flex; justify-content:space-between;"><span>Esta venta:</span><span id="saldoEstaVenta">Bs. 0.00</span></div>
          <div style="display:flex; justify-content:space-between; font-weight:700; border-top:1px dashed #f97316; margin-top:4px; padding-top:4px;"><span>Total a descontar:</span><span id="saldoTotalDescontar">Bs. 0.00</span></div>
        </div>
        <!-- Panel de edición inline cliente externo -->
        <div id="clienteExternoEditPanel" class="border rounded p-3 mt-2 bg-light" style="display:none;">
          <div class="alert alert-info py-2 px-3 mb-2" style="font-size:0.82rem;">
            <i class="ri-information-line me-1"></i>
            Puede editar el último cliente externo registrado por usted hoy.
          </div>
          <div class="row g-2">
            <div class="col-sm-4">
              <input type="text" class="form-control form-control-sm" id="editExtNombre" placeholder="Nombre">
            </div>
            <div class="col-sm-3">
              <input type="text" class="form-control form-control-sm" id="editExtDip" placeholder="DIP / CI">
            </div>
            <div class="col-sm-2">
              <select class="form-select form-select-sm" id="editExtSegmento">
                <option value="SEGURO_UNIV">Seguro Univ.</option>
                <option value="SPECTROLAB">Spectrolab</option>
                <option value="OTROS">Otros</option>
              </select>
            </div>
            <div class="col-sm-3 d-flex gap-2">
              <button type="button" class="btn btn-sm btn-warning flex-grow-1" id="btnGuardarEditExterno">
                <i class="ri-save-line me-1"></i> Guardar
              </button>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCancelarEditExterno">
                <i class="ri-close-line"></i>
              </button>
            </div>
          </div>
          <div id="editExternoError" class="text-danger mt-1" style="font-size:0.8rem; display:none;"></div>
        </div>
      </div>

      <!-- Popular Products -->
      <div class="popular-products-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h3 class="section-title mb-0">Productos Disponibles</h3>
          <div class="d-flex align-items-center gap-2">
            <label for="itemsPerPage" class="form-label mb-0">Mostrar:</label>
            <select id="itemsPerPage" class="form-select form-select-sm" style="width: auto;">
              <option value="9">9</option>
              <option value="18">18</option>
              <option value="36">36</option>
            </select>
          </div>
        </div>

        <div class="mb-3 d-flex gap-2">
          <input type="text" id="productFilter" class="form-control" placeholder="Filtrar por nombre...">
          <select id="categoryFilter" class="form-select" style="max-width:160px;">
            <option value="">Todas las categorías</option>
            <option value="TUBERCULOS">Tubérculos</option>
            <option value="HORTALIZAS">Hortalizas</option>
            <option value="DESHIDRATADOS">Deshidratados</option>
            <option value="CEREALES">Cereales</option>
            <option value="LEGUMINOSAS">Leguminosas</option>
          </select>
        </div>
   

        <div class="row g-3" id="productsGrid"></div>

        <nav class="mt-3">
          <ul class="pagination justify-content-center mb-0" id="pagination"></ul>
        </nav>
      </div>

      <!-- Sales Table -->
      <div class="table-responsive mb-4">
        <table class="table table-hover table-sales">
          <thead>
            <tr>
              <th>Producto</th>
              <th>Cantidad</th>
              <th>Precio</th>
              <th>Desc.</th>
              <th>Subtotal</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="cartItems">
            <!-- Carrito dinámico -->
          </tbody>
        </table>
      </div>
    </div>

    <!-- Summary Sidebar -->
    <div class="summary-sidebar">
      <h2 class="section-title">Resumen de Venta</h2>

     
      <form id="ventaForm" method="post" action="<?= base_url('productosagro/guardarCreditoVenta') ?>">
        <input type="hidden" name="cliente_id" id="clienteIdInput" value="">
        <input type="hidden" name="tipo_receptor" id="tipoReceptorInput" value="">

        <input type="hidden" name="productos" id="productosInput">

        <div class="total-summary-card">
          <div class="summary-item d-flex justify-content-between mb-2">
            <span>Subtotal:</span>
            <strong id="subtotal">Bs. 0.00</strong>
          </div>
          <div class="summary-item d-flex justify-content-between mb-2">
            <span>Descuento:</span>
            <strong class="text-danger" id="discount">- Bs. 0.00</strong>
          </div>
          <div class="summary-item total d-flex justify-content-between pt-2 mt-2 border-top">
            <span>Total:</span>
            <strong class="text-success fs-4" id="total">Bs. 0.00</strong>
          </div>

          <div class="payment-section mt-4 pt-3 border-top">
            <div class="mb-3">
              <label for="tipo_pago" class="form-label">Método de Pago</label>
              <select class="form-select" name="tipo_pago" id="tipoPagoSelect">
                <option value="credito">Crédito</option>
              </select>
            </div>
            <div class="mb-3" id="montoRecibidoGroup">
              <label for="montoRecibido" class="form-label">Monto Recibido</label>
              <input type="number" step="0.01" class="form-control" id="montoRecibido" placeholder="0.00">
            </div>
            <div class="mb-3" id="cambioGroup">
              <label for="cambio" class="form-label">Cambio</label>
              <input type="text" class="form-control" id="cambio" value="Bs. 0.00" readonly>
            </div>

            <button type="button" class="btn btn-success w-100 mb-2" id="finalizeSaleBtn">
              <i class="ri-checkbox-circle-line me-1"></i> Finalizar Venta
            </button>
            <button type="button" class="btn btn-outline-secondary w-100 mb-2" id="printReceiptBtn">
              <i class="ri-printer-line me-1"></i> Imprimir Recibo
            </button>
            <button type="button" class="btn btn-outline-danger w-100" id="cancelSaleBtn">
              <i class="ri-close-circle-line me-1"></i> Cancelar Venta
            </button>
          </div>
        </div>
      </form>

      <!-- Sales of the Day -->
      <!-- Panel Última Venta -->
      <div id="panelUltimaVenta" style="display:none" class="sales-of-day-card">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h3 class="h5 mb-0 text-warning"><i class="ri-edit-line me-1"></i>Última Venta</h3>
          <span class="badge bg-warning text-dark" id="uvCode"></span>
        </div>
        <p class="text-muted small mb-2" style="font-size:0.78rem;">
          <i class="ri-information-line me-1"></i>
          ¿Cometió un error en la última venta? Puede corregir los productos o el receptor antes de cerrar el día. Solo aplica a su última venta registrada hoy.
        </p>
        <div class="mb-2 pb-2 border-bottom">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="text-muted small">Receptor:</span>
            <strong class="small" id="uvCliente"></strong>
          </div>
          <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted small">Monto:</span>
            <strong class="text-success" id="uvMonto"></strong>
          </div>
        </div>
        <div class="mb-2">
          <span class="text-muted small d-block mb-1">Productos:</span>
          <div id="uvProductos" class="small" style="max-height:80px;overflow-y:auto;"></div>
        </div>
        <button type="button" class="btn btn-warning w-100 mt-2 btn-sm" id="btnCorregirVenta">
          <i class="ri-pencil-line me-1"></i> Corregir esta venta
        </button>
        <button type="button" class="btn btn-outline-danger w-100 mt-1 btn-sm" id="btnEliminarVenta">
          <i class="ri-delete-bin-line me-1"></i> Eliminar esta venta
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal confirmación eliminar última venta -->
<div class="modal fade" id="modalEliminarVenta" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="ri-delete-bin-line me-1"></i>Eliminar venta</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <div id="elimVentaError" class="alert alert-danger d-none mb-2"></div>
        <p class="mb-1">¿Eliminar la venta <strong id="elimVentaCode"></strong>?</p>
        <p class="text-muted small mb-0">Monto: <strong id="elimVentaMonto"></strong></p>
        <p class="text-muted small mt-2">El stock de los productos será repuesto automáticamente. Esta acción no se puede deshacer.</p>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger btn-sm" id="btnConfirmarEliminar">
          <i class="ri-delete-bin-line me-1"></i>Sí, eliminar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL NUEVO CLIENTE EXTERNO -->
<div class="modal fade" id="modalNuevoExterno" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-success"><i class="ri-user-add-line me-1"></i> Registrar Cliente Externo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="nuevoExternoError" class="alert alert-danger d-none"></div>
        <div class="mb-2">
          <label class="form-label fw-semibold">Apellido Paterno <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="extApellidoPaterno"
            style="text-transform:uppercase" placeholder="Ej: MAMANI">
        </div>
        <div class="mb-2">
          <label class="form-label fw-semibold">Apellido Materno</label>
          <input type="text" class="form-control" id="extApellidoMaterno"
            style="text-transform:uppercase" placeholder="Ej: QUISPE">
        </div>
        <div class="mb-2">
          <label class="form-label fw-semibold">Nombre(s) <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="extNombres"
            style="text-transform:uppercase" placeholder="Ej: JUAN CARLOS">
        </div>
        <div class="mb-2">
          <label class="form-label fw-semibold">DIP / CI <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="extDip" placeholder="Ej. 7456123">
        </div>
        <div class="mb-2">
          <label class="form-label fw-semibold">Segmento <span class="text-danger">*</span></label>
          <select class="form-select" id="extSegmento">
            <option value="">Seleccione...</option>
            <option value="SEGURO_UNIV">Seguro Universitario</option>
            <option value="SPECTROLAB">Spectrolab</option>
            <option value="OTROS">Otros</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btnGuardarExterno">
          <i class="ri-save-line me-1"></i> Guardar y seleccionar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL DE CONFIRMACIÓN DE VENTA -->
<div class="modal fade" id="confirmSaleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-success"><i class="ri-shopping-cart-line me-1"></i> Confirmar Venta a Crédito</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-3 fw-bold">¿Desea finalizar la venta a crédito para el siguiente personal?</p>

        <div class="mb-3">
          <strong>Personal UTO:</strong> <span id="modalClientName">N/A</span>
        </div>

        <div class="mb-3">
          <strong>Productos:</strong>
          <div id="modalProductsList" class="mt-2"></div>
        </div>

        <div class="row mb-3">
          <div class="col-6"><strong>Método de Pago:</strong> Crédito</div>
          <div class="col-6"><strong>Total a pagar:</strong> <span class="text-success fw-bold fs-5" id="modalTotalAmount">Bs. 0.00</span></div>
        </div>

        <div class="alert alert-info py-2 mt-3" role="alert">
          Esta acción no se puede deshacer y el stock será actualizado.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Revisar Venta</button>
        <button type="button" class="btn btn-success" id="confirmSaleFinalBtn">
          <i class="ri-check-line me-1"></i> Confirmar y Vender
        </button>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const confirmSaleModalInstance = new bootstrap.Modal(document.getElementById('confirmSaleModal'));

    const allProducts = <?= json_encode($productos) ?>;
    const SESSION_USER_ID = <?= (int)session()->get('id') ?>;
    const TODAY = '<?= date('Y-m-d') ?>';
    let itemsPerPage = 9;
    let currentPage = 1;
    let cart = [];
    let selectedPersonal = null;

    // Elementos del DOM
    const dipSearch = document.getElementById('dipSearch');
    const personalInfo = document.getElementById('personalInfo');
    const clienteIdInput = document.getElementById('clienteIdInput');

    const productsGrid = document.getElementById('productsGrid');
    const paginationContainer = document.getElementById('pagination');
    const cartItemsContainer = document.getElementById('cartItems');
    const subtotalDisplay = document.getElementById('subtotal');
    const discountDisplay = document.getElementById('discount');
    const totalDisplay = document.getElementById('total');
    const montoRecibidoInput = document.getElementById('montoRecibido');
    const cambioInput = document.getElementById('cambio');
    const finalizeSaleBtn = document.getElementById('finalizeSaleBtn');
    const confirmSaleFinalBtn = document.getElementById('confirmSaleFinalBtn');
    const cancelSaleBtn = document.getElementById('cancelSaleBtn');
    const ventaForm = document.getElementById('ventaForm');
    const productosInput = document.getElementById('productosInput');
    const tipoPagoSelect = document.getElementById('tipoPagoSelect');
    const montoRecibidoGroup = document.getElementById('montoRecibidoGroup');
    const cambioGroup = document.getElementById('cambioGroup');

    const tipoReceptorInput = document.getElementById('tipoReceptorInput');
    const modalNuevoExterno = new bootstrap.Modal(document.getElementById('modalNuevoExterno'));

    let searchTimeout;
    let saldoAnteriorActual = 0;

    function fetchSaldoCredito(tipo, id) {
      fetch(`<?= base_url('productosagro/saldoCredito') ?>?tipo=${tipo}&id=${id}`)
        .then(r => r.json())
        .then(data => {
          saldoAnteriorActual = parseFloat(data.saldo_anterior) || 0;
          const estaVenta = parseFloat(totalDisplay.textContent.replace('Bs. ', '')) || 0;
          document.getElementById('saldoPeriodo').textContent = data.periodo ? '(' + data.periodo + ')' : '';
          document.getElementById('saldoAnteriorVal').textContent = 'Bs. ' + saldoAnteriorActual.toFixed(2);
          document.getElementById('saldoEstaVenta').textContent = 'Bs. ' + estaVenta.toFixed(2);
          document.getElementById('saldoTotalDescontar').textContent = 'Bs. ' + (saldoAnteriorActual + estaVenta).toFixed(2);
          document.getElementById('panelSaldoCredito').style.display = 'block';
        })
        .catch(() => {});
    }

    function ocultarPanelSaldo() {
      saldoAnteriorActual = 0;
      document.getElementById('panelSaldoCredito').style.display = 'none';
    }

    dipSearch.addEventListener('input', function() {
      clearTimeout(searchTimeout);
      const dip = this.value.trim();
      personalInfo.innerHTML = '';
      clienteIdInput.value = '';
      tipoReceptorInput.value = '';
      selectedPersonal = null;
      ocultarPanelSaldo();
      if (dip.length < 3) return;
      searchTimeout = setTimeout(() => {
        fetch(`<?= base_url('productosagro/buscarPersonalUto') ?>?dip=${encodeURIComponent(dip)}`)
          .then(r => r.json())
          .then(data => {
            if (!data || data.length === 0) {
              personalInfo.innerHTML = '<div class="alert alert-warning">No se encontró ningún resultado. Puede registrar un cliente externo con el botón "Nuevo externo".</div>';
              return;
            }
            const html = data.map(p => {
              const esExterno = p.tipo === 'externo';
              const badge = esExterno
                ? `<span class="badge bg-warning text-dark">${p.cargo}</span>`
                : `<span class="badge bg-info text-dark">${p.cargo || 'Personal UTO'}</span>`;
              const idVal = esExterno ? p.id : p.id_persona;
              return `
                <div class="card border-success mb-2 resultado-persona" style="cursor:pointer;"
                     data-id="${idVal}" data-tipo="${p.tipo}" data-nombre="${p.nombre}" data-dip="${p.dip}"
                     data-user-id="${p.user_id || ''}" data-created-at="${p.created_at || ''}" data-segmento="${p.cargo || ''}">
                  <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                    <div>
                      <strong>${p.nombre}</strong> — DIP: ${p.dip}<br>
                      <small>${p.seccion || ''}</small>
                    </div>
                    ${badge}
                  </div>
                </div>`;
            }).join('');
            personalInfo.innerHTML = html;

            document.querySelectorAll('.resultado-persona').forEach(el => {
              el.addEventListener('click', () => {
                selectedPersonal = { nombre: el.dataset.nombre, dip: el.dataset.dip, tipo: el.dataset.tipo };
                clienteIdInput.value    = el.dataset.id;
                tipoReceptorInput.value = el.dataset.tipo;
                fetchSaldoCredito(el.dataset.tipo, el.dataset.id);

                const panel = document.getElementById('clienteExternoEditPanel');
                if (el.dataset.tipo === 'externo') {
                  const createdAt = el.dataset.createdAt || '';
                  const userId    = el.dataset.userId    || '';
                  if (parseInt(userId) === SESSION_USER_ID && createdAt.substring(0, 10) === TODAY) {
                    document.getElementById('editExtNombre').value   = el.dataset.nombre;
                    document.getElementById('editExtDip').value      = el.dataset.dip;
                    document.getElementById('editExtSegmento').value = el.dataset.segmento || '';
                    document.getElementById('editExternoError').style.display = 'none';
                    panel.style.display = 'block';
                  } else {
                    panel.style.display = 'none';
                  }
                } else {
                  panel.style.display = 'none';
                }

                personalInfo.innerHTML  = `
                  <div class="alert alert-success py-2">
                    <strong>Seleccionado:</strong> ${el.dataset.nombre} — DIP: ${el.dataset.dip}
                    <span class="badge ${el.dataset.tipo === 'externo' ? 'bg-warning text-dark' : 'bg-info text-dark'} ms-2">
                      ${el.dataset.tipo === 'externo' ? 'Externo' : 'Personal UTO'}
                    </span>
                  </div>`;
              });
            });
          })
          .catch(() => {
            personalInfo.innerHTML = '<div class="alert alert-danger">Error al buscar. Intente nuevamente.</div>';
          });
      }, 500);
    });

    document.getElementById('btnGuardarExterno').addEventListener('click', function() {
      const apellidoPaterno = document.getElementById('extApellidoPaterno').value.trim().toUpperCase();
      const apellidoMaterno = document.getElementById('extApellidoMaterno').value.trim().toUpperCase();
      const nombres         = document.getElementById('extNombres').value.trim().toUpperCase();
      const dip      = document.getElementById('extDip').value.trim();
      const segmento = document.getElementById('extSegmento').value;
      const errDiv   = document.getElementById('nuevoExternoError');

      if (!apellidoPaterno || !nombres || !dip || !segmento) {
        errDiv.textContent = 'Apellido paterno, nombre(s), DIP y segmento son obligatorios.';
        errDiv.classList.remove('d-none');
        return;
      }
      errDiv.classList.add('d-none');

      fetch('<?= base_url('productosagro/guardarClienteExterno') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: new URLSearchParams({
          apellido_paterno: apellidoPaterno,
          apellido_materno: apellidoMaterno,
          nombres, dip, segmento,
          '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
        })
      })
      .then(r => r.json())
      .then(res => {
        if (!res.success) {
          errDiv.textContent = res.error;
          errDiv.classList.remove('d-none');
          return;
        }
        const c = res.cliente;
        selectedPersonal = { nombre: c.nombre, dip: c.dip, tipo: 'externo' };
        clienteIdInput.value    = c.id;
        tipoReceptorInput.value = 'externo';

        document.getElementById('editExtNombre').value   = c.nombre;
        document.getElementById('editExtDip').value      = c.dip;
        document.getElementById('editExtSegmento').value = c.segmento || '';
        document.getElementById('editExternoError').style.display = 'none';
        document.getElementById('clienteExternoEditPanel').style.display = 'block';

        personalInfo.innerHTML  = `
          <div class="alert alert-success py-2">
            <strong>Seleccionado:</strong> ${c.nombre} — DIP: ${c.dip}
            <span class="badge bg-warning text-dark ms-2">Externo</span>
          </div>`;
        fetchSaldoCredito('externo', c.id);
        modalNuevoExterno.hide();
        document.getElementById('extApellidoPaterno').value = '';
        document.getElementById('extApellidoMaterno').value = '';
        document.getElementById('extNombres').value = '';
        document.getElementById('extDip').value       = '';
        document.getElementById('extSegmento').value  = '';
      })
      .catch(() => {
        errDiv.textContent = 'Error de conexión. Intente nuevamente.';
        errDiv.classList.remove('d-none');
      });
    });

    // Colores por categoría (mismo esquema que el formulario)
    const CAT_COLORS = {
      'TUBERCULOS':    { bg: '#fde68a', border: '#d97706', text: '#78350f' },
      'HORTALIZAS':    { bg: '#bbf7d0', border: '#16a34a', text: '#14532d' },
      'DESHIDRATADOS': { bg: '#fed7aa', border: '#ea580c', text: '#7c2d12' },
      'CEREALES':      { bg: '#bfdbfe', border: '#2563eb', text: '#1e3a8a' },
      'LEGUMINOSAS':   { bg: '#e9d5ff', border: '#7c3aed', text: '#4c1d95' },
      'DEFAULT':       { bg: '#f1f5f9', border: '#64748b', text: '#1e293b' },
    };
    function getCatColor(cat) {
      return CAT_COLORS[(cat || '').toUpperCase()] || CAT_COLORS['DEFAULT'];
    }

    // --- FUNCIONES DE PRODUCTOS Y CARRITO ---

    function renderProducts(nameFilter = '', catFilter = '') {
      const filtered = allProducts.filter(p =>
        p.producto.toLowerCase().includes(nameFilter.toLowerCase()) &&
        (!catFilter || (p.categoria || '').toUpperCase() === catFilter)
      );

      const totalPages = Math.ceil(filtered.length / itemsPerPage);
      const start = (currentPage - 1) * itemsPerPage;
      const paginated = filtered.slice(start, start + itemsPerPage);

      productsGrid.innerHTML = paginated.map(p => {
        const c = getCatColor(p.categoria);
        return `
        <div class="col-6 col-md-4 product-card"
             data-id="${p.id}"
             data-name="${p.producto}"
             data-price="${p.precio_contado}"
             data-stock="${p.cantidad_inve}"
             data-unidad="und"
             data-categoria="${(p.categoria||'').toUpperCase()}">
          <div class="card h-100 shadow-sm border-0" style="border-left:4px solid ${c.border} !important; cursor:pointer;">
            <div class="card-body text-center p-3" style="background:linear-gradient(135deg,${c.bg} 0%,#ffffff 100%);">
              <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2"
                   style="width:56px;height:56px;background-color:${c.border};">
                <span class="fw-bold" style="color:#fff;font-size:0.85rem;">${p.producto.substring(0,2)}</span>
              </div>
              <h6 class="card-title mb-1" style="color:${c.text};font-size:0.82rem;">${p.producto}</h6>
              <p class="card-text mb-1">
                <span class="fw-bold" style="color:${c.border};font-size:0.85rem;">Bs. ${parseFloat(p.precio_contado).toFixed(2)}</span>
              </p>
              <span class="badge" style="background-color:${c.border};font-size:0.62rem;">${p.cantidad_inve} ${p.unidad_nombre || 'und'}</span>
            </div>
          </div>
        </div>`;
      }).join('');

      renderPagination(totalPages);

      document.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('click', () => addToCart({
          id: card.dataset.id,
          name: card.dataset.name,
          price: parseFloat(card.dataset.price),
          stock: parseInt(card.dataset.stock),
          unidad: card.dataset.unidad
        }));
      });
    }

    function renderPagination(totalPages) {
      if (totalPages <= 1) {
        paginationContainer.innerHTML = '';
        return;
      }

      let html = '';
      const maxVisible = 5;
      let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
      let endPage = Math.min(totalPages, startPage + maxVisible - 1);
      if (endPage - startPage + 1 < maxVisible) {
        startPage = Math.max(1, endPage - maxVisible + 1);
      }

      if (currentPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Anterior</a></li>`;
      }

      for (let i = startPage; i <= endPage; i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                  <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>`;
      }

      if (currentPage < totalPages) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Siguiente</a></li>`;
      }

      paginationContainer.innerHTML = html;

      paginationContainer.querySelectorAll('a[data-page]').forEach(link => {
        link.addEventListener('click', (e) => {
          e.preventDefault();
          currentPage = parseInt(link.dataset.page);
          const filter = document.getElementById('productFilter').value;
          renderProducts(filter);
        });
      });
    }

    function addToCart(product) {
      if (product.stock <= 0) return;

      const existing = cart.find(item => item.id === product.id);
      if (existing) {
        existing.quantity = Math.min(existing.quantity + 1, product.stock);
      } else {
        cart.push({ ...product, quantity: 1, discount: 0 });
      }
      renderCart();
      updateSummary();

      // Feedback visual en la card del producto
      const card = document.querySelector(`.product-card[data-id="${product.id}"] .card`);
      if (card) {
        card.style.transition = 'transform 0.15s, box-shadow 0.15s';
        card.style.transform = 'scale(1.06)';
        card.style.boxShadow = '0 0 0 3px #28a745';
        setTimeout(() => { card.style.transform = ''; card.style.boxShadow = ''; }, 200);
      }
    }

    function renderCart() {
      cartItemsContainer.innerHTML = cart.map(item => `
        <tr>
          <td>
            <div>${item.name}</div>
            <small class="text-muted">Stock: ${item.stock} ${item.unidad || 'und'}</small>
          </td>
          <td>
            <div class="input-group input-group-sm" style="width: 120px;">
              <button class="btn btn-outline-secondary dec" type="button" data-id="${item.id}">-</button>
              <input type="number" class="form-control text-center qty" value="${item.quantity}" min="1" max="${item.stock}" data-id="${item.id}">
              <button class="btn btn-outline-secondary inc" type="button" data-id="${item.id}">+</button>
            </div>
          </td>
          <td>Bs. ${parseFloat(item.price).toFixed(2)}</td>
          <td>
            <input type="number" class="form-control form-control-sm text-center discount-input" value="${item.discount}" data-id="${item.id}" min="0" max="100" step="0.01" style="width: 70px;">
          </td>
          <td>Bs. ${(item.price * item.quantity * (1 - item.discount/100)).toFixed(2)}</td>
          <td>
            <button class="btn btn-sm btn-outline-danger del" data-id="${item.id}">
              <i class="ri-delete-bin-line"></i>
            </button>
          </td>
        </tr>
      `).join('');
    }

    function updateSummary() {
      const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
      const totalDiscount = cart.reduce((sum, item) => sum + (item.price * item.quantity * (item.discount/100)), 0);
      const total = subtotal - totalDiscount;

      subtotalDisplay.textContent = `Bs. ${subtotal.toFixed(2)}`;
      discountDisplay.textContent = `- Bs. ${totalDiscount.toFixed(2)}`;
      totalDisplay.textContent = `Bs. ${total.toFixed(2)}`;
      updateChange();
      if (document.getElementById('panelSaldoCredito').style.display !== 'none') {
        document.getElementById('saldoEstaVenta').textContent = 'Bs. ' + total.toFixed(2);
        document.getElementById('saldoTotalDescontar').textContent = 'Bs. ' + (saldoAnteriorActual + total).toFixed(2);
      }
    }

    function updateChange() {
      const recibido = parseFloat(montoRecibidoInput.value) || 0;
      const total = parseFloat(totalDisplay.textContent.replace('Bs. ', '')) || 0;
      const cambio = Math.max(0, recibido - total);
      cambioInput.value = `Bs. ${cambio.toFixed(2)}`;
    }

    function prepareSaleData() {
      const productosData = cart.map(item => ({
        id: item.id,
        quantity: item.quantity,
        price: item.price,
        discount: item.discount
      }));
      productosInput.value = JSON.stringify(productosData);
    }

    // --- MANEJO DE VENTA ---

    finalizeSaleBtn.addEventListener('click', function() {
      const total = parseFloat(totalDisplay.textContent.replace('Bs. ', '')) || 0;
      const clienteId = clienteIdInput.value;

      if (!clienteId) {
        alert('ERROR: Debe buscar y seleccionar un personal UTO antes de finalizar.');
        return;
      }
      if (cart.length === 0) {
        alert('ERROR: El carrito está vacío.');
        return;
      }

      document.getElementById('modalClientName').textContent = selectedPersonal?.nombre || 'Personal UTO';
      document.getElementById('modalTotalAmount').textContent = totalDisplay.textContent;

      document.getElementById('modalProductsList').innerHTML = cart.map(item => {
        const subtotal = item.price * item.quantity * (1 - item.discount / 100);
        return `
          <div class="d-flex align-items-center mb-2 p-2 rounded border">
            <div class="flex-grow-1">
              <div class="fw-semibold" style="font-size: 0.9rem;">${item.name}</div>
              <small class="text-muted">Cantidad: ${item.quantity} und × Bs. ${item.price.toFixed(2)}</small>
            </div>
            <div class="text-end">
              <div class="fw-bold text-success">Bs. ${subtotal.toFixed(2)}</div>
            </div>
          </div>
        `;
      }).join('');

      confirmSaleModalInstance.show();
    });

    confirmSaleFinalBtn.addEventListener('click', function() {
      confirmSaleModalInstance.hide();
      prepareSaleData();
      ventaForm.submit();
    });

    cancelSaleBtn.addEventListener('click', () => {
      if (confirm('¿Cancelar la venta actual?')) {
        cart = [];
        selectedPersonal = null;
        renderCart();
        updateSummary();
        dipSearch.value = '';
        personalInfo.innerHTML = '';
        clienteIdInput.value = '';
        montoRecibidoInput.value = '';
        cambioInput.value = 'Bs. 0.00';
        ocultarPanelSaldo();
      }
    });

    tipoPagoSelect.addEventListener('change', (e) => {
      const isCredito = e.target.value === 'credito';
      montoRecibidoGroup.style.display = isCredito ? 'none' : 'block';
      cambioGroup.style.display = isCredito ? 'none' : 'block';
      if (isCredito) {
        montoRecibidoInput.value = '';
        updateChange();
      }
    });

    // --- LISTENERS PRODUCTOS ---
    document.getElementById('productFilter').addEventListener('input', (e) => {
      currentPage = 1;
      renderProducts(e.target.value, document.getElementById('categoryFilter').value);
    });

    document.getElementById('categoryFilter').addEventListener('change', (e) => {
      currentPage = 1;
      renderProducts(document.getElementById('productFilter').value, e.target.value);
    });

    document.getElementById('itemsPerPage').addEventListener('change', (e) => {
      itemsPerPage = parseInt(e.target.value);
      currentPage = 1;
      renderProducts(document.getElementById('productFilter').value);
    });

    document.getElementById('cartItems').addEventListener('input', (e) => {
      const target = e.target;
      const id = target.dataset.id;
      const item = cart.find(i => i.id == id);
      if (!item) return;

      if (target.classList.contains('qty')) {
        let newQty = parseInt(target.value) || 1;
        newQty = Math.max(1, Math.min(newQty, item.stock));
        item.quantity = newQty;
      } else if (target.classList.contains('discount-input')) {
        let newDiscount = parseFloat(target.value) || 0;
        newDiscount = Math.max(0, Math.min(newDiscount, 100));
        item.discount = newDiscount;
      }
      renderCart();
      updateSummary();
    });

    document.getElementById('cartItems').addEventListener('click', (e) => {
      const target = e.target.closest('[data-id]');
      if (!target) return;
      const id = target.dataset.id;
      const item = cart.find(i => i.id == id);
      if (!item) return;

      if (target.classList.contains('dec')) {
        if (item.quantity > 1) item.quantity--; else cart = cart.filter(i => i.id != id);
      } else if (target.classList.contains('inc')) {
        if (item.quantity < item.stock) item.quantity++;
      } else if (target.classList.contains('del')) {
        cart = cart.filter(i => i.id != id);
      }
      renderCart();
      updateSummary();
    });

    montoRecibidoInput.addEventListener('input', updateChange);

    // Edición inline cliente externo
    document.getElementById('btnGuardarEditExterno').addEventListener('click', function () {
      const nombre   = document.getElementById('editExtNombre').value.trim();
      const dip      = document.getElementById('editExtDip').value.trim();
      const segmento = document.getElementById('editExtSegmento').value;
      const errDiv   = document.getElementById('editExternoError');

      if (!nombre || !dip || !segmento) {
        errDiv.textContent = 'Nombre, DIP y segmento son obligatorios.';
        errDiv.style.display = 'block';
        return;
      }

      fetch('<?= base_url('cliente/updateExterno') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ id: clienteIdInput.value, nombre, dip, segmento }),
      })
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            selectedPersonal.nombre = res.nombre;
            selectedPersonal.dip    = res.dip;
            personalInfo.querySelector('.alert-success').innerHTML =
              `<strong>Seleccionado:</strong> ${res.nombre} — DIP: ${res.dip}
               <span class="badge bg-warning text-dark ms-2">Externo</span>`;
            document.getElementById('clienteExternoEditPanel').style.display = 'none';
          } else {
            errDiv.textContent = res.error;
            errDiv.style.display = 'block';
          }
        })
        .catch(() => {
          errDiv.textContent = 'Error de conexión. Intente nuevamente.';
          errDiv.style.display = 'block';
        });
    });

    document.getElementById('btnCancelarEditExterno').addEventListener('click', function () {
      document.getElementById('clienteExternoEditPanel').style.display = 'none';
    });

    // Inicializar
    renderProducts('', '');
    tipoPagoSelect.dispatchEvent(new Event('change'));
  });
</script>

<!-- MODAL EDITAR ÚLTIMA VENTA -->
<div class="modal fade" id="modalEditarVenta" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="ri-edit-line me-1"></i>Corregir Última Venta</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="editVentaError" class="alert alert-danger d-none"></div>
        <input type="hidden" id="editVentaId">
        <div class="mb-3">
          <label class="form-label fw-bold">Receptor</label>
          <input type="text" class="form-control" id="editReceptorSearch"
                 placeholder="Buscar por CI o nombre (mín. 3 caracteres)" autocomplete="off">
          <div id="editReceptorResults" class="list-group mt-1"
               style="position:absolute;z-index:1050;width:calc(100% - 3rem);max-height:180px;overflow-y:auto;display:none;"></div>
          <input type="hidden" id="editReceptorId" value="">
          <input type="hidden" id="editTipoReceptor" value="">
          <small class="text-muted" id="editReceptorSeleccionado"></small>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">Productos</label>
          <div id="editCarritoContainer"></div>
          <div class="mt-2">
            <select class="form-select form-select-sm" id="editAgregarProducto">
              <option value="">+ Agregar producto...</option>
            </select>
          </div>
        </div>
        <div class="d-flex justify-content-between align-items-center border-top pt-2">
          <span class="fw-bold">Total:</span>
          <strong class="text-success fs-5" id="editTotal">Bs. 0.00</strong>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-warning" id="btnGuardarCorreccion">
          <i class="ri-save-line me-1"></i>Guardar corrección
        </button>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  const ENDPOINT_GET  = '<?= base_url('productosagro/ultimaVenta') ?>';
  const ENDPOINT_POST = '<?= base_url('productosagro/updateUltimaVenta') ?>';
  const ENDPOINT_UTO  = '<?= base_url('productosagro/buscarPersonalUto') ?>';
  const CAMPO_ID      = 'producto_agro_id';

  let uvData = null;
  let editCarrito = [];
  let searchUtoTimeout;

  function cargarUltimaVenta() {
    fetch(ENDPOINT_GET)
      .then(r => r.json())
      .then(data => {
        if (!data.venta) return;
        uvData = data;
        document.getElementById('uvCode').textContent  = data.venta.code;
        document.getElementById('uvMonto').textContent = 'Bs. ' + parseFloat(data.venta.monto_total).toFixed(2);
        document.getElementById('uvCliente').textContent = data.receptor && data.receptor.id
          ? data.receptor.nombre : '(sin receptor)';
        document.getElementById('uvProductos').innerHTML = (data.detalles || []).map(d =>
          `<div class="d-flex justify-content-between">
            <span class="text-truncate me-2">${d.producto}</span>
            <span class="text-nowrap text-muted">${d.cantidad} &times; Bs.${parseFloat(d.precio_unitario).toFixed(2)}</span>
          </div>`
        ).join('');
        document.getElementById('panelUltimaVenta').style.display = 'block';
      })
      .catch(() => {});
  }

  // --- Abrir modal de confirmación de eliminación ---
  document.getElementById('btnEliminarVenta').addEventListener('click', function () {
    if (!uvData) return;
    document.getElementById('elimVentaCode').textContent  = uvData.venta.code;
    document.getElementById('elimVentaMonto').textContent = 'Bs. ' + parseFloat(uvData.venta.monto_total).toFixed(2);
    document.getElementById('elimVentaError').classList.add('d-none');
    const btn = document.getElementById('btnConfirmarEliminar');
    btn.disabled = false;
    btn.innerHTML = '<i class="ri-delete-bin-line me-1"></i>Sí, eliminar';
    new bootstrap.Modal(document.getElementById('modalEliminarVenta')).show();
  });

  // --- Confirmar eliminación ---
  document.getElementById('btnConfirmarEliminar').addEventListener('click', function () {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Eliminando...';
    const params = new URLSearchParams({ venta_id: uvData.venta.id });
    fetch('<?= base_url('productosagro/deleteUltimaVenta') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: params
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('modalEliminarVenta')).hide();
        window.location.reload();
      } else {
        document.getElementById('elimVentaError').textContent = data.error;
        document.getElementById('elimVentaError').classList.remove('d-none');
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-delete-bin-line me-1"></i>Sí, eliminar';
      }
    })
    .catch(() => {
      document.getElementById('elimVentaError').textContent = 'Error de conexión.';
      document.getElementById('elimVentaError').classList.remove('d-none');
      btn.disabled = false;
      btn.innerHTML = '<i class="ri-delete-bin-line me-1"></i>Sí, eliminar';
    });
  });

  document.getElementById('btnCorregirVenta').addEventListener('click', function() {
    if (!uvData) return;
    document.getElementById('editVentaId').value = uvData.venta.id;
    document.getElementById('editVentaError').classList.add('d-none');
    const receptor = uvData.receptor;
    document.getElementById('editReceptorId').value     = receptor && receptor.id ? receptor.id : '';
    document.getElementById('editTipoReceptor').value   = receptor ? receptor.tipo : '';
    document.getElementById('editReceptorSearch').value = receptor && receptor.id ? receptor.nombre : '';
    document.getElementById('editReceptorSeleccionado').textContent = receptor && receptor.id
      ? receptor.nombre + (receptor.tipo === 'externo' ? ' (Externo)' : ' (Personal UTO)') : 'Sin receptor seleccionado';
    editCarrito = uvData.detalles.map(d => ({
      id: d[CAMPO_ID],
      nombre: d.producto,
      cantidad: parseInt(d.cantidad),
      precio_unitario: parseFloat(d.precio_unitario)
    }));
    renderEditCarrito();
    const sel = document.getElementById('editAgregarProducto');
    sel.innerHTML = '<option value="">+ Agregar producto...</option>';
    (uvData.productos_disponibles || []).forEach(p => {
      const opt = document.createElement('option');
      opt.value = p.id;
      opt.dataset.nombre = p.producto;
      opt.dataset.precio = p.precio_contado ?? 0;
      opt.textContent = p.producto + ' (stock: ' + (p.cantidad_inve ?? 0) + ')';
      sel.appendChild(opt);
    });
    document.getElementById('editReceptorResults').style.display = 'none';
    new bootstrap.Modal(document.getElementById('modalEditarVenta')).show();
  });

  document.getElementById('editReceptorSearch').addEventListener('input', function() {
    clearTimeout(searchUtoTimeout);
    const termino = this.value.trim();
    const results = document.getElementById('editReceptorResults');
    if (termino.length < 3) { results.style.display = 'none'; return; }
    searchUtoTimeout = setTimeout(() => {
      fetch(ENDPOINT_UTO + '?dip=' + encodeURIComponent(termino))
        .then(r => r.json())
        .then(data => {
          results.innerHTML = data.length
            ? data.map(p => {
                const esExterno = p.tipo === 'externo';
                const idVal = esExterno ? p.id : p.id_persona;
                const badge = esExterno
                  ? `<span class="badge bg-warning text-dark ms-1">${p.cargo || 'Externo'}</span>`
                  : `<span class="badge bg-info text-dark ms-1">${p.cargo || 'Personal UTO'}</span>`;
                return `<a href="#" class="list-group-item list-group-item-action small py-1"
                    data-id="${idVal}" data-tipo="${p.tipo}" data-nombre="${p.nombre}">
                  ${p.nombre} &mdash; ${p.dip} ${badge}</a>`;
              }).join('')
            : '<a class="list-group-item list-group-item-action text-muted small">Sin resultados</a>';
          results.style.display = 'block';
        }).catch(() => {});
    }, 400);
  });

  document.getElementById('editReceptorResults').addEventListener('click', function(e) {
    e.preventDefault();
    const a = e.target.closest('a[data-id]');
    if (!a) return;
    document.getElementById('editReceptorId').value    = a.dataset.id;
    document.getElementById('editTipoReceptor').value  = a.dataset.tipo;
    document.getElementById('editReceptorSearch').value = a.dataset.nombre;
    document.getElementById('editReceptorSeleccionado').textContent =
      a.dataset.nombre + (a.dataset.tipo === 'externo' ? ' (Externo)' : ' (Personal UTO)');
    this.style.display = 'none';
  });

  document.addEventListener('click', function(e) {
    if (!e.target.closest('#editReceptorSearch') && !e.target.closest('#editReceptorResults')) {
      const r = document.getElementById('editReceptorResults');
      if (r) r.style.display = 'none';
    }
  });

  document.getElementById('editAgregarProducto').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (!opt.value) return;
    const existe = editCarrito.find(i => i.id == opt.value);
    if (existe) { existe.cantidad++; }
    else { editCarrito.push({id: parseInt(opt.value), nombre: opt.dataset.nombre, cantidad: 1, precio_unitario: parseFloat(opt.dataset.precio) || 0}); }
    this.value = '';
    renderEditCarrito();
  });

  function renderEditCarrito() {
    const cont = document.getElementById('editCarritoContainer');
    if (!editCarrito.length) { cont.innerHTML = '<p class="text-muted small">Sin productos.</p>'; document.getElementById('editTotal').textContent = 'Bs. 0.00'; return; }
    let total = 0;
    cont.innerHTML = editCarrito.map((item, i) => {
      const sub = item.cantidad * item.precio_unitario; total += sub;
      return `<div class="d-flex align-items-center gap-2 mb-2 border-bottom pb-2">
        <span class="flex-grow-1 small">${item.nombre}</span>
        <input type="number" min="1" value="${item.cantidad}" class="form-control form-control-sm" style="width:65px"
          onchange="editCantidadCredito(${i}, this.value)">
        <span class="small text-muted" style="width:55px">Bs.${item.precio_unitario.toFixed(2)}</span>
        <span class="small fw-bold" style="width:60px">Bs.${sub.toFixed(2)}</span>
        <button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="editEliminarCredito(${i})">
          <i class="ri-delete-bin-line"></i></button>
      </div>`;
    }).join('');
    document.getElementById('editTotal').textContent = 'Bs. ' + total.toFixed(2);
  }

  window.editCantidadCredito = function(i, val) { editCarrito[i].cantidad = Math.max(1, parseInt(val) || 1); renderEditCarrito(); };
  window.editEliminarCredito = function(i) { editCarrito.splice(i, 1); renderEditCarrito(); };

  document.getElementById('btnGuardarCorreccion').addEventListener('click', function() {
    if (!editCarrito.length) {
      document.getElementById('editVentaError').textContent = 'El carrito no puede estar vacío.';
      document.getElementById('editVentaError').classList.remove('d-none'); return;
    }
    if (!document.getElementById('editReceptorId').value) {
      document.getElementById('editVentaError').textContent = 'Debe seleccionar un receptor.';
      document.getElementById('editVentaError').classList.remove('d-none'); return;
    }
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
    fetch(ENDPOINT_POST, {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: new URLSearchParams({
        venta_id:      document.getElementById('editVentaId').value,
        receptor_id:   document.getElementById('editReceptorId').value,
        tipo_receptor: document.getElementById('editTipoReceptor').value,
        carrito:       JSON.stringify(editCarrito.map(i => ({id: i.id, cantidad: i.cantidad, precio_unitario: i.precio_unitario})))
      })
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('modalEditarVenta')).hide();
        window.open(data.recibo_url, '_blank');
        window.location.reload();
      } else {
        document.getElementById('editVentaError').textContent = data.error;
        document.getElementById('editVentaError').classList.remove('d-none');
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-save-line me-1"></i>Guardar corrección';
      }
    })
    .catch(() => {
      document.getElementById('editVentaError').textContent = 'Error de conexión.';
      document.getElementById('editVentaError').classList.remove('d-none');
      btn.disabled = false;
      btn.innerHTML = '<i class="ri-save-line me-1"></i>Guardar corrección';
    });
  });

  document.addEventListener('DOMContentLoaded', cargarUltimaVenta);
})();
</script>

<?= $this->endSection() ?>