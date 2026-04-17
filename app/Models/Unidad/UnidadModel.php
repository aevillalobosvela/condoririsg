<?php

namespace App\Models\Unidad;

use CodeIgniter\Model;

class UnidadModel extends Model
{
    protected $table = 'condoriri.unidades';
    protected $primaryKey = 'id';
      

  
    protected $allowedFields = [
        'nombre', 'descripcion', 'estado', 'tipo', 'user_id'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';    

    
    protected $validationRules = [
        'nombre'    => 'required|min_length[1]|max_length[100]',
        'descripcion' => 'max_length[100]',
        'user_id'   => 'required|integer|min_length[1]|max_length[11]',  
    ];
    protected $validationMessages = [];
    protected $skipValidation     = false;
    
}
