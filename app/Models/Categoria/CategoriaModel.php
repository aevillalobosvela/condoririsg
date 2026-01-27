<?php

namespace App\Models\Categoria;

use CodeIgniter\Model;

class CategoriaModel extends Model
{
    protected $table = 'condoriri.categorias';
    protected $primaryKey = 'id';

  
    protected $allowedFields = [
      
        'nombre', 'descripcion', 'estado','user_id'

    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at'; 

    
    protected $validationRules = [
        'nombre'    => 'required|min_length[1]|max_length[10]',
        'descripcion' => 'max_length[100]',
        'user_id'   => 'required|integer|min_length[1]|max_length[11]',
        
    ];


    protected $validationMessages = [];
    protected $skipValidation     = false;

 

    
}
