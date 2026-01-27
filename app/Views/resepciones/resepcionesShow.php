<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  .bg-soft-enviado {
    background-color: #d4edda !important;
    color: #155724 !important;
  }

  .bg-soft-pendiente {
    background-color: #fff3cd !important;
    color: #856404 !important;
  }

  .bg-soft-rechazado {
    background-color: #f8d7da !important;
    color: #721c24 !important;
  }

  .bg-soft-entregado { /* Añadido un estilo para Entregado (estado 3) */
    background-color: #cce5ff !important;
    color: #004085 !important;
  }

  .card-header {
    background-color: #f8fdfa;
    border-bottom: 1px solid #e0f0e9;
    font-weight: 600;
    color: #28a745;
  }

  .alert-info {
    background-color: #f8fdfa;
    border-color: #e0f0e9;
    color: #28a745;
  }

  .product-row.accepted {
    background-color: #f0f9f4;
  }

  .product-row.rejected {
    background-color: #fcf0f0;
  }

  .btn-accept {
    background-color: #28a745;
    border-color: #28a745;
  }

  .btn-reject {
    background-color: #dc3545;
    border-color: #dc3545;
  }

  .btn-accept:hover {
    background-color: #218838;
  }

  .btn-reject:hover {
    background-color: #c82333;
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-sm-flex align-items-center justify-content-between">
        <h4 class="mb-sm-0"><?= esc($title) ?></h4>
        <div class="page-title-right">
          <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="<?= base_url('envios') ?>">Envíos</a></li>
            <li class="breadcrumb-item active"><?= esc($title) ?></li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <!-- Mensajes -->
  <?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="ri-checkbox-circle-fill me-2"></i><?= session()->getFlashdata('message') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="ri-error-warning-fill me-2"></i><?= session()->getFlashdata('error') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="card-title mb-0">Detalles del Envío</h5>
          <div>
            <a href="<?= base_url('resepciones/reporteDetallado/' . $envio->id) ?>" class="btn btn-danger btn-sm me-2">
              <i class="ri-file-pdf-line me-1"></i> Exportar PDF
            </a>
            <a href="<?= base_url('resepciones') ?>" class="btn btn-secondary btn-sm">
              <i class="ri-arrow-left-line me-1"></i> Volver
            </a>
          </div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <h6 class="text-muted">Información Básica</h6>
                <hr class="mt-1 mb-2">
                <div class="row mb-2">
                  <div class="col-md-4 fw-bold">Código:</div>
                  <div class="col-md-8"><?= esc($envio->code) ?></div>
                </div>
                <div class="row mb-2">
                  <div class="col-md-4 fw-bold">Sucursal Origen:</div>
                  <div class="col-md-8"><?= esc($sucursalOrigen->nombre ?? 'N/A') ?></div>
                </div>
                <div class="row mb-2">
                  <div class="col-md-4 fw-bold">Sucursal Destino:</div>
                  <div class="col-md-8"><?= esc($sucursalDestino->nombre ?? 'N/A') ?></div>
                </div>
                <div class="row mb-2">
                  <div class="col-md-4 fw-bold">Vendedor / Creador:</div>
                  <div class="col-md-8"><?= esc($userCreador->nombre_completo ?? 'N/A') ?></div>
                </div>
                <div class="row mb-2">
                  <div class="col-md-4 fw-bold">Transporte:</div>
                  <div class="col-md-8"><?= esc($userTransporte->nombre_completo ?? 'N/A') ?></div>
                </div>
                <?php if (!empty($envio->observacion_origen)): ?>
                  <div class="row mb-2">
                    <div class="col-md-4 fw-bold">Observación Origen:</div>
                    <div class="col-md-8"><?= esc($envio->observacion_origen) ?></div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <h6 class="text-muted">Estado y Fechas</h6>
                <hr class="mt-1 mb-2">
                <div class="row mb-2">
                  <div class="col-md-4 fw-bold">Estado:</div>
                  <div class="col-md-8">
                    <?php if ($envio->estado_id == 1): ?>
                      <span class="badge bg-soft-pendiente">Pendiente</span>
                    <?php elseif ($envio->estado_id == 2): ?>
                      <span class="badge bg-soft-enviado">Enviado</span>
                    <?php elseif ($envio->estado_id == 3): ?>
                      <span class="badge bg-soft-entregado">Entregado</span>
                    <?php elseif ($envio->estado_id == 4): ?>
                      <span class="badge bg-soft-rechazado">Rechazado</span>
                    <?php else: ?>
                      <span class="badge bg-secondary"><?= esc($estado->nombre ?? 'N/A') ?></span>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="row mb-2">
                  <div class="col-md-4 fw-bold">Fecha de Envío:</div>
                  <div class="col-md-8"><?= esc($envio->fecha_envio ? date('d/m/Y H:i', strtotime($envio->fecha_envio)) : 'N/A') ?></div>
                </div>
                <?php if ($envio->user_recepcion_id): ?>
                  <div class="row mb-2">
                    <div class="col-md-4 fw-bold">Receptor:</div>
                    <div class="col-md-8"><?= esc($userRecepcion->nombre_completo ?? 'N/A') ?></div>
                  </div>
                <?php endif; ?>
                <?php if ($envio->fecha_recepcion): ?>
                  <div class="row mb-2">
                    <div class="col-md-4 fw-bold">Fecha de Recepción:</div>
                    <div class="col-md-8"><?= esc(date('d/m/Y H:i', strtotime($envio->fecha_recepcion))) ?></div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Productos del envío -->
      <div class="card mt-4">
        
        <?php if ($envio->estado_id < 3): ?>
        <form action="<?= base_url('resepciones/recepcionar/' . $envio->id) ?>" method="post" id="recepcionForm">
        <?php endif; ?>

          <div class="card-header">
            <h5 class="card-title mb-0">Productos Enviados</h5>
          </div>
          <div class="card-body">
            <?php if (empty($transferencias)): ?>
              <div class="alert alert-info text-center">
                No hay productos en este envío.
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-bordered align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>Code Producto</th>
                      <th>Producto</th>
                      <th>Cantidad Enviada</th>
                      <th>Cantidad Aceptada</th>
                      <th>Aceptar</th>
                      <th>Observaciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($transferencias as $item): 
                      // *** CORRECCIÓN CRÍTICA: Filtrar solo las transferencias de este envío ***
                      if ($item->envio_id == $envio->id):
                      ?>
                      
                      <tr class="product-row" id="row-<?= $item->id ?>">
                        <td><?= esc($item->producto_code ?? 'N/A') ?></td>
                        <td><?= esc($item->producto_nombre) ?></td>
                        <td><?= esc($item->cantidad) ?></td>
                        <td>
                          <!-- *** CAMPO DE CANTIDAD ACEPTADA *** -->
                          <input type="number"
                            name="productos[<?= $item->id ?>][cantidad_recibida]"
                            class="form-control cantidad-recibida-field"
                            id="qty-<?= $item->id ?>"
                            value="<?= esc($item->cantidad) ?>"
                            min="0"
                            max="<?= esc($item->cantidad) ?>"
                            <?= ($envio->estado_id >= 3) ? 'disabled' : 'required' ?>
                          >
                        </td>
                        <td>
                          <div class="form-check form-switch">
                            <input class="form-check-input accept-checkbox"
                              type="checkbox"
                              name="productos[<?= $item->id ?>][aceptar]"
                              id="accept-<?= $item->id ?>"
                              value="1"
                              <?= ($envio->estado_id >= 3) ? 'disabled' : 'checked' ?>
                              onchange="toggleReceptionFields(<?= $item->id ?>, <?= esc($item->cantidad) ?>)">
                          </div>
                        </td>
                        <td>
                          <textarea
                            name="productos[<?= $item->id ?>][observacion]"
                            class="form-control observacion-field"
                            rows="2"
                            placeholder="Ej: Cantidad incorrecta, producto dañado..."
                            id="obs-<?= $item->id ?>"
                            <?= ($envio->estado_id >= 3) ? 'disabled' : '' ?>
                          ><?= esc($item->observacion_destino ?? '') ?></textarea>
                        </td>
                      </tr>
                    <?php endif; endforeach; ?>
                  </tbody>
                </table>
              </div>

              <?php if ($envio->estado_id < 3): ?>
              <div class="d-grid mt-3">
                <button type="submit" class="btn btn-success btn-lg">
                  <i class="ri-check-double-line me-2"></i>Confirmar Recepción
                </button>
                <p class="text-muted text-center mt-2">
                  Puede rechazar productos (poniendo la cantidad a 0 o desmarcando el switch) y agregando una observación.
                </p>
              </div>
              <?php else: ?>
              <div class="alert alert-info text-center mt-3">
                  Este envío ya fue recibido y el stock actualizado. No se permiten más modificaciones.
              </div>
              <?php endif; ?>

            <?php endif; ?>

        <?php if ($envio->estado_id < 3): ?>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
 
  function toggleReceptionFields(id, maxQty) {
    const checkbox = document.getElementById('accept-' + id);
    const textarea = document.getElementById('obs-' + id);
    const qtyInput = document.getElementById('qty-' + id);

    if (!checkbox.checked) {
      
      qtyInput.disabled = true;
      qtyInput.value = 0;
      textarea.required = true;
      textarea.placeholder = 'Obligatorio al rechazar: explique el motivo';
      
    } else {
      // ACEPTADO: Activar cantidad, observación opcional, restaurar cantidad al máximo
      qtyInput.disabled = false;
      // Asegurar que no exceda el máximo
      if (qtyInput.value == 0 || qtyInput.value === "") { 
        qtyInput.value = maxQty; 
      }
      textarea.required = false;
      textarea.placeholder = 'Opcional: agregue observaciones';
    }
  }

  document.addEventListener('DOMContentLoaded', function() {
    // Inicializar estado de campos al cargar la página
    document.querySelectorAll('.accept-checkbox').forEach(checkbox => {
      const id = checkbox.id.replace('accept-', '');
      const maxQty = parseInt(document.getElementById('qty-' + id).getAttribute('max'));
      toggleReceptionFields(id, maxQty);
    });

    // Validar antes de enviar el formulario
    const form = document.getElementById('recepcionForm');
    if (form) {
      form.addEventListener('submit', function(event) {
        let isInvalid = false;
        document.querySelectorAll('.cantidad-recibida-field').forEach(input => {
          const id = input.id.replace('qty-', '');
          const checkbox = document.getElementById('accept-' + id);
          
          if (checkbox.checked) {
              const val = parseInt(input.value);
              const max = parseInt(input.getAttribute('max'));
              
              if (val < 0 || val > max) {
                  alert(`La cantidad recibida para el producto (ID: ${id}) debe estar entre 0 y ${max}.`);
                  input.focus();
                  isInvalid = true;
                  event.preventDefault();
              }
          }
        });

        if (isInvalid) {
          event.preventDefault();
        }
      });
    }
  });
</script>
<?= $this->endSection() ?>