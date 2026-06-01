<!doctype html>
<html lang="es" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta charset="utf-8" />
	<title><?= ($title) ? $title : '' ?> | Condoriri-SG</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta content="Sistema de ventas Condoriri" name="description" />
	<meta content="DTIC-UTO" name="author" />
	<!-- App favicon -->
	<link rel="shortcut icon" href="<?=base_url('/assets/images/favicon.ico')?>">

	<!-- jsvectormap css -->
	<link href="/assets/libs/jsvectormap/jsvectormap.min.css" rel="stylesheet" type="text/css" />

	<!--Swiper slider css-->
	<link href="/assets/libs/swiper/swiper-bundle.min.css" rel="stylesheet" type="text/css" />

	<?= $this->include('partials/head-css') ?>
	<?= $this->renderSection('styles') ?>
</head>

<body class="d-flex flex-column min-vh-100">
	<!-- Begin page -->
	<div id="layout-wrapper">

		<?= $this->include('partials/menu') ?>
		<!-- ============================================================== -->
		<!-- Start right Content here -->
		<!-- ============================================================== -->
		<div class="main-content">

			<div class="page-content">
				<div class="container-fluid">
					<?= $this->renderSection('content') ?>
				</div>
				<!-- container-fluid -->
			</div>
			<!-- End Page-content -->

			<!-- Footer -->
			<?= $this->include('partials/footer') ?>

		</div>
		<!-- end main content-->

	</div>
	<!-- END layout-wrapper -->

	<?= $this->include('partials/vendor-scripts') ?>
	<!-- App js -->
	<script src="/assets/js/app.js"></script>
	<!-- Prevención de doble submit en formularios -->
	<script src="/assets/js/prevent-double-submit.js"></script>

	<?php
	$_rolModal = session()->get('rol_nombre');
	if (in_array($_rolModal, ['admin', 'almacen'])):
	?>
	<!-- Modal Resumen Global — visible solo para admin y almacen -->
	<div class="modal fade" id="modalResumenGlobal" tabindex="-1" aria-hidden="true">
	  <div class="modal-dialog modal-sm modal-dialog-centered">
	    <div class="modal-content">
	      <div class="modal-header" style="background:#1F4E79;">
	        <h5 class="modal-title text-white">
	          <i class="ri-file-chart-line me-1"></i> Resumen Global
	        </h5>
	        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
	      </div>
	      <div class="modal-body">
	        <p class="text-muted small mb-3">
	          Selecciona el mes para generar el reporte consolidado (Producción, Ventas, Envíos y Balance).
	        </p>
	        <label for="resumenGlobalMes" class="form-label fw-semibold">Mes</label>
	        <input type="month" class="form-control" id="resumenGlobalMes"
	               value="<?= date('Y-m') ?>"
	               max="<?= date('Y-m') ?>">
	        <div id="resumenGlobalError" class="text-danger small mt-1 d-none">
	          Selecciona un mes válido.
	        </div>
	      </div>
	      <div class="modal-footer">
	        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
	        <button type="button" class="btn btn-sm text-white" id="btnDescargarPdfResumen"
	                style="background:#C0392B;">
	          <i class="ri-file-pdf-line me-1"></i> PDF
	        </button>
	        <button type="button" class="btn btn-sm text-white" id="btnDescargarResumen"
	                style="background:#1F4E79;">
	          <i class="ri-download-2-line me-1"></i> Excel
	        </button>
	      </div>
	    </div>
	  </div>
	</div>
	<script>
	function resumenGlobalDescargar(formato) {
	  const mes = document.getElementById('resumenGlobalMes').value;
	  const err = document.getElementById('resumenGlobalError');
	  if (!mes || !/^\d{4}-\d{2}$/.test(mes)) {
	    err.classList.remove('d-none');
	    return;
	  }
	  err.classList.add('d-none');
	  bootstrap.Modal.getInstance(document.getElementById('modalResumenGlobal')).hide();
	  const ruta = formato === 'pdf'
	    ? '<?= base_url('resumen-global/exportarPdf') ?>'
	    : '<?= base_url('resumen-global/exportar') ?>';
	  window.location.href = ruta + '?mes=' + mes;
	}
	document.getElementById('btnDescargarResumen').addEventListener('click',    () => resumenGlobalDescargar('excel'));
	document.getElementById('btnDescargarPdfResumen').addEventListener('click', () => resumenGlobalDescargar('pdf'));
	</script>
	<?php endif; ?>

	<!-- Scripts específicos de página -->
	<?= $this->renderSection('scripts') ?>
</body>

</html>