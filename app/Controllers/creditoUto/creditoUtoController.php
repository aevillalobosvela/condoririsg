<?php

namespace App\Controllers\creditoUto;

use App\Controllers\BaseController;
use CodeIgniter\Database\Exceptions\DatabaseException;

class creditoUtoController extends BaseController
{
    public function index()
    {
       
        $db = db_connect();
        $dipPattern = '7379932%';

        $sql = "
            SELECT 
                p.id_persona, 
                p.nombre, 
                p.dip, 
                p.telefono, 
                p.celular, 
                COALESCE(e.\"id_estado\", false) AS es_empleado_uto, 
                c.cargo, 
                s.seccion 
            FROM public.personas p 
            LEFT JOIN rrhh.empleados e ON (p.id_persona = e.id_persona AND e.\"id_estado\") 
            LEFT JOIN rrhh.cargos c ON (e.id_cargo = c.id_cargo) 
            LEFT JOIN rrhh.secciones s ON (e.id_seccion = s.id_seccion) 
            WHERE p.\"id_estado\"  
              AND p.dip ILIKE ?
              OR p.nombre ILIKE
        ";

        $query = $db->query($sql, [$dipPattern]);
        $personal = $query->getResult();

        $data = [
            'title'    => 'Personal de la UTO',
            'personal' => $personal,
        ];

      var_dump($data);
        // return view('ventas/ventasCredito', $data);
    }
}
