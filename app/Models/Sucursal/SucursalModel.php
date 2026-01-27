<?php

namespace App\Models\Sucursal;

use CodeIgniter\Model;

class SucursalModel extends Model
{
    protected $table = 'condoriri.sucursales';
    protected $primaryKey = 'id';
     protected $returnType = 'array'; 

  
    protected $allowedFields = [
      
        'nombre', 'descripcion', 'direccion','telefono', 'user_id'

    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at'; 

    
    protected $validationRules = [
        'nombre'    => 'required|min_length[1]|max_length[100]',
        'descripcion' => 'max_length[100]',
        'direccion' => 'max_length[100]',
        'telefono' => 'max_length[15]',
        'user_id'   => 'required|integer|min_length[1]|max_length[11]',
        
    ];

    


    protected $validationMessages = [];
    protected $skipValidation     = false;

 

    
}
