<?php

namespace App\Models;

use CodeIgniter\Model;

class ComLookupValuesModel extends Model
{
    protected $table = 'com_lookup_values_mst_t';
    protected $primaryKey = 'lookup_value_id';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'uuid',
        'lookup_value',
        'lookup_value_order',
        'lookup_id',
        'status_flag',
        'entered_by',
        'created_at',
        'modified_by',
        'updated_at',
        'machine_name',
        'all_access',
        'deleted_at'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation rules
    protected $validationRules = [

        'lookup_value' => 'required|max_length[255]',
        'lookup_value_order' => 'required|integer',
        'lookup_id' => 'required|integer',
    ];

    protected $validationMessages = [

        'lookup_name' => [
            'required' => 'Lookup name is required',
            'max_length' => 'Lookup  name cannot exceed 255 characters',
        ],
        'lookup_editable_status' => [
            'required' => 'Lookup editable status is required',
            'max_length' => 'Lookup editable status cannot exceed 1 characters',
        ],
        'lookup_id' => [
            'required' => 'Lookup  id is required',

        ], 

    ];

    public function getValuesByCategory($lookupId)
    {
        return $this->where('lookup_id', $lookupId)->findAll();
    }
    // Private method to transform  data into custom format
    public function transformData($data)
    {

        if (!$data) {
            return null;
        }

        return [
            'uuid' => $data['uuid'],
            'lookup_value_id' => $data['lookup_value_id'],
            'lookup_name' => $data['lookup_name'],
            'lookup_value' => $data['lookup_value'],
            'lookup_value' => $data['lookup_value'],
            'lookup_value_order' => $data['lookup_value_order'],
            'lookup_id' => $data['lookup_id'],
            'status_flag' => $data['status_flag'],
            'created_at' => $data['created_at'],

        ];
    }
}
