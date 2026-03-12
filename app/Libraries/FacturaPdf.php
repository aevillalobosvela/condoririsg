<?php

namespace App\Libraries;

// Cargar la librería FPDF desde la ruta de terceros
require_once APPPATH . 'ThirdParty/fpdf/fpdf.php';

use FPDF;

class FacturaPdf extends FPDF
{
    /**
     * Define el encabezado de la página.
     * Este método se llama automáticamente en cada nueva página.
     * En este caso, para una boleta, solo contiene el título.
     */
    public function Header()
    {
       // Ruta de los logos
        $logo_left = FCPATH . 'assets/img/condoriri.jpeg';
        $logo_right = FCPATH . 'assets/img/uto.jpeg';

        // Agregar logo izquierdo
        if (file_exists($logo_left)) {
            $this->Image($logo_left, 15, 10, 25);
        }

        // Agregar logo derecho
        if (file_exists($logo_right)) {
            $this->Image($logo_right, $this->GetPageWidth() - 40, 10, 25);
        }

        // Título y subtítulos centrados
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 7, "UNIVERSIDAD TECNICA DE ORURO", 0, 1, 'C');
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 5, "CONDORIRI - AGRONOMIA", 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, "Telf.: 5281745 | Interno: 120;  FAX  5242215;  Casilla 49", 0, 1, 'C');
        $this->Cell(0, 5, "Email: dpdi@uto.edu.bo; Internet: www.uto.edu.bo", 0, 1, 'C');

        // Espacio
        $this->Ln(10);
    }

    /**
     * Define el pie de página.
     * En este tipo de boleta, no se necesita un pie de página fijo.
     */
    public function Footer()
    {
        // Pie de página vacío
    }

    /**
     * Genera un reporte detallado para el control de envíos de productos.
     *
     * @param array $envio      Array asociativo con los datos del envío.
     * @param array $productos  Array de productos transferidos.
     * @param bool $consolidado Si es true, indica que los productos ya están consolidados.
     */
    public function generarReporteEnvio($envio, $productos, $consolidado = false)
    {
        // Configuración de la página en formato A4 vertical
        $this->AddPage('P', 'A4');
        $this->SetMargins(20, 20, 20);
        $this->SetAutoPageBreak(true, 25);
        $this->AliasNbPages();

        // --- Título del Reporte ---
        $this->SetFont('Arial', 'B', 16);
        $titulo = $consolidado ? 'REPORTE DE CONTROL DE ENVÍO' : 'REPORTE DE CONTROL DE ENVÍO';
        $this->Cell(0, 10, utf8_decode($titulo), 0, 1, 'C');
        $this->Ln(10);

        // --- Sección de Detalles del Envío ---
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, utf8_decode('Información del Envío'), 0, 1, 'L');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, utf8_decode('Código de Envío: ') . utf8_decode($envio['id']), 0, 1);
        $this->Cell(0, 6, utf8_decode('Fecha de Envío: ') . date('d/m/Y H:i', strtotime($envio['created_at'])), 0, 1);
        $this->Cell(0, 6, utf8_decode('Sucursal de Origen: ') . utf8_decode($envio['sucursal_origen_nombre']), 0, 1);
        $this->Cell(0, 6, utf8_decode('Sucursal de Destino: ') . utf8_decode($envio['sucursal_destino_nombre']), 0, 1);
        $creador = ($envio['creador_nombre'] ?? '') . ' ' . ($envio['creador_apellido'] ?? '') . ' (CI: ' . ($envio['creador_ci'] ?? 'N/A') . ')';
        $transporte = ($envio['transporte_nombre'] ?? '') . ' ' . ($envio['transporte_apellido'] ?? '') . ' (CI: ' . ($envio['transporte_ci'] ?? 'N/A') . ')';

        $this->Cell(0, 6, utf8_decode('Usuario que creó el envío: ') . utf8_decode($creador), 0, 1);
        $this->Cell(0, 6, utf8_decode('Encargado de Transporte: ') . utf8_decode($transporte), 0, 1);
        $this->Cell(0, 6, utf8_decode('Observaciones: ') . utf8_decode($envio['observacion_origen']), 0, 1);
        $this->Ln(10);

        // --- Tabla de Productos ---
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, utf8_decode('Detalle de Productos'), 0, 1, 'L');
        $this->SetFont('Arial', 'B', 9);
        
        $columnWidths = [55, 20, 25, 25, 55]; // Anchos de las columnas
        $this->Cell($columnWidths[0], 7, utf8_decode('Producto'), 1, 0, 'C');
        $this->Cell($columnWidths[1], 7, utf8_decode('Cant.'), 1, 0, 'C');
        $this->Cell($columnWidths[2], 7, utf8_decode('P. Unit.'), 1, 0, 'C');
        $this->Cell($columnWidths[3], 7, utf8_decode('Subtotal'), 1, 0, 'C');
        $this->Cell($columnWidths[4], 7, utf8_decode('Observación'), 1, 1, 'C');
        
        $this->SetFont('Arial', '', 9);
        $totalProductos = 0;
        $totalGeneral = 0;
        foreach ($productos as $producto) {
            $totalProductos += $producto->cantidad;
            $precioUnit = (float)($producto->precio_unitario ?? 0);
            $subtotal = $precioUnit * $producto->cantidad;
            $totalGeneral += $subtotal;
            
            $this->Cell($columnWidths[0], 7, utf8_decode($producto->producto_nombre), 1, 0, 'L');
            $this->Cell($columnWidths[1], 7, $producto->cantidad, 1, 0, 'C');
            $this->Cell($columnWidths[2], 7, number_format($precioUnit, 2), 1, 0, 'R');
            $this->Cell($columnWidths[3], 7, number_format($subtotal, 2), 1, 0, 'R');
            $this->Cell($columnWidths[4], 7, utf8_decode($producto->observacion_origen ?: 'N/A'), 1, 1, 'L');
        }
        $this->Ln(5);

        // --- Totales ---
        $this->SetFont('Arial', 'B', 10);
        $this->Cell($columnWidths[0] + $columnWidths[1], 7, utf8_decode('Total de Productos:'), 1, 0, 'R');
        $this->Cell($columnWidths[2], 7, $totalProductos, 1, 0, 'C');
        $this->Cell($columnWidths[3], 7, utf8_decode('Total Bs:'), 1, 0, 'R');
        $this->Cell($columnWidths[4], 7, number_format($totalGeneral, 2), 1, 1, 'R');
        $this->Ln(15);
        

        // --- Sección de Firmas ---
        $this->SetFont('Arial', '', 10);
        $y = $this->GetY();
        $this->SetXY(20, $y);
        $this->Cell(40, 5, utf8_decode('_________________________'), 0, 0, 'C');
        $this->SetXY(85, $y);
        $this->Cell(40, 5, utf8_decode('_________________________'), 0, 0, 'C');
        $this->SetXY(150, $y);
        $this->Cell(40, 5, utf8_decode('_________________________'), 0, 0, 'C');
        $this->SetXY(20, $y + 5);
        $this->Cell(40, 5, utf8_decode('Firma del Transportista'), 0, 0, 'C');
        $this->SetXY(85, $y + 5);
        $this->Cell(40, 5, utf8_decode('Firma del Técnico'), 0, 0, 'C');
        $this->SetXY(150, $y + 5);
        $this->Cell(40, 5, utf8_decode('Firma del Supervisor'), 0, 0, 'C');
        $this->Ln(15);
        
        $this->Cell(0, 5, utf8_decode('_________________________'), 0, 1, 'C');
        $this->Cell(0, 5, utf8_decode('Firma del Creador del Envío'), 0, 1, 'C');

        // Salida del PDF. La opción 'D' fuerza la descarga del archivo.
        $nombreArchivo = $consolidado ? 'reporte_envio_consolidado_' . $envio['id'] . '.pdf' : 'reporte_envio_' . $envio['id'] . '.pdf';
        $this->Output('D', $nombreArchivo);
    }
}
