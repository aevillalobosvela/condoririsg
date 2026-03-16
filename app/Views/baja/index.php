<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  .badge-baja { background-color: #fff5f5; color: #e74c3c; }
  .modal-xl { max-width: 95%; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
  <!-- Header -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="page-title-box d-sm-flex align-items-center justify-content-between">
        <div>
          <h4 class="mb-1">📦 Bajas de Productos</h4>
          <p class="text-muted mb-0">Registre salidas no comerciales (daños, vencidos, etc.)</p>
        </div>
        <div class="page-title-right">
          <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Inicio</a></li>
            <li class="breadcrumb-item active">Bajas</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <!-- Filtros -->
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <form method="get" class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Producto</label>
              <input type="text" class="form-control" name="producto" value="<?= esc($filters['producto'] ?? '') ?>" placeholder="Nombre o código">
            </div>
            <div class="col-md-3">
              <label class="form-label">Usuario</label>
              <input type="text" class="form-control" name="usuario" value="<?= esc($filters['usuario'] ?? '') ?>" placeholder="Nombre o apellido">
            </div>
            <div class="col-md-2">
              <label class="form-label">Desde</label>
              <input type="date" class="form-control" name="fecha_desde" value="<?= esc($filters['fecha_desde'] ?? '') ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label">Hasta</label>
              <input type="date" class="form-control" name="fecha_hasta" value="<?= esc($filters['fecha_hasta'] ?? '') ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
              <div class="d-grid w-100">
                <button type="submit" class="btn btn-primary">
                  <i class="ri-search-line me-1"></i> Filtrar
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Botones y tabla -->
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0"><i class="ri-delete-bin-6-line text-danger me-2"></i> Últimas Bajas</h5>
          <div>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalBaja">
              <i class="ri-add-line me-1"></i> Registrar Baja
            </button>
            <a href="<?= base_url('baja/exportarExcel') ?>?<?= http_build_query($filters) ?>" class="btn btn-success ms-2">
              <i class="ri-file-excel-2-line me-1"></i> Excel
            </a>
            <a href="<?= base_url('baja/reportePDF') ?>?<?= http_build_query($filters) ?>" class="btn btn-outline-secondary ms-2">
              <i class="ri-file-pdf-line me-1"></i> PDF
            </a>
          </div>
        </div>
        <div class="card-body">
          <?php if (empty($bajas)): ?>
            <div class="text-center py-5">
              <i class="ri-inbox-unarchive-line fs-1 text-muted mb-3"></i>
              <h5 class="text-muted">No hay bajas registradas</h5>
              <p class="text-muted">Use el botón <strong>Registrar Baja</strong> para comenzar.</p>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Fecha</th>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Usuario</th>
                    <th>Observación</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($bajas as $baja): ?>
                    <tr>
                      <td class="text-nowrap"><?= date('d/m/Y H:i', strtotime($baja->created_at)) ?></td>
                      <td>
                        <span class="badge bg-light text-dark">Lote:<?= esc($baja->producto_id)  ?></span>
                        <br><?= esc($baja->producto_nombre ?? 'Producto no encontrado') ?>
                      </td>
                      <td><span class="badge bg-danger"><?= esc($baja->cantidad) ?></span></td>
                      <td>
                        <?= esc($baja->usuario_nombre ?? '—') ?> 
                        <?= esc($baja->usuario_apellidos ?? '') ?>
                      </td>
                      <td>
                        <?= !empty($baja->observacion) 
                          ? '<span class="text-muted">' . esc($baja->observacion) . '</span>' 
                          : '<span class="text-muted">—</span>' 
                        ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <!-- ✅ Corregido: 'default' → 'bootstrap' -->
            <?= $pager ? $pager->links('bajas', 'bootstrap') : '' ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Registrar Baja -->
<div class="modal fade" id="modalBaja" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-delete-bin-6-line text-danger me-2"></i> Registrar Nueva Baja</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="formBaja">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Producto <span class="text-danger">*</span></label>
              <select class="form-select" name="producto_id" required>
                <option value="">Seleccione un producto</option>
                <?php foreach ($productos as $p): ?>
                  <option value="<?= $p->id ?>" data-stock="<?= $p->stock_inve ?>">
                    ID: <?= esc($p->id) ?> — <?= esc($p->nombre) ?> (Stock: <?= esc($p->stock_inve) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Cantidad <span class="text-danger">*</span></label>
              <input type="number" class="form-control" name="cantidad" min="1" value="1" required>
            </div>
            <div class="col-12">
              <label class="form-label">Observación</label>
              <textarea class="form-control" name="observacion" rows="2" placeholder="Ej: Producto vencido, dañado en transporte, etc."></textarea>
            </div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="no_restar_stock" id="noRestarStock">
                <label class="form-check-label" for="noRestarStock">
                  <strong>Sucursal 4:</strong> No restar de stock_inve (solo registrar en historial)
                </label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger">
            <i class="ri-save-3-line me-1"></i> Registrar Baja
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- ✅ SweetAlert2 sin espacios -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const productoSelect = document.querySelector('select[name="producto_id"]');
    const cantidadInput = document.querySelector('input[name="cantidad"]');

    // Actualizar máximo de cantidad cuando cambia el producto
    if (productoSelect) {
      productoSelect.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const stock = option.dataset.stock || 1000;
        cantidadInput.max = stock;
        cantidadInput.value = Math.min(cantidadInput.value, stock);
      });
    }

    // Manejar envío del formulario
    const form = document.getElementById('formBaja');
    if (form) {
      form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        try {
          // ✅ Ruta corregida a singular: 'baja/store'
          const res = await fetch('<?= base_url('baja/store') ?>', {
            method: 'POST',
            body: formData,
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          });
          
          const data = await res.json();
          
          if (data.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: '¡Éxito!',
              text: data.message,
              timer: 1500,
              showConfirmButton: false
            }).then(() => {
              // ✅ Redirección a singular: 'baja'
              window.location.href = '<?= base_url('baja') ?>';
            });
          } else {
            let msg = data.message || 'Error desconocido';
            if (data.errors) {
              msg = Object.values(data.errors).join('<br>');
            }
            Swal.fire('Error', msg, 'error');
          }
        } catch (err) {
          Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
        }
      });
    }
  });
</script>
<?= $this->endSection() ?>