<?php
namespace App\Models;

use CodeIgniter\Model;

class StateModel extends Model
{
    protected $table = 'com_state_mst_t';
    protected $primaryKey = 'state_id';
    protected $useSoftDeletes = true; // Enable soft deletes

    protected $allowedFields = [
        'uuid',
        'state_name',
        'state_short_name',
        'state_sort_index',
        'country_id',
        'machine_name',
        'entered_by',
        'created_at',
        'modified_by',
        'updated_at',
        'deleted_at',
        'status_flag',
        
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Add validation rules here if necessary
    // Validation rules
    protected $validationRules = [
        'country_id' => 'required|integer',
        'state_short_name' => 'required|max_length[10]|is_unique[com_state_mst_t.state_short_name]',
        'state_name' => 'required|max_length[100]|is_unique[com_state_mst_t.state_name]',
        'state_sort_index' => 'permit_empty|integer',
        'status_flag' => 'in_list[Active,Inactive]',
    ];
    
    protected $validationMessages = [
        'country_id' => [
            'required' => 'Country ID is required',
            'integer' => 'Country ID must be an integer',
        ],
        'state_sort_index' => [
            'required' => 'Sort order   is required',
            'integer' => 'Sort order  must be an integer',
        ],
        'state_short_name' => [
            'required' => 'Short name is required',
            'max_length' => 'Name cannot exceed 50 characters',
        ],
        'state_name' => [
            'required' => 'Name is required',
            'max_length' => 'Name cannot exceed 255 characters',
        ],

    ];

    public function getValuesByParent($parentId)
    {
        return $this->where('country_id', $parentId)->findAll();
    }

    // Private method to transform  data into custom format
    public function transformData($data)
    {

        if (!$data) {
            return null;
        }

        return [
            'state_id' => $data['state_id'],
            'uuid' => $data['uuid'],
            'country_id' => $data['country_id'],
            'country_short_name' => $data['country_short_name'],
            'state_short_name' => $data['state_short_name'],
            'state_name' => $data['state_name'],
            'state_sort_index' => $data['state_sort_index'],
            'status_flag' => $data['status_flag'],
            'created_at' => $data['created_at'],
        ];
    }

}
