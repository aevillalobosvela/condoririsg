<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  /* Reuse styles from usuarios/devoluciones for consistency */
  :root {
    --primary: #28a745;
    --primary-dark: #218838;
    --primary-light: #e8f5ec;
    --secondary: #6c757d;
    --light: #f8f9fa;
    --dark: #343a40;
    --border: #e0f0e9;
    --shadow: 0 4px 12px rgba(40,167,69,0.1);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .dashboard-layout {
    padding: 0 16px;
    max-width: 1400px;
    margin: 0 auto;
  }
  @media (min-width: 768px) {
    .dashboard-layout { padding: 0 24px; }
  }

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

  .table-card {
    background: white;
    border-radius: 16px;
    box-shadow: var(--shadow);
    overflow: hidden;
    margin-bottom: 24px;
  }

  .table-responsive {
    overflow-x: auto;
  }
  
  .table-custom {
    width: 100%;
    margin: 0;
  }
  .table-custom thead th {
    background-color: var(--primary-light);
    color: var(--dark);
    font-weight: 700;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    padding: 14px 12px;
    border: none;
  }
  .table-custom tbody td {
    padding: 14px 12px;
    vertical-align: middle;
    border-bottom: 1px solid #f0f0f0;
  }
  .table-custom tbody tr:hover {
    background-color: var(--primary-light);
  }

  .badge-custom {
    font-weight: 600;
    padding: 0.45em 0.9em;
    border-radius: 20px;
    font-size: 0.8rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .badge-active {
    background: #d4edda;
    color: #155724;
  }
  .badge-inactive {
    background: #f8d7da;
    color: #721c24;
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
<div class="dashboard-layout">
  <!-- Page Header -->
  <div class="page-header animate-fade-in-up">
    <h1 class="page-title">👥 Gestión de Clientes</h1>
    
    <form action="<?= base_url('cliente/lista') ?>" method="get" class="d-flex gap-2">
      <div class="input-group">
        <span class="input-group-text bg-white border-end-0"><i class="ri-search-line text-muted"></i></span>
        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Buscar por Nombre o CI/NIT..." value="<?= esc($search ?? '') ?>">
        <button type="submit" class="btn btn-primary">Buscar</button>
      </div>
      <?php if (!empty($search)): ?>
        <a href="<?= base_url('cliente/lista') ?>" class="btn btn-outline-secondary" title="Limpiar búsqueda"><i class="ri-close-line"></i></a>
      <?php endif; ?>
    </form>
  </div>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show animate-fade-in-up" role="alert">
      <?= session()->getFlashdata('success') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show animate-fade-in-up" role="alert">
      <?= session()->getFlashdata('error') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Clients Table -->
  <div class="table-card animate-fade-in-up">
    <div class="table-responsive">
      <table class="table table-custom mb-0">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre Completo</th>
            <th>CI / NIT</th>
            <th>Estado</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($clientes)): ?>
            <?php foreach ($clientes as $cliente): ?>
              <tr>
                <td><strong><?= esc($cliente['id']) ?></strong></td>
                <td><?= esc($cliente['nombre_completo']) ?></td>
                <td><?= esc($cliente['ci_nit']) ?></td>
                <td>
                  <span class="badge-custom <?= $cliente['estado'] ? 'badge-active' : 'badge-inactive' ?>">
                    <i class="<?= $cliente['estado'] ? 'ri-checkbox-circle-line' : 'ri-close-circle-line' ?>"></i>
                    <?= $cliente['estado'] ? 'Activo' : 'Inactivo' ?>
                  </span>
                </td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-primary" onclick="editarCliente(<?= $cliente['id'] ?>)" title="Editar">
                    <i class="ri-pencil-fill"></i> Editar
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="text-center py-5">
                <div class="text-muted">
                  <i class="ri-user-unfollow-line fs-1"></i>
                  <p class="mt-2 fw-semibold">No hay clientes registrados.</p>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Editar Cliente -->
<div class="modal fade" id="modalEditarCliente" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="formEditarCliente" class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Editar Cliente</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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
        <button type="submit" class="btn btn-success">
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
  const formEditar = document.getElementById('formEditarCliente');

  function editarCliente(id) {
    fetch(`<?= base_url('cliente/get/') ?>${id}`)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const cliente = data.cliente;
          document.getElementById('cliente_id').value = cliente.id;
          document.getElementById('ci_nit').value = cliente.ci_nit;

          // Descomponer nombre_completo en los 3 campos
          const partes = (cliente.nombre_completo || '').trim().split(/\s+/);
          document.getElementById('edit_apellido_paterno').value = partes[0] || '';
          document.getElementById('edit_apellido_materno').value = partes.length >= 3 ? partes[1] : '';
          document.getElementById('edit_nombres').value = partes.length >= 3
            ? partes.slice(2).join(' ')
            : (partes[1] || '');

          modalEditar.show();
        } else {
          alert('Error al cargar los datos del cliente: ' + (data.error || 'Desconocido'));
        }
      })
      .catch(err => {
        console.error(err);
        alert('Error de conexión al cargar el cliente. Verifique su conexión o contacte al administrador.');
      });
  }

  formEditar.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch(`<?= base_url('cliente/update') ?>`, {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert(data.message);
        window.location.reload();
      } else {
        alert('Error al actualizar: ' + (data.error || 'Desconocido'));
      }
    })
    .catch(err => {
      console.error(err);
      alert('Error de conexión al actualizar el cliente.');
    });
  });
</script>
<?= $this->endSection() ?>
