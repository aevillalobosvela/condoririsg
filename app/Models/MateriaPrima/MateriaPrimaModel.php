<?php

namespace App\Models\MateriaPrima;

use CodeIgniter\Model;

class MateriaPrimaModel extends Model
{
    protected $table = 'condoriri.materias_primas';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nombre'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    protected $validationRules = [
        'nombre' => 'required|max_length[100]'
    ];
}
