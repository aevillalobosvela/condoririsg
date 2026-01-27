<?php


namespace App\Controllers\paneles;

use App\Controllers\BaseController;


use App\Models\Sucursal\SucursalModel;

class panelesController extends BaseController
{
    /**
     * @var CategoriaModel 
     */

    protected $sucursalModel;

    /**
     * Constructor del controlador.
     */
    public function __construct()
    {

        $this->sucursalModel = new SucursalModel();
    }

    /**
     * Muestra la lista de ventas (Operación READ - todas).
     *
     * @return string
     */

    public function panel()
    {

        $session = session();
        $userRole = $session->get('rol_nombre');
        $data['title'] = 'Panel de Administración';
        $viewPath = '';
        switch ($userRole) {
            case 'admin':

                return $this->admin();


                break;

            case 'vendedor':

                $viewPath = 'panel/vendedor';
                break;

            case 'almacen':

                $viewPath = 'panel/inventario';
                break;
            case 'agropecuario':
                $viewPath = 'panel/agropecuario';
                break;
            case 'ganaderia':
                $viewPath = 'panel/ganaderia';
                break;
            case 'contabilidad':

                $data = [
                    'sucursales' => $this->sucursalModel->findAll(),
                    'title'    => 'Listado de sucursales',
                ];
                $data['title'] = 'Panel de Contabilidad';
                return view('panel/contabilidad', $data);
                break;

            default:

                $viewPath = 'admin/default_panel';
        }

        if (!empty($viewPath)) {
            echo view($viewPath, $data);
        }
    }


    public function admin()
    {


        return view('admin/admin', [

            'title'          => 'Panel Administrativo',




        ]);
    }


    public function vendedor()
    {
        $data['title'] = 'Panel de Vendedor';
        return view('panel/vendedor', $data);
    }

    public function inventario()
    {
        $data['title'] = 'Panel de Inventario';
        return view('panel/inventario', $data);
    }
    public function agropecuario()
    {
        $data['title'] = 'Panel de Agropecuario';
        return view('panel/agropecuario', $data);
    }
    public function ganadaeria()
    {
        $data['title'] = 'Panel de Ganadería';
        return view('panel/ganaderia', $data);
    }
    public function contabilidad()
    {
        $data = [
            'sucursales' => $this->sucursalModel->findAll(),
            'title'    => 'Listado de sucursales',
        ];




        $data['title'] = 'Panel de Contabilidad';
        return view('panel/contabilidad', $data);
    }
}
