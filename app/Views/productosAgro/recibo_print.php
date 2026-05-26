<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <script src="<?= base_url('assets/js/qrcode.min.js') ?>"></script>
    <style>
        body {
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #000;
            font-weight: bold;
        }

        #print-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        #receipt-container {
            background-color: #fff;
            width: 300px;
            padding: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5);
            border-radius: 4px;
            max-height: 95vh;
            overflow-y: auto;
            position: relative;
        }

        #receipt-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('<?= base_url("assets/images/watermark.png") ?>');
            background-repeat: no-repeat;
            background-position: center;
            background-size: 60%;
            opacity: 0.1;
            z-index: 0;
            pointer-events: none;
        }

        #receipt-container > * {
            position: relative;
            z-index: 1;
        }

        .header {
            text-align: center;
            margin-bottom: 6px;
        }
        .header .recibo {
            font-size: 16px;
            margin: 0 0 2px 0;
            font-weight: bold;
        }
        .header .universidad {
            font-size: 12px;
            margin: 2px 0;
            font-weight: bold;
        }
        .header .centro {
            font-size: 11px;
            margin: 1px 0;
        }
        .header .direccion-telefono {
            font-size: 10px;
            margin: 1px 0;
        }
        .header .ciudad {
            font-size: 10px;
            margin: 1px 0;
        }
        .separator {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        .info-line {
            margin: 2px 0;
            font-size: 12px;
        }
        .info-line-tipo-pago {
            margin: 2px 0;
            font-size: 14px;
            text-align: right;
        }
        .cliente-section {
            margin: 6px 0;
        }
        .cliente-qr-row {
            display: flex;
            align-items: center;
            margin: 2px 0;
        }
        .cliente-qr-row .cliente-info {
            flex: 3;
        }
        .cliente-qr-row .qr-box {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .cliente-qr-row .qr-box canvas,
        .cliente-qr-row .qr-box img {
            width: 52px !important;
            height: 52px !important;
        }
        .qr-label {
            font-size: 8px;
            text-align: center;
            font-weight: normal;
            margin-top: 1px;
        }
        .productos-header {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            margin: 5px 0 3px 0;
            font-size: 11px;
        }
        .producto-item {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
            font-size: 12px;
        }
        .producto-nombre {
            flex: 1;
        }
        .producto-precio {
            text-align: right;
            min-width: 60px;
        }
        .total-section {
            text-align: center;
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px solid #000;
        }
        .total-section p {
            font-size: 14px;
            font-weight: bold;
            margin: 3px 0;
        }
        .footer {
            text-align: center;
            margin-top: 8px;
        }
        .footer p {
            margin: 3px 0;
            font-size: 12px;
        }
        .modal-controls {
            text-align: center;
            padding: 10px 0 0;
            margin-top: 10px;
        }
        .modal-controls button {
            padding: 8px 16px;
            margin: 0 5px;
            cursor: pointer;
            border: none;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        #print-button {
            background-color: #007bff;
            color: white;
        }
        #close-button {
            background-color: #6c757d;
            color: white;
        }

        @media print {
            body > * {
                visibility: hidden;
                display: none;
            }
            #print-modal {
                position: absolute;
                left: 0;
                top: 0;
                background: none;
                display: flex !important;
                visibility: visible;
            }
            #receipt-container {
                width: 80mm;
                box-shadow: none;
                margin: 0;
                padding: 5mm;
                max-height: none;
                overflow-y: visible;
                display: block !important;
                visibility: visible;
            }
            #receipt-container > * {
                visibility: visible;
                display: block;
            }
            .productos-header,
            .producto-item {
                display: flex !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<?php
$userSucursalName = session()->get('sucursal_nombre');
$nombreUsuario    = session()->get('nombre') ?? session()->get('username') ?? 'Usuario';
?>
<body>

    <div id="print-modal">
        <div id="receipt-container">

            <div class="header">
                <div class="recibo">RECIBO</div>
                <div class="universidad">UNIVERSIDAD TECNICA DE ORURO</div>
                <div class="centro">CENTRO EXP. AGROPECUARIO CONDORIRI</div>
                <div class="direccion-telefono">6 de Octubre y Aroma - Tel: 55693</div>
                <div class="ciudad">Oruro - Bolivia</div>
            </div>

            <div class="separator"></div>

            <div class="info-line">Sucursal: <?= esc($userSucursalName) ?></div>
            <div class="info-line">Fecha: <?= date('d/m/Y H:i', strtotime($venta->created_at)) ?></div>
            <div class="info-line">Nro: <?= esc($venta->id) ?></div>
            <div class="info-line">Código: <?= esc($venta->code) ?></div>
            <div class="info-line-tipo-pago">Tipo Pago: <?= esc(ucfirst($venta->tipo_pago)) ?></div>

            <div class="separator"></div>

            <div class="cliente-section">
                <?php if (!empty($venta->personal_uto_id) && !empty($personal)): ?>
                    <div class="cliente-qr-row">
                        <div class="cliente-info">
                            <div class="info-line">Personal UTO: <?= esc($personal['nombre'] ?? 'N/A') ?></div>
                            <div class="info-line">Cargo: <?= esc($personal['cargo'] ?? 'Sin cargo') ?></div>
                            <div class="info-line">Sección: <?= esc($personal['seccion'] ?? 'Sin sección') ?></div>
                        </div>
                        <div class="qr-box">
                            <div id="qr-doc" data-qr="<?= esc($personal['dip'] ?? '') ?>"></div>
                            <span class="qr-label">CI</span>
                        </div>
                    </div>
                <?php elseif (!empty($venta->cliente_externo_id) && !empty($clienteExterno)): ?>
                    <div class="cliente-qr-row">
                        <div class="cliente-info">
                            <div class="info-line">Cliente: <?= esc($clienteExterno['nombre']) ?></div>
                            <div class="info-line">Segmento: <?= esc($clienteExterno['segmento']) ?></div>
                        </div>
                        <div class="qr-box">
                            <div id="qr-doc" data-qr="<?= esc($clienteExterno['dip']) ?>"></div>
                            <span class="qr-label">CI</span>
                        </div>
                    </div>
                <?php elseif (!empty($cliente)): ?>
                    <div class="cliente-qr-row">
                        <div class="cliente-info">
                            <div class="info-line">Cliente: <?= esc($cliente['nombre_completo'] ?? 'Cliente General') ?></div>
                        </div>
                        <?php if (!empty($cliente['ci_nit'])): ?>
                        <div class="qr-box">
                            <div id="qr-doc" data-qr="<?= esc($cliente['ci_nit']) ?>"></div>
                            <span class="qr-label">CI</span>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="info-line">Cliente: CLIENTE GENERAL</div>
                <?php endif; ?>
            </div>

            <div class="separator"></div>

            <div class="productos-header">
                <span style="width: 12%;">Cant.</span>
                <span style="width: 33%;">Producto</span>
                <span style="width: 15%;">Unidad</span>
                <span style="width: 18%; text-align: right;">P.U.</span>
                <span style="width: 22%; text-align: right;">Subtotal</span>
            </div>

            <?php foreach ($detalles as $item): ?>
                <div class="producto-item">
                    <span style="width: 12%;"><?= esc($item['cantidad']) ?></span>
                    <span style="width: 33%;"><?= esc(strtoupper($item['producto'] ?? 'PRODUCTO')) ?></span>
                    <span style="width: 15%;"><?= esc(strtoupper($item['unidad_nombre'] ?? 'UND')) ?></span>
                    <span style="width: 18%; text-align: right;"><?= number_format($item['precio_unitario'], 2) ?></span>
                    <span style="width: 22%; text-align: right;"><?= number_format($item['subtotal'], 2) ?></span>
                </div>
            <?php endforeach; ?>

            <div class="total-section">
                <p>TOTAL PAGADO: <?= number_format($venta->monto_total, 2) ?> Bs</p>
            </div>

            <div class="separator"></div>

            <div class="footer">
                <p>Atendido por: <?= esc(ucwords(strtolower($nombreUsuario))) ?></p>
                <?php if (!empty($venta->personal_uto_id)): ?>
                    <p style="font-size: 10px; margin-top: 4px;">Venta a crédito - Personal UTO</p>
                <?php endif; ?>
                <p style="margin-top: 30px;">&nbsp;</p>
                <p style="border-top: 1px solid #000; width: 60%; margin: 0 auto;">&nbsp;</p>
            </div>

            <div class="modal-controls no-print">
                <button id="print-button">Imprimir Recibo</button>
                <button id="close-button">Cerrar</button>
            </div>

        </div>
    </div>

    <script>
        window.onload = function() {
            var qrEl = document.getElementById('qr-doc');
            if (qrEl && qrEl.dataset.qr) {
                new QRCode(qrEl, {
                    text: qrEl.dataset.qr,
                    width: 56, height: 56,
                    correctLevel: QRCode.CorrectLevel.M
                });
            }

            document.getElementById('print-button').addEventListener('click', function() {
                window.print();
            });
            document.getElementById('close-button').addEventListener('click', function() {
                window.location.href = '/productosagro/ventas';
            });
        };
    </script>
</body>
</html>
