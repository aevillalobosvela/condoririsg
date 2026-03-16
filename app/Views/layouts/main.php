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
	<!-- Scripts específicos de página -->
	<?= $this->renderSection('scripts') ?>
</body>

</html>