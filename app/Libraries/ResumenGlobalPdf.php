<?php

namespace App\Libraries;

require_once APPPATH . 'ThirdParty/fpdf/fpdf.php';

use FPDF;

/**
 * ResumenGlobalPdf
 * Genera el reporte global en PDF con encabezado institucional.
 * Misma estructura que el Excel: 4 bloques + indicadores entre secciones.
 */
class ResumenGlobalPdf extends FPDF
{
    private \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        parent::__construct('L', 'mm', 'A4');
        $this->db = \Config\Database::connect();
        $this->SetAuthor('DTIC');
        $this->SetTitle('Resumen Global Condoriri');
        $this->SetMargins(12, 38, 12);
        $this->SetAutoPageBreak(true, 15);
        $this->SetFont('Arial', '', 8);
        $this->AliasNbPages();
    }

    public function Header(): void
    {
        $logoL = FCPATH . 'assets/img/condoriri.jpeg';
        $logoR = FCPATH . 'assets/img/uto.jpeg';
        $this->SetY(8);
        if (file_exists($logoL)) $this->Image($logoL, 12, 8, 20);
        if (file_exists($logoR)) $this->Image($logoR, $this->GetPageWidth() - 32, 8, 20);
        $this->SetFont('Arial', 'B', 13);
        $this->Cell(0, 6, utf8_decode('UNIVERSIDAD TÉCNICA DE ORURO'), 0, 1, 'C');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 5, utf8_decode('FACULTAD DE CIENCIAS AGRARIAS Y NATURALES'), 0, 1, 'C');
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, utf8_decode('CONDORIRI - LABORATORIO DE INNOVACIÓN'), 0, 1, 'C');
        $this->Cell(0, 4, 'Telf.: 5281745 | Interno: 120 | FAX: 5242215 | Casilla 49', 0, 1, 'C');
        $this->Ln(2);
        $this->SetDrawColor(0, 0, 0);
        $this->Line(12, $this->GetY(), $this->GetPageWidth() - 12, $this->GetY());
        $this->Ln(3);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 7, utf8_decode('RESUMEN GLOBAL CONDORIRI'), 0, 1, 'C');
        $this->Ln(2);
    }

    public function Footer(): void
    {
        $this->SetY(-15);
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(120);
        $this->Cell(0, 4, utf8_decode('Generado el: ' . date('d/m/Y H:i:s') . '   |   Sistema de Gestión CEAC-UTO'), 0, 1, 'L');
        $this->Cell(0, 5, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
        $this->SetTextColor(0);
    }

    // ── Punto de entrada ──────────────────────────────────────────────────────

    public function generar(string $fecha_inicio, string $fecha_fin): void
    {
        $produccion = $this->queryProduccion($fecha_inicio, $fecha_fin);
        $ventas     = $this->queryVentas($fecha_inicio, $fecha_fin);
        $envios     = $this->queryEnvios($fecha_inicio, $fecha_fin);
        $balance    = $this->calcularBalance($produccion, $ventas, $envios);

        $this->AddPage();

        $periodo = $this->periodoTexto($fecha_inicio, $fecha_fin);
        $this->SetFont('Arial', 'I', 9);
        $this->Cell(0, 5, utf8_decode('Período: ' . $periodo), 0, 1, 'C');
        $this->Ln(3);

        $this->bloqueProduccion($produccion);
        $this->indicadoresProduccion($produccion, $fecha_inicio, $fecha_fin);
        $this->Ln(4);
        $this->bloqueVentas($ventas);
        $this->indicadoresVentas($ventas, $fecha_inicio, $fecha_fin);
        $this->Ln(4);
        $this->bloqueEnvios($envios);
        $this->indicadoresEnvios($envios, $fecha_inicio, $fecha_fin);
        $this->Ln(4);
        $this->bloqueBalance($balance);

        $this->Output('D', 'resumen_global_' . str_replace('-', '', $fecha_inicio) . '.pdf');
    }

    // ── Queries (idénticas al servicio Excel) ─────────────────────────────────

    private function queryProduccion(string $fi, string $ff): array
    {
        return $this->db->query("
            SELECT TO_CHAR(i.created_at,'YYYY-MM') AS mes,
                   SUM(i.stock) AS litros_recibidos,
                   COUNT(DISTINCT DATE(i.created_at)) AS dias_activos,
                   SUM(p.cantidad_produccion) AS unidades_producidas,
                   COALESCE(SUM(p.merma),0) AS merma_total,
                   COALESCE(SUM(p.agrega),0) AS agrega_total,
                   COUNT(DISTINCT p.id) AS lotes_produccion
            FROM condoriri.inventarios i
            LEFT JOIN condoriri.productos p ON p.inventario_id=i.id AND p.deleted_at IS NULL
            WHERE i.deleted_at IS NULL AND i.nombre='LECHE'
              AND i.created_at>=? AND i.created_at<=?
            GROUP BY TO_CHAR(i.created_at,'YYYY-MM') ORDER BY mes
        ", [$fi.' 00:00:00', $ff.' 23:59:59'])->getResultArray();
    }

    private function queryVentas(string $fi, string $ff): array
    {
        return $this->db->query("
            SELECT TO_CHAR(v.created_at,'YYYY-MM') AS mes,
                   CASE WHEN EXISTS(SELECT 1 FROM condoriri.detalle_venta dv2
                        WHERE dv2.venta_id=v.id AND dv2.producto_agro_id IS NOT NULL)
                        THEN 'Agro'
                        WHEN s.nombre='ORURO-VENTAS' THEN 'Tienda' ELSE 'Planta' END AS modulo,
                   COUNT(DISTINCT v.id) AS num_ventas,
                   COUNT(DISTINCT CASE WHEN v.tipo_pago='contado' THEN v.id END) AS ventas_contado,
                   COUNT(DISTINCT CASE WHEN v.tipo_pago='credito' THEN v.id END) AS ventas_credito,
                   SUM(CASE WHEN v.tipo_pago='contado' THEN v.monto_total ELSE 0 END) AS ingresos_contado,
                   SUM(CASE WHEN v.tipo_pago='credito' THEN v.monto_total ELSE 0 END) AS ingresos_credito,
                   SUM(v.monto_total) AS ingresos_total
            FROM condoriri.ventas v JOIN condoriri.sucursales s ON s.id=v.sucursal_id
            WHERE v.deleted_at IS NULL AND v.created_at>=? AND v.created_at<=?
            GROUP BY TO_CHAR(v.created_at,'YYYY-MM'),modulo ORDER BY mes,modulo
        ", [$fi.' 00:00:00', $ff.' 23:59:59'])->getResultArray();
    }

    private function queryEnvios(string $fi, string $ff): array
    {
        return $this->db->query("
            SELECT TO_CHAR(e.created_at,'YYYY-MM') AS mes,
                   COUNT(DISTINCT e.id) AS num_envios,
                   COUNT(DISTINCT CASE WHEN e.estado_id=9 THEN e.id END) AS entregados,
                   COUNT(DISTINCT CASE WHEN e.estado_id=2 THEN e.id END) AS en_transito,
                   COUNT(DISTINCT CASE WHEN e.estado_id=1 THEN e.id END) AS pendientes,
                   COALESCE(SUM(tp.cantidad),0) AS unidades_enviadas
            FROM condoriri.envios e
            LEFT JOIN condoriri.transferencias_productos tp ON tp.envio_id=e.id AND tp.deleted_at IS NULL
            WHERE e.deleted_at IS NULL AND e.created_at>=? AND e.created_at<=?
            GROUP BY TO_CHAR(e.created_at,'YYYY-MM') ORDER BY mes
        ", [$fi.' 00:00:00', $ff.' 23:59:59'])->getResultArray();
    }

    private function calcularBalance(array $prod, array $ventas, array $envios): array
    {
        $prodIdx  = array_column($prod,   null, 'mes');
        $envioIdx = array_column($envios, null, 'mes');
        $ventasMes = [];
        foreach ($ventas as $v) {
            $ventasMes[$v['mes']]['num_ventas']     = ($ventasMes[$v['mes']]['num_ventas']     ?? 0) + (int)$v['num_ventas'];
            $ventasMes[$v['mes']]['ingresos_total'] = ($ventasMes[$v['mes']]['ingresos_total'] ?? 0) + (float)$v['ingresos_total'];
        }
        $meses = array_unique(array_merge(array_keys($prodIdx), array_keys($ventasMes), array_keys($envioIdx)));
        sort($meses);
        $balance = [];
        foreach ($meses as $mes) {
            $producido = (float)($prodIdx[$mes]['unidades_producidas'] ?? 0);
            $merma     = (float)($prodIdx[$mes]['merma_total']         ?? 0);
            $agrega    = (float)($prodIdx[$mes]['agrega_total']        ?? 0);
            $enviado   = (int)($envioIdx[$mes]['unidades_enviadas']    ?? 0);
            $balance[] = [
                'mes'       => $mes,
                'producido' => $producido, 'merma' => $merma, 'agrega' => $agrega,
                'n_ventas'  => (int)($ventasMes[$mes]['num_ventas']    ?? 0),
                'ingresos'  => (float)($ventasMes[$mes]['ingresos_total'] ?? 0),
                'enviado'   => $enviado,
                'balance_u' => $producido + $agrega - $merma - $enviado,
            ];
        }
        return $balance;
    }

    // ── Helpers de renderizado ────────────────────────────────────────────────

    private function tituloBanda(string $texto, array $rgb): void
    {
        if ($this->GetY() + 30 > $this->GetPageHeight() - 15) $this->AddPage();
        $this->SetFillColor(...$rgb);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(0, 7, utf8_decode($texto), 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(1);
    }

    private function encabezadoTabla(array $headers, array $anchos): void
    {
        $this->SetFillColor(91, 155, 213);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 7);
        foreach ($headers as $i => $h) {
            $this->Cell($anchos[$i], 6, utf8_decode($h), 1, 0, 'C', true);
        }
        $this->Ln();
        $this->SetTextColor(0, 0, 0);
    }

    private function filaTabla(array $valores, array $anchos, array $alineaciones, bool $par): void
    {
        if ($this->GetY() + 6 > $this->GetPageHeight() - 15) $this->AddPage();
        $rgb = $par ? [235, 243, 251] : [245, 249, 254];
        $this->SetFillColor(...$rgb);
        $this->SetFont('Arial', '', 7);
        foreach ($valores as $i => $v) {
            $this->Cell($anchos[$i], 5, utf8_decode((string)$v), 1, 0, $alineaciones[$i], true);
        }
        $this->Ln();
    }

    private function filaTotalTabla(array $valores, array $anchos, array $alineaciones): void
    {
        $this->SetFillColor(31, 78, 121);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 7);
        foreach ($valores as $i => $v) {
            $this->Cell($anchos[$i], 6, utf8_decode((string)$v), 1, 0, $alineaciones[$i], true);
        }
        $this->Ln();
        $this->SetTextColor(0, 0, 0);
    }

    private function filaIndicador(string $label, string $valor): void
    {
        if ($this->GetY() + 5 > $this->GetPageHeight() - 15) $this->AddPage();
        $pageW = $this->GetPageWidth() - $this->lMargin - $this->rMargin;
        $wL = (int)round($pageW * 0.35);
        $wV = $pageW - $wL;
        $this->SetFillColor(242, 242, 242);
        $this->SetFont('Arial', 'B', 7);
        $this->Cell($wL, 5, utf8_decode($label), 1, 0, 'L', true);
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(31, 78, 121);
        $this->Cell($wV, 5, utf8_decode($valor), 1, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
    }

    private function tituloBandaIndicadores(string $texto): void
    {
        $this->SetFillColor(64, 64, 64);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(0, 6, utf8_decode('  ★  ' . $texto), 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(1);
    }

    private function textoMes(string $mes): string
    {
        $m = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio',
              '07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
        [$y, $n] = explode('-', $mes);
        return ($m[$n] ?? $n) . ' ' . $y;
    }

    private function periodoTexto(string $fi, string $ff): string
    {
        $m = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio',
              '07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
        $di=date('d',strtotime($fi)); $mi=date('m',strtotime($fi)); $yi=date('Y',strtotime($fi));
        $df=date('d',strtotime($ff)); $mf=date('m',strtotime($ff)); $yf=date('Y',strtotime($ff));
        if ($fi===$ff) return "$di de {$m[$mi]} de $yi";
        return "$di de {$m[$mi]} de $yi al $df de {$m[$mf]} de $yf";
    }

    // ── Bloques ───────────────────────────────────────────────────────────────

    private function bloqueProduccion(array $data): void
    {
        $this->tituloBanda('1. PRODUCCIÓN MENSUAL', [55, 86, 35]);
        $pageW = $this->GetPageWidth() - $this->lMargin - $this->rMargin;
        $w = [(int)round($pageW*0.14),(int)round($pageW*0.13),(int)round($pageW*0.10),(int)round($pageW*0.12),(int)round($pageW*0.13),(int)round($pageW*0.10),(int)round($pageW*0.10),0];
        $w[7] = $pageW - array_sum(array_slice($w,0,7));
        $al   = ['L','R','C','R','R','R','R','C'];
        $this->encabezadoTabla(['MES','LITROS RECIBIDOS','DÍAS ACTIVOS','PROM. DIARIO (L)','UNIDADES PROD.','MERMA','AGREGA','LOTES'], $w);
        $par=true; $tL=$tD=$tU=$tM=$tA=$tLo=0.0;
        foreach ($data as $r) {
            $dias=max(1,(int)$r['dias_activos']); $prom=round((float)$r['litros_recibidos']/$dias,1);
            $this->filaTabla([$this->textoMes($r['mes']),number_format((float)$r['litros_recibidos'],1,',','.'),
                $dias,number_format($prom,1,',','.'),number_format((float)$r['unidades_producidas'],1,',','.'),
                number_format((float)$r['merma_total'],1,',','.'),number_format((float)$r['agrega_total'],1,',','.'),(int)$r['lotes_produccion']],$w,$al,$par);
            $tL+=(float)$r['litros_recibidos']; $tD+=$dias; $tU+=(float)$r['unidades_producidas'];
            $tM+=(float)$r['merma_total']; $tA+=(float)$r['agrega_total']; $tLo+=(int)$r['lotes_produccion'];
            $par=!$par;
        }
        $this->filaTotalTabla(['TOTAL',number_format($tL,1,',','.'),'',' — ',number_format($tU,1,',','.'),number_format($tM,1,',','.'),number_format($tA,1,',','.'),(int)$tLo],$w,$al);
    }

    private function bloqueVentas(array $data): void
    {
        $this->tituloBanda('2. VENTAS MENSUALES (TIENDA / PLANTA / AGRO)', [31, 78, 121]);
        $pageW = $this->GetPageWidth() - $this->lMargin - $this->rMargin;
        $w = [(int)round($pageW*0.13),(int)round($pageW*0.09),(int)round($pageW*0.09),(int)round($pageW*0.09),(int)round($pageW*0.14),(int)round($pageW*0.09),(int)round($pageW*0.14),0];
        $w[7] = $pageW - array_sum(array_slice($w,0,7));
        $al   = ['L','L','C','C','R','C','R','R'];
        $this->encabezadoTabla(['MES','MÓDULO','N° VENTAS','VTA. CONTADO','ING. CONTADO (Bs)','VTA. CRÉDITO','ING. CRÉDITO (Bs)','TOTAL (Bs)'], $w);
        $par=true; $tV=$tVC=$tIC=$tVR=$tIR=$tT=0.0;
        foreach ($data as $r) {
            $this->filaTabla([$this->textoMes($r['mes']),$r['modulo'],(int)$r['num_ventas'],(int)$r['ventas_contado'],
                number_format((float)$r['ingresos_contado'],2,',','.'), (int)$r['ventas_credito'],
                number_format((float)$r['ingresos_credito'],2,',','.'),number_format((float)$r['ingresos_total'],2,',','.')],$w,$al,$par);
            $tV+=(int)$r['num_ventas']; $tVC+=(int)$r['ventas_contado']; $tIC+=(float)$r['ingresos_contado'];
            $tVR+=(int)$r['ventas_credito']; $tIR+=(float)$r['ingresos_credito']; $tT+=(float)$r['ingresos_total'];
            $par=!$par;
        }
        $this->filaTotalTabla(['TOTAL','',(int)$tV,(int)$tVC,number_format($tIC,2,',','.'),(int)$tVR,number_format($tIR,2,',','.'),number_format($tT,2,',','.')],$w,$al);
    }

    private function bloqueEnvios(array $data): void
    {
        $this->tituloBanda('3. ENVÍOS MENSUALES', [123, 63, 0]);
        $pageW = $this->GetPageWidth() - $this->lMargin - $this->rMargin;
        $w = [(int)round($pageW*0.18),(int)round($pageW*0.14),(int)round($pageW*0.14),(int)round($pageW*0.14),(int)round($pageW*0.14),0];
        $w[5] = $pageW - array_sum(array_slice($w,0,5));
        $al   = ['L','C','C','C','C','C'];
        $this->encabezadoTabla(['MES','N° ENVÍOS','ENTREGADOS','EN TRÁNSITO','PENDIENTES','UNIDADES ENVIADAS'], $w);
        $par=true; $tE=$tEnt=$tTr=$tP=$tU=0;
        foreach ($data as $r) {
            $this->filaTabla([$this->textoMes($r['mes']),(int)$r['num_envios'],(int)$r['entregados'],
                (int)$r['en_transito'],(int)$r['pendientes'],number_format((int)$r['unidades_enviadas'],0,',','.')],$w,$al,$par);
            $tE+=(int)$r['num_envios']; $tEnt+=(int)$r['entregados']; $tTr+=(int)$r['en_transito'];
            $tP+=(int)$r['pendientes']; $tU+=(int)$r['unidades_enviadas'];
            $par=!$par;
        }
        $this->filaTotalTabla(['TOTAL',$tE,$tEnt,$tTr,$tP,number_format($tU,0,',','.')],$w,$al);
    }

    private function bloqueBalance(array $data): void
    {
        $this->tituloBanda('4. BALANCE MENSUAL ESTIMADO', [74, 35, 90]);
        $pageW = $this->GetPageWidth() - $this->lMargin - $this->rMargin;
        $w = [(int)round($pageW*0.13),(int)round($pageW*0.12),(int)round($pageW*0.10),(int)round($pageW*0.10),(int)round($pageW*0.09),(int)round($pageW*0.14),(int)round($pageW*0.10),0];
        $w[7] = $pageW - array_sum(array_slice($w,0,7));
        $al   = ['L','R','R','R','C','R','C','R'];
        $this->encabezadoTabla(['MES','PRODUCIDO (uds)','MERMA','AGREGA','N° VENTAS','INGRESOS (Bs)','ENVIADO (uds)','BALANCE (uds)'], $w);
        $par=true; $tP=$tM=$tA=$tV=$tI=$tE=$tB=0.0;
        foreach ($data as $r) {
            $this->filaTabla([$this->textoMes($r['mes']),number_format((float)$r['producido'],1,',','.'),
                number_format((float)$r['merma'],1,',','.'),number_format((float)$r['agrega'],1,',','.'),(int)$r['n_ventas'],
                number_format((float)$r['ingresos'],2,',','.'),(int)$r['enviado'],number_format((float)$r['balance_u'],1,',','.')],$w,$al,$par);
            $tP+=(float)$r['producido']; $tM+=(float)$r['merma']; $tA+=(float)$r['agrega'];
            $tV+=(int)$r['n_ventas']; $tI+=(float)$r['ingresos']; $tE+=(int)$r['enviado']; $tB+=(float)$r['balance_u'];
            $par=!$par;
        }
        $this->filaTotalTabla(['TOTAL',number_format($tP,1,',','.'),number_format($tM,1,',','.'),number_format($tA,1,',','.'),(int)$tV,number_format($tI,2,',','.'),(int)$tE,number_format($tB,1,',','.')],$w,$al);
    }

    // ── Indicadores ───────────────────────────────────────────────────────────

    private function indicadoresProduccion(array $produccion, string $fi, string $ff): void
    {
        if (empty($produccion)) return;
        $this->tituloBandaIndicadores('INDICADORES DE PRODUCCIÓN');
        $r = $this->db->query("SELECT DATE(created_at) AS dia, SUM(stock) AS litros FROM condoriri.inventarios WHERE deleted_at IS NULL AND nombre='LECHE' AND created_at>=? AND created_at<=? GROUP BY DATE(created_at) ORDER BY litros DESC LIMIT 1", [$fi.' 00:00:00',$ff.' 23:59:59'])->getRow();
        if ($r) $this->filaIndicador('Día con más leche recibida', date('d/m/Y',strtotime($r->dia)).' — '.number_format((float)$r->litros,0,',','.').' L');
        $r2 = $this->db->query("SELECT DATE(p.created_at) AS dia, SUM(p.cantidad_produccion) AS total FROM condoriri.productos p JOIN condoriri.inventarios i ON i.id=p.inventario_id WHERE p.deleted_at IS NULL AND i.deleted_at IS NULL AND i.nombre='LECHE' AND p.created_at>=? AND p.created_at<=? GROUP BY DATE(p.created_at) ORDER BY total DESC LIMIT 1", [$fi.' 00:00:00',$ff.' 23:59:59'])->getRow();
        if ($r2) $this->filaIndicador('Día con más unidades producidas', date('d/m/Y',strtotime($r2->dia)).' — '.number_format((float)$r2->total,0,',','.').' uds');
        $r3 = $this->db->query("SELECT p.nombre, SUM(p.cantidad_produccion) AS total FROM condoriri.productos p JOIN condoriri.inventarios i ON i.id=p.inventario_id WHERE p.deleted_at IS NULL AND i.deleted_at IS NULL AND i.nombre='LECHE' AND p.created_at>=? AND p.created_at<=? GROUP BY p.nombre ORDER BY total DESC LIMIT 1", [$fi.' 00:00:00',$ff.' 23:59:59'])->getRow();
        if ($r3) $this->filaIndicador('Producto más producido', $r3->nombre.' — '.number_format((float)$r3->total,0,',','.').' uds');
    }

    private function indicadoresVentas(array $ventas, string $fi, string $ff): void
    {
        if (empty($ventas)) return;
        $this->tituloBandaIndicadores('INDICADORES DE VENTAS');
        $contado = array_sum(array_column($ventas,'ventas_contado'));
        $credito = array_sum(array_column($ventas,'ventas_credito'));
        $this->filaIndicador('Tipo de pago más frecuente', $contado>=$credito ? 'Contado ('.number_format((int)$contado,0,',','.').' ventas)' : 'Crédito ('.number_format((int)$credito,0,',','.').' ventas)');
        $r = $this->db->query("SELECT DATE(created_at) AS dia, SUM(monto_total) AS total FROM condoriri.ventas WHERE deleted_at IS NULL AND created_at>=? AND created_at<=? GROUP BY DATE(created_at) ORDER BY total DESC LIMIT 1", [$fi.' 00:00:00',$ff.' 23:59:59'])->getRow();
        if ($r) $this->filaIndicador('Día con mayores ingresos', date('d/m/Y',strtotime($r->dia)).' — Bs. '.number_format((float)$r->total,2,',','.'));
        $porMod=[]; foreach($ventas as $v) $porMod[$v['modulo']]=($porMod[$v['modulo']]??0)+(float)$v['ingresos_total'];
        arsort($porMod); $mod=array_key_first($porMod);
        $this->filaIndicador('Módulo con más ingresos', $mod.' — Bs. '.number_format($porMod[$mod],2,',','.'));
        $r2 = $this->db->query("SELECT p.nombre, SUM(dv.cantidad) AS total FROM condoriri.detalle_venta dv JOIN condoriri.productos p ON p.id=dv.producto_id JOIN condoriri.ventas v ON v.id=dv.venta_id WHERE v.deleted_at IS NULL AND dv.deleted_at IS NULL AND v.created_at>=? AND v.created_at<=? GROUP BY p.nombre ORDER BY total DESC LIMIT 1", [$fi.' 00:00:00',$ff.' 23:59:59'])->getRow();
        if ($r2) $this->filaIndicador('Producto más vendido', $r2->nombre.' — '.number_format((float)$r2->total,0,',','.').' uds');
        $r3 = $this->db->query("SELECT COALESCE(p.nombre,ce.nombre,c.nombre_completo) AS cliente, COUNT(DISTINCT v.id) AS num_ventas FROM condoriri.ventas v LEFT JOIN condoriri.clientes c ON c.id=v.cliente_id LEFT JOIN condoriri.clientes_externos ce ON ce.id=v.cliente_externo_id LEFT JOIN public.personas p ON p.id_persona=v.personal_uto_id WHERE v.deleted_at IS NULL AND (v.cliente_id IS NOT NULL OR v.personal_uto_id IS NOT NULL OR v.cliente_externo_id IS NOT NULL) AND v.created_at>=? AND v.created_at<=? GROUP BY cliente ORDER BY num_ventas DESC LIMIT 1", [$fi.' 00:00:00',$ff.' 23:59:59'])->getRow();
        if ($r3 && $r3->cliente) $this->filaIndicador('Cliente más frecuente', $r3->cliente.' — '.$r3->num_ventas.' compras');
    }

    private function indicadoresEnvios(array $envios, string $fi, string $ff): void
    {
        if (empty($envios)) return;
        $this->tituloBandaIndicadores('INDICADORES DE ENVÍOS');
        $r = $this->db->query("SELECT DATE(created_at) AS dia, COUNT(*) AS total FROM condoriri.envios WHERE deleted_at IS NULL AND created_at>=? AND created_at<=? GROUP BY DATE(created_at) ORDER BY total DESC LIMIT 1", [$fi.' 00:00:00',$ff.' 23:59:59'])->getRow();
        if ($r) $this->filaIndicador('Día con más envíos', date('d/m/Y',strtotime($r->dia)).' — '.$r->total.' envíos');
        $totE=array_sum(array_column($envios,'num_envios')); $totEnt=array_sum(array_column($envios,'entregados'));
        $pct=$totE>0?round($totEnt/$totE*100,1):0;
        $this->filaIndicador('Tasa de entrega', $pct.'% ('.$totEnt.' de '.$totE.' envíos entregados)');
        $r2 = $this->db->query("SELECT s.nombre, COUNT(*) AS total FROM condoriri.envios e JOIN condoriri.sucursales s ON s.id=e.sucursal_destino_id WHERE e.deleted_at IS NULL AND e.created_at>=? AND e.created_at<=? GROUP BY s.nombre ORDER BY total DESC LIMIT 1", [$fi.' 00:00:00',$ff.' 23:59:59'])->getRow();
        if ($r2) $this->filaIndicador('Destino más frecuente', $r2->nombre.' — '.$r2->total.' envíos');
    }
}
