<?php
namespace App\Models;

use CodeIgniter\Model;

class ItemModel extends Model
{
    protected $table            = 'ams_item_mst_t';
    protected $primaryKey       = 'item_id';
    protected $useSoftDeletes = true; // Enable soft deletes

    protected $allowedFields = [
        'uuid',
        'item_group_id',
        'item_short_name',
        'item_name',
        'item_desc',
        'item_uom_id',
        'item_rate',
        'item_rol',
        'lock_status',
        'record_modified_count',
        'entered_by',
        'created_at',
        'modified_by',
        'updated_at',
        'machine_name',
        'vat_item',
        'sp1',
        'dp1',
        'search_name',
        'status_flag',
        'deleted_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Add validation rules here if necessary
       // Validation rules
       protected $validationRules = [
        'item_group_id' => 'required|integer',
        'item_short_name' => 'required|max_length[50]',
        'item_name' => 'required|max_length[100]',
        // 'LOCK_STATUS'           => 'required|in_list[Y,N]',
    ];

    protected $validationMessages = [
        'item_group_id' => [
            'required' => 'Item group ID is required',
            'integer' => 'Item group ID must be an integer',
        ],
        'item_short_name' => [
            'required' => 'Item short name is required',
            'max_length' => 'Item short name cannot exceed 50 characters',
        ],
        'item_name' => [
            'required' => 'Item name is required',
            'max_length' => 'Item name cannot exceed 100 characters',
        ],

    ];

    public function getValuesByItemgroup($item_group_id)
    {
        return $this->where('item_group_id', $item_group_id)->findAll();
    }

    // Private method to transform  data into custom format
    public function transformData($data)
    {
        
        if (!$data) {
            return null;
        }

        return [
            'uuid' => $data['uuid'],
            'item_id' => $data['item_id'],
            'item_group_id' => $data['item_group_id'],
            'item_group_short_name' => $data['item_group_short_name'],
            'item_short_name' => $data['item_short_name'],
            'item_name' => $data['item_name'],
            'item_desc' => $data['item_desc'],
            'item_uom_id' => $data['item_uom_id'],
            'item_rate' => $data['item_rate'],
            'item_rol' => $data['item_rol'],
            'lock_status' => $data['lock_status'],
            'record_modified_count' => $data['record_modified_count'],
            'vat_item' => $data['vat_item'],
            'sp1' => $data['sp1'],
            'dp1' => $data['dp1'],
            'search_name' => $data['search_name'],
            'status_flag' => $data['status_flag'],
            
        ];
    }

}
