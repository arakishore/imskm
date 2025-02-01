<?php

namespace App\Models;

use CodeIgniter\Model;

class LookupValuesModel extends Model
{
    protected $table = 'com_lookup_values_mst_t';
    protected $primaryKey = 'LOOKUP_VALUE_ID';
    
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'LOOKUP_VALUE',
        'LOOKUP_VALUE_ORDER',
        'LOOKUP_ID',
        'ENTERED_BY',
        'created_at',
        'MODIFIED_BY',
        'updated_at',
        'MACHINE_NAME',
        'ALL_ACCESS',
        'deleted_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}

