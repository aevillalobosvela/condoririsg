<?php

namespace App\Models\Venta;

use CodeIgniter\Model;

class VentaModel extends Model
{

    protected $table = 'condoriri.ventas';
    protected $primaryKey = 'id';


    protected $returnType = 'object';

    protected $allowedFields = [
        'code',
        'cliente_id',
        'sucursal_id',
        'tipo_pago',
        'monto_total',
        'estado',
        'observaciones',
        'user_id',
        'personal_uto_id'
    ];


    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';


    protected $useSoftDeletes = true;
    protected $deletedField  = 'deleted_at';


    protected $skipValidation = true;

    /**
     * Genera código de venta según sucursal y tipo de pago
     * @param int $sucursal_id ID de la sucursal (2=Tienda, 4=Planta)
     * @param string $tipo_pago Tipo de pago ('contado' o 'credito')
     * @return string Código generado
     */
    public function generarCodigoVenta(int $sucursal_id, string $tipo_pago): string
    {
        $prefijo_sucursal = ($sucursal_id == 2) ? 'SC' : 'PP';
        $prefijo_tipo = (strtolower($tipo_pago) == 'contado') ? 'CO' : 'CR';
        $seq_name = 'seq_venta_' . strtolower($prefijo_sucursal . '_' . $prefijo_tipo);

        $query = $this->db->query("SELECT nextval('condoriri.{$seq_name}') as numero");
        $numero = $query->getRow()->numero;

        return "{$prefijo_sucursal}-{$prefijo_tipo}-" . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Recupera todas las ventas no eliminadas con el nombre completo del cliente.
     * Utiliza el constructor explícito para evitar conflictos de Soft Delete en CodeIgniter.
     *
     * @return array<object> Retorna un array de objetos de venta, cada uno con un campo 'cliente_nombre'.
     */
    public function getVentasWithClienteName(): array
    {
        $builder = $this->db->table('condoriri.ventas v');
        $builder->select("v.*, COALESCE(c.nombre_completo, 'Consumidor Final') AS cliente_nombre")
            ->join('condoriri.clientes c', 'c.id = v.cliente_id', 'left')
            ->where('v.deleted_at', null)
            ->orderBy('v.created_at', 'DESC');

        $query = $builder->get();
        if (! $query) {
            log_message('error', 'Error en consulta getVentasWithClienteName: ' . $this->db->error()['message']);
            return [];
        }

        return $query->getResult();
    }

    /**
     * Obtiene una sola venta con el nombre del cliente y sucursal.
     * @param int $id ID de la venta
     * @return object|null Objeto de la venta o null si no se encuentra.
     */
    public function getVentaWithRelations(int $id)
    {
        return $this->select('condoriri.ventas.*, COALESCE(condoriri.clientes.nombre_completo, "Consumidor Final") as cliente_nombre, sucursales.nombre as sucursal_nombre, usuarios.nombre_completo as user_nombre')
            ->join('condoriri.clientes', 'condoriri.clientes.id = condoriri.ventas.cliente_id', 'left')
            ->join('condoriri.sucursales', 'condoriri.sucursales.id = condoriri.ventas.sucursal_id')
            ->join('condoriri.usuarios', 'condoriri.usuarios.id = condoriri.ventas.user_id')
            ->where('condoriri.ventas.id', $id)
            ->where('condoriri.ventas.deleted_at', null) // Soft Delete explícito
            ->first();
    }

    /**
     * Filtra ventas basándose en criterios como cliente, sucursal, estado o código.
     * @param array $filters Array asociativo de filtros.
     * @return array<object> Lista de ventas filtradas.
     */
    public function getFilteredVentas(array $filters): array
    {
        // Usamos el builder estándar ya que no hay conflicto de Soft Delete cuando se usan where() y findAll()
        $this->select('condoriri.ventas.*, COALESCE(condoriri.clientes.nombre_completo, "Consumidor Final") as cliente_nombre');
        $this->join('condoriri.clientes', 'condoriri.clientes.id = condoriri.ventas.cliente_id', 'left');

        if (!empty($filters['cliente_id'])) {
            $this->where('ventas.cliente_id', $filters['cliente_id']);
        }

        if (!empty($filters['sucursal_id'])) {
            $this->where('ventas.sucursal_id', $filters['sucursal_id']);
        }

        if (isset($filters['estado']) && $filters['estado'] !== '') {
            $this->where('ventas.estado', $filters['estado']);
        }

        if (!empty($filters['search'])) {
            $this->groupStart()
                ->like('ventas.code', $filters['search'])
                ->orLike('clientes.nombre_completo', $filters['search'])
                ->groupEnd();
        }

        $this->orderBy('ventas.created_at', 'DESC');

        //findAll() aplicará automáticamente el filtro deleted_at = NULL y devolverá objetos.
        return $this->findAll();
    }

    /**
     * Obtiene el reporte detallado de ventas (con productos) para un rango de fechas.
     * Se asume la existencia de la tabla 'condoriri.venta_detalles' y 'condoriri.productos'.
     *
     * @param string $fecha_inicio Fecha de inicio (YYYY-MM-DD).
     * @param string $fecha_fin Fecha de fin (YYYY-MM-DD).
     * @return array Array de objetos con el detalle de ventas agrupado por producto.
     */
    public function getDailySalesReportData(string $fecha_inicio, string $fecha_fin, ?string $tipo = null): array
    {
        $db = \Config\Database::connect();

        // ✅ Obtener sucursal_id de la sesión (crítico)
        $sucursalId = session()->get('sucursal_id');
        if (empty($sucursalId)) {
            log_message('error', 'No se encontró sucursal_id en la sesión para getDailySalesReportData');
            return [];
        }

        $fecha_inicio_full = $fecha_inicio . ' 00:00:00';
        $fecha_fin_full = $fecha_fin . ' 23:59:59';

        // Construir condiciones dinámicas
        $whereConditions = [
            'v.deleted_at IS NULL',
            'v.sucursal_id = ?',  // ✅ Filtro por sucursal (clave)
            'v.created_at >= ?',
            'v.created_at <= ?'
        ];

        $params = [$sucursalId, $fecha_inicio_full, $fecha_fin_full];

        // Filtro opcional por tipo de pago (contado/credito)
        if ($tipo === 'contado') {
            $whereConditions[] = "LOWER(v.tipo_pago) = 'contado'";
        } elseif ($tipo === 'credito') {
            $whereConditions[] = "LOWER(v.tipo_pago) = 'credito'";
        }

        $whereClause = implode(' AND ', $whereConditions);

        $query = $db->query("
        SELECT
            v.id AS venta_id,
            v.code AS codigo_venta,
            v.created_at AS fecha_venta,
            v.monto_total AS monto_total_venta,
            v.tipo_pago,
            v.estado AS estado_venta,
            COALESCE(c.nombre_completo, 'Consumidor Final') AS cliente_nombre,
            s.nombre AS sucursal_nombre,
            vd.cantidad,
            vd.precio_unitario,
            vd.subtotal AS subtotal_item,
            ss.producto AS producto_nombre,
            -- Datos del personal UTO (solo relevante para créditos, pero lo dejamos)
            COALESCE(p.nombre, 'No asignado') AS personal_nombre,
            p.dip AS personal_dip,
            COALESCE(se.seccion, 'Sin sección') AS personal_seccion
        FROM
            condoriri.ventas v
        LEFT JOIN
            condoriri.clientes c ON c.id = v.cliente_id
        INNER JOIN
            condoriri.sucursales s ON s.id = v.sucursal_id
        LEFT JOIN
            condoriri.detalle_venta vd ON vd.venta_id = v.id
        LEFT JOIN
            condoriri.stock_sucursales ss ON ss.id = vd.stock_id
        -- JOIN con personal UTO (solo si hay personal_uto_id)
        LEFT JOIN
            public.personas p ON p.id_persona = v.personal_uto_id
        LEFT JOIN
            rrhh.empleados e ON e.id_persona = p.id_persona AND e.id_estado = true
        LEFT JOIN
            rrhh.secciones se ON se.id_seccion = e.id_seccion
        WHERE
            {$whereClause}
        ORDER BY
            v.created_at ASC, v.id ASC
    ", $params);

        if (!$query) {
            $error = $db->error();
            log_message('error', 'Error en getDailySalesReportData: ' . $error['message'] . ' | Query: ' . $db->getLastQuery());
            return [];
        }

        return $query->getResult(); // array de objetos stdClass
    }


    public function getDailySalesReportDataByType(string $fecha_inicio, string $fecha_fin, string $tipo_pago): array
    {
        $db = \Config\Database::connect();
        $fecha_inicio_full = $fecha_inicio . ' 00:00:00';
        $fecha_fin_full    = $fecha_fin . ' 23:59:59';

        // Base de la consulta
        $sql = "
        SELECT
            v.id AS venta_id,
            v.code AS codigo_venta,
            v.created_at AS fecha_venta,
            v.monto_total AS monto_total_venta,
            v.tipo_pago,
            v.estado AS estado_venta,
            v.observaciones AS observaciones_venta,
            v.user_id,
            v.sucursal_id,
            v.personal_uto_id,

            -- Nombre explícito de la Persona UTO para referencia en el PDF
            p.nombre AS nombre_personal_uto, 
            
            -- Nombre del Comprador principal: Usa nombre de Persona (para crédito) si existe, si no usa cliente (para contado)
            COALESCE(p.nombre, c.nombre_completo, 'Consumidor Final') AS cliente_nombre,
            
            -- Datos del personal UTO (para ventas a crédito)
            p.dip,
            cargos.cargo,
            secciones.seccion,
            
            -- Estado de empleado UTO activo (Corregido para INT)
            COALESCE(e.\"id_estado\", 0) AS es_empleado_uto, 

            -- Detalle de productos
            vd.cantidad,
            vd.precio_unitario,
            vd.subtotal AS subtotal_item,
            ss.producto AS producto_nombre

        FROM condoriri.ventas v
        LEFT JOIN public.personas p ON p.id_persona = v.personal_uto_id
        -- Unir con empleados y filtrar solo activos para la bandera es_empleado_uto (CORRECCIÓN: Se asume que 1 es ACTIVO)
        LEFT JOIN rrhh.empleados e ON p.id_persona = e.id_persona AND e.\"id_estado\" = 1 
        LEFT JOIN rrhh.cargos cargos ON cargos.id_cargo = e.id_cargo
        LEFT JOIN rrhh.secciones secciones ON secciones.id_seccion = e.id_seccion
        LEFT JOIN condoriri.clientes c ON c.id = v.cliente_id
        LEFT JOIN condoriri.detalle_venta vd ON vd.venta_id = v.id
        LEFT JOIN condoriri.stock_sucursales ss ON ss.id = vd.stock_id

        WHERE v.deleted_at IS NULL
          AND v.estado = 1
          AND v.created_at >= ?
          AND v.created_at <= ?";

        $params = [$fecha_inicio_full, $fecha_fin_full];

        // 🔴 Aplicar filtro por tipo de pago solo si NO es 'general'
        if ($tipo_pago === 'contado') {
            $sql .= " AND v.tipo_pago = ?";
            $params[] = 'contado';
        } elseif ($tipo_pago === 'credito') {
            $sql .= " AND v.tipo_pago = ?";
            $params[] = 'credito';
        }
        // Si es 'general', no se agrega condición → trae ambos

        $sql .= " ORDER BY v.created_at ASC";

        $query = $db->query($sql, $params);

        return $query ? $query->getResult() : [];
    }



    // En app/Models/VentaModel.php

    public function getSalesDataForGraph(string $fechaInicio, string $fechaFin, int $sucursalId)
    {
        // Definimos los filtros de la consulta
        $dateFilter = "created_at BETWEEN '{$fechaInicio} 00:00:00' AND '{$fechaFin} 23:59:59'";
        $sucursalFilter = "sucursal_id = {$sucursalId}";
        $estadoFilter = "estado != 'ANULADO'";

        // --- 1. Obtener Ventas Desglosadas por Tipo de Pago (Contado/Crédito) ---

        $builder = $this->db->table($this->table);

        $builder->select('
        LOWER(tipo_pago) AS tipo_pago,
        COUNT(id) AS cantidad, 
        COALESCE(SUM(monto_total), 0.00) AS monto
    ');

        $builder->where($sucursalFilter);
        $builder->where($dateFilter);
        $builder->where($estadoFilter);
        $builder->groupBy('tipo_pago');

        $results = $builder->get()->getResultArray();

        // Inicializar la estructura de desglose y el total general
        $desgloseVentas = [
            'contado' => ['cantidad' => 0, 'monto' => 0.00],
            'credito' => ['cantidad' => 0, 'monto' => 0.00],
        ];
        $totalGeneral = ['cantidad' => 0, 'monto' => 0.00];

        foreach ($results as $row) {
            $tipo = trim(strtolower($row['tipo_pago']));

            // 1. Llenar el desglose
            if (isset($desgloseVentas[$tipo])) {
                $desgloseVentas[$tipo] = [
                    'cantidad' => (int) $row['cantidad'],
                    'monto' => (float) $row['monto']
                ];
            }

            // 2. Sumar al total general
            $totalGeneral['cantidad'] += (int) $row['cantidad'];
            $totalGeneral['monto'] += (float) $row['monto'];
        }

        // --- 2. Preparar el Output Final para el JavaScript ---

        $data = [
            // Total General (Suma de todo: Cantidad y Monto)
            'ventasTotal' => $totalGeneral,

            // Desglose por tipo de pago (Contado/Crédito)
            'ventasPorTipo' => $desgloseVentas,

            // Producción por Producto (Mantenida vacía, lista para implementarse)
            'produccionPorProducto' => []
        ];

        return $data;
    }






    public function getDailySalesReportDataInve(string $fecha_inicio, string $fecha_fin, ?string $tipo = null): array
    {
        $db = \Config\Database::connect();
        // La fecha de fin debe incluir todo el día hasta las 23:59:59
        $fecha_fin_full = $fecha_fin . ' 23:59:59';

        // Construir condiciones dinámicas
        $whereConditions = [
            'v.deleted_at IS NULL',
            'v.sucursal_id = 4', // ✅ Filtro fijo por sucursal 4
            'v.created_at >= ?',
            'v.created_at <= ?'
        ];

        $params = [$fecha_inicio, $fecha_fin_full];

        // Filtro opcional por tipo de pago (contado/credito)
        if ($tipo === 'contado') {
            $whereConditions[] = "LOWER(v.tipo_pago) = 'contado'";
        } elseif ($tipo === 'credito') {
            $whereConditions[] = "LOWER(v.tipo_pago) = 'credito'";
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Se realiza la unión directa a condoriri.productos (p2) en lugar de stock_sucursales
        $query = $db->query("
        SELECT
            v.id AS venta_id,
            v.code AS codigo_venta,
            v.created_at AS fecha_venta,
            v.monto_total AS monto_total_venta,
            v.tipo_pago,
            v.estado AS estado_venta,
            COALESCE(c.nombre_completo, 'Consumidor Final') AS cliente_nombre,
            s.nombre AS sucursal_nombre,
            vd.cantidad,
            vd.precio_unitario,
            vd.subtotal AS subtotal_item,
            
            -- Se obtiene el nombre y el ID directamente de la tabla productos (p2)
            p2.nombre AS producto_nombre,
            p2.id AS producto_agro_id, 
            
            -- Datos del personal UTO
            COALESCE(p.nombre, 'No asignado') AS personal_nombre,
            p.dip AS personal_dip,
            COALESCE(se.seccion, 'Sin sección') AS personal_seccion
        FROM
            condoriri.ventas v
        LEFT JOIN
            condoriri.clientes c ON c.id = v.cliente_id
        INNER JOIN
            condoriri.sucursales s ON s.id = v.sucursal_id
        LEFT JOIN
            condoriri.detalle_venta vd ON vd.venta_id = v.id
        LEFT JOIN
            condoriri.productos p2 ON p2.id = vd.producto_id -- NUEVO JOIN a tabla productos
        -- JOIN con personal UTO
        LEFT JOIN
            public.personas p ON p.id_persona = v.personal_uto_id
        LEFT JOIN
            rrhh.empleados e ON e.id_persona = p.id_persona AND e.id_estado
        LEFT JOIN
            rrhh.secciones se ON se.id_seccion = e.id_seccion
        WHERE
            {$whereClause}
        ORDER BY
            v.created_at ASC
    ", $params);

        if (!$query) {
            log_message('error', 'Error en consulta getDailySalesReportDataInve: ' . $db->error()['message']);
            return [];
        }

        return $query->getResult(); // array de objetos stdClass
    }



    public function getSalesDataForGraphAdmin(string $fechaInicio, string $fechaFin, int $sucursalId)
    {

        $dateFilter = "created_at BETWEEN '{$fechaInicio} 00:00:00' AND '{$fechaFin} 23:59:59'";
        $estadoFilter = "estado != 'ANULADO'";


        if ($sucursalId == 5) {

            $sucursalFilter = "sucursal_id IN (2, 11, 10, 4)";
        } else {

            $sucursalFilter = "sucursal_id = {$sucursalId}";
        }


        $builder = $this->db->table($this->table);

        $builder->select('
        LOWER(tipo_pago) AS tipo_pago,
        COUNT(id) AS cantidad, 
        COALESCE(SUM(monto_total), 0.00) AS monto
    ');

        $builder->where($sucursalFilter);
        $builder->where($dateFilter);
        $builder->where($estadoFilter);
        $builder->groupBy('tipo_pago');

        $results = $builder->get()->getResultArray();


        $desgloseVentas = [
            'contado' => ['cantidad' => 0, 'monto' => 0.00],
            'credito' => ['cantidad' => 0, 'monto' => 0.00],
        ];
        $totalGeneral = ['cantidad' => 0, 'monto' => 0.00];

        foreach ($results as $row) {
            $tipo = trim(strtolower($row['tipo_pago']));


            if (isset($desgloseVentas[$tipo])) {
                $desgloseVentas[$tipo] = [
                    'cantidad' => (int) $row['cantidad'],
                    'monto' => (float) $row['monto']
                ];
            }


            $totalGeneral['cantidad'] += (int) $row['cantidad'];
            $totalGeneral['monto'] += (float) $row['monto'];
        }



        $data = [

            'ventasTotal' => $totalGeneral,


            'ventasPorTipo' => $desgloseVentas,


            'produccionPorProducto' => []
        ];

        return $data;
    }





   public function getDailySalesReportDataVenta(string $fecha_inicio, string $fecha_fin, ?string $tipo = null): array
    {
        $db = \Config\Database::connect();

        // ✅ Obtener sucursal_id de la sesión (crítico)
        $sucursalId = session()->get('sucursal_id');
        if (empty($sucursalId)) {
            log_message('error', 'No se encontró sucursal_id en la sesión para getDailySalesReportData');
            return [];
        }

        $fecha_inicio_full = $fecha_inicio . ' 00:00:00';
        $fecha_fin_full = $fecha_fin . ' 23:59:59';

        // Construir condiciones dinámicas
        $whereConditions = [
            'v.deleted_at IS NULL',
            'v.sucursal_id = ?',  // ✅ Filtro por sucursal (clave)
            'v.created_at >= ?',
            'v.created_at <= ?'
        ];

        $params = [$sucursalId, $fecha_inicio_full, $fecha_fin_full];

        // Filtro opcional por tipo de pago (contado/credito)
        if ($tipo === 'contado') {
            $whereConditions[] = "LOWER(v.tipo_pago) = 'contado'";
        } elseif ($tipo === 'credito') {
            $whereConditions[] = "LOWER(v.tipo_pago) = 'credito'";
        }

        $whereClause = implode(' AND ', $whereConditions);

        $query = $db->query("
        SELECT
            v.id AS venta_id,
            v.code AS codigo_venta,
            v.created_at AS fecha_venta,
            v.monto_total AS monto_total_venta,
            v.tipo_pago,
            v.estado AS estado_venta,
            COALESCE(c.nombre_completo, 'Consumidor Final') AS cliente_nombre,
            s.nombre AS sucursal_nombre,
            vd.cantidad,
            vd.precio_unitario,
            vd.subtotal AS subtotal_item,
            ss.producto AS producto_nombre,
            -- Datos del personal UTO (solo relevante para créditos, pero lo dejamos)
            COALESCE(p.nombre, 'No asignado') AS personal_nombre,
            p.dip AS personal_dip,
            COALESCE(se.seccion, 'Sin sección') AS personal_seccion
        FROM
            condoriri.ventas v
        LEFT JOIN
            condoriri.clientes c ON c.id = v.cliente_id
        INNER JOIN
            condoriri.sucursales s ON s.id = v.sucursal_id
        LEFT JOIN
            condoriri.detalle_venta vd ON vd.venta_id = v.id
        LEFT JOIN
            condoriri.stock_sucursales ss ON ss.id = vd.stock_id
        -- JOIN con personal UTO (solo si hay personal_uto_id)
        LEFT JOIN
            public.personas p ON p.id_persona = v.personal_uto_id
        LEFT JOIN
            rrhh.empleados e ON e.id_persona = p.id_persona AND e.id_estado = true
        LEFT JOIN
            rrhh.secciones se ON se.id_seccion = e.id_seccion
        WHERE
            {$whereClause}
        ORDER BY
            v.created_at ASC, v.id ASC
    ", $params);

        if (!$query) {
            $error = $db->error();
            log_message('error', 'Error en getDailySalesReportData: ' . $error['message'] . ' | Query: ' . $db->getLastQuery());
            return [];
        }

        return $query->getResult(); 
    }












    public function getDailySalesReportData1(
        string $fecha_inicio,
        string $fecha_fin,
        int $sucursal_id
    ): array {
        $db = \Config\Database::connect();

        $query = $db->query("
        SELECT
            v.id AS venta_id,
            v.code AS codigo_venta,
            v.created_at AS fecha_venta,
            v.monto_total AS monto_total_venta,
            v.tipo_pago,
            v.estado AS estado_venta,
            COALESCE(c.nombre_completo, 'Consumidor Final') AS cliente_nombre,
            s.nombre AS sucursal_nombre,
            vd.cantidad,
            vd.precio_unitario,
            vd.subtotal AS subtotal_item,
            ss.producto AS producto_nombre,
            -- Datos del personal UTO
            COALESCE(p.nombre, 'No asignado') AS personal_nombre,
            p.dip AS personal_dip,
            COALESCE(se.seccion, 'Sin sección') AS personal_seccion
        FROM
            condoriri.ventas v
        LEFT JOIN condoriri.clientes c ON c.id = v.cliente_id
        INNER JOIN condoriri.sucursales s ON s.id = v.sucursal_id
        LEFT JOIN condoriri.detalle_venta vd ON vd.venta_id = v.id
        LEFT JOIN condoriri.stock_sucursales ss ON ss.id = vd.stock_id
        LEFT JOIN public.personas p ON p.id_persona = v.personal_uto_id
        LEFT JOIN rrhh.empleados e ON e.id_persona = p.id_persona AND e.id_estado = TRUE  
        LEFT JOIN rrhh.secciones se ON se.id_seccion = e.id_seccion
        WHERE
            v.deleted_at IS NULL
            AND v.created_at >= ?::DATE
            AND v.created_at < (?::DATE + INTERVAL '1 day')
            AND v.sucursal_id = ?
        ORDER BY v.created_at ASC
    ", [
            $fecha_inicio,   // ← Ej: '2025-01-01'
            $fecha_fin,      // ← Ej: '2025-01-10'
            $sucursal_id
        ]);

        if (!$query) {
            $error = $db->error();
            log_message('error', 'Error en getDailySalesReportData1: ' . $error['message']);
            return [];
        }

        return $query->getResult();
    }


    public function getDailySalesReportData2(
        string $fecha_inicio,
        string $fecha_fin,
        int $sucursal_id
    ): array {
        $db = \Config\Database::connect();

        $query = $db->query("
        SELECT
            v.id AS venta_id,
            v.code AS codigo_venta,
            v.created_at AS fecha_venta,
            v.monto_total AS monto_total_venta,
            v.tipo_pago,
            v.estado AS estado_venta,
            COALESCE(c.nombre_completo, 'Consumidor Final') AS cliente_nombre,
            s.nombre AS sucursal_nombre,
            vd.cantidad,
            vd.precio_unitario,
            vd.subtotal AS subtotal_item,
            -- 🔹 LÓGICA PARA PRODUCTOS AGROPECUARIOS
            CASE 
                WHEN v.sucursal_id IN (10, 11) THEN pa.producto
                ELSE ss.producto
            END AS producto_nombre,
            -- Datos del personal UTO
            COALESCE(p.nombre, 'No asignado') AS personal_nombre,
            p.dip AS personal_dip,
            COALESCE(se.seccion, 'Sin sección') AS personal_seccion
        FROM
            condoriri.ventas v
        LEFT JOIN condoriri.clientes c ON c.id = v.cliente_id
        INNER JOIN condoriri.sucursales s ON s.id = v.sucursal_id
        LEFT JOIN condoriri.detalle_venta vd ON vd.venta_id = v.id
        LEFT JOIN condoriri.stock_sucursales ss ON ss.id = vd.stock_id
        LEFT JOIN condoriri.productos_agro pa ON pa.id = vd.producto_agro_id
        LEFT JOIN public.personas p ON p.id_persona = v.personal_uto_id
        LEFT JOIN rrhh.empleados e ON e.id_persona = p.id_persona AND e.id_estado = TRUE  
        LEFT JOIN rrhh.secciones se ON se.id_seccion = e.id_seccion
        WHERE
            v.deleted_at IS NULL
            AND v.created_at >= ?::DATE
            AND v.created_at < (?::DATE + INTERVAL '1 day')
            AND v.sucursal_id = ?
        ORDER BY v.created_at ASC
    ", [
            $fecha_inicio,
            $fecha_fin,
            $sucursal_id
        ]);

        if (!$query) {
            $error = $db->error();
            log_message('error', 'Error en getDailySalesReportData2: ' . $error['message']);
            return [];
        }

        return $query->getResult();
    }

    public function getDailySalesReportDataAdmin1(
        string $fecha_inicio,
        string $fecha_fin,
        int $sucursal_id
    ): array {
        $db = \Config\Database::connect();

        $query = $db->query("
        SELECT
            v.id AS venta_id,
            v.code AS codigo_venta,
            v.created_at AS fecha_venta,
            v.monto_total AS monto_total_venta,
            v.tipo_pago,
            v.estado AS estado_venta,
            COALESCE(c.nombre_completo, 'Consumidor Final') AS cliente_nombre,
            s.nombre AS sucursal_nombre,
            vd.cantidad,
            vd.precio_unitario,
            vd.subtotal AS subtotal_item,
            -- 🔹 LÓGICA MULTIPLE SUCURSALES
            CASE v.sucursal_id
                WHEN 4 THEN prod.nombre
                WHEN 10 THEN pa.producto
                WHEN 11 THEN pa.producto
                ELSE ss.producto
            END AS producto_nombre,
            COALESCE(p.nombre, 'No asignado') AS personal_nombre,
            p.dip AS personal_dip,
            COALESCE(se.seccion, 'Sin sección') AS personal_seccion
        FROM
            condoriri.ventas v
        LEFT JOIN condoriri.clientes c ON c.id = v.cliente_id
        INNER JOIN condoriri.sucursales s ON s.id = v.sucursal_id
        LEFT JOIN condoriri.detalle_venta vd ON vd.venta_id = v.id
        LEFT JOIN condoriri.stock_sucursales ss ON ss.id = vd.stock_id
        LEFT JOIN condoriri.productos prod ON prod.id = vd.producto_id
        LEFT JOIN condoriri.productos_agro pa ON pa.id = vd.producto_agro_id
        LEFT JOIN public.personas p ON p.id_persona = v.personal_uto_id
        LEFT JOIN rrhh.empleados e ON e.id_persona = p.id_persona AND e.id_estado = TRUE  
        LEFT JOIN rrhh.secciones se ON se.id_seccion = e.id_seccion
        WHERE
            v.deleted_at IS NULL
            AND v.created_at >= ?::DATE
            AND v.created_at < (?::DATE + INTERVAL '1 day')
            AND v.sucursal_id IN (2, 11, 10, 4)
        ORDER BY v.created_at ASC
    ", [
            $fecha_inicio,
            $fecha_fin
        ]);

        if (!$query) {
            $error = $db->error();
            log_message('error', 'Error en getDailySalesReportDataAdmin: ' . $error['message']);
            return [];
        }

        return $query->getResult();
    }





public function getDailySalesReportData11(
    string $fecha_inicio,
    string $fecha_fin,
    int $sucursal_id,
    string $tipo_pago // Nuevo parámetro
): array {
    $db = \Config\Database::connect();
    
    
    $tipoPagoCondition = "";
    $binds = [
        $fecha_inicio . ' 00:00:00', 
        $fecha_fin . ' 23:59:59',    
        $sucursal_id
    ];
    
  
    if (strtolower($tipo_pago) !== 'general') {
        $tipoPagoCondition = " AND v.tipo_pago = ? ";
        
        $binds[] = $tipo_pago;
    }
    
 
    $sql = "
        SELECT
            v.id AS venta_id,
            v.code AS codigo_venta,
            v.created_at AS fecha_venta,
            v.monto_total AS monto_total_venta,
            v.tipo_pago,
            v.estado AS estado_venta,
            COALESCE(c.nombre_completo, 'Consumidor Final') AS cliente_nombre,
            s.nombre AS sucursal_nombre,
            vd.cantidad,
            vd.precio_unitario,
            vd.subtotal AS subtotal_item,
            ss.producto AS producto_nombre,
            COALESCE(p.nombre, 'No asignado') AS personal_nombre,
            p.dip AS personal_dip,
            COALESCE(se.seccion, 'Sin sección') AS personal_seccion
        FROM
            condoriri.ventas v
        LEFT JOIN condoriri.clientes c ON c.id = v.cliente_id
        INNER JOIN condoriri.sucursales s ON s.id = v.sucursal_id
        LEFT JOIN condoriri.detalle_venta vd ON vd.venta_id = v.id
        LEFT JOIN condoriri.stock_sucursales ss ON ss.id = vd.stock_id
        LEFT JOIN public.personas p ON p.id_persona = v.personal_uto_id
        LEFT JOIN rrhh.empleados e ON e.id_persona = p.id_persona AND e.id_estado = TRUE  
        LEFT JOIN rrhh.secciones se ON se.id_seccion = e.id_seccion
        WHERE
            v.deleted_at IS NULL
            AND v.created_at BETWEEN ? AND ?  /* 👈 CAMBIO A LÓGICA DE RANGO FUNCIONAL */
            AND v.sucursal_id = ?
            {$tipoPagoCondition}
        ORDER BY v.created_at ASC
    ";

    $query = $db->query($sql, $binds);

    if (!$query) {
        $error = $db->error();
        log_message('error', 'Error en getDailySalesReportData1: ' . $error['message']);
        return [];
    }

    return $query->getResult();
}

public function getDailySalesReportData22(
    string $fecha_inicio,
    string $fecha_fin,
    int $sucursal_id,
    string $tipo_pago // 👈 Nuevo parámetro añadido
): array {
    $db = \Config\Database::connect();

    $tipoPagoCondition = "";
    $binds = [
        $fecha_inicio . ' 00:00:00', 
        $fecha_fin . ' 23:59:59',    
        $sucursal_id
    ];

   
    if (strtolower($tipo_pago) !== 'general') {
        $tipoPagoCondition = " AND v.tipo_pago = ? ";
        $binds[] = $tipo_pago;
    }

    $sql = "
        SELECT
            v.id AS venta_id,
            v.code AS codigo_venta,
            v.created_at AS fecha_venta,
            v.monto_total AS monto_total_venta,
            v.tipo_pago,
            v.estado AS estado_venta,
            COALESCE(c.nombre_completo, 'Consumidor Final') AS cliente_nombre,
            s.nombre AS sucursal_nombre,
            vd.cantidad,
            vd.precio_unitario,
            vd.subtotal AS subtotal_item,
            -- 🔹 LÓGICA PARA PRODUCTOS AGROPECUARIOS
            CASE 
                WHEN v.sucursal_id IN (10, 11) THEN pa.producto
                ELSE ss.producto
            END AS producto_nombre,
            -- Datos del personal UTO
            COALESCE(p.nombre, 'No asignado') AS personal_nombre,
            p.dip AS personal_dip,
            COALESCE(se.seccion, 'Sin sección') AS personal_seccion
        FROM
            condoriri.ventas v
        LEFT JOIN condoriri.clientes c ON c.id = v.cliente_id
        INNER JOIN condoriri.sucursales s ON s.id = v.sucursal_id
        LEFT JOIN condoriri.detalle_venta vd ON vd.venta_id = v.id
        LEFT JOIN condoriri.stock_sucursales ss ON ss.id = vd.stock_id
        LEFT JOIN condoriri.productos_agro pa ON pa.id = vd.producto_agro_id
        LEFT JOIN public.personas p ON p.id_persona = v.personal_uto_id
        LEFT JOIN rrhh.empleados e ON e.id_persona = p.id_persona AND e.id_estado = TRUE  
        LEFT JOIN rrhh.secciones se ON se.id_seccion = e.id_seccion
        WHERE
            v.deleted_at IS NULL
            AND v.created_at BETWEEN ? AND ? /* 👈 CAMBIO A LÓGICA DE RANGO FUNCIONAL */
            AND v.sucursal_id = ?
            {$tipoPagoCondition} -- 👈 Filtro por tipo de pago inyectado aquí
        ORDER BY v.created_at ASC
    ";

    // 2. Ejecutar la consulta con el array de bindings dinámico
    $query = $db->query($sql, $binds);

    if (!$query) {
        $error = $db->error();
        log_message('error', 'Error en getDailySalesReportData2: ' . $error['message']);
        return [];
    }

    return $query->getResult();
}
public function getDailySalesReportData3(
    string $fecha_inicio,
    string $fecha_fin,
    int $sucursal_id,
    string $tipo_pago // 👈 Nuevo parámetro añadido
): array {
    $db = \Config\Database::connect();

    $tipoPagoCondition = "";
    $binds = [
        $fecha_inicio . ' 00:00:00', 
        $fecha_fin . ' 23:59:59',    
        $sucursal_id
    ];

   
    if (strtolower($tipo_pago) !== 'general') {
        $tipoPagoCondition = " AND v.tipo_pago = ? ";
        $binds[] = $tipo_pago;
    }

    $sql = "
        SELECT
            v.id AS venta_id,
            v.code AS codigo_venta,
            v.created_at AS fecha_venta,
            v.monto_total AS monto_total_venta,
            v.tipo_pago,
            v.estado AS estado_venta,
            COALESCE(c.nombre_completo, 'Consumidor Final') AS cliente_nombre,
            s.nombre AS sucursal_nombre,
            vd.cantidad,
            vd.precio_unitario,
            vd.subtotal AS subtotal_item,
            -- 🔹 LÓGICA PARA PRODUCTOS AGROPECUARIOS
            CASE 
                WHEN v.sucursal_id IN (10, 11) THEN pa.producto
                ELSE ss.producto
            END AS producto_nombre,
            -- Datos del personal UTO
            COALESCE(p.nombre, 'No asignado') AS personal_nombre,
            p.dip AS personal_dip,
            COALESCE(se.seccion, 'Sin sección') AS personal_seccion
        FROM
            condoriri.ventas v
        LEFT JOIN condoriri.clientes c ON c.id = v.cliente_id
        INNER JOIN condoriri.sucursales s ON s.id = v.sucursal_id
        LEFT JOIN condoriri.detalle_venta vd ON vd.venta_id = v.id
        LEFT JOIN condoriri.productos ss ON ss.id = vd.producto_id
        LEFT JOIN condoriri.productos_agro pa ON pa.id = vd.producto_agro_id
        LEFT JOIN public.personas p ON p.id_persona = v.personal_uto_id
        LEFT JOIN rrhh.empleados e ON e.id_persona = p.id_persona AND e.id_estado = TRUE  
        LEFT JOIN rrhh.secciones se ON se.id_seccion = e.id_seccion
        WHERE
            v.deleted_at IS NULL
            AND v.created_at BETWEEN ? AND ? /* 👈 CAMBIO A LÓGICA DE RANGO FUNCIONAL */
            AND v.sucursal_id = ?
            {$tipoPagoCondition} -- 👈 Filtro por tipo de pago inyectado aquí
        ORDER BY v.created_at ASC
    ";

    // 2. Ejecutar la consulta con el array de bindings dinámico
    $query = $db->query($sql, $binds);

    if (!$query) {
        $error = $db->error();
        log_message('error', 'Error en getDailySalesReportData2: ' . $error['message']);
        return [];
    }

    return $query->getResult();
}

public function getDailySalesReportDataAdmin(
    string $fecha_inicio,
    string $fecha_fin,
    int $sucursal_id, // Se mantiene por consistencia
    string $tipo_pago // 👈 Nuevo parámetro añadido
): array {
    $db = \Config\Database::connect();

    // 1. Configuración de la lógica condicional del tipo de pago y preparar Binds
    $tipoPagoCondition = "";
    
    // Ajuste: Los primeros dos elementos son para el rango de fecha BETWEEN
    $binds = [
        $fecha_inicio . ' 00:00:00', // 👈 Ajuste: Fecha y hora de inicio
        $fecha_fin . ' 23:59:59'     // 👈 Ajuste: Fecha y hora de fin
    ];

    // Si el tipo de pago no es 'General', se añade el filtro y el valor al binding
    if (strtolower($tipo_pago) !== 'general') {
        $tipoPagoCondition = " AND v.tipo_pago = ? ";
        $binds[] = $tipo_pago;
    }

    $sql = "
        SELECT
            v.id AS venta_id,
            v.code AS codigo_venta,
            v.created_at AS fecha_venta,
            v.monto_total AS monto_total_venta,
            v.tipo_pago,
            v.estado AS estado_venta,
            COALESCE(c.nombre_completo, 'Consumidor Final') AS cliente_nombre,
            s.nombre AS sucursal_nombre,
            vd.cantidad,
            vd.precio_unitario,
            vd.subtotal AS subtotal_item,
            -- 🔹 LÓGICA MULTIPLE SUCURSALES
            CASE v.sucursal_id
                WHEN 4 THEN prod.nombre
                WHEN 10 THEN pa.producto
                WHEN 11 THEN pa.producto
                ELSE ss.producto
            END AS producto_nombre,
            COALESCE(p.nombre, 'No asignado') AS personal_nombre,
            p.dip AS personal_dip,
            COALESCE(se.seccion, 'Sin sección') AS personal_seccion
        FROM
            condoriri.ventas v
        LEFT JOIN condoriri.clientes c ON c.id = v.cliente_id
        INNER JOIN condoriri.sucursales s ON s.id = v.sucursal_id
        LEFT JOIN condoriri.detalle_venta vd ON vd.venta_id = v.id
        LEFT JOIN condoriri.stock_sucursales ss ON ss.id = vd.stock_id
        LEFT JOIN condoriri.productos prod ON prod.id = vd.producto_id
        LEFT JOIN condoriri.productos_agro pa ON pa.id = vd.producto_agro_id
        LEFT JOIN public.personas p ON p.id_persona = v.personal_uto_id
        LEFT JOIN rrhh.empleados e ON e.id_persona = p.id_persona AND e.id_estado = TRUE  
        LEFT JOIN rrhh.secciones se ON se.id_seccion = e.id_seccion
        WHERE
            v.deleted_at IS NULL
            AND v.created_at BETWEEN ? AND ? /* 👈 CAMBIO A LÓGICA DE RANGO FUNCIONAL */
            AND v.sucursal_id IN (2, 11, 10, 4)
            {$tipoPagoCondition} -- 👈 Filtro por tipo de pago inyectado aquí
        ORDER BY v.created_at ASC
    ";

    // 2. Ejecutar la consulta con el array de bindings dinámico
    $query = $db->query($sql, $binds);

    if (!$query) {
        $error = $db->error();
        log_message('error', 'Error en getDailySalesReportDataAdmin: ' . $error['message']);
        return [];
    }

    return $query->getResult();
}




}
