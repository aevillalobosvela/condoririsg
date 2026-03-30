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
        <label for="dipSearch" class="form-label mb-2">Buscar Personal UTO por CI o Nombre:</label>
        <input type="text" class="form-control" id="dipSearch" placeholder="Ingrese CI o nombre (ej. 745687 o Juan Perez)" autocomplete="off">
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

    let searchTimeout;

    dipSearch.addEventListener('input', function() {
      clearTimeout(searchTimeout);
      const dip = this.value.trim();
      personalInfo.innerHTML = '';
      clienteIdInput.value = '';
      selectedPersonal = null;
      if (dip.length < 3) return;
      searchTimeout = setTimeout(() => {
        fetch(`<?= base_url('productosagro/buscarPersonalUto') ?>?dip=${encodeURIComponent(dip)}`)
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

    // Inicializar
    renderProducts('', '');
    tipoPagoSelect.dispatchEvent(new Event('change'));
  });
</script>
<?= $this->endSection() ?>