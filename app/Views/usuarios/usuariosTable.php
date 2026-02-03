<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  /* Colores personalizados para roles y estados */
  .bg-soft-primary {
    background-color: #d4edda !important;
    color: #28a745 !important;
  }
  .bg-soft-admin {
    background-color: #e6f4ea !important;
    color: #1e7e34 !important;
  }
  .bg-soft-vendedor {
    background-color: #f0f9f2 !important;
    color: #28a745 !important;
  }
  .bg-soft-cliente {
    background-color: #f8fdfa !important;
    color: #28a745 !important;
  }
  /* Estado Activo (Verde) */
  .bg-soft-activo {
    background-color: #d4edda !important;
    color: #155724 !important;
  }
  /* Estado Inactivo (Gris/Suave) */
  .bg-soft-inactivo {
    background-color: #f1f3f5 !important;
    color: #6c757d !important;
  }

  /* Ajuste íconos en tarjetas KPI */
  .card-animate .avatar-title {
    background-color: #f8fdfa !important;
    border: 1px solid #e0f0e9;
  }
  .card-animate .avatar-title i {
    color: #28a745 !important;
  }

  /* Badge de rol */
  .badge-role {
    font-weight: 600;
    padding: 0.35em 0.6em;
    border-radius: 6px;
  }

  /* Badge de estado */
  .badge-status {
    font-weight: 600;
    padding: 0.35em 0.6em;
    border-radius: 6px;
  }

  /* Mejoras de responsividad para botones de acción en encabezado */
  @media (max-width: 575.98px) {
    .card-header .flex-shrink-0 {
        display: flex;
        flex-direction: column; 
        width: 100%;
        margin-top: 10px;
    }
    .card-header .flex-shrink-0 a.btn {
        width: 100%; 
        margin-bottom: 8px !important;
        margin-right: 0 !important;
    }
    .table-responsive {
      overflow-x: auto;
    }
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-sm-flex align-items-center justify-content-between">
        <h4 class="mb-sm-0">Gestión de Usuarios</h4>
        <div class="page-title-right">
          <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="javascript: void(0);">Usuarios</a></li>
            <li class="breadcrumb-item active">Listado</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-xl-3 col-md-6">
      <div class="card card-animate">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1 overflow-hidden">
              <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total de Usuarios</p>
            </div>
          </div>
          <div class="d-flex align-items-end justify-content-between mt-4">
            <div>
              <h4 class="fs-22 fw-semibold ff-secondary mb-4"><?= $totalUsuarios ?? '0' ?></h4>
              <a href="<?= base_url('usuarios?estado=') ?>" class="text-decoration-underline">Ver todos los usuarios</a>
            </div>
            <div class="avatar-sm flex-shrink-0">
              <span class="avatar-title bg-soft-primary rounded fs-3">
                <i class="ri-team-fill"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6">
      <div class="card card-animate">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1 overflow-hidden">
              <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Administradores</p>
            </div>
          </div>
          <div class="d-flex align-items-end justify-content-between mt-4">
            <div>
              <h4 class="fs-22 fw-semibold ff-secondary mb-4"><?= $totalAdmins ?? '0' ?></h4>
              <a href="<?= base_url('usuarios?rol=admin') ?>" class="text-decoration-underline">Ver administradores</a>
            </div>
            <div class="avatar-sm flex-shrink-0">
              <span class="avatar-title bg-soft-admin rounded fs-3">
                <i class="ri-user-settings-line"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6">
      <div class="card card-animate">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1 overflow-hidden">
              <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Vendedores</p>
            </div>
          </div>
          <div class="d-flex align-items-end justify-content-between mt-4">
            <div>
              <h4 class="fs-22 fw-semibold ff-secondary mb-4"><?= $totalVendedores ?? '0' ?></h4>
              <a href="<?= base_url('usuarios?rol=vendedor') ?>" class="text-decoration-underline">Ver vendedores</a>
            </div>
            <div class="avatar-sm flex-shrink-0">
              <span class="avatar-title bg-soft-vendedor rounded fs-3">
                <i class="ri-store-2-line"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6">
      <div class="card card-animate">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1 overflow-hidden">
              <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Clientes</p>
            </div>
          </div>
          <div class="d-flex align-items-end justify-content-between mt-4">
            <div>
              <h4 class="fs-22 fw-semibold ff-secondary mb-4"><?= $totalClientes ?? '0' ?></h4>
              <a href="<?= base_url('cliente/lista') ?>" class="text-decoration-underline">Ver clientes</a>
            </div>
            <div class="avatar-sm flex-shrink-0">
              <span class="avatar-title bg-soft-cliente rounded fs-3">
                <i class="ri-shopping-cart-2-line"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?= session()->getFlashdata('success') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?= session()->getFlashdata('error') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="row">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-header border-0">
          <div class="d-flex align-items-center">
            <h5 class="card-title mb-0 flex-grow-1">Lista de Usuarios</h5>
            <div class="flex-shrink-0">
              <a href="<?= base_url('usuarios/register') ?>" class="btn btn-success add-btn">
                <i class="ri-add-line align-bottom me-1"></i> Añadir Nuevo Usuario
              </a>
            </div>
          </div>
        </div>

        <div class="card-body border-dashed border-end-0 border-start-0">
          <form onsubmit="event.preventDefault(); aplicarFiltros(true);">
            <div class="row g-3">
              <div class="col-xxl-5 col-sm-6">
                <div class="search-box">
                  <input type="text" class="form-control search" id="buscadorUsuarios" placeholder="Buscar por nombre, correo o rol..." value="<?= esc($filters['search'] ?? '') ?>">
                  <i class="ri-search-line search-icon"></i>
                </div>
              </div>
              <div class="col-xxl-2 col-sm-4">
                <select class="form-control" id="filtroRol">
                  <option value="">Todos los roles</option>
                  <option value="admin" <?= (isset($filters['rol']) && $filters['rol'] === 'administrador') ? 'selected' : '' ?>>Administrador</option>
                  <option value="vendedor" <?= (isset($filters['rol']) && $filters['rol'] === 'vendedor') ? 'selected' : '' ?>>Vendedor</option>
                  <option value="almacen" <?= (isset($filters['rol']) && $filters['rol'] === 'almacen') ? 'selected' : '' ?>>Almacén</option>
                  <option value="contabilidad" <?= (isset($filters['rol']) && $filters['rol'] === 'contabilidad') ? 'selected' : '' ?>>Contabilidad</option>
                  <option value="agropecuario" <?= (isset($filters['rol']) && $filters['rol'] === 'agropecuario') ? 'selected' : '' ?>>Agropecuario</option>
                  <option value="ganaderia" <?= (isset($filters['rol']) && $filters['rol'] === 'ganaderia') ? 'selected' : '' ?>>Ganadería</option>
                  <option value="dev" <?= (isset($filters['rol']) && $filters['rol'] === 'dev') ? 'selected' : '' ?>>Desarrollador</option>
                  <option value="envios" <?= (isset($filters['rol']) && $filters['rol'] === 'envios') ? 'selected' : '' ?>>Envíos</option>
                </select>
              </div>
              <div class="col-xxl-2 col-sm-4">
                <select class="form-control" id="filtroEstado">
                  <option value="">Todos los estados</option>
                  <option value="activo" <?= (!isset($filters['estado']) || $filters['estado'] === 'activo') ? 'selected' : '' ?>>Activo</option>
                  <option value="inactivo" <?= (isset($filters['estado']) && $filters['estado'] === 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
                </select>
              </div>
              <div class="col-xxl-1 col-sm-4">
                <button type="submit" class="btn btn-success w-100">
                  <i class="ri-equalizer-fill me-1 align-bottom"></i> Filtrar
                </button>
              </div>
              <div class="col-xxl-2 col-sm-4">
                <a href="<?= base_url('usuarios') ?>" class="btn btn-secondary w-100">
                  <i class="ri-refresh-line me-1 align-bottom"></i> Limpiar Filtros
                </a>
              </div>
            </div>
          </form>
        </div>

        <div class="card-body pt-0">
          <div class="table-responsive table-card mb-1">
            <table class="table align-middle table-nowrap" id="tablaUsuarios">
              <thead class="table-light text-muted">
                <tr>
                  <th>Nombre Completo</th>
                  <th>Correo</th>
                  <th>Rol</th>
                  <th>Sucursal</th>
                  <th>Celular</th>
                  <th>Fecha de Registro</th>
                  <th>Estado</th>
                  <th class="text-end">Acciones</th>                </tr>
              </thead>
              <tbody class="list form-check-all">
                <?php if (!empty($usuarios)): ?>
                  <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                      
                      <td>
                        <a href="<?= base_url('usuarios/edit/' . $usuario['id']) ?>" class="fw-semibold">
                          <?= esc($usuario['nombre']) . ' ' . esc($usuario['apellidos']) ?>
                        </a>
                      </td>
                     
                      <td><?= esc($usuario['correo']) ?></td>
                      <td>
                        <?php
                          $rol = strtolower($usuario['rol_nombre']);
                          $claseRol = match($rol) {
                            'administrador' => 'bg-soft-admin',
                            'vendedor' => 'bg-soft-vendedor',
                            'cliente' => 'bg-soft-cliente',
                            default => 'bg-light text-dark'
                          };
                        ?>
                        <span class="badge <?= $claseRol ?> badge-role"><?= esc(ucfirst($usuario['rol_nombre'])) ?></span>
                      </td>
                      <td><?= esc($usuario['sucursal_nombre'] ?? 'N/A') ?></td>
                      <td><?= esc($usuario['celular'] ?? 'N/A') ?></td>
                      
                      <td><?= esc($usuario['created_at'] ?? 'N/A') ?></td>
                      <td>
                        <?php
                          // Convertir string de PostgreSQL a booleano
                          $estadoBool = ($usuario['estado'] === 't');
                          $estado = $estadoBool ? 'Activo' : 'Inactivo';
                          $claseEstado = $estadoBool ? 'bg-soft-vendedor' : 'bg-light text-dark';
                        ?>
                        <span class="badge <?= $claseEstado ?>"><?= $estado ?></span>
                      </td>
                      <td class="text-end">
                        <div class="btn-group">
                          <a href="<?= base_url('usuarios/edit/' . $usuario['id']) ?>" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Editar">
                            <i class="ri-pencil-fill align-bottom">Editar</i>
                          </a>

                          <?php if ($usuario['estado'] === 't'): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Desactivar" onclick="confirmDelete(<?= $usuario['id'] ?>, 'desactivar')">
                              <i class="ri-forbid-line align-bottom">Desactivar</i>
                            </button>
                          <?php else: ?>
                            <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Activar" onclick="confirmDelete(<?= $usuario['id'] ?>, 'activar')">
                              <i class="ri-check-line align-bottom">Activar</i>
                            </button>
                          <?php endif; ?>

                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="8" class="text-center py-4">No hay usuarios registrados que coincidan con los filtros.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Confirmar Operación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="deleteModalMessage"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
        <a href="" id="confirmDeleteButton" class="btn btn-danger">Confirmar</a>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  // Función para abrir el modal de confirmación
  function confirmDelete(userId, action) {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const modalMessage = document.getElementById('deleteModalMessage');
    const confirmButton = document.getElementById('confirmDeleteButton');
    
    let message = '';
    let buttonClass = '';
    let buttonText = '';
    let url = '';

    if (action === 'desactivar') {
        message = '¿Está seguro de que desea **desactivar** este usuario? No podrá acceder al sistema.';
        buttonClass = 'btn-danger';
        buttonText = 'Sí, Desactivar';
        // Usar la ruta 'delete' (que realiza la eliminación lógica = estado 0)
        url = `<?= base_url('usuarios/delete') ?>/${userId}`; 
    } else if (action === 'activar') {
        message = '¿Está seguro de que desea **activar** este usuario? Volverá a tener acceso al sistema.';
        buttonClass = 'btn-info';
        buttonText = 'Sí, Activar';
        // Usar una ruta diferente o la misma si el controlador maneja el cambio de estado
        url = `<?= base_url('usuarios/activate') ?>/${userId}`; 
    }

    modalMessage.innerHTML = message;
    confirmButton.className = `btn ${buttonClass}`;
    confirmButton.textContent = buttonText;
    confirmButton.href = url;
    
    modal.show();
  }

  // Función para aplicar filtros y redirigir (necesaria para el filtrado del lado del servidor)
  const aplicarFiltros = (isManual = false) => {
      const busqueda = document.getElementById('buscadorUsuarios').value.trim();
      const rolSeleccionado = document.getElementById('filtroRol').value;
      const estadoSeleccionado = document.getElementById('filtroEstado').value;
      
      const url = new URL(window.location.origin + window.location.pathname);
      
      // Limpiar parámetros existentes
      url.search = '';
      
      if (busqueda) {
          url.searchParams.set('search', busqueda);
      }
      if (rolSeleccionado) {
          url.searchParams.set('rol', rolSeleccionado);
      }
      if (estadoSeleccionado) {
          url.searchParams.set('estado', estadoSeleccionado);
      }
      
      // Redirigir si fue una acción manual (click en Filtrar)
      if (isManual) {
          window.location.href = url.toString();
      }
  };

  // Ejecutar filtros al cargar la página si hay parámetros en la URL (para mantener el estado)
  document.addEventListener('DOMContentLoaded', function() {
    // Configurar el formulario de filtros
    document.querySelector('form').onsubmit = (e) => {
        e.preventDefault();
        aplicarFiltros(true);
    };
    
    // Permitir búsqueda con Enter en el campo de búsqueda
    document.getElementById('buscadorUsuarios').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            aplicarFiltros(true);
        }
    });
    
    // Aplicar filtros automáticamente cuando cambian los selectores
    document.getElementById('filtroRol').addEventListener('change', function() {
        aplicarFiltros(true);
    });
    
    document.getElementById('filtroEstado').addEventListener('change', function() {
        aplicarFiltros(true);
    });
  });
</script>
<?= $this->endSection() ?>