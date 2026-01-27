<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  :root {
    --primary: #28a745;
    --primary-dark: #218838;
    --primary-light: #e8f5ec;
    --secondary: #6c757d;
    --success: #28a745;
    --warning: #ffc106;
    --danger: #dc3545;
    --info: #17a2b8;
    --observado: #ff6b6b;
    --light: #f8f9fa;
    --dark: #343a40;
    --border: #e0f0e9;
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
    --shadow: 0 4px 12px rgba(40,167,69,0.1);
    --shadow-lg: 0 10px 25px rgba(40,167,69,0.15);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  /* Layout */
  .dashboard-layout {
    padding: 0 16px;
    max-width: 1400px;
    margin: 0 auto;
  }
  @media (min-width: 768px) {
    .dashboard-layout { padding: 0 24px; }
  }

  /* Page header */
  .page-header {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--border);
  }
  .page-title {
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--dark);
    margin: 0;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  /* KPI Cards */
  .kpi-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
  }

  .kpi-card {
    background: white;
    border-radius: 16px;
    box-shadow: var(--shadow);
    overflow: hidden;
    transition: var(--transition);
    text-decoration: none;
    display: block;
    position: relative;
    cursor: pointer;
  }
  .kpi-card:hover {
    transform: translateY(-6px) scale(1.02);
    box-shadow: var(--shadow-lg);
  }
  .kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: var(--primary);
    transition: var(--transition);
  }
  .kpi-card:hover::before {
    height: 6px;
  }
  .kpi-card.admin::before { background: linear-gradient(90deg, #dc3545 0%, #c82333 100%); }
  .kpi-card.vendedor::before { background: linear-gradient(90deg, #28a745 0%, #218838 100%); }
  .kpi-card.cliente::before { background: linear-gradient(90deg, #17a2b8 0%, #138496 100%); }

  .kpi-body {
    padding: 24px;
    position: relative;
  }
  .kpi-title {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--secondary);
    margin: 0 0 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .kpi-value {
    font-size: 2.25rem;
    font-weight: 800;
    color: var(--dark);
    margin: 0 0 8px;
    font-family: 'Segoe UI', 'Arial', sans-serif;
    line-height: 1;
  }
  .kpi-subtitle {
    font-size: 0.8rem;
    color: var(--secondary);
    margin: 0;
    font-weight: 500;
  }

  .kpi-icon {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 56px;
    height: 56px;
    background: var(--primary-light);
    color: var(--primary);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    transition: var(--transition);
  }
  .kpi-card:hover .kpi-icon {
    transform: rotate(10deg) scale(1.1);
  }
  .kpi-card.admin .kpi-icon { background: #f8d7da; color: #721c24; }
  .kpi-card.vendedor .kpi-icon { background: #d4edda; color: #155724; }
  .kpi-card.cliente .kpi-icon { background: #d1ecf1; color: #0c5460; }

  /* Filters */
  .filters-section {
    background: white;
    border-radius: 16px;
    box-shadow: var(--shadow);
    margin-bottom: 24px;
  }
  .filters-header {
    padding: 18px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border);
    cursor: pointer;
    transition: var(--transition);
  }
  .filters-header:hover {
    background: var(--primary-light);
  }
  .filters-header h3 {
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0;
    color: var(--dark);
  }
  .filters-body {
    padding: 24px;
    display: block;
  }
  .filter-row {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
  }
  @media (min-width: 768px) {
    .filter-row {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  .filter-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
  }
  .btn-filter {
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: var(--transition);
  }
  .btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
  }

  /* Tabs */
  .tabs-container {
    background: white;
    border-radius: 16px;
    box-shadow: var(--shadow);
    overflow: hidden;
    margin-bottom: 24px;
  }
  .nav-tabs-custom {
    display: flex;
    border-bottom: 2px solid var(--border);
    background: var(--light);
    padding: 0;
    margin: 0;
    overflow-x: auto;
    scrollbar-width: thin;
  }
  .nav-tabs-custom::-webkit-scrollbar {
    height: 4px;
  }
  .nav-tabs-custom::-webkit-scrollbar-thumb {
    background: var(--primary);
    border-radius: 4px;
  }
  .nav-tab {
    flex: 1;
    min-width: 140px;
    padding: 16px 20px;
    text-align: center;
    cursor: pointer;
    border: none;
    background: transparent;
    color: var(--secondary);
    font-weight: 600;
    font-size: 0.9rem;
    transition: var(--transition);
    position: relative;
    border-bottom: 3px solid transparent;
  }
  .nav-tab:hover {
    background: rgba(40, 167, 69, 0.05);
    color: var(--primary);
  }
  .nav-tab.active {
    background: white;
    color: var(--primary);
    border-bottom-color: var(--primary);
  }
  .nav-tab .tab-count {
    display: inline-block;
    margin-left: 6px;
    padding: 2px 8px;
    background: var(--primary-light);
    color: var(--primary);
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 700;
  }
  .nav-tab.active .tab-count {
    background: var(--primary);
    color: white;
  }

  .tab-content {
    display: none;
    padding: 0;
  }
  .tab-content.active {
    display: block;
    animation: fadeIn 0.3s ease;
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }

  /* Table */
  .table-header {
    padding: 20px 24px;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    background: var(--light);
  }
  .table-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--dark);
    margin: 0;
  }

  .table-responsive {
    overflow-x: auto;
  }
  .table-envios {
    min-width: 800px;
    margin: 0;
  }
  .table-envios thead th {
    background-color: var(--primary-light);
    color: var(--dark);
    font-weight: 700;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    padding: 14px 12px;
    border: none;
  }
  .table-envios tbody td {
    padding: 14px 12px;
    vertical-align: middle;
    border-bottom: 1px solid #f0f0f0;
  }
  .table-envios tbody tr {
    transition: var(--transition);
  }
  .table-envios tbody tr:hover {
    background-color: var(--primary-light);
    transform: scale(1.005);
  }

  /* Badges */
  .badge-custom {
    font-weight: 600;
    padding: 0.45em 0.9em;
    border-radius: 20px;
    font-size: 0.8rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .badge-admin {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24;
    box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
  }
  .badge-vendedor {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
    box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
  }
  .badge-cliente {
    background: linear-gradient(135deg, #d1ecf1 0%, #b8e9f3 100%);
    color: #0c5460;
    box-shadow: 0 2px 4px rgba(23, 162, 184, 0.2);
  }
  .badge-activo {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
  }
  .badge-inactivo {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24;
  }

  /* Empty state */
  .empty-state {
    text-align: center;
    padding: 60px 20px;
  }
  .empty-icon {
    font-size: 4rem;
    color: var(--secondary);
    margin-bottom: 20px;
    opacity: 0.5;
  }
  .empty-text {
    color: var(--secondary);
    margin: 0;
    font-size: 1.2rem;
    font-weight: 600;
  }

  /* Responsive */
  @media (max-width: 767.98px) {
    .page-title {
      font-size: 1.4rem;
    }
    .kpi-cards {
      grid-template-columns: repeat(2, 1fr);
    }
    .kpi-value {
      font-size: 1.8rem;
    }
    .kpi-icon {
      width: 44px;
      height: 44px;
      font-size: 1.3rem;
    }
    .filter-row {
      grid-template-columns: 1fr !important;
    }
    .nav-tab {
      min-width: 100px;
      padding: 12px 10px;
      font-size: 0.8rem;
    }
  }

  /* Animations */
  @keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .animate-fade-in-up {
    animation: fadeInUp 0.5s ease forwards;
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
// Prepare data for tabs
$usuariosPorRol = [
    'todos' => $usuarios ?? [],
    'administrador' => [],
    'vendedor' => [],
    'cliente' => []
];

foreach ($usuarios ?? [] as $u) {
    $rol = strtolower($u->rol_nombre);
    if (isset($usuariosPorRol[$rol])) {
        $usuariosPorRol[$rol][] = $u;
    }
}
?>

<div class="dashboard-layout">
  <!-- Page Header -->
  <div class="page-header animate-fade-in-up">
    <h1 class="page-title">👥 Gestión de Usuarios</h1>
    <button type="button" class="btn btn-success btn-filter" onclick="nuevoUsuario()">
      <i class="ri-user-add-line"></i> Nuevo Usuario
    </button>
  </div>

  <!-- KPI Cards -->
  <div class="kpi-cards animate-fade-in-up">
    <div class="kpi-card" onclick="showAllTabs()">
      <div class="kpi-body">
        <h3 class="kpi-title">Total Usuarios</h3>
        <h2 class="kpi-value"><?= $totalUsuarios ?? 0 ?></h2>
        <p class="kpi-subtitle">Registrados</p>
      </div>
      <div class="kpi-icon">
        <i class="ri-team-line"></i>
      </div>
    </div>

    <div class="kpi-card admin" onclick="switchTab('administrador')">
      <div class="kpi-body">
        <h3 class="kpi-title">Administradores</h3>
        <h2 class="kpi-value"><?= $totalAdmins ?? 0 ?></h2>
        <p class="kpi-subtitle">Acceso total</p>
      </div>
      <div class="kpi-icon">
        <i class="ri-admin-line"></i>
      </div>
    </div>

    <div class="kpi-card vendedor" onclick="switchTab('vendedor')">
      <div class="kpi-body">
        <h3 class="kpi-title">Vendedores</h3>
        <h2 class="kpi-value"><?= $totalVendedores ?? 0 ?></h2>
        <p class="kpi-subtitle">Ventas y pedidos</p>
      </div>
      <div class="kpi-icon">
        <i class="ri-store-2-line"></i>
      </div>
    </div>

    <div class="kpi-card cliente" onclick="switchTab('cliente')">
      <div class="kpi-body">
        <h3 class="kpi-title">Clientes</h3>
        <h2 class="kpi-value"><?= $totalClientes ?? 0 ?></h2>
        <p class="kpi-subtitle">Compras</p>
      </div>
      <div class="kpi-icon">
        <i class="ri-user-smile-line"></i>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <div class="filters-section animate-fade-in-up">
    <div class="filters-header" id="filtersToggle">
      <h3>🔍 Filtros de Búsqueda</h3>
      <i class="ri-filter-3-line"></i>
    </div>
    <div class="filters-body" id="filtersBody">
      <form action="<?= base_url('usuarios/buscar') ?>" method="get" id="filterForm">
        <div class="filter-row">
          <div style="grid-column: span 2;">
            <label for="q" class="form-label fw-medium">Buscar</label>
            <input type="text" name="q" id="q" class="form-control" placeholder="Nombre, apellido, usuario, correo..." value="<?= isset($terminoBusqueda) ? esc($terminoBusqueda) : '' ?>">
          </div>
          
          <div>
             <!-- Placeholder for future filters if needed -->
          </div>
        </div>

        <div class="filter-actions mt-3">
          <button type="submit" class="btn btn-success btn-filter">
            <i class="ri-search-line"></i> Buscar
          </button>
          <a href="<?= base_url('usuarios') ?>" class="btn btn-outline-secondary btn-filter">
            <i class="ri-refresh-line"></i> Limpiar
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabs Container -->
  <div class="tabs-container animate-fade-in-up">
    <div class="nav-tabs-custom">
      <button class="nav-tab active" data-tab="todos">
        Todos <span class="tab-count"><?= count($usuariosPorRol['todos']) ?></span>
      </button>
      <button class="nav-tab" data-tab="administrador">
        Administradores <span class="tab-count"><?= count($usuariosPorRol['administrador']) ?></span>
      </button>
      <button class="nav-tab" data-tab="vendedor">
        Vendedores <span class="tab-count"><?= count($usuariosPorRol['vendedor']) ?></span>
      </button>
      <button class="nav-tab" data-tab="cliente">
        Clientes <span class="tab-count"><?= count($usuariosPorRol['cliente']) ?></span>
      </button>
    </div>

    <?php foreach ($usuariosPorRol as $tabName => $usuariosTab): ?>
      <div class="tab-content <?= $tabName === 'todos' ? 'active' : '' ?>" id="tab-<?= $tabName ?>">
        <div class="table-responsive">
          <table class="table table-envios table-hover mb-0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nombre Completo</th>
                <th>Usuario</th>
                <th>Correo</th>
                <th>Celular</th>
                <th>Rol</th>
                <th>Sucursal</th>
                <th>Estado</th>
                <th class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($usuariosTab)): ?>
                <?php foreach ($usuariosTab as $u): ?>
                  <tr>
                    <td><strong><?= esc($u->id) ?></strong></td>
                    <td><?= esc($u->nombre) ?> <?= esc($u->apellidos) ?></td>
                    <td><?= esc($u->usuario) ?></td>
                    <td><?= esc($u->correo) ?></td>
                    <td><?= esc($u->celular) ?></td>
                    <td>
                      <?php
                        $rol = strtolower($u->rol_nombre);
                        $badgeClass = match($rol) {
                            'administrador' => 'badge-admin',
                            'vendedor' => 'badge-vendedor',
                            'cliente' => 'badge-cliente',
                            default => 'badge-secondary'
                        };
                        $icon = match($rol) {
                            'administrador' => 'ri-admin-line',
                            'vendedor' => 'ri-store-2-line',
                            'cliente' => 'ri-user-smile-line',
                            default => 'ri-user-line'
                        };
                      ?>
                      <span class="badge-custom <?= $badgeClass ?>">
                        <i class="<?= $icon ?>"></i> <?= esc(ucfirst($u->rol_nombre)) ?>
                      </span>
                    </td>
                    <td><?= esc($u->sucursal_nombre) ?></td>
                    <td>
                      <span class="badge-custom <?= $u->estado ? 'badge-activo' : 'badge-inactivo' ?>">
                        <i class="<?= $u->estado ? 'ri-checkbox-circle-line' : 'ri-close-circle-line' ?>"></i>
                        <?= $u->estado ? 'Activo' : 'Inactivo' ?>
                      </span>
                    </td>
                    <td class="text-end">
                      <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-warning" onclick="editarUsuario(<?= $u->id ?>)" title="Editar">
                            <i class="ri-pencil-fill"></i>
                        </button>
                        <?php if ($u->estado): ?>
                            <button class="btn btn-sm btn-outline-secondary" onclick="cambiarEstado(<?= $u->id ?>, 0)" title="Desactivar">
                                <i class="ri-toggle-fill"></i>
                            </button>
                        <?php else: ?>
                            <button class="btn btn-sm btn-outline-success" onclick="cambiarEstado(<?= $u->id ?>, 1)" title="Activar">
                                <i class="ri-toggle-line"></i>
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarUsuario(<?= $u->id ?>)" title="Eliminar">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="9">
                    <div class="empty-state">
                      <div class="empty-icon">
                        <i class="ri-user-unfollow-line"></i>
                      </div>
                      <h3 class="empty-text">No hay usuarios en esta categoría</h3>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Modal para usuario -->
<div class="modal fade" id="modalUsuario" tabindex="-1" aria-labelledby="modalUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="formUsuario" method="post" action="<?= base_url('usuarios/guardar') ?>" class="modal-content shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalUsuarioLabel">Nuevo Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="usuario_id">

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" id="nombre" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Apellidos <span class="text-danger">*</span></label>
                            <input type="text" name="apellidos" id="apellidos" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Usuario <span class="text-danger">*</span></label>
                            <input type="text" name="usuario" id="usuario" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Correo electrónico <span class="text-danger">*</span></label>
                            <input type="email" name="correo" id="correo" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Contraseña <span class="text-danger" id="password_required">*</span></label>
                            <input type="password" name="password" id="password" class="form-control">
                            <small class="text-muted">Dejar vacío para mantener la contraseña actual (solo en edición)</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Celular</label>
                            <input type="text" name="celular" id="celular" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Dirección</label>
                    <textarea name="direccion" id="direccion" class="form-control" rows="2"></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Rol <span class="text-danger">*</span></label>
                            <select name="rol_id" id="rol_id" class="form-select" required>
                                <option value="">Seleccione un rol...</option>
                                <?php foreach ($roles as $rol): ?>
                                    <option value="<?= $rol->id ?>"><?= esc(ucfirst($rol->nombre)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Sucursal <span class="text-danger">*</span></label>
                            <select name="sucursal_id" id="sucursal_id" class="form-select" required>
                                <option value="">Seleccione una sucursal...</option>
                                <?php foreach ($sucursales as $sucursal): ?>
                                    <option value="<?= $sucursal->id ?>"><?= esc($sucursal->nombre) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" name="estado" id="estado" class="form-check-input" checked>
                    <label class="form-check-label" for="estado">Usuario activo</label>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab switching functionality
        const tabs = document.querySelectorAll('.nav-tab');
        const tabContents = document.querySelectorAll('.tab-content');

        window.switchTab = function(tabName) {
            tabs.forEach(tab => {
                if (tab.dataset.tab === tabName) {
                    tab.click();
                }
            });
        };

        window.showAllTabs = function() {
            switchTab('todos');
        };

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const targetTab = this.dataset.tab;
                
                // Remove active class from all tabs and contents
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding content
                this.classList.add('active');
                document.getElementById('tab-' + targetTab).classList.add('active');
            });
        });

        // Toggle filters on mobile
        const filtersToggle = document.getElementById('filtersToggle');
        const filtersBody = document.getElementById('filtersBody');
        
        if (window.innerWidth < 768) {
            filtersBody.style.display = 'none';
        }
        
        filtersToggle.addEventListener('click', () => {
            const isHidden = filtersBody.style.display === 'none';
            filtersBody.style.display = isHidden ? 'block' : 'none';
        });
    });

    function nuevoUsuario() {
        document.getElementById('formUsuario').reset();
        document.getElementById('usuario_id').value = '';
        document.getElementById('modalUsuarioLabel').textContent = 'Nuevo Usuario';
        document.getElementById('password_required').style.display = 'inline';
        document.getElementById('password').setAttribute('required', 'required');
        new bootstrap.Modal(document.getElementById('modalUsuario')).show();
    }

    function editarUsuario(id) {
        fetch(`<?= base_url('usuarios/obtener/') ?>${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const usuario = data.usuario;
                    document.getElementById('usuario_id').value = usuario.id;
                    document.getElementById('nombre').value = usuario.nombre;
                    document.getElementById('apellidos').value = usuario.apellidos;
                    document.getElementById('usuario').value = usuario.usuario;
                    document.getElementById('correo').value = usuario.correo;
                    document.getElementById('celular').value = usuario.celular || '';
                    document.getElementById('direccion').value = usuario.direccion || '';
                    document.getElementById('rol_id').value = usuario.rol_id;
                    document.getElementById('sucursal_id').value = usuario.sucursal_id;
                    document.getElementById('estado').checked = usuario.estado;
                    
                    document.getElementById('modalUsuarioLabel').textContent = 'Editar Usuario';
                    document.getElementById('password_required').style.display = 'none';
                    document.getElementById('password').removeAttribute('required');
                    
                    new bootstrap.Modal(document.getElementById('modalUsuario')).show();
                } else {
                    alert('Error al cargar los datos del usuario');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar los datos del usuario');
            });
    }

    function cambiarEstado(id, estado) {
        const accion = estado ? 'activar' : 'desactivar';
        const estadoInt = estado ? 1 : 0;
        if (confirm(`¿Seguro que quieres ${accion} este usuario?`)) {
            window.location.href = `<?= base_url('usuarios/cambiar-estado/') ?>${id}/${estadoInt}`;
        }
    }

    function eliminarUsuario(id) {
        if (confirm("¿Seguro que quieres eliminar este usuario? Esta acción no se puede deshacer.")) {
            window.location.href = `<?= base_url('usuarios/eliminar/') ?>${id}`;
        }
    }

    // Manejar envío del formulario con AJAX
    document.getElementById('formUsuario').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const url = this.action;
        
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.error || 'Error al guardar el usuario');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al guardar el usuario');
        });
    });
</script>
<?= $this->endSection() ?>