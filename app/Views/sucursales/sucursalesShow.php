<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($sucursal['nombre']) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
    :root {
        --primary-green: #28a745;
        --primary-green-dark: #218838;
        --primary-green-light: #d4edda;
        --gray-700: #495057;
        --gray-500: #6c757d;
        --gray-300: #dee2e6;
        --white: #ffffff;
        --shadow-lg: 0 8px 30px rgba(0, 0, 0, 0.1);
        --shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }

    /* ✅ HEADER PRINCIPAL — Todo aquí */
    .sucursal-hero {
        background: linear-gradient(135deg, #f8fdfa 0%, #e8f5ec 100%);
        border-bottom: 1px solid #d1e7dd;
        padding: 2rem 0;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }

    .hero-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 1.5rem;
    }

    /* Logo + Nombre */
    .hero-head {
        display: flex;
        align-items: flex-start;
        gap: 1.5rem;
        margin-bottom: 1.75rem;
        flex-wrap: wrap;
    }

    .sucursal-logo {
        flex: 0 0 auto;
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, var(--primary-green), var(--primary-green-dark));
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2.25rem;
        flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(40, 167, 69, 0.25);
    }

    .hero-text {
        flex: 1;
        min-width: 0;
    }

    .hero-title {
        font-size: 2.25rem;
        font-weight: 800;
        color: var(--gray-700);
        margin: 0 0 0.5rem;
        line-height: 1.2;
    }

    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem 1.1rem;
        border-radius: 50px;
        font-weight: 700;
        font-size: 1rem;
        background-color: var(--white);
        color: var(--primary-green);
        border: 2px solid var(--primary-green);
        box-shadow: 0 2px 6px rgba(40, 167, 69, 0.15);
    }

    .badge-icon {
        font-size: 1.1rem;
    }

    /* ✅ TODO EL RESTO EN LA MISMA SECCIÓN DEL HEADER */
    .hero-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.25rem;
        padding-top: 0.5rem;
    }

    .info-card {
        background: white;
        border-radius: 12px;
        padding: 1.25rem;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
        border-left: 4px solid var(--primary-green);
        transition: transform 0.2s;
    }

    .info-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 12px rgba(0, 0, 0, 0.08);
    }

    .info-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--gray-500);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .info-title i {
        color: var(--primary-green);
        font-size: 1rem;
    }

    .info-value {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--gray-700);
        line-height: 1.4;
        word-break: break-word;
    }

    .info-value.empty {
        color: var(--gray-500);
        font-style: italic;
    }

    /* Botón de regreso */
    .hero-actions {
        margin-top: 1.5rem;
        text-align: right;
    }

    .btn-back {
        background-color: transparent;
        border: 2px solid var(--primary-green);
        color: var(--primary-green);
        padding: 0.5rem 1.5rem;
        font-weight: 600;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s;
    }

    .btn-back:hover {
        background-color: var(--primary-green);
        color: white;
        transform: translateY(-2px);
    }

    /* Responsive */
    @media (max-width: 767.98px) {
        .hero-head {
            flex-direction: column;
            text-align: center;
        }

        .hero-text {
            width: 100%;
        }

        .hero-actions {
            text-align: center;
        }

        .hero-info-grid {
            grid-template-columns: 1fr;
        }

        .hero-title {
            font-size: 1.875rem;
        }

        .sucursal-logo {
            width: 70px;
            height: 70px;
            font-size: 2rem;
        }
    }

    @media (min-width: 768px) {
        .hero-info-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (min-width: 992px) {
        .hero-info-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">

    <div class="sucursal-hero">
        <div class="hero-container">
            <div class="hero-head">
                <div class="sucursal-logo">
                    <i class="ri-store-3-fill"></i>
                </div>
                <div class="hero-text">
                    <h1 class="hero-title"><?= esc($sucursal['nombre']) ?></h1>
                    <div class="hero-badge">
                        <i class="badge-icon <?= $sucursal['estado'] ? 'ri-checkbox-circle-fill' : 'ri-close-circle-fill' ?>"></i>
                        <span><?= $sucursal['estado'] ? 'ACTIVA' : 'INACTIVA' ?></span>
                    </div>
                </div>
            </div>

            
            <div class="hero-info-grid">
                <!-- Dirección -->
                <div class="info-card">
                    <div class="info-title">
                        <i class="ri-map-pin-line"></i> Dirección
                    </div>
                    <div class="info-value <?= empty($sucursal['direccion']) ? 'empty' : '' ?>">
                        <?= !empty($sucursal['direccion']) ? esc($sucursal['direccion']) : 'Sin dirección registrada' ?>
                    </div>
                </div>

                <!-- Teléfono -->
                <div class="info-card">
                    <div class="info-title">
                        <i class="ri-phone-line"></i> Teléfono
                    </div>
                    <div class="info-value <?= empty($sucursal['telefono']) ? 'empty' : '' ?>">
                        <?= !empty($sucursal['telefono']) ? esc($sucursal['telefono']) : 'Sin teléfono' ?>
                    </div>
                </div>




                <!-- Horario L-V -->
                <div class="info-card">
                    <div class="info-title">
                        <i class="ri-time-line"></i> Lunes - Viernes
                    </div>
                    <div class="info-value">08:00 - 18:00</div>
                </div>




                <!-- Botón de regreso (parte del header) -->
                <div class="hero-actions">
                    <a href="<?= base_url('/') ?>" class="btn-back">
                        <i class="ri-arrow-left-s-line"></i> Volver al inicio
                    </a>
                </div>
            </div>



        </div>
    </div>
</div>

<div>
    hola
</div>
    <?= $this->endSection() ?>

    <?= $this->section('scripts') ?>
    <script>
        // Opcional: animación suave al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.info-card');
            cards.forEach((card, i) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(15px)';
                setTimeout(() => {
                    card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100 * i);
            });
        });
    </script>
    <?= $this->endSection() ?>