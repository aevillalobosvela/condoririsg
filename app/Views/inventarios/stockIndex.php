<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  /* ── Variables ─────────────────────────────────────────── */
  :root {
    --green:       #28a745;
    --green-dark:  #1a6335;
    --green-light: #d1fae5;
    --yellow-bg:   #fef3c7;
    --yellow-text: #92400e;
    --red-bg:      #fee2e2;
    --red-text:    #991b1b;
    --border:      #e0f0e9;
    --radius:      12px;
  }

  /* ── Tarjetas de resumen ────────────────────────────────── */
  .stat-card {
    border-radius: var(--radius);
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.07);
  }

  /* ── Filtros de fecha ───────────────────────────────────── */
  .filter-card {
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: #f9fdfb;
  }

  /* ── Botones de filtro rápido por producto ──────────────── */
  .prod-filter-btn {
    font-size: 0.75rem;
    padding: 0.25rem 0.65rem;
    border-radius: 20px;
    border: 1px solid #a3cfbb;
    color: var(--green-dark);
    background: #fff;
    transition: all 0.15s;
    white-space: nowrap;
  }
  .prod-filter-btn:hover,
  .prod-filter-btn.active {
    background: var(--green);
    border-color: var(--green);
    color: #fff;
  }

  /* ── Tabla ──────────────────────────────────────────────── */
  .stock-table th {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #495057;
    background: #f8f9fa;
    padding: 0.75rem 1rem;
    border-bottom: 2px solid var(--border);
  }
  .stock-table td {
    font-size: 0.95rem;
    padding: 0.75rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #f0f0f0;
  }
  .stock-table tbody tr:hover {
    background: #f0fdf4 !important;
  }

  /* Nombre del producto */
  .prod-nombre {
    font-weight: 600;
    font-size: 0.95rem;
    color: #1a1a1a;
  }

  /* Columnas numéricas */
  .num-col {
    font-weight: 700;
    font-size: 1rem;
    text-align: right;
  }

  /* ── Badges de estado ───────────────────────────────────── */
  .badge-disponible {
    background: var(--green-light);
    color: var(--green-dark);
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0.25em 0.6em;
    border-radius: 20px;
    letter-spacing: 0.03em;
  }
  .badge-bajo {
    background: var(--yellow-bg);
    color: var(--yellow-text);
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0.25em 0.6em;
    border-radius: 20px;
    letter-spacing: 0.03em;
  }
  .badge-agotado {
    background: var(--red-bg);
    color: var(--red-text);
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0.25em 0.6em;
    border-radius: 20px;
    letter-spacing: 0.03em;
  }

  /* ── Colores de fila por stock ──────────────────────────── */
  tr.row-disponible td { background: #f0fdf4; }
  tr.row-bajo       td { background: #fffbeb; }
  tr.row-agotado    td { background: #fff5f5; }

  /* ── Barra de búsqueda ──────────────────────────────────── */
  .search-wrap {
    position: relative;
  }
  .search-wrap .ri-search-line {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #adb5bd;
    font-size: 1rem;
    pointer-events: none;
  }
  .search-wrap input {
    padding-left: 2.25rem;
    border-radius: 8px;
    border: 1px solid #ced4da;
    font-size: 0.9rem;
  }
  .search-wrap input:focus {
    border-color: var(--green);
    box-shadow: 0 0 0 0.2rem rgba(40,167,69,0.15);
    outline: none;
  }

  /* ── Sin resultados ─────────────────────────────────────── */
  .no-results-row { display: none; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">

  <!-- Encabezado -->
  <div class="row mb-3">
    <div class="col-12 d-sm-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-0 fw-bold"><?= esc($title) ?></h4>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0" style="font-size:0.82rem;">
            <li class="breadcrumb-item"><a href="<?= base_url('/') ?>" style="color:var(--green)">Inicio</a></li>
            <li class="breadcrumb-item active">Stock Inventario</li>
          </ol>
        </nav>
      </div>
      <a href="<?= base_url('/') ?>" class="btn btn-sm btn-outline-secondary mt-2 mt-sm-0">
        <i class="ri-arrow-left-line me-1"></i> Volver
      </a>
    </div>
  </div>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <i class="ri-checkbox-circle-fill me-2"></i><?= session()->getFlashdata('success') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="ri-error-warning-fill me-2"></i><?= session()->getFlashdata('error') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Tarjetas de resumen -->
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="card stat-card bg-primary text-white">
        <div class="card-body py-3">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <p class="text-white-50 mb-1 fw-medium" style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em;">Total de Lotes</p>
              <h3 class="mb-0 fw-bold"><?= esc($resumenGeneral->total_productos ?? 0) ?></h3>
              <p class="text-white-50 mb-0 mt-1" style="font-size:0.8rem;">Lotes de productos registrados</p>
            </div>
            <i class="ri-box-3-line fs-1 text-white-50"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card stat-card bg-success text-white">
        <div class="card-body py-3">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <p class="text-white-50 mb-1 fw-medium" style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em;">Stock Total Actual</p>
              <h3 class="mb-0 fw-bold"><?= esc($resumenGeneral->total_stock_actual ?? 0) ?> <small class="fs-6 fw-normal">unidades</small></h3>
              <p class="text-white-50 mb-0 mt-1" style="font-size:0.8rem;">Stock físico de todos los productos</p>
            </div>
            <i class="ri-shopping-basket-line fs-1 text-white-50"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filtros de fecha -->
  <div class="card filter-card mb-4">
    <div class="card-body py-3">
      <form method="get" id="filterForm">
        <?php if ($vistaCompleta): ?>
          <input type="hidden" name="vista" value="completa">
        <?php endif; ?>
        <div class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label mb-1" style="font-size:0.8rem; font-weight:600;">Fecha inicio</label>
            <input type="date" class="form-control form-control-sm" name="fecha_inicio"
                   value="<?= esc($filters['fecha_inicio'] ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label mb-1" style="font-size:0.8rem; font-weight:600;">Fecha fin</label>
            <input type="date" class="form-control form-control-sm" name="fecha_fin"
                   value="<?= esc($filters['fecha_fin'] ?? '') ?>">
          </div>
          <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-success flex-fill">
              <i class="ri-filter-3-line me-1"></i> Filtrar
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" onclick="limpiarFiltros()">
              <i class="ri-close-line me-1"></i> Limpiar
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabla principal -->
  <div class="card">
    <div class="card-header border-0 pb-0">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="card-title mb-0">Detalle Agrupado por Producto</h5>
        <div class="d-flex flex-wrap gap-2">
          <!-- Vista completa / solo disponibles -->
          <?php if ($vistaCompleta): ?>
            <a href="<?= base_url('stockinventario') ?>?<?= http_build_query(array_filter(['fecha_inicio' => $filters['fecha_inicio'], 'fecha_fin' => $filters['fecha_fin']])) ?>"
               class="btn btn-sm btn-outline-success">
              <i class="ri-eye-line me-1"></i> Solo disponibles
            </a>
          <?php else: ?>
            <a href="<?= base_url('stockinventario') ?>?vista=completa&<?= http_build_query(array_filter(['fecha_inicio' => $filters['fecha_inicio'], 'fecha_fin' => $filters['fecha_fin']])) ?>"
               class="btn btn-sm btn-outline-secondary">
              <i class="ri-eye-off-line me-1"></i> Vista completa
            </a>
          <?php endif; ?>

          <!-- Dropdown PDF -->
          <div class="dropdown">
            <button class="btn btn-sm btn-danger dropdown-toggle" type="button" data-bs-toggle="dropdown">
              <i class="ri-file-pdf-line me-1"></i> Reporte PDF
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item" href="#" id="btnPdfDisponibles">
                  <i class="ri-checkbox-circle-line me-1 text-success"></i> Solo disponibles (stock &gt; 0)
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="#" id="btnPdfCompleto">
                  <i class="ri-file-list-3-line me-1 text-secondary"></i> Completo (todos los productos)
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <?php if ($vistaCompleta): ?>
        <p class="text-muted mb-0 mt-1" style="font-size:0.8rem;">
          <i class="ri-information-line me-1"></i> Vista completa — se muestran también los productos agotados.
        </p>
      <?php else: ?>
        <p class="text-muted mb-0 mt-1" style="font-size:0.8rem;">
          <i class="ri-information-line me-1"></i> Mostrando solo productos con stock disponible.
          <?php
            // Contar agotados para informar al usuario
            $totalAgotados = 0;
            // No tenemos el array completo aquí, pero podemos mostrar un mensaje genérico
          ?>
          Use "Vista completa" para ver todos.
        </p>
      <?php endif; ?>
    </div>

    <div class="card-body pt-3">

      <!-- Botones de filtro rápido por producto -->
      <div class="d-flex flex-wrap gap-1 mb-3" id="prodFilterBtns">
        <button type="button" class="prod-filter-btn active" data-filter="">
          <i class="ri-apps-line me-1"></i> Todos
        </button>
        <?php foreach ($stockAgrupado as $item): ?>
          <button type="button" class="prod-filter-btn" data-filter="<?= esc(strtolower($item->nombre)) ?>">
            <?= esc($item->nombre) ?>
          </button>
        <?php endforeach; ?>
      </div>

      <!-- Barra de búsqueda rápida -->
      <div class="search-wrap mb-3" style="max-width:380px;">
        <i class="ri-search-line"></i>
        <input type="text" id="searchInput" class="form-control form-control-sm"
               placeholder="Buscar producto...">
      </div>

      <!-- Tabla -->
      <div class="table-responsive">
        <table class="table stock-table mb-0" id="stockTable">
          <thead>
            <tr>
              <th>Producto</th>
              <th class="text-end">Precio</th>
              <th class="text-end">Total lotes</th>
              <th class="text-end">Lotes activos</th>
              <th class="text-end">Stock total</th>
              <th class="text-center">Último lote</th>
              <th class="text-center">Estado</th>
            </tr>
          </thead>
          <tbody id="stockTbody">
            <?php if (!empty($stockAgrupado)): ?>
              <?php foreach ($stockAgrupado as $item): ?>
                <?php
                  $stock        = (int)($item->suma_stock_inve ?? 0);
                  $precio       = number_format((float)($item->precio_contado ?? 0), 2);
                  $lotesActivos = (int)($item->lotes_con_stock ?? 0);
                  $totalLotes   = (int)($item->cantidad_registros ?? 0);
                  $ultimoLote   = $item->ultimo_lote
                                  ? date('d/m/Y', strtotime($item->ultimo_lote))
                                  : '—';

                  if ($stock > 20) {
                      $rowClass   = 'row-disponible';
                      $badgeClass = 'badge-disponible';
                      $badgeText  = 'DISPONIBLE';
                  } elseif ($stock > 0) {
                      $rowClass   = 'row-bajo';
                      $badgeClass = 'badge-bajo';
                      $badgeText  = 'STOCK BAJO';
                  } else {
                      $rowClass   = 'row-agotado';
                      $badgeClass = 'badge-agotado';
                      $badgeText  = 'AGOTADO';
                  }
                ?>
                <tr class="<?= $rowClass ?>" data-nombre="<?= esc(strtolower($item->nombre)) ?>">
                  <td><span class="prod-nombre"><?= esc($item->nombre) ?></span></td>
                  <td class="num-col">Bs <?= $precio ?></td>
                  <td class="num-col"><?= $totalLotes ?></td>
                  <td class="num-col"><?= $lotesActivos ?></td>
                  <td class="num-col"><?= $stock ?></td>
                  <td class="text-center" style="font-size:0.85rem; color:#6c757d;"><?= $ultimoLote ?></td>
                  <td class="text-center"><span class="<?= $badgeClass ?>"><?= $badgeText ?></span></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
            <tr class="no-results-row" id="noResultsRow">
              <td colspan="7" class="text-center text-muted py-4">
                <i class="ri-search-line fs-4 d-block mb-1"></i>
                No se encontraron productos con ese nombre.
              </td>
            </tr>
          </tbody>
        </table>
        <?php if (empty($stockAgrupado)): ?>
          <div class="text-center text-muted py-5">
            <i class="ri-inbox-line fs-1 d-block mb-2"></i>
            <?= $vistaCompleta ? 'No hay productos registrados.' : 'No hay productos con stock disponible.' ?>
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

  // ── URLs para los reportes PDF ──────────────────────────────────────────
  const baseReporte = '<?= base_url('stockinventario/reporteStock') ?>';

  function buildPdfUrl(soloDisponibles) {
    const fi = document.querySelector('[name="fecha_inicio"]')?.value ?? '';
    const ff = document.querySelector('[name="fecha_fin"]')?.value ?? '';
    const params = new URLSearchParams();
    if (fi) params.set('fecha_inicio', fi);
    if (ff) params.set('fecha_fin', ff);
    if (soloDisponibles) params.set('solo_disponibles', '1');
    return baseReporte + (params.toString() ? '?' + params.toString() : '');
  }

  document.getElementById('btnPdfDisponibles').addEventListener('click', function (e) {
    e.preventDefault();
    window.location.href = buildPdfUrl(true);
  });

  document.getElementById('btnPdfCompleto').addEventListener('click', function (e) {
    e.preventDefault();
    window.location.href = buildPdfUrl(false);
  });

  // ── Búsqueda en tiempo real ─────────────────────────────────────────────
  const searchInput  = document.getElementById('searchInput');
  const tbody        = document.getElementById('stockTbody');
  const noResultsRow = document.getElementById('noResultsRow');
  let searchTimer;

  function applySearch(term) {
    const rows    = tbody.querySelectorAll('tr[data-nombre]');
    const lower   = term.toLowerCase().trim();
    let visible   = 0;

    rows.forEach(row => {
      const nombre = row.dataset.nombre ?? '';
      const match  = !lower || nombre.includes(lower);
      row.style.display = match ? '' : 'none';
      if (match) visible++;
    });

    noResultsRow.style.display = (visible === 0 && lower) ? '' : 'none';

    // Sincronizar botón activo
    document.querySelectorAll('.prod-filter-btn').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.filter === lower);
    });
  }

  searchInput.addEventListener('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => applySearch(this.value), 200);
  });

  // ── Botones de filtro rápido ────────────────────────────────────────────
  document.querySelectorAll('.prod-filter-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const filter = this.dataset.filter;
      searchInput.value = filter;
      applySearch(filter);
    });
  });

  // ── Limpiar filtros de fecha ────────────────────────────────────────────
  window.limpiarFiltros = function () {
    document.querySelector('[name="fecha_inicio"]').value = '';
    document.querySelector('[name="fecha_fin"]').value    = '';
    window.location.href = window.location.pathname;
  };

});
</script>
<?= $this->endSection() ?>
