<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  :root { --primary:#28a745; --primary-dark:#218838; --primary-light:#e8f5ec; --border:#e0f0e9; --shadow:0 4px 12px rgba(40,167,69,.1); }
  .dashboard-layout { padding:0 16px; max-width:1400px; margin:0 auto; }
  @media(min-width:768px){ .dashboard-layout{ padding:0 24px; } }
  .page-header { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:16px; margin-bottom:20px; padding-bottom:16px; border-bottom:2px solid var(--border); }
  .page-title { font-size:1.6rem; font-weight:800; margin:0; background:linear-gradient(135deg,var(--primary),var(--primary-dark)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
  .table-card { background:#fff; border-radius:16px; box-shadow:var(--shadow); overflow:hidden; margin-bottom:20px; }
  .table-custom { width:100%; margin:0; }
  .table-custom thead th { background:var(--primary-light); color:#343a40; font-weight:700; font-size:.82rem; text-transform:uppercase; letter-spacing:.3px; padding:12px; border:none; white-space:nowrap; }
  .table-custom thead th.sortable { cursor:pointer; user-select:none; }
  .table-custom thead th.sortable:hover { background:#d4edda; }
  .sort-icon { margin-left:4px; opacity:.4; font-size:.75rem; }
  .sort-icon.active { opacity:1; color:var(--primary-dark); }
  .table-custom tbody td { padding:11px 12px; vertical-align:middle; border-bottom:1px solid #f0f0f0; font-size:.9rem; }
  .table-custom tbody tr:hover { background:var(--primary-light); }
  .badge-active { background:#d4edda; color:#155724; padding:.3em .75em; border-radius:20px; font-size:.78rem; font-weight:600; }
  .badge-inactive { background:#f8d7da; color:#721c24; padding:.3em .75em; border-radius:20px; font-size:.78rem; font-weight:600; }
  .stat-chip { background:#fff; border:1px solid var(--border); border-radius:20px; padding:5px 12px; font-size:.82rem; font-weight:600; color:#495057; }
  .stat-chip span { color:var(--primary); }
  #inputBusqueda:focus { box-shadow:0 0 0 .2rem rgba(40,167,69,.25); border-color:var(--primary); }
  .text-muted-sm { font-size:.78rem; color:#6c757d; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
  $sortBy  = $sortBy  ?? 'nombre_completo';
  $sortDir = $sortDir ?? 'ASC';
  $search  = $search  ?? '';
  $estado  = $estado  ?? '';
  $page    = $page    ?? 1;

  function sortUrl(string $col, string $currentCol, string $currentDir, string $search, string $estado): string {
    $dir = ($col === $currentCol && $currentDir === 'ASC') ? 'DESC' : 'ASC';
    return base_url('cliente/lista') . '?sort_by=' . $col . '&sort_dir=' . $dir
         . '&search=' . urlencode($search) . '&estado=' . urlencode($estado) . '&page=1';
  }
  function sortIcon(string $col, string $currentCol, string $currentDir): string {
    if ($col !== $currentCol) return '<i class="ri-expand-up-down-line sort-icon"></i>';
    $icon = $currentDir === 'ASC' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line';
    return '<i class="' . $icon . ' sort-icon active"></i>';
  }
?>
<div class="dashboard-layout">

  <div class="page-header">
    <h1 class="page-title">👥 Gestión de Clientes</h1>
    <div class="d-flex gap-2 flex-wrap align-items-center">
      <span class="stat-chip">Total: <span><?= number_format($total) ?></span></span>
      <span class="stat-chip">Pág. <span><?= $page ?></span> / <span><?= $totalPages ?: 1 ?></span></span>
    </div>
  </div>

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

  <!-- Filtros -->
  <form id="formFiltros" action="<?= base_url('cliente/lista') ?>" method="get">
    <input type="hidden" name="sort_by" value="<?= $sortBy ?>">
    <input type="hidden" name="sort_dir" value="<?= $sortDir ?>">
    <input type="hidden" name="page" value="1">
    <div class="row g-2 mb-3 align-items-center">
      <div class="col-12 col-md-6">
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0"><i class="ri-search-line text-muted"></i></span>
          <input type="text" id="inputBusqueda" name="search" class="form-control border-start-0 ps-0"
            placeholder="Buscar por nombre o CI/NIT..." value="<?= esc($search) ?>" autocomplete="off">
          <?php if (!empty($search)): ?>
            <a href="<?= base_url('cliente/lista') ?>?sort_by=<?= $sortBy ?>&sort_dir=<?= $sortDir ?>" class="btn btn-outline-secondary" title="Limpiar">
              <i class="ri-close-line"></i>
            </a>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <select name="estado" id="selectEstado" class="form-select">
          <option value=""       <?= $estado === ''        ? 'selected' : '' ?>>Todos</option>
          <option value="activo" <?= $estado === 'activo'  ? 'selected' : '' ?>>Activos</option>
          <option value="inactivo" <?= $estado === 'inactivo' ? 'selected' : '' ?>>Inactivos</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i>Buscar</button>
      </div>
      <?php if (!empty($search) || !empty($estado)): ?>
      <div class="col-12 col-md-2">
        <a href="<?= base_url('cliente/lista') ?>" class="btn btn-outline-secondary w-100">
          <i class="ri-refresh-line me-1"></i>Limpiar
        </a>
      </div>
      <?php endif; ?>
    </div>
  </form>

  <!-- Tabla -->
  <div class="table-card">
    <div class="table-responsive">
      <table class="table table-custom mb-0">
        <thead>
          <tr>
            <th class="sortable" onclick="location.href='<?= sortUrl('id', $sortBy, $sortDir, $search, $estado) ?>'">
              ID <?= sortIcon('id', $sortBy, $sortDir) ?>
            </th>
            <th class="sortable" onclick="location.href='<?= sortUrl('nombre_completo', $sortBy, $sortDir, $search, $estado) ?>'">
              Nombre Completo <?= sortIcon('nombre_completo', $sortBy, $sortDir) ?>
            </th>
            <th class="sortable" onclick="location.href='<?= sortUrl('ci_nit', $sortBy, $sortDir, $search, $estado) ?>'">
              CI / NIT <?= sortIcon('ci_nit', $sortBy, $sortDir) ?>
            </th>
            <th class="sortable" onclick="location.href='<?= sortUrl('estado', $sortBy, $sortDir, $search, $estado) ?>'">
              Estado <?= sortIcon('estado', $sortBy, $sortDir) ?>
            </th>
            <th class="sortable" onclick="location.href='<?= sortUrl('created_at', $sortBy, $sortDir, $search, $estado) ?>'">
              Registro <?= sortIcon('created_at', $sortBy, $sortDir) ?>
            </th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($clientes)): ?>
            <?php foreach ($clientes as $cliente): ?>
              <tr>
                <td><strong><?= esc($cliente['id']) ?></strong></td>
                <td><?= esc($cliente['nombre_completo']) ?></td>
                <td><code><?= esc($cliente['ci_nit']) ?></code></td>
                <td>
                  <span class="<?= $cliente['estado'] ? 'badge-active' : 'badge-inactive' ?>">
                    <?= $cliente['estado'] ? 'Activo' : 'Inactivo' ?>
                  </span>
                </td>
                <td class="text-muted-sm">
                  <?= !empty($cliente['created_at']) ? date('d/m/Y', strtotime($cliente['created_at'])) : '—' ?>
                </td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-primary" onclick="editarCliente(<?= $cliente['id'] ?>)">
                    <i class="ri-pencil-fill"></i> Editar
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="ri-user-unfollow-line fs-1"></i>
                <p class="mt-2 fw-semibold">No se encontraron clientes.</p>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Paginación -->
  <?php if ($totalPages > 1): ?>
    <nav>
      <ul class="pagination justify-content-center flex-wrap gap-1">
        <?php if ($page > 1): ?>
          <li class="page-item">
            <a class="page-link" href="<?= base_url('cliente/lista') ?>?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&estado=<?= urlencode($estado) ?>&sort_by=<?= $sortBy ?>&sort_dir=<?= $sortDir ?>">
              <i class="ri-arrow-left-s-line"></i>
            </a>
          </li>
        <?php endif; ?>
        <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
          <li class="page-item <?= $i===$page?'active':'' ?>">
            <a class="page-link" href="<?= base_url('cliente/lista') ?>?page=<?= $i ?>&search=<?= urlencode($search) ?>&estado=<?= urlencode($estado) ?>&sort_by=<?= $sortBy ?>&sort_dir=<?= $sortDir ?>">
              <?= $i ?>
            </a>
          </li>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
          <li class="page-item">
            <a class="page-link" href="<?= base_url('cliente/lista') ?>?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&estado=<?= urlencode($estado) ?>&sort_by=<?= $sortBy ?>&sort_dir=<?= $sortDir ?>">
              <i class="ri-arrow-right-s-line"></i>
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </nav>
  <?php endif; ?>

</div>

<!-- Modal Editar Cliente -->
<div class="modal fade" id="modalEditarCliente" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="formEditarCliente" class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="ri-pencil-fill me-1"></i> Editar Cliente</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="cliente_id">
        <div id="editarClienteError" class="alert alert-danger d-none"></div>
        <div class="mb-2">
          <label class="form-label fw-semibold">Apellido Paterno <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="edit_apellido_paterno" name="apellido_paterno" required
            style="text-transform:uppercase" placeholder="Ej: MAMANI">
        </div>
        <div class="mb-2">
          <label class="form-label fw-semibold">Apellido Materno</label>
          <input type="text" class="form-control" id="edit_apellido_materno" name="apellido_materno"
            style="text-transform:uppercase" placeholder="Ej: QUISPE">
        </div>
        <div class="mb-2">
          <label class="form-label fw-semibold">Nombre(s) <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="edit_nombres" name="nombres" required
            style="text-transform:uppercase" placeholder="Ej: JUAN CARLOS">
        </div>
        <div class="mb-2">
          <label class="form-label fw-semibold">CI / NIT <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="ci_nit" name="ci_nit" required>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-success" id="btnGuardarEdicion">
          <i class="ri-save-line"></i> Guardar Cambios
        </button>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  const modalEditar = new bootstrap.Modal(document.getElementById('modalEditarCliente'));
  const formEditar  = document.getElementById('formEditarCliente');

  function editarCliente(id) {
    fetch(`<?= base_url('cliente/get/') ?>${id}`)
      .then(r => r.json())
      .then(data => {
        if (!data.success) { alert('Error al cargar el cliente.'); return; }
        const c = data.cliente;
        document.getElementById('cliente_id').value = c.id;
        document.getElementById('ci_nit').value      = c.ci_nit;
        document.getElementById('editarClienteError').classList.add('d-none');

        const partes = (c.nombre_completo || '').trim().split(/\s+/);
        document.getElementById('edit_apellido_paterno').value = partes[0] || '';
        document.getElementById('edit_apellido_materno').value = partes.length >= 3 ? partes[1] : '';
        document.getElementById('edit_nombres').value = partes.length >= 3
          ? partes.slice(2).join(' ') : (partes[1] || '');

        modalEditar.show();
      })
      .catch(() => alert('Error de conexión.'));
  }

  formEditar.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn    = document.getElementById('btnGuardarEdicion');
    const errDiv = document.getElementById('editarClienteError');
    btn.disabled = true;
    errDiv.classList.add('d-none');

    fetch(`<?= base_url('cliente/updateAdmin') ?>`, { method:'POST', body: new FormData(this) })
      .then(r => {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      })
      .then(data => {
        if (data.success) { modalEditar.hide(); location.reload(); }
        else {
          errDiv.textContent = data.error;
          errDiv.classList.remove('d-none');
          btn.disabled = false;
        }
      })
      .catch(err => {
        errDiv.textContent = 'Error al guardar: ' + err.message;
        errDiv.classList.remove('d-none');
        btn.disabled = false;
      });
  });
</script>
<?= $this->endSection() ?>
