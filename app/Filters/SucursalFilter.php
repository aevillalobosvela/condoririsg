<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class SucursalFilter implements FilterInterface
{
    /**
     * Do whatever you want here.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $userSucursal = $session->get('sucursal_nombre');

        if (is_null($arguments)) {
          
            return;
        }

        
        if (in_array($userSucursal, $arguments) === false) {
            
            return redirect()->to('/')->with('error', 'Acceso denegado: No tienes permiso para acceder a esta sucursal.');
        }
    }

    /**
     * Allows After filters to inspect and modify the response.
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // ...
    }
}