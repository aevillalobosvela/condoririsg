
    /**
     * Genera el reporte de ventas al contado con formato matricial (Pivot).
     */
    public function generarReporteContadoMatrix(array $reportData, array $filters)
    {
        $fecha_inicio = $filters['fecha_inicio'];
        $fecha_fin = $filters['fecha_fin'];
        
        $this->setReporteTitle('VENTAS AL CONTADO - TIENDA CEAC');
        $this->AddPage('P', 'A4'); // Puede ser 'L' si hay muchos productos, por ahora 'P' según imagen
        $this->SetMargins(10, 10, 10);
        $this->AliasNbPages();

        // --- 1. Procesamiento de Datos ---
        $productosUnicos = []; // [nombre => ['precio' => X, 'unidad' => U, 'total_cantidad' => 0]]
        $ventasAgrupadas = []; // [venta_id => ['cliente' => X, 'notas' => N, 'total_venta' => 0, 'items' => [prod => [q, bs]]]]
        
        $totalGeneralBs = 0;
        $totalGeneralCant = 0; // Solo referencial, suma de cantidades de todos los productos

        foreach ($reportData as $item) {
            if ($item->estado_venta != 1) continue;
            if (strtolower($item->tipo_pago) !== 'contado') continue; // Solo contado

            $prodNombre = utf8_decode($item->producto_nombre);
            
            // Registrar producto único
            if (!isset($productosUnicos[$prodNombre])) {
                $productosUnicos[$prodNombre] = [
                    'precio' => $item->precio_unitario, // Asumimos precio constante por ahora
                    'unidad' => 'UNIDAD', // Default, si tienes el campo unidad úsalo aquí
                    'total_cantidad' => 0
                ];
            }
            $productosUnicos[$prodNombre]['total_cantidad'] += $item->cantidad;

            // Agrupar venta
            $ventaId = $item->venta_id;
            if (!isset($ventasAgrupadas[$ventaId])) {
                $cliente = $item->cliente_nombre ?? 'Consumidor Final';
                // Limpieza de nombre si es "Sin Nombre"
                if (stripos($cliente, 'Sin Nombre') !== false) {
                    $cliente = ''; 
                }

                $ventasAgrupadas[$ventaId] = [
                    'cliente' => utf8_decode($cliente),
                    'notas' => $item->venta_id ?? $ventaId,
                    'total_venta' => 0, // Se calcula sumarizando items para precisión
                    'items' => []
                ];
            }

            // Agregar item a la venta
            if (!isset($ventasAgrupadas[$ventaId]['items'][$prodNombre])) {
                $ventasAgrupadas[$ventaId]['items'][$prodNombre] = ['q' => 0, 'bs' => 0];
            }
            $ventasAgrupadas[$ventaId]['items'][$prodNombre]['q'] += $item->cantidad;
            $ventasAgrupadas[$ventaId]['items'][$prodNombre]['bs'] += $item->subtotal_item;
            
            $ventasAgrupadas[$ventaId]['total_venta'] += $item->subtotal_item;

            $totalGeneralBs += $item->subtotal_item;
            $totalGeneralCant += $item->cantidad;
        }

        // Ordenar productos alfabéticamente para consistencia en columnas
        ksort($productosUnicos);
        
        // --- 2. Encabezado Resumen (Tabla Superior) ---
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(0, 5, utf8_decode("Del: " . date('d de F de Y', strtotime($fecha_inicio)) . " Al: " . date('d de F de Y', strtotime($fecha_fin))), 0, 1, 'L');
        $this->SetX($this->GetPageWidth() - 60);
        $this->Cell(50, 5, "TOTAL: " . number_format($totalGeneralBs, 2, ',', '.') . " Bs.", 0, 1, 'R');
        $this->Ln(2);

        // Tabla de Resumen
        // Filas: Total Bolivianos, Total Cantidades, Productos, Unidades, Precios
        // Columnas: Headers + 1 columna por cada producto
        
        $colWidth = 25; // Ancho base col producto
        $labelWidth = 50;
        
        // Ajuste dinámico de ancho si hay muchos productos (básico)
        $numProds = count($productosUnicos);
        if ($numProds > 0) {
            $availableWidth = $this->GetPageWidth() - 20 - $labelWidth;
            $colWidth = min(25, $availableWidth / $numProds);
        }

        $this->SetFillColor(220, 220, 220);
        $this->SetFont('Arial', 'B', 8);

        // Fila 1: Total Bolivianos
        $this->Cell($labelWidth, 5, 'TOTAL BOLIVIANOS:', 1, 0, 'R', true);
        foreach ($productosUnicos as $prod => $info) {
             // Calculamos total Bs por producto
             $totalBsProd = $info['precio'] * $info['total_cantidad']; // Aprox si precio varia
             $this->Cell($colWidth, 5, round($totalBsProd), 1, 0, 'C');
        }
        $this->Cell(0, 5, number_format($totalGeneralBs, 2, ',', '.'), 1, 1, 'R', true); // Total final derecha

        // Fila 2: Total Cantidades
        $this->Cell($labelWidth, 5, 'TOTAL CANTIDADES:', 1, 0, 'R', true);
        foreach ($productosUnicos as $prod => $info) {
            $this->Cell($colWidth, 5, $info['total_cantidad'], 1, 0, 'C');
        }
        $this->Cell(0, 5, $totalGeneralCant, 1, 1, 'R', true);

        // Fila 3: Productos (Nombres)
        $this->Cell($labelWidth, 8, 'PRODUCTOS:', 1, 0, 'R', true);
        $x = $this->GetX();
        $y = $this->GetY();
        foreach ($productosUnicos as $prod => $info) {
            $currX = $this->GetX();
            // MultiCell simulado para nombres largos, o recortar
            $this->SetFont('Arial', '', 6);
            $this->Cell($colWidth, 8, substr($prod, 0, 15), 1, 0, 'C'); 
            // TODO: Mejorar renderizado de nombre producto si es muy largo
        }
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(0, 8, "TOTAL", 1, 1, 'C', true);

        // Fila 4: Unidades (Placeholder, usamos 'PIEZA' o lo que tenga)
        $this->Cell($labelWidth, 5, 'UNIDADES DE MEDIDA:', 1, 0, 'R', true);
        foreach ($productosUnicos as $prod => $info) {
            $this->Cell($colWidth, 5, 'PIEZA', 1, 0, 'C'); // Placeholder
        }
        $this->Cell(0, 5, number_format($totalGeneralBs, 2, ',', '.'), 1, 1, 'R', true);

         // Fila 5: Precios
        $this->Cell($labelWidth, 5, 'PRECIOS:', 1, 0, 'R', true);
        foreach ($productosUnicos as $prod => $info) {
            $this->Cell($colWidth, 5, number_format($info['precio'], 0), 1, 0, 'C'); // Mostrar entero si es posible como en img
        }
        // Total esperado (repetido del encabezado?) En la img dice 6.195,00
        $this->Cell(0, 5, number_format($totalGeneralBs, 2, ',', '.'), 1, 1, 'R', true);
        
        $this->Ln(5);

        // --- 3. Tabla Principal (Detalle) ---
        // Header
        $wNombre = 50; 
        $wNota = 25;
        $wTotal = 25;
        $wProdCol = $colWidth; // Mismo ancho que arriba
        
        $this->SetFillColor(200, 200, 200);
        $this->Cell($wNombre, 5, 'APELLIDOS Y NOMBRES:', 1, 0, 'L', true);
        
        // Sub-columnas Q y Bs por producto
        // OJO: La imagen muestra 2 subcolumnas por producto: Q y Bs.
        // Si el ancho calculado $wProdCol es pequeño, dividirlo en 2 será ilegible.
        // Asumiremos que $wProdCol es para el CONJUNTO (Q+Bs) o simplificamos.
        // Viendo la imagen: "QUESO..." ocupa 2 columnas pequeñas Q y Bs debajo.
        // Ajustaremos: Headers de arriba (Productos) abarcan 2 subcolumnas.
        
        // Re-calibración de anchos para la tabla principal
        // Producto Width = 2 * SubColWidth. 
        // Si $colWidth arriba era X, ahora cada subcol es X/2.
        
        $subColW = $colWidth / 2;
        
        foreach ($productosUnicos as $prod => $info) {
            $this->Cell($subColW, 5, 'Q', 1, 0, 'C', true);
            $this->Cell($subColW, 5, 'Bs', 1, 0, 'C', true);
        }
        $this->Cell($wNota, 5, 'Nro. Venta', 1, 0, 'C', true);
        $this->Cell(0, 5, 'TOTAL', 1, 1, 'C', true);

        $this->SetFont('Arial', '', 7);
        $fill = false;

        foreach ($ventasAgrupadas as $venta) {
            $this->SetFillColor(245, 245, 245);
            
            // Nombre Cliente
            $this->Cell($wNombre, 5, substr($venta['cliente'], 0, 30), 1, 0, 'L', $fill);
            
            // Items por producto
            foreach ($productosUnicos as $prod => $info) {
                if (isset($venta['items'][$prod])) {
                    $q = $venta['items'][$prod]['q'];
                    $bs = $venta['items'][$prod]['bs'];
                    // Mostrar vacio si es 0 para limpieza visual (como en excel)
                    $qStr = $q > 0 ? $q : '';
                    $bsStr = $bs > 0 ? number_format($bs, 0) : ''; 
                    
                    $this->Cell($subColW, 5, $qStr, 1, 0, 'C', $fill);
                    $this->Cell($subColW, 5, $bsStr, 1, 0, 'R', $fill);
                } else {
                    $this->Cell($subColW, 5, '', 1, 0, 'C', $fill);
                    $this->Cell($subColW, 5, '', 1, 0, 'C', $fill);
                }
            }
            
            // Nota y Total
            $this->Cell($wNota, 5, $venta['notas'], 1, 0, 'C', $fill);
            $this->Cell(0, 5, number_format($venta['total_venta'], 2), 1, 1, 'R', $fill);
            
            $fill = !$fill; // Alternar color si se quiere
        }

        $this->Ln(5);
        $this->SetFont('Arial', 'B', 10);
        $literal = $this->numeroALiteral($totalGeneralBs);
        $this->Cell(0, 6, "TOTAL VENTAS AL CONTADO: " . strtoupper($literal) . " BOLIVIANOS", 0, 1, 'L');
        
        $this->Ln(10);
        $this->Cell(0, 5, utf8_decode("NOTA.- El día " . strftime('%A, %d de %B de %Y', strtotime($fecha_inicio)) . ", No hubo ventas al contado"), 0, 1, 'L'); // Placeholder texto nota

        // Firmas (Placeholder)
        $this->Ln(30);
        // ... Logica firmas si necesaria
        
        $filename = 'reporte_contado_matrix_' . $fecha_inicio . '.pdf';
        $this->Output('D', $filename);
    }
    
    private function numeroALiteral($monto)
    {
        // Implementación básica o placeholder
        // Para producción robusta usar librería NumberToWords
        $monto = (float)$monto;
        $formatter = new \NumberFormatter("es", \NumberFormatter::SPELLOUT);
        return strtoupper($formatter->format($monto));
    }
