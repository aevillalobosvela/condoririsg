<?php


namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class adminController extends BaseController
{
    public function index()
    {
          
        $data['title'] = 'Panel de Administración';
        // echo view('template/header', $data);
        //  echo view('template/sidebar');
        //  echo  view('template/topbar');
        echo view('admin/admin', $data);
        //  echo view('template/footer');
    }
}