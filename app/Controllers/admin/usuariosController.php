<?php


namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class usuariosController extends BaseController
{
    public function index()
    {


        $data['title'] = 'Panel de Administración';

        $dataa['usuarios'] = [
            ['id' => 1, 'nombre' => 'John', 'apellido' => 'Doe', 'celular' => '123456789', 'rol' => 'Admin', 'nombre_sucursal' => 'Sucursal 1'],
            ['id' => 2, 'nombre' => 'Jane', 'apellido' => 'Smith', 'celular' => '987654321', 'rol' => 'User', 'nombre_sucursal' => 'Sucursal 2'],
        ];





        echo view('template/header', $data);
        echo view('template/sidebar');
        echo  view('template/topbar');
        echo view('admin/usuarios', $dataa);

        echo view('template/footer');
    }
}
