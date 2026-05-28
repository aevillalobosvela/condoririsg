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

  /* Dropdown de clientes y productos */
  #clientResults {
    position: absolute;
    z-index: 1000;
    background: white;
    border: 1px solid var(--primary-green-border);
    border-top: none;
    max-height: 200px;
    overflow-y: auto;
    width: 100%;
    display: none;
  }
  #clientResults a {
    display: block;
    padding: 8px 12px;
    text-decoration: none;
    color: #212529;
  }
  #clientResults a:hover {
    background-color: var(--primary-green-light);
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

  /* Estilos para el dropdown de categorías con colores */
  #categoryFilter {
    font-weight: 500;
  }
  #categoryFilter option[value="LECHE"] {
    background-color: #E3F2FD;
    color: #1565C0;
  }
  #categoryFilter option[value="QUESO"] {
    background-color: #FFF3E0;
    color: #E65100;
  }
  #categoryFilter option[value="REQUESON"] {
    background-color: #E8F5E9;
    color: #2E7D32;
  }
  #categoryFilter option[value="YOGURT"] {
    background-color: #FCE4EC;
    color: #C2185B;
  }

  /* Animación de feedback al clickear producto */
  @keyframes productClick {
    0% { transform: scale(1); }
    50% { transform: scale(0.95); }
    100% { transform: scale(1); }
  }
  .product-card-clicked {
    animation: productClick 0.3s ease;
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
    <div class="nav-buttons">
      <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="ri-dashboard-line align-bottom me-1"></i> Panel
      </a>
      <a href="<?= base_url('resepciones') ?>" class="btn btn-success btn-sm">
        <i class="ri-truck-line align-bottom me-1"></i> Aceptar envíos
      </a>
      <a href="<?= base_url('ventas/reportes') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="ri-file-chart-line align-bottom me-1"></i> Reportes
      </a>
    </div>
  </div>

  <!-- POS Container -->
  <div class="pos-container">
    <!-- Main Sales Content -->
    <div class="main-sales-content">
      <h2 class="section-title">Nueva Venta</h2>

      <!-- Client & Search -->
      <div class="mb-4 d-flex flex-column flex-md-row gap-3">
        <div class="flex-grow-1 position-relative">
          <label for="clientSearch" class="form-label mb-2">Cliente:</label>
          <div class="input-group">
            <input type="text" class="form-control" id="clientSearch" placeholder="Buscar por nombre o CI..." autocomplete="off">
            <button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#newClientModal" title="Registrar Nuevo Cliente">
              <i class="ri-user-add-line me-1"></i> Registrar Cliente
            </button>
          </div>
         <div id="clientResults" class="list-group"></div>
          <!-- Indicador de cliente seleccionado -->
          <div id="clienteSeleccionadoInfo" class="mt-2"></div>
          <!-- Panel de edición inline -->
          <div id="clientEditPanel" class="border rounded p-3 mt-2 bg-light" style="display:none;">
            <div class="alert alert-info py-2 px-3 mb-2" style="font-size:0.82rem;">
              <i class="ri-information-line me-1"></i>
              Puede editar el último cliente registrado por usted hoy.
            </div>
            <div class="row g-2">
              <div class="col-sm-5">
                <input type="text" class="form-control form-control-sm" id="editNombre" placeholder="Nombre completo">
              </div>
              <div class="col-sm-3">
                <input type="text" class="form-control form-control-sm" id="editCi" placeholder="CI / NIT">
              </div>
              <div class="col-sm-4 d-flex gap-2">
                <button type="button" class="btn btn-sm btn-warning flex-grow-1" id="btnGuardarEdit">
                  <i class="ri-save-line me-1"></i> Guardar
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCancelarEdit">
                  <i class="ri-close-line"></i>
                </button>
              </div>
            </div>
            <div id="editClientError" class="text-danger mt-1" style="font-size:0.8rem; display:none;"></div>
          </div>
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

        <div class="row mb-3 g-2">
          <div class="col-md-6">
            <input type="text" id="productFilter" class="form-control" placeholder="Buscar productos por nombre...">
          </div>
          <div class="col-md-6">
            <select id="categoryFilter" class="form-select">
              <option value="">Todos los productos</option>
              <option value="LECHE" data-color="#2196F3">🔵 Leche</option>
              <option value="QUESO" data-color="#FF9800">🟠 Queso</option>
              <option value="REQUESON" data-color="#4CAF50">🟢 Requesón</option>
              <option value="YOGURT" data-color="#E91E63">🌸 Yogurt</option>
            </select>
          </div>
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
      
      <!-- Formulario de venta -->
      <form id="ventaForm" method="post" action="<?= base_url('ventas/guardarVenta') ?>">
        <!-- Campos ocultos -->
        <input type="hidden" name="cliente_id" id="clienteIdInput">
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
                <option value="contado">Contado</option>
               
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
            
            <!-- CAMBIADO: type="submit" a type="button" para manejar el modal -->
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

      <!-- Panel Última Venta -->
      <div id="panelUltimaVenta" style="display:none" class="sales-of-day-card">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h3 class="h5 mb-0 text-warning"><i class="ri-edit-line me-1"></i>Última Venta</h3>
          <span class="badge bg-warning text-dark" id="uvCode"></span>
        </div>
        <p class="text-muted small mb-2" style="font-size:0.78rem;">
          <i class="ri-information-line me-1"></i>
          ¿Cometió un error en la última venta? Puede corregir los productos o el cliente antes de cerrar el día. Solo aplica a su última venta registrada hoy.
        </p>
        
        <div class="mb-2 pb-2 border-bottom">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="text-muted small">Cliente:</span>
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

<!-- MODAL DE NUEVO CLIENTE -->
<div class="modal fade" id="newClientModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formNuevoCliente" action="<?= base_url('cliente/create') ?>" method="post">
        <div class="modal-header">
          <h5 class="modal-title">Registrar Nuevo Cliente</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="nuevoClienteError" class="alert alert-danger d-none"></div>
          <div class="mb-2">
            <label class="form-label fw-semibold">Apellido Paterno <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="apellido_paterno" required
              style="text-transform:uppercase" placeholder="Ej: MAMANI">
          </div>
          <div class="mb-2">
            <label class="form-label fw-semibold">Apellido Materno</label>
            <input type="text" class="form-control" name="apellido_materno"
              style="text-transform:uppercase" placeholder="Ej: QUISPE">
          </div>
          <div class="mb-2">
            <label class="form-label fw-semibold">Nombre(s) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nombres" required
              style="text-transform:uppercase" placeholder="Ej: JUAN CARLOS">
          </div>
          <div class="mb-2">
            <label class="form-label fw-semibold">CI / NIT <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="ci_nit" required placeholder="Ej: 7456123">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success">Guardar Cliente</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL DE CONFIRMACIÓN DE VENTA -->
<div class="modal fade" id="confirmSaleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-success"><i class="ri-shopping-cart-line me-1"></i> Confirmar Venta</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-3 fw-bold">¿Desea finalizar la transacción con los siguientes detalles?</p>
        
        <div class="mb-3">
          <strong>Cliente:</strong> <span id="modalClientName">N/A</span>
        </div>
        
        <div class="mb-3">
          <strong>Productos:</strong>
          <div id="modalProductsList" class="mt-2"></div>
        </div>
        
        <div class="row mb-3">
          <div class="col-6"><strong>Método de Pago:</strong> <span id="modalPaymentType">Contado</span></div>
          <div class="col-6"><strong>Monto Recibido:</strong> <span id="modalMontoRecibido">Bs. 0.00</span></div>
        </div>
        
        <div class="row mb-3">
          <div class="col-6"><strong>Cambio:</strong> <span class="text-danger fw-bold" id="modalCambio">Bs. 0.00</span></div>
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
    // Inicializar Modals de Bootstrap
    const newClientModal = new bootstrap.Modal(document.getElementById('newClientModal'));
    const confirmSaleModalInstance = new bootstrap.Modal(document.getElementById('confirmSaleModal'));

    // Datos iniciales
    const allProducts = <?= json_encode($productos) ?>;
    const SESSION_USER_ID = <?= (int)session()->get('id') ?>;
    const TODAY = '<?= date('Y-m-d') ?>';
    let itemsPerPage = 9;
    let currentPage = 1;
    let selectedClient = null;
    let cart = [];
    let currentNameFilter = '';
    let currentCategoryFilter = '';

    // Referencias a elementos
    const clientSearch = document.getElementById('clientSearch');
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
    const clienteIdInput = document.getElementById('clienteIdInput');
    const tipoPagoSelect = document.getElementById('tipoPagoSelect');
    const montoRecibidoGroup = document.getElementById('montoRecibidoGroup');
    const cambioGroup = document.getElementById('cambioGroup');

    // --- FUNCIONES DE RENDERIZADO Y LÓGICA ---

    const IMAGE_MAP = {
      'LECHE': 1,
      'QUESO 900 GRAMOS': 2,
      'QUESO SIN SAL 500 GRAMOS': 3,
      'REQUESON 250 GRAMOS': 4,
      'YOGURT 1 LITRO': 5,
      'YOGURT 120 ML': 6,
      'YOGURT GRIEGO 250 GRAMOS': 7,
      'LACTOFRUT 120 ML': 8,
    };
    const BASE_IMAGE_URL = 'https://www.uto.edu.bo/wp-content/uploads/2026/03/';

    // Cache: num -> url resuelta (png, jpg, o null)
    const resolvedImages = {};

    function probeImage(url) {
      return new Promise(resolve => {
        const img = new Image();
        img.onload  = () => resolve(url);
        img.onerror = () => resolve(null);
        img.src = url;
      });
    }

    async function resolveAllImages() {
      const nums = [...new Set(Object.values(IMAGE_MAP))];
      await Promise.all(nums.map(async num => {
        const png = BASE_IMAGE_URL + num + '.png';
        const jpg = BASE_IMAGE_URL + num + '.jpg';
        const result = await probeImage(png) ?? await probeImage(jpg);
        resolvedImages[num] = result; // null si ninguno existe
      }));
    }

    function getProductImage(nombre) {
      const num = IMAGE_MAP[nombre.toUpperCase().trim()];
      if (!num) return null;
      return resolvedImages[num] ?? null; // ya resuelta o null
    }

    // Función para detectar tipo de producto y asignar colores
    function getProductColors(productName) {
      const name = productName.toUpperCase();
      
      // IMPORTANTE: Verificar REQUESÓN antes que QUESO (contiene la palabra QUESO)
      if (name.includes('REQUESON') || name.includes('REQUESÓN')) {
        return { bg: '#E8F5E9', border: '#4CAF50', text: '#2E7D32', badge: '#388E3C' }; // Verde
      } else if (name.includes('LECHE')) {
        return { bg: '#E3F2FD', border: '#2196F3', text: '#1565C0', badge: '#1976D2' }; // Azul
      } else if (name.includes('QUESO')) {
        return { bg: '#FFF3E0', border: '#FF9800', text: '#E65100', badge: '#F57C00' }; // Naranja
      } else if (name.includes('YOGURT') || name.includes('YOGUR')) {
        return { bg: '#FCE4EC', border: '#E91E63', text: '#C2185B', badge: '#D81B60' }; // Rosa
      } else {
        return { bg: '#F5F5F5', border: '#9E9E9E', text: '#424242', badge: '#757575' }; // Gris (otros)
      }
    }

    function renderProducts(nameFilter = '', categoryFilter = '') {
      const filtered = allProducts.filter(p => {
        const matchesName = p.producto.toLowerCase().includes(nameFilter.toLowerCase());
        
        // IMPORTANTE: Verificar REQUESON antes que QUESO para evitar falsos positivos
        let matchesCategory = true;
        if (categoryFilter) {
          const nameUpper = p.producto.toUpperCase();
          if (categoryFilter === 'REQUESON') {
            matchesCategory = nameUpper.includes('REQUESON') || nameUpper.includes('REQUESÓN');
          } else if (categoryFilter === 'QUESO') {
            // Solo QUESO, excluyendo REQUESON
            matchesCategory = nameUpper.includes('QUESO') && !nameUpper.includes('REQUESON') && !nameUpper.includes('REQUESÓN');
          } else {
            matchesCategory = nameUpper.includes(categoryFilter);
          }
        }
        
        return matchesName && matchesCategory;
      });

      const totalPages = Math.ceil(filtered.length / itemsPerPage);
      const start = (currentPage - 1) * itemsPerPage;
      const paginated = filtered.slice(start, start + itemsPerPage);

      productsGrid.innerHTML = paginated.map(p => {
        const colors = getProductColors(p.producto);
        const createdDate = p.created_at ? new Date(p.created_at).toLocaleDateString('es-BO', {day: '2-digit', month: '2-digit', year: 'numeric'}) : '';
        return `
        <div class="col-6 col-md-4 product-card" 
             data-id="${p.id}" 
             data-name="${p.producto}" 
             data-price="${p.precio_contado}" 
             data-stock="${p.stock}"
             data-unidad="${p.unidad || 'und'}"
             data-created="${createdDate}">
          <div class="card h-100 shadow-sm border-0 cursor-pointer" style="border-left: 4px solid ${colors.border} !important;">
            <div class="card-body text-center p-3" style="background: linear-gradient(135deg, ${colors.bg} 0%, #ffffff 100%);">
              <!-- Icono circular o Imagen -->
              ${
                (() => {
                  const imgUrl = getProductImage(p.producto);
                  if (imgUrl) {
                    return `<div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2 overflow-hidden"
                       style="width: 90px; height: 90px; background-color: ${colors.border};">
                      <img src="${imgUrl}" alt="${p.producto}"
                           style="width: 100%; height: 100%; object-fit: cover;">
                    </div>`;
                  } else {
                    return `<div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2"
                       style="width: 90px; height: 90px; background-color: ${colors.border}; color: white;">
                      <span class="fw-bold" style="font-size: 0.9rem;">${p.producto.substring(0,2)}</span>
                    </div>`;
                  }
                })()
              }
              
              <!-- Nombre del producto -->
              <h6 class="card-title mb-2" style="color: ${colors.text}; font-size: 0.82rem; line-height: 1.2;">${p.producto}</h6>
              
              <!-- Precio -->
              <div class="mb-1">
                <span class="fw-bold" style="color: ${colors.badge}; font-size: 0.88rem;">Bs. ${parseFloat(p.precio_contado).toFixed(2)}</span>
              </div>
              
              <!-- Stock -->
              <div class="mb-2">
                <span style="font-size: 1.05rem; font-weight: 700; color: ${colors.badge}; line-height: 1;">${p.stock}</span>
                <span style="font-size: 0.72rem; font-weight: 400; color: ${colors.text};"> ${p.unidad || 'und'}</span>
              </div>
              
              <!-- Fecha de creación -->
              ${createdDate ? `<div class="text-muted" style="font-size: 0.65rem;"><i class="ri-calendar-line"></i> ${createdDate}</div>` : ''}
            </div>
          </div>
        </div>
      `;
      }).join('');

      renderPagination(totalPages);
      
      document.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('click', () => {
          const product = {
            id: card.dataset.id,
            name: card.dataset.name,
            price: parseFloat(card.dataset.price),
            stock: parseInt(card.dataset.stock),
            unidad: card.dataset.unidad
          };
          
          // Animación de feedback
          card.classList.add('product-card-clicked');
          setTimeout(() => card.classList.remove('product-card-clicked'), 300);
          
          addToCart(product);
        });
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
          renderProducts(currentNameFilter, currentCategoryFilter);
        });
      });
    }

    function addToCart(product) {
      if (product.stock <= 0) {
        console.warn('Stock insuficiente para ' + product.name);
        return; 
      }

      const existing = cart.find(item => item.id === product.id);
      if (existing) {
        existing.quantity = Math.min(existing.quantity + 1, product.stock);
      } else {
        cart.push({ ...product, quantity: 1, discount: 0 });
      }
      renderCart();
      updateSummary();
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
    }
    
    function updateChange() {
      const recibido = parseFloat(montoRecibidoInput.value) || 0;
      const total = parseFloat(totalDisplay.textContent.replace('Bs. ', '')) || 0;
      const cambio = Math.max(0, recibido - total);
      cambioInput.value = `Bs. ${cambio.toFixed(2)}`;
    }

    function buscarClientes(termino) {
      const clientResults = document.getElementById('clientResults');
      if (!termino.trim()) {
        clientResults.style.display = 'none';
        return;
      }

      // Los datos de clientes ya están disponibles desde PHP
      const clientes = <?= json_encode($clientes) ?>;
      const terminoLower = termino.toLowerCase();
      const filtered = clientes.filter(c => 
        c.nombre_completo.toLowerCase().includes(terminoLower) || 
        (c.ci_nit && c.ci_nit.toString().includes(termino))
      );

      if (filtered.length === 0) {
        clientResults.innerHTML = `
          <a href="#" class="list-group-item list-group-item-action text-center client-add-new">
            <i class="ri-user-add-line me-1"></i> No se encontró, registrar nuevo
          </a>
        `;
      } else {
        clientResults.innerHTML = filtered.map(c => `
          <a href="#" class="list-group-item list-group-item-action client-item"
             data-id="${c.id}"
             data-name="${c.nombre_completo}"
             data-ci="${c.ci_nit}"
             data-user-id="${c.user_id}"
             data-created-at="${c.created_at || ''}">
            ${c.nombre_completo} - CI: ${c.ci_nit}
          </a>
        `).join('');
      }
      clientResults.style.display = 'block';
    }

    function selectClient(client) {
      selectedClient = client;
      clientSearch.value = client.name;
      clienteIdInput.value = client.id;
      document.getElementById('clientResults').style.display = 'none';

      // Indicador de cliente seleccionado
      const infoEl = document.getElementById('clienteSeleccionadoInfo');
      if (client.id && parseInt(client.id) > 0) {
        infoEl.innerHTML = `
          <div class="alert alert-success py-2 mb-0">
            <i class="ri-user-check-line me-1"></i>
            <strong>Cliente:</strong> ${client.name}${client.ci ? ' — CI: ' + client.ci : ''}
          </div>`;
      } else {
        infoEl.innerHTML = `
          <div class="alert alert-secondary py-2 mb-0">
            <i class="ri-user-line me-1"></i>
            <strong>Consumidor Final</strong> — sin CI registrado
          </div>`;
      }

      // Mostrar panel de edición si el cliente cumple las condiciones
      const panel = document.getElementById('clientEditPanel');
      const clientDate = client.created_at ? client.created_at.substring(0, 10) : '';
      if (parseInt(client.user_id) === SESSION_USER_ID && clientDate === TODAY) {
        document.getElementById('editNombre').value = client.name;
        document.getElementById('editCi').value = client.ci || '';
        document.getElementById('editClientError').style.display = 'none';
        panel.style.display = 'block';
      } else {
        panel.style.display = 'none';
      }
    }
    
    /**
     * Prepara los datos del carrito para ser enviados al controlador PHP.
     * Esta función debe ser llamada justo antes de enviar el formulario.
     */
    function prepareSaleData() {
        const productosData = cart.map(item => ({
            id: item.id, // ID del stock (clave primaria de stock_sucursal)
            quantity: item.quantity,
            price: item.price,
            discount: item.discount
        }));
        productosInput.value = JSON.stringify(productosData);
    }

    // --- MANEJO DE VENTA Y MODALES ---

    // Evento para mostrar el modal de confirmación
    finalizeSaleBtn.addEventListener('click', function(e) {
        const total = parseFloat(totalDisplay.textContent.replace('Bs. ', '')) || 0;
        const recibido = parseFloat(montoRecibidoInput.value) || 0;
        const tipoPago = tipoPagoSelect.value;
        const cambio = parseFloat(cambioInput.value.replace('Bs. ', '')) || 0;

        if (!selectedClient || !clienteIdInput.value) {
            console.error('ERROR: Debe seleccionar un cliente antes de finalizar la venta.');
            alert('ERROR: Debe seleccionar un cliente antes de finalizar la venta.');
            return;
        }
        if (cart.length === 0) {
            console.error('ERROR: El carrito está vacío. Agregue al menos un producto.');
            alert('ERROR: El carrito está vacío. Agregue al menos un producto.');
            return;
        }
        
        // Validación de pago solo si es "contado"
        if (tipoPago === 'contado' && recibido < total) {
             console.error('ERROR: El monto recibido es insuficiente para el pago de contado.');
             alert('ERROR: El monto recibido es insuficiente para el pago de contado.');
             return;
        }

        // Actualizar datos del modal
        document.getElementById('modalClientName').textContent = selectedClient.name;
        document.getElementById('modalTotalAmount').textContent = totalDisplay.textContent;
        document.getElementById('modalPaymentType').textContent = (tipoPago === 'credito' ? 'Crédito' : 'Contado');
        document.getElementById('modalMontoRecibido').textContent = (tipoPago === 'contado' ? `Bs. ${recibido.toFixed(2)}` : 'N/A');
        document.getElementById('modalCambio').textContent = (tipoPago === 'contado' ? `Bs. ${cambio.toFixed(2)}` : 'N/A');
        
        // Renderizar lista de productos con colores
        const productsList = cart.map(item => {
          const colors = getProductColors(item.name);
          const subtotal = item.price * item.quantity * (1 - item.discount/100);
          return `
            <div class="d-flex align-items-center mb-2 p-2 rounded" style="background: linear-gradient(135deg, ${colors.bg} 0%, #ffffff 100%); border-left: 3px solid ${colors.border};">
              <div class="flex-grow-1">
                <div style="color: ${colors.text}; font-weight: 600; font-size: 0.9rem;">${item.name}</div>
                <small class="text-muted">Cantidad: ${item.quantity} ${item.unidad} × Bs. ${item.price.toFixed(2)}</small>
              </div>
              <div class="text-end">
                <div style="color: ${colors.badge}; font-weight: bold;">Bs. ${subtotal.toFixed(2)}</div>
              </div>
            </div>
          `;
        }).join('');
        document.getElementById('modalProductsList').innerHTML = productsList;

        // Mostrar el modal
        confirmSaleModalInstance.show();
    });

    // Evento para la confirmación final dentro del modal
    confirmSaleFinalBtn.addEventListener('click', function() {
        // 1. Ocultar el modal
        confirmSaleModalInstance.hide();
        
        // 2. Preparar los datos del carrito
        prepareSaleData();
        
        // 3. Enviar el formulario
        ventaForm.submit();
    });
    
    // Evento para cancelar la venta
    cancelSaleBtn.addEventListener('click', () => {
        if (window.confirm('¿Está seguro de que desea cancelar la venta actual? Se perderán todos los datos.')) {
            cart = [];
            selectedClient = null;
            renderCart();
            updateSummary();
            clientSearch.value = '';
            document.getElementById('clienteSeleccionadoInfo').innerHTML = '';
            document.getElementById('clientEditPanel').style.display = 'none';
            montoRecibidoInput.value = '';
            cambioInput.value = 'Bs. 0.00';
            clienteIdInput.value = '';
        }
    });
    
    // Ocultar Monto Recibido y Cambio si es a Crédito
    tipoPagoSelect.addEventListener('change', (e) => {
        const isCredito = e.target.value === 'credito';
        montoRecibidoGroup.style.display = isCredito ? 'none' : 'block';
        cambioGroup.style.display = isCredito ? 'none' : 'block';
        if (isCredito) {
            montoRecibidoInput.value = 0; // Resetear monto
            updateChange();
        }
    });


    // --- EVENT LISTENERS GENERALES ---
    
    clientSearch.value = ''; 

    clientSearch.addEventListener('input', () => {
      buscarClientes(clientSearch.value);
    });

    document.getElementById('clientResults').addEventListener('click', (e) => {
      e.preventDefault();
      const target = e.target.closest('a');
      if(!target) return;

      if (target.classList.contains('client-item')) {
        selectClient({
          id: target.dataset.id,
          name: target.dataset.name,
          ci: target.dataset.ci,
          user_id: target.dataset.userId,
          created_at: target.dataset.createdAt,
        });
      } else if (target.classList.contains('client-add-new')) {
        newClientModal.show();
        document.getElementById('clientResults').style.display = 'none';
      }
    });

    document.addEventListener('click', (e) => {
      if (!e.target.closest('.position-relative')) {
        document.getElementById('clientResults').style.display = 'none';
      }
    });

    document.getElementById('productFilter').addEventListener('input', (e) => {
      currentPage = 1;
      currentNameFilter = e.target.value;
      renderProducts(currentNameFilter, currentCategoryFilter);
    });

    document.getElementById('categoryFilter').addEventListener('change', (e) => {
      currentPage = 1;
      currentCategoryFilter = e.target.value;
      renderProducts(currentNameFilter, currentCategoryFilter);
      
      // Cambiar el color del select según la categoría seleccionada
      const select = e.target;
      const colorMap = {
        'LECHE': { bg: '#E3F2FD', border: '#2196F3', text: '#1565C0' },
        'QUESO': { bg: '#FFF3E0', border: '#FF9800', text: '#E65100' },
        'REQUESON': { bg: '#E8F5E9', border: '#4CAF50', text: '#2E7D32' },
        'YOGURT': { bg: '#FCE4EC', border: '#E91E63', text: '#C2185B' }
      };
      
      if (currentCategoryFilter && colorMap[currentCategoryFilter]) {
        const colors = colorMap[currentCategoryFilter];
        select.style.backgroundColor = colors.bg;
        select.style.borderColor = colors.border;
        select.style.color = colors.text;
        select.style.fontWeight = '600';
      } else {
        select.style.backgroundColor = '';
        select.style.borderColor = '';
        select.style.color = '';
        select.style.fontWeight = '500';
      }
    });

    document.getElementById('itemsPerPage').addEventListener('change', (e) => {
      itemsPerPage = parseInt(e.target.value);
      currentPage = 1;
      renderProducts(currentNameFilter, currentCategoryFilter);
    });

    // Carrito interacción (Input change)
    document.getElementById('cartItems').addEventListener('input', (e) => {
        const target = e.target;
        const id = target.dataset.id;
        const item = cart.find(i => i.id == id);
        if(!item) return;

        if (target.classList.contains('qty')) {
            let newQty = parseInt(target.value) || 1;
            newQty = Math.max(1, Math.min(newQty, item.stock)); 
            item.quantity = newQty;
            target.value = newQty; 
        } else if (target.classList.contains('discount-input')) {
            let newDiscount = parseFloat(target.value) || 0;
            newDiscount = Math.max(0, Math.min(newDiscount, 100));
            item.discount = newDiscount;
        }
        renderCart();
        updateSummary();
    });

    // Carrito interacción (Botones)
    document.getElementById('cartItems').addEventListener('click', (e) => {
      const target = e.target.closest('[data-id]');
      if (!target) return;
      
      const id = target.dataset.id;
      const item = cart.find(i => i.id == id);
      if(!item) return;

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

    // Edición inline de cliente
    document.getElementById('btnGuardarEdit').addEventListener('click', function () {
      const nombre = document.getElementById('editNombre').value.trim();
      const ci     = document.getElementById('editCi').value.trim();
      const errDiv = document.getElementById('editClientError');

      if (!nombre || !ci) {
        errDiv.textContent = 'Nombre y CI/NIT son obligatorios.';
        errDiv.style.display = 'block';
        return;
      }

      fetch('<?= base_url('cliente/update') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          id: clienteIdInput.value,
          nombre_completo: nombre,
          ci_nit: ci,
        }),
      })
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            clientSearch.value = res.nombre_completo;
            selectedClient.name = res.nombre_completo;
            selectedClient.ci   = res.ci_nit;
            document.getElementById('clientEditPanel').style.display = 'none';
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

    document.getElementById('btnCancelarEdit').addEventListener('click', function () {
      document.getElementById('clientEditPanel').style.display = 'none';
    });

    montoRecibidoInput.addEventListener('input', updateChange);

    // Inicializar
    resolveAllImages().then(() => {
      renderProducts();
      tipoPagoSelect.dispatchEvent(new Event('change'));
      document.getElementById('categoryFilter').dispatchEvent(new Event('change'));
    });
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

        <!-- Receptor -->
        <div class="mb-3">
          <label class="form-label fw-bold">Cliente</label>
          <div class="position-relative">
            <input type="text" class="form-control" id="editClienteSearch" placeholder="Buscar por nombre o CI..." autocomplete="off">
            <div id="editClienteResults" class="list-group" style="position:absolute;z-index:1050;width:100%;max-height:180px;overflow-y:auto;display:none;"></div>
          </div>
          <input type="hidden" id="editReceptorId" value="0">
          <input type="hidden" id="editTipoReceptor" value="cliente">
          <small class="text-muted" id="editClienteSeleccionado">Consumidor Final</small>
        </div>

        <!-- Carrito editable -->
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
  const ENDPOINT_GET  = '<?= base_url('ventas/ultimaVenta') ?>';
  const ENDPOINT_POST = '<?= base_url('ventas/updateUltimaVenta') ?>';
  const CAMPO_ID      = 'stock_id'; // campo que identifica el producto en detalle

  let uvData = null; // datos cargados del GET
  let editCarrito = []; // [{id, nombre, cantidad, precio_unitario}]

  // --- Cargar última venta al iniciar ---
  function cargarUltimaVenta() {
    fetch(ENDPOINT_GET)
      .then(r => r.json())
      .then(data => {
        if (!data.venta) return;
        uvData = data;
        document.getElementById('uvCode').textContent  = data.venta.code;
        document.getElementById('uvMonto').textContent = 'Bs. ' + parseFloat(data.venta.monto_total).toFixed(2);
        document.getElementById('uvCliente').textContent = data.receptor && data.receptor.id
          ? data.receptor.nombre
          : 'Consumidor Final';
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
    fetch('<?= base_url('ventas/deleteUltimaVenta') ?>', {
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

  // --- Abrir modal y pre-llenar ---
  document.getElementById('btnCorregirVenta').addEventListener('click', function() {
    if (!uvData) return;

    document.getElementById('editVentaId').value = uvData.venta.id;
    document.getElementById('editVentaError').classList.add('d-none');

    // Pre-llenar receptor
    const receptor = uvData.receptor;
    document.getElementById('editReceptorId').value = receptor && receptor.id ? receptor.id : 0;
    document.getElementById('editTipoReceptor').value = 'cliente';
    document.getElementById('editClienteSearch').value = receptor && receptor.id ? receptor.nombre : '';
    document.getElementById('editClienteSeleccionado').textContent = receptor && receptor.id ? receptor.nombre : 'Consumidor Final';

    // Pre-llenar carrito con detalles originales
    editCarrito = uvData.detalles.map(d => ({
      id: d[CAMPO_ID] ?? d.stock_id,
      nombre: d.producto,
      cantidad: parseInt(d.cantidad),
      precio_unitario: parseFloat(d.precio_unitario)
    }));
    renderEditCarrito();

    // Pre-llenar selector de productos disponibles
    const sel = document.getElementById('editAgregarProducto');
    sel.innerHTML = '<option value="">+ Agregar producto...</option>';
    (uvData.productos_disponibles || []).forEach(p => {
      const opt = document.createElement('option');
      opt.value = p.id;
      opt.dataset.nombre = p.producto ?? p.nombre;
      opt.dataset.precio = p.precio_contado ?? p.precio ?? 0;
      opt.textContent = (p.producto ?? p.nombre) + ' (stock: ' + (p.stock ?? p.cantidad_inve ?? p.stock_inve ?? 0) + ')';
      sel.appendChild(opt);
    });

    new bootstrap.Modal(document.getElementById('modalEditarVenta')).show();
  });

  // --- Buscador de clientes en el modal ---
  document.getElementById('editClienteSearch').addEventListener('input', function() {
    const termino = this.value.trim().toLowerCase();
    const results = document.getElementById('editClienteResults');
    if (!termino) {
      document.getElementById('editReceptorId').value = 0;
      document.getElementById('editClienteSeleccionado').textContent = 'Consumidor Final';
      results.style.display = 'none';
      return;
    }
    const clientes = uvData.clientes || [];
    const filtered = clientes.filter(c =>
      c.nombre_completo.toLowerCase().includes(termino) ||
      (c.ci_nit && c.ci_nit.toString().includes(termino))
    ).slice(0, 8);
    results.innerHTML = filtered.length
      ? filtered.map(c =>
          `<a href="#" class="list-group-item list-group-item-action small py-1"
              data-id="${c.id}" data-nombre="${c.nombre_completo}">
            ${c.nombre_completo} <span class="text-muted">&mdash; ${c.ci_nit}</span>
          </a>`
        ).join('')
      : '<a class="list-group-item list-group-item-action text-muted small">Sin resultados</a>';
    results.style.display = 'block';
  });

  document.getElementById('editClienteResults').addEventListener('click', function(e) {
    e.preventDefault();
    const a = e.target.closest('a[data-id]');
    if (!a) return;
    document.getElementById('editReceptorId').value = a.dataset.id;
    document.getElementById('editClienteSearch').value = a.dataset.nombre;
    document.getElementById('editClienteSeleccionado').textContent = a.dataset.nombre;
    this.style.display = 'none';
  });

  document.addEventListener('click', function(e) {
    if (!e.target.closest('#editClienteSearch') && !e.target.closest('#editClienteResults')) {
      const r = document.getElementById('editClienteResults');
      if (r) r.style.display = 'none';
    }
  });

  // --- Agregar producto desde selector ---
  document.getElementById('editAgregarProducto').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (!opt.value) return;
    const existe = editCarrito.find(i => i.id == opt.value);
    if (existe) { existe.cantidad++; }
    else {
      editCarrito.push({
        id: parseInt(opt.value),
        nombre: opt.dataset.nombre,
        cantidad: 1,
        precio_unitario: parseFloat(opt.dataset.precio) || 0
      });
    }
    this.value = '';
    renderEditCarrito();
  });

  // --- Renderizar carrito editable ---
  function renderEditCarrito() {
    const cont = document.getElementById('editCarritoContainer');
    if (!editCarrito.length) {
      cont.innerHTML = '<p class="text-muted small">Sin productos.</p>';
      document.getElementById('editTotal').textContent = 'Bs. 0.00';
      return;
    }
    let total = 0;
    cont.innerHTML = editCarrito.map((item, i) => {
      const sub = item.cantidad * item.precio_unitario;
      total += sub;
      return `<div class="d-flex align-items-center gap-2 mb-2 border-bottom pb-2">
        <span class="flex-grow-1 small">${item.nombre}</span>
        <input type="number" min="1" value="${item.cantidad}" class="form-control form-control-sm" style="width:65px"
          onchange="editCantidad(${i}, this.value)">
        <span class="small text-muted" style="width:55px">Bs.${item.precio_unitario.toFixed(2)}</span>
        <span class="small fw-bold" style="width:60px">Bs.${sub.toFixed(2)}</span>
        <button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="editEliminar(${i})">
          <i class="ri-delete-bin-line"></i>
        </button>
      </div>`;
    }).join('');
    document.getElementById('editTotal').textContent = 'Bs. ' + total.toFixed(2);
  }

  window.editCantidad = function(i, val) {
    editCarrito[i].cantidad = Math.max(1, parseInt(val) || 1);
    renderEditCarrito();
  };
  window.editEliminar = function(i) {
    editCarrito.splice(i, 1);
    renderEditCarrito();
  };

  // --- Guardar corrección ---
  document.getElementById('btnGuardarCorreccion').addEventListener('click', function() {
    if (!editCarrito.length) {
      document.getElementById('editVentaError').textContent = 'El carrito no puede estar vacío.';
      document.getElementById('editVentaError').classList.remove('d-none');
      return;
    }
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

    const carrito = editCarrito.map(i => ({id: i.id, cantidad: i.cantidad, precio_unitario: i.precio_unitario}));
    const params  = new URLSearchParams({
      venta_id:      document.getElementById('editVentaId').value,
      receptor_id:   document.getElementById('editReceptorId').value,
      tipo_receptor: document.getElementById('editTipoReceptor').value,
      carrito:       JSON.stringify(carrito)
    });

    fetch(ENDPOINT_POST, {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: params
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

  // Iniciar al cargar
  document.addEventListener('DOMContentLoaded', cargarUltimaVenta);
})();
</script>

<?= $this->endSection() ?>
