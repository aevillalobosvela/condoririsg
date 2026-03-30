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
      <h2 class="section-title">NUEVA VENTA A CREDITO</h2>

      <!-- Búsqueda por DIP -->
      <div class="mb-4">
        <label for="dipSearch" class="form-label mb-2">Buscar Personal UTO por CI o Nombre:</label>
        <input type="text" class="form-control" id="dipSearch" placeholder="Ingrese CI o Nombre Completo (ej. 745687 o Juan Perez)" autocomplete="off">
        <div id="personalInfo" class="mt-3"></div>
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
   

        <div class="row g-3" id="productsGrid">
          <?php foreach ($productos as $p): ?>
            <?php
              $nombre_upper = strtoupper($p['producto']);
              if (strpos($nombre_upper, 'REQUESON') !== false || strpos($nombre_upper, 'REQUESÓN') !== false) {
                $bg = '#E8F5E9'; $border = '#4CAF50'; $text = '#2E7D32'; $badge = '#388E3C';
              } elseif (strpos($nombre_upper, 'LECHE') !== false) {
                $bg = '#E3F2FD'; $border = '#2196F3'; $text = '#1565C0'; $badge = '#1976D2';
              } elseif (strpos($nombre_upper, 'QUESO') !== false) {
                $bg = '#FFF3E0'; $border = '#FF9800'; $text = '#E65100'; $badge = '#F57C00';
              } elseif (strpos($nombre_upper, 'YOGURT') !== false || strpos($nombre_upper, 'YOGUR') !== false) {
                $bg = '#FCE4EC'; $border = '#E91E63'; $text = '#C2185B'; $badge = '#D81B60';
              } else {
                $bg = '#F5F5F5'; $border = '#9E9E9E'; $text = '#424242'; $badge = '#757575';
              }
            ?>
            <div class="col-6 col-md-4 product-card"
              data-id="<?= $p['id'] ?>"
              data-name="<?= esc($p['producto']) ?>"
              data-price="<?= $p['precio_contado'] ?>"
              data-stock="<?= $p['stock'] ?>"
              data-unidad="<?= esc($p['unidad'] ?? 'und') ?>">
              <div class="card h-100 shadow-sm border-0" style="border-left: 4px solid <?= $border ?> !important;">
                <div class="card-body text-center p-3" style="background: linear-gradient(135deg, <?= $bg ?> 0%, #ffffff 100%);">
                  <?php 
                    $imagenPorNombre = 'assets/images/productos/' . $p['producto'] . '.png';
                    $imagenExiste = file_exists(FCPATH . $imagenPorNombre);
                  ?>
                  <?php if ($imagenExiste): ?>
                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2 overflow-hidden" 
                         style="width: 90px; height: 90px; background-color: <?= $border ?>;">
                      <img src="<?= base_url($imagenPorNombre) ?>" alt="<?= esc($p['producto']) ?>" 
                           style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                  <?php else: ?>
                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" 
                         style="width: 90px; height: 90px; background-color: <?= $border ?>; color: white;">
                      <span class="fw-bold" style="font-size: 0.9rem;"><?= substr(esc($p['producto']), 0, 2) ?></span>
                    </div>
                  <?php endif; ?>
                  <h6 class="card-title mb-2" style="color: <?= $text ?>; font-size: 0.8rem; line-height: 1.2;"><?= esc($p['producto']) ?></h6>
                  <div class="mb-2">
                    <span class="fw-bold" style="color: <?= $badge ?>; font-size: 0.9rem;">Bs. <?= number_format($p['precio_contado'], 2) ?></span>
                  </div>
                  <div class="mb-2">
                    <span class="badge" style="background-color: <?= $border ?>; font-size: 0.65rem;"><?= $p['stock'] ?> <?= esc($p['unidad'] ?? 'und') ?></span>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

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

     
      <form id="ventaForm" method="post" action="<?= base_url('ventas/guardarCreditoVenta') ?>">
        
        <input type="hidden" name="cliente_id" id="clienteIdInput" value="">

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
      <div class="sales-of-day-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h3 class="h5 mb-0 text-success">Ventas del Día</h3>
          <span class="badge bg-light text-success" id="salesCount">0 ventas</span>
        </div>
        <div class="chart-placeholder rounded text-center py-3">
          <i class="ri-bar-chart-2-line display-5 text-success"></i>
        </div>
        <div class="text-center mt-3">
          <a href="<?= base_url('ventas/reportes') ?>" class="text-success text-decoration-none">
            <i class="ri-file-list-line me-1"></i> Ver reporte completo
          </a>
        </div>
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
          <div class="col-6"><strong>Método de Pago:</strong> <span id="modalPaymentType">Crédito</span></div>
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
    let itemsPerPage = 9;
    let currentPage = 1;
    let cart = [];
    let selectedPersonal = null;
    let currentNameFilter = '';
    let currentCategoryFilter = '';

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

    let searchTimeout;

    // --- BÚSQUEDA DE PERSONAL POR DIP ---
    dipSearch.addEventListener('input', function() {
      clearTimeout(searchTimeout);
      const dip = this.value.trim();
      personalInfo.innerHTML = '';
      clienteIdInput.value = '';
      selectedPersonal = null;
      if (dip.length < 3) return;
      searchTimeout = setTimeout(() => {
        fetch(`<?= base_url('ventas/buscarPersonalUto') ?>?dip=${encodeURIComponent(dip)}`)
          .then(r => r.json())
          .then(data => {
            if (data && data.length > 0) {
              const p = data[0];
              selectedPersonal = p;
              clienteIdInput.value = p.id_persona;
              personalInfo.innerHTML = `
                <div class="card border-success">
                  <div class="card-body">
                    <p><strong>Nombre:</strong> ${p.nombre}</p>
                    <p><strong>DIP:</strong> ${p.dip}</p>
                    <p><strong>Teléfono:</strong> ${p.telefono || 'No disponible'}</p>
                    <p><strong>Celular:</strong> ${p.celular || 'No disponible'}</p>
                    <p><strong>Cargo:</strong> ${p.cargo || 'Sin cargo'}</p>
                    <p><strong>Sección:</strong> ${p.seccion || 'Sin sección'}</p>
                  </div>
                </div>`;
            } else {
              personalInfo.innerHTML = '<div class="alert alert-warning">No se encontró personal con ese dato.</div>';
            }
          })
          .catch(() => {
            personalInfo.innerHTML = '<div class="alert alert-danger">Error al buscar. Intente nuevamente.</div>';
          });
      }, 500);
    });

    // --- FUNCIONES DE PRODUCTOS Y CARRITO ---

    function getProductImage(p) {
      return '<?= base_url() ?>/assets/images/productos/' + p.producto + '.png';
    }

    function getProductColors(productName) {
      const name = productName.toUpperCase();
      if (name.includes('REQUESON') || name.includes('REQUESÓN')) {
        return { bg: '#E8F5E9', border: '#4CAF50', text: '#2E7D32', badge: '#388E3C' };
      } else if (name.includes('LECHE')) {
        return { bg: '#E3F2FD', border: '#2196F3', text: '#1565C0', badge: '#1976D2' };
      } else if (name.includes('QUESO')) {
        return { bg: '#FFF3E0', border: '#FF9800', text: '#E65100', badge: '#F57C00' };
      } else if (name.includes('YOGURT') || name.includes('YOGUR')) {
        return { bg: '#FCE4EC', border: '#E91E63', text: '#C2185B', badge: '#D81B60' };
      } else {
        return { bg: '#F5F5F5', border: '#9E9E9E', text: '#424242', badge: '#757575' };
      }
    }

    function renderProducts(nameFilter = '', categoryFilter = '') {
      const filtered = allProducts.filter(p => {
        const matchesName = p.producto.toLowerCase().includes(nameFilter.toLowerCase());
        let matchesCategory = true;
        if (categoryFilter) {
          const nameUpper = p.producto.toUpperCase();
          if (categoryFilter === 'REQUESON') {
            matchesCategory = nameUpper.includes('REQUESON') || nameUpper.includes('REQUESÓN');
          } else if (categoryFilter === 'QUESO') {
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
        return `
        <div class="col-6 col-md-4 product-card" 
             data-id="${p.id}" 
             data-name="${p.producto}" 
             data-price="${p.precio_contado}" 
             data-stock="${p.stock}"
             data-unidad="${p.unidad || 'und'}">
          <div class="card h-100 shadow-sm border-0 cursor-pointer" style="border-left: 4px solid ${colors.border} !important;">
            <div class="card-body text-center p-3" style="background: linear-gradient(135deg, ${colors.bg} 0%, #ffffff 100%);">
              <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2 overflow-hidden" 
                   style="width: 90px; height: 90px; background-color: ${colors.border}; position: relative;">
                <img src="${getProductImage(p)}" 
                     alt="${p.producto}" 
                     style="width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0;"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <span class="fw-bold" style="font-size: 0.9rem; color: white; display: none; width: 100%; height: 100%; align-items: center; justify-content: center;">${p.producto.substring(0,2)}</span>
              </div>
              <h6 class="card-title mb-2" style="color: ${colors.text}; font-size: 0.8rem; line-height: 1.2;">${p.producto}</h6>
              <div class="mb-2">
                <span class="fw-bold" style="color: ${colors.badge}; font-size: 0.9rem;">Bs. ${parseFloat(p.precio_contado).toFixed(2)}</span>
              </div>
              <div class="mb-2">
                <span class="badge" style="background-color: ${colors.border}; font-size: 0.65rem;">${p.stock} ${p.unidad || 'und'}</span>
              </div>
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
      if (product.stock <= 0) return;

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
      currentNameFilter = e.target.value;
      renderProducts(currentNameFilter, currentCategoryFilter);
    });

    document.getElementById('categoryFilter').addEventListener('change', (e) => {
      currentPage = 1;
      currentCategoryFilter = e.target.value;
      renderProducts(currentNameFilter, currentCategoryFilter);
      
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

    // Inicializar
    renderProducts();
    tipoPagoSelect.dispatchEvent(new Event('change'));
    document.getElementById('categoryFilter').dispatchEvent(new Event('change'));
  });
</script>
<?= $this->endSection() ?>