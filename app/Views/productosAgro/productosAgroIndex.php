<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  :root {
    --agro-green:       #16a34a;
    --agro-green-light: #f0fdf4;
    --agro-green-border:#bbf7d0;
    --agro-amber:       #d97706;
    --agro-amber-light: #fffbeb;
  }

  .agro-header-card {
    background: linear-gradient(135deg, var(--agro-green-light) 0%, #ffffff 100%);
    border: 1px solid var(--agro-green-border);
    border-radius: 12px;
  }

  .filter-bar {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px 18px;
  }

  /* Tabla */
  .table-agro thead th {
    background: var(--agro-green-light);
    border-bottom: 2px solid var(--agro-green-border);
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
    cursor: pointer;
    user-select: none;
  }
  .table-agro thead th:hover { background: #dcfce7; }
  .table-agro thead th .sort-icon { font-size: 0.7rem; opacity: 0.5; margin-left: 3px; }
  .table-agro thead th.sorted .sort-icon { opacity: 1; color: var(--agro-green); }

  .table-agro tbody tr { transition: background 0.15s; }
  .table-agro tbody tr:hover { background: #f0fdf4 !important; }

  /* Badge stock */
  .badge-stock-ok   { background: #dcfce7; color: #15803d; border: 1px solid #86efac; font-weight: 600; }
  .badge-stock-low  { background: #fef9c3; color: #854d0e; border: 1px solid #fde047; font-weight: 600; }
  .badge-stock-zero { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; font-weight: 600; }

  /* Dot de color por nombre */
  .prod-dot {
    display: inline-block;
    width: 10px; height: 10px;
    border-radius: 50%;
    margin-right: 6px;
    flex-shrink: 0;
  }

  /* Acciones */
  .btn-accion {
    padding: 4px 10px;
    font-size: 0.78rem;
    border-radius: 6px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    border: 1px solid transparent;
    transition: all 0.15s;
  }
  .btn-accion-edit {
    background: var(--agro-green-light);
    color: var(--agro-green);
    border-color: var(--agro-green-border);
  }
  .btn-accion-edit:hover {
    background: var(--agro-green);
    color: #fff;
  }
  .btn-accion-del {
    background: #fff1f2;
    color: #be123c;
    border-color: #fecdd3;
  }
  .btn-accion-del:hover {
    background: #be123c;
    color: #fff;
  }

  /* Stat cards */
  .stat-mini {
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 0.82rem;
  }

  #noResults { display: none; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Breadcrumb -->
<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <h4 class="mb-sm-0"><?= esc($title) ?></h4>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Inicio</a></li>
          <li class="breadcrumb-item active">Productos Agro</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<!-- Stats rápidas -->
<?php
  $totalProductos  = count($productos);
  $conStock        = count(array_filter($productos, fn($p) => $p->cantidad_inve > 0));
  $sinStock        = $totalProductos - $conStock;
  $stockBajo       = count(array_filter($productos, fn($p) => $p->cantidad_inve > 0 && $p->cantidad_inve <= 5));
?>
<div class="row g-3 mb-3">
  <div class="col-6 col-md-3">
    <div class="stat-mini border" style="background:#f0fdf4; border-color:#bbf7d0 !important;">
      <div class="text-muted" style="font-size:0.72rem;">TOTAL PRODUCTOS</div>
      <div class="fw-bold fs-5" style="color:#16a34a;"><?= $totalProductos ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-mini border" style="background:#f0fdf4; border-color:#bbf7d0 !important;">
      <div class="text-muted" style="font-size:0.72rem;">CON STOCK</div>
      <div class="fw-bold fs-5" style="color:#16a34a;"><?= $conStock ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-mini border" style="background:#fffbeb; border-color:#fde68a !important;">
      <div class="text-muted" style="font-size:0.72rem;">STOCK BAJO (≤5)</div>
      <div class="fw-bold fs-5" style="color:#d97706;"><?= $stockBajo ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-mini border" style="background:#fff1f2; border-color:#fecdd3 !important;">
      <div class="text-muted" style="font-size:0.72rem;">SIN STOCK</div>
      <div class="fw-bold fs-5" style="color:#be123c;"><?= $sinStock ?></div>
    </div>
  </div>
</div>

<!-- Card principal -->
<div class="row">
  <div class="col-12">
    <div class="card agro-header-card">

      <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2" style="background:transparent; border-bottom:1px solid var(--agro-green-border);">
        <h5 class="card-title mb-0" style="color:var(--agro-green);">
          <i class="ri-leaf-line me-1"></i> Listado de Productos Agropecuarios
        </h5>
        <a href="<?= base_url('productosagro/create') ?>" class="btn btn-sm" style="background:var(--agro-green); color:#fff; border-radius:8px;">
          <i class="ri-add-line me-1"></i> Nuevo Producto
        </a>
      </div>

      <div class="card-body">

        <!-- Flash messages -->
        <?php if (session()->getFlashdata('message')): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('message') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <!-- Barra de filtros -->
        <div class="filter-bar mb-3">
          <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
              <label class="form-label mb-1" style="font-size:0.78rem; font-weight:600;">Buscar</label>
              <input type="text" id="filterSearch" class="form-control form-control-sm" placeholder="Nombre, categoría, código...">
            </div>
            <div class="col-6 col-md-2">
              <label class="form-label mb-1" style="font-size:0.78rem; font-weight:600;">Stock</label>
              <select id="filterStock" class="form-select form-select-sm">
                <option value="">Todos</option>
                <option value="ok">Con stock</option>
                <option value="low">Stock bajo (≤5)</option>
                <option value="zero">Sin stock</option>
              </select>
            </div>
            <div class="col-6 col-md-2">
              <label class="form-label mb-1" style="font-size:0.78rem; font-weight:600;">Ordenar por</label>
              <select id="filterSort" class="form-select form-select-sm">
                <option value="fecha_desc">Fecha ↓ (reciente)</option>
                <option value="fecha_asc">Fecha ↑ (antiguo)</option>
                <option value="stock_desc">Stock ↓</option>
                <option value="stock_asc">Stock ↑</option>
                <option value="nombre_asc">Nombre A-Z</option>
                <option value="nombre_desc">Nombre Z-A</option>
              </select>
            </div>
            <div class="col-6 col-md-2">
              <label class="form-label mb-1" style="font-size:0.78rem; font-weight:600;">Categoría</label>
              <select id="filterCategoria" class="form-select form-select-sm">
                <option value="">Todas</option>
                <?php
                  $categorias = array_unique(array_map(fn($p) => $p->categoria, $productos));
                  sort($categorias);
                  foreach ($categorias as $cat): ?>
                  <option value="<?= esc($cat) ?>"><?= esc($cat) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6 col-md-2 d-flex align-items-end">
              <button id="btnLimpiar" class="btn btn-sm btn-outline-secondary w-100">
                <i class="ri-refresh-line me-1"></i> Limpiar
              </button>
            </div>
          </div>
        </div>

        <?php if (empty($productos)): ?>
          <div class="alert alert-info text-center">No hay productos registrados.</div>
        <?php else: ?>

          <div class="table-responsive">
            <table class="table table-agro table-hover align-middle mb-0" id="tablaProductos">
              <thead>
                <tr>
                  <th data-col="producto">Producto <span class="sort-icon">↕</span></th>
                  <th data-col="categoria">Categoría <span class="sort-icon">↕</span></th>
                  <th data-col="precio_contado" class="text-end">P. Contado <span class="sort-icon">↕</span></th>
                  <th data-col="precio_credito" class="text-end">P. Crédito <span class="sort-icon">↕</span></th>
                  <th data-col="cantidad_inve" class="text-center">Stock Inv. <span class="sort-icon">↕</span></th>
                  <th data-col="fecha_creacion" class="text-center">Registrado <span class="sort-icon">↕</span></th>
                  <th class="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody id="tbodyProductos">
                <?php foreach ($productos as $p): ?>
                  <?php
                    $stockInve = (int)$p->cantidad_inve;
                    $stockClass = $stockInve <= 0 ? 'badge-stock-zero' : ($stockInve <= 5 ? 'badge-stock-low' : 'badge-stock-ok');
                    $stockIcon  = $stockInve <= 0 ? 'ri-close-circle-line' : ($stockInve <= 5 ? 'ri-error-warning-line' : 'ri-checkbox-circle-line');
                    $fecha = !empty($p->fecha_creacion) ? date('d/m/Y', strtotime($p->fecha_creacion)) : '—';
                    $fechaSort = !empty($p->fecha_creacion) ? $p->fecha_creacion : '0000-00-00';
                  ?>
                  <tr
                    data-producto="<?= strtolower(esc($p->producto)) ?>"
                    data-categoria="<?= strtolower(esc($p->categoria)) ?>"
                    data-code="<?= strtolower(esc($p->code)) ?>"
                    data-stock="<?= $stockInve ?>"
                    data-fecha="<?= $fechaSort ?>"
                    data-precio-contado="<?= $p->precio_contado ?>"
                    data-precio-credito="<?= $p->precio_credito ?>"
                  >
                    <td>
                      <div class="d-flex align-items-center">
                        <span class="prod-dot" data-nombre="<?= esc($p->producto) ?>"></span>
                        <div>
                          <div class="fw-semibold" style="font-size:0.88rem;"><?= esc($p->producto) ?></div>
                          <div class="text-muted" style="font-size:0.72rem;"><?= esc($p->code) ?><?= !empty($p->descripcion) ? ' · ' . mb_strimwidth(esc($p->descripcion), 0, 40, '…') : '' ?></div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge" style="background:#e0f2fe; color:#0369a1; font-weight:600; font-size:0.72rem;">
                        <?= esc($p->categoria) ?>
                      </span>
                    </td>
                    <td class="text-end fw-semibold" style="color:var(--agro-green); font-size:0.88rem;">
                      Bs. <?= number_format($p->precio_contado, 2) ?>
                    </td>
                    <td class="text-end" style="color:var(--agro-amber); font-size:0.88rem;">
                      Bs. <?= number_format($p->precio_credito, 2) ?>
                    </td>
                    <td class="text-center">
                      <span class="badge <?= $stockClass ?>" style="font-size:0.78rem; padding:4px 8px; border-radius:6px;">
                        <i class="<?= $stockIcon ?> me-1"></i><?= $stockInve ?> und
                      </span>
                    </td>
                    <td class="text-center text-muted" style="font-size:0.78rem; white-space:nowrap;">
                      <i class="ri-calendar-line me-1"></i><?= $fecha ?>
                    </td>
                    <td class="text-center">
                      <div class="d-flex justify-content-center gap-1">
                        <a href="<?= base_url('productosagro/edit/' . $p->id) ?>" class="btn-accion btn-accion-edit">
                          <i class="ri-pencil-line"></i> Editar
                        </a>
                        <a href="<?= base_url('productosagro/create?from=' . $p->id) ?>" class="btn-accion" style="background:#fffbeb; color:#d97706; border-color:#fde68a;" title="Crear un nuevo producto con los mismos datos">
                          <i class="ri-file-copy-line"></i> Duplicar
                        </a>
                        <a href="<?= base_url('productosagro/delete/' . $p->id) ?>"
                           class="btn-accion btn-accion-del"
                           onclick="return confirm('¿Eliminar <?= esc($p->producto) ?>?')">
                          <i class="ri-delete-bin-line"></i> Eliminar
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div id="noResults" class="text-center text-muted py-4">
            <i class="ri-search-line fs-3 d-block mb-2"></i>
            No se encontraron productos con los filtros aplicados.
          </div>

          <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
            <small class="text-muted" id="countLabel">Mostrando <span id="countVisible"><?= $totalProductos ?></span> de <?= $totalProductos ?> productos</small>
          </div>

        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {

  // --- PALETA DE COLORES POR NOMBRE DE PRODUCTO ---
  const PALETTE = [
    '#16a34a','#2563eb','#d97706','#dc2626','#7c3aed',
    '#0891b2','#c2410c','#0f766e','#be185d','#4338ca',
  ];
  const colorMap = {};
  let colorIdx = 0;
  function getColor(nombre) {
    const key = nombre.trim().toUpperCase();
    if (!colorMap[key]) {
      colorMap[key] = PALETTE[colorIdx % PALETTE.length];
      colorIdx++;
    }
    return colorMap[key];
  }

  // Asignar colores a los dots
  document.querySelectorAll('.prod-dot').forEach(dot => {
    dot.style.backgroundColor = getColor(dot.dataset.nombre);
  });

  // --- FILTROS Y ORDENAMIENTO ---
  const rows        = Array.from(document.querySelectorAll('#tbodyProductos tr'));
  const tbody       = document.getElementById('tbodyProductos');
  const noResults   = document.getElementById('noResults');
  const countVisible = document.getElementById('countVisible');

  const filterSearch    = document.getElementById('filterSearch');
  const filterStock     = document.getElementById('filterStock');
  const filterSort      = document.getElementById('filterSort');
  const filterCategoria = document.getElementById('filterCategoria');
  const btnLimpiar      = document.getElementById('btnLimpiar');

  function applyFilters() {
    const search   = filterSearch.value.toLowerCase().trim();
    const stock    = filterStock.value;
    const categoria = filterCategoria.value.toLowerCase();
    const sort     = filterSort.value;

    let visible = rows.filter(row => {
      const matchSearch = !search ||
        row.dataset.producto.includes(search) ||
        row.dataset.categoria.includes(search) ||
        row.dataset.code.includes(search);

      const stockVal = parseInt(row.dataset.stock);
      const matchStock = !stock ||
        (stock === 'ok'   && stockVal > 5) ||
        (stock === 'low'  && stockVal > 0 && stockVal <= 5) ||
        (stock === 'zero' && stockVal <= 0);

      const matchCat = !categoria || row.dataset.categoria === categoria;

      return matchSearch && matchStock && matchCat;
    });

    // Ordenar
    visible.sort((a, b) => {
      switch (sort) {
        case 'fecha_desc':   return b.dataset.fecha.localeCompare(a.dataset.fecha);
        case 'fecha_asc':    return a.dataset.fecha.localeCompare(b.dataset.fecha);
        case 'stock_desc':   return parseInt(b.dataset.stock) - parseInt(a.dataset.stock);
        case 'stock_asc':    return parseInt(a.dataset.stock) - parseInt(b.dataset.stock);
        case 'nombre_asc':   return a.dataset.producto.localeCompare(b.dataset.producto);
        case 'nombre_desc':  return b.dataset.producto.localeCompare(a.dataset.producto);
        default: return 0;
      }
    });

    // Ocultar todas, mostrar las visibles en orden
    rows.forEach(r => r.style.display = 'none');
    visible.forEach(r => { r.style.display = ''; tbody.appendChild(r); });

    noResults.style.display   = visible.length === 0 ? 'block' : 'none';
    countVisible.textContent  = visible.length;
  }

  filterSearch.addEventListener('input', applyFilters);
  filterStock.addEventListener('change', applyFilters);
  filterSort.addEventListener('change', applyFilters);
  filterCategoria.addEventListener('change', applyFilters);

  btnLimpiar.addEventListener('click', () => {
    filterSearch.value = '';
    filterStock.value  = '';
    filterSort.value   = 'fecha_desc';
    filterCategoria.value = '';
    applyFilters();
  });

  // Ordenamiento por click en cabecera
  document.querySelectorAll('.table-agro thead th[data-col]').forEach(th => {
    th.addEventListener('click', () => {
      const col = th.dataset.col;
      const sortMap = {
        producto:       ['nombre_asc', 'nombre_desc'],
        categoria:      ['nombre_asc', 'nombre_desc'],
        cantidad_inve:  ['stock_desc', 'stock_asc'],
        fecha_creacion: ['fecha_desc', 'fecha_asc'],
        precio_contado: ['stock_desc', 'stock_asc'],
        precio_credito: ['stock_desc', 'stock_asc'],
      };
      const opts = sortMap[col];
      if (!opts) return;
      const current = filterSort.value;
      filterSort.value = current === opts[0] ? opts[1] : opts[0];

      document.querySelectorAll('.table-agro thead th').forEach(t => t.classList.remove('sorted'));
      th.classList.add('sorted');
      applyFilters();
    });
  });

  // Aplicar orden inicial
  applyFilters();
});
</script>
<?= $this->endSection() ?>
