<?= $this->include('partials/main') ?>

<head>
    <?php echo view('partials/title-meta', array('title' => 'Sign In')); ?>
    <?= $this->include('partials/head-css') ?>
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56e4;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --white: #ffffff;
            --card-bg: rgba(255, 255, 255, 0.92);
            --border-radius: 16px;
            --shadow: 0 10px 30px -15px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
        }

        .auth-logo img {
            max-height: 120px;
            filter: drop-shadow(0 2px 6px rgba(0,0,0,0.15));
            transition: var(--transition);
        }

        .auth-logo:hover img {
            transform: scale(1.03);
        }

        .login-card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            max-width: 420px;
            margin: 1rem auto;
            transition: var(--transition);
        }

        .login-card:hover {
            box-shadow: 0 12px 40px -15px rgba(0, 0, 0, 0.2);
        }

        .card-title {
            font-weight: 700;
            color: var(--dark);
            font-size: 1.5rem;
            margin: 0 0 1rem 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-control {
            padding: 0.6rem 0.8rem;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
            outline: none;
        }

        .btn-primary {
            background: var(--primary);
            border: none;
            padding: 0.6rem;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 10px;
            transition: var(--transition);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }

        .alert {
            border-radius: 10px;
            margin-bottom: 0.8rem;
            padding: 0.6rem 0.8rem;
        }

        .footer .text-muted {
            font-size: 0.8rem;
        }

        @media (max-width: 576px) {
            .login-card {
                margin: 1.5rem auto;
                padding: 1.5rem !important;
            }
            .auth-logo img {
                max-height: 100px;
            }
            .card-title {
                font-size: 1.3rem;
            }
        }
    </style>
</head>

<body>
    <div class="auth-page-wrapper pt-2">
    
        <!-- auth page content -->
        <div class="auth-page-content">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-12 text-center mt-3">
                        <a href="/" class="auth-logo d-inline-block mb-2">
                            <img src="/assets/img/condoriri1.png" alt="CONDORIRI-SG Logo">
                        </a>
                        <p class="card-title text-white-75 fw-small mb-0">SISTEMA DE GESTION INTEGRAL</p>
                    </div>

                    <div class="col-lg-10 col-xl-8">
                        <div class="card shadow-sm login-card">
                            <div class="card-body p-3 p-sm-4">
                                <h3 class="card-title text-center">Iniciar Sesión</h3>

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

                                <form action="<?= site_url('login') ?>" method="post" class="mt-2">
                                    <?= csrf_field() ?>
                                    
                                    <div class="mb-2">
                                        <label for="usuario" class="form-label">Usuario o Correo Electrónico</label>
                                        <input type="text" class="form-control" id="usuario" name="usuario" value="<?= old('usuario') ?>" required autocomplete="username">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Contraseña</label>
                                        <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">Ingresar al Sistema</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- footer -->
        <footer class="footer">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center">
                            <p class="mb-0 text-muted">
                                &copy; <script>document.write(new Date().getFullYear())</script> CONDORIRI-SG. 
                                Todos los derechos reservados DTIC -UTO.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <?= $this->include('partials/vendor-scripts') ?>
    <script src="/assets/libs/particles.js/particles.js"></script>
    <script src="/assets/js/pages/particles.app.js"></script>
    <script src="/assets/js/pages/password-addon.init.js"></script>
</body>
</html>