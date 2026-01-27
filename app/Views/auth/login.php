<?= $this->include('partials/main') ?>

<head>

    <?php echo view('partials/title-meta', array('title' => 'Sign In')); ?>

    <?= $this->include('partials/head-css') ?>

</head>

<body>

    <div class="auth-page-wrapper pt-5">
        <!-- auth page bg -->
        <div class="auth-one-bg-position auth-one-bg" id="auth-particles">
            <div class="bg-overlay"></div>

            <div class="shape">
                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 1440 120">
                    <path d="M 0,36 C 144,53.6 432,123.2 720,124 C 1008,124.8 1296,56.8 1440,40L1440 140L0 140z"></path>
                </svg>
            </div>
        </div>

        <!-- auth page content -->
        <div class="auth-page-content">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center mt-sm-5 mb-4 text-white-50">
                            <div>
                                <a href="/" class="d-inline-block auth-logo">
                                    <img src="/assets/images/logo-light.png" alt="" height="20">
                                </a>
                            </div>
                            <p class="mt-3 fs-15 fw-medium">Premium Admin & Dashboard Template</p>
                        </div>
                    </div>
                </div>
                <!-- end row -->

             
<div class="card shadow-sm login-card">
    <div class="card-body p-5">
        <h3 class="card-title text-center mb-4">Iniciar Sesión</h3>

        <?php if (session()->getFlashdata('message')): ?>
            <div class="alert alert-success"><?= session()->getFlashdata('message') ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger">
                <?php foreach (session()->getFlashdata('errors') as $error): ?>
                    <p class="mb-0"><?= esc($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form action="<?= site_url('login') ?>" method="post">
            <?= csrf_field() ?>
            
            <div class="mb-3">
                <label for="usuario" class="form-label">Usuario o Correo</label>
                <input type="text" class="form-control" id="usuario" name="usuario" value="<?= old('usuario') ?>" required>
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Ingresar</button>
            </div>
        </form>
    </div>
</div>
                <!-- end row -->
            </div>
            <!-- end container -->
        </div>
        <!-- end auth page content -->

        <!-- footer -->
        <footer class="footer">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center">
                            <p class="mb-0 text-muted">&copy;
                                <script>document.write(new Date().getFullYear())</script> Velzon. Crafted with <i class="mdi mdi-heart text-danger"></i> by Themesbrand
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
        <!-- end Footer -->
    </div>
    <!-- end auth-page-wrapper -->

    <?= $this->include('partials/vendor-scripts') ?>

    <!-- particles js -->
    <script src="/assets/libs/particles.js/particles.js"></script>
    <!-- particles app js -->
    <script src="/assets/js/pages/particles.app.js"></script>
    <!-- password-addon init -->
    <script src="/assets/js/pages/password-addon.init.js"></script>
</body>

</html>
