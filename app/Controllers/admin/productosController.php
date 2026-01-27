<?php


namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class productosController extends BaseController
{
    public function index()
    {
        
  
      
        $data['title'] = 'Panel de Administración';




        echo view('template/header', $data);
         echo view('template/sidebar');
         echo  view('template/topbar');
    echo view('admin/productos',);
       
         echo view('template/footer');
    }
}