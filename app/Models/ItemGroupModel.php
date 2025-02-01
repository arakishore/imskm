<?php namespace App\Models;

use CodeIgniter\Model;

class ItemGroupModel extends Model
{
    protected $table = 'ams_item_group_mst_t';
    protected $primaryKey = 'item_group_id';
    protected $useSoftDeletes = true; // Enable soft deletes

    // Define the fields that can be mass assigned
    protected $allowedFields = [
        'uuid', 'category_lookup_id', 'item_group_short_name', 'item_group_name', 'status_flag', 'entered_by', 'created_at', 'modified_by', 'updated_at', 'deleted_at'
        
    ];

    // Enable timestamps to automatically manage `ENTERED_ON` and `MODIFIED_ON`
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at'; // or any other field name

    // Validation rules
    protected $validationRules = [
        'category_lookup_id' => 'required|integer',
        'item_group_short_name' => 'required|max_length[50]',
        'item_group_name' => 'required|max_length[100]',
        // 'LOCK_STATUS'           => 'required|in_list[Y,N]',
    ];

    protected $validationMessages = [
        'category_lookup_id' => [
            'required' => 'Category lookup ID is required',
            'integer' => 'Category lookup ID must be an integer',
        ],
        'item_group_short_name' => [
            'required' => 'Item group short name is required',
            'max_length' => 'Item group short name cannot exceed 50 characters',
        ],
        'item_group_name' => [
            'required' => 'Item group name is required',
            'max_length' => 'Item group name cannot exceed 100 characters',
        ],

    ];

    // Private method to transform  data into custom format
    public function transformData($data)
    {
        
        if (!$data) {
            return null;
        }

        return [
            'uuid' => $data['uuid'],
            'item_group_id' => $data['item_group_id'],
            'item_group_short_name' => $data['item_group_short_name'],
            'item_group_name' => $data['item_group_name'],
            'category_lookup_id' => $data['category_lookup_id'],
            'category_lookup_value' => $data['category_lookup_value'],
            //'record_modified_count' => $data['record_modified_count'],
            'machine_name' => $data['machine_name'],
            'status_flag' => $data['status_flag'],
            'created_at' => $data['created_at'],
            'ItemValues' => $data['ItemValues'],
            
        ];
    }

}
