<?php namespace App\Controllers;

use App\Models\ItemGroupModel;
use App\Models\ItemModel;
//use App\Models\ComLookupValuesModel;

use CodeIgniter\RESTful\ResourceController;

class ItemGroupController extends ResourceController
{
    protected $itemGroupModel;
    protected $itemModel;
    // protected $lookupvalueModel;
    protected $format = 'json';

    public function __construct()
    {
        $this->itemGroupModel = new ItemGroupModel();
        $this->itemModel = new ItemModel();
        //$this->lookupvalueModel = new ComLookupValuesModel();

    }

    public function index()
    {

        // Get the limit, ids, and search keyword from the query parameters
        $limit = (int) $this->request->getGet('limit') ?: 0; // Default limit is 10
        $ids = $this->request->getGet('ids'); // Expecting a comma-separated list of IDs
        $search = $this->request->getGet('search'); // Search keyword

        //$query = clone $this->itemGroupModel;
        // Clone the model to create a base query
        $query = $this->itemGroupModel->select('ams_item_group_mst_t.*, com_lookup_values_mst_t.lookup_value as category_lookup_value'); // Select specific fields
        $query->join('com_lookup_values_mst_t', 'ams_item_group_mst_t.category_lookup_id = com_lookup_values_mst_t.lookup_value_id', 'inner'); // Add a join with the item_groups table

        // Filter by IDs if provided
        if ($ids) {
            $idArray = explode(',', $ids);
            $query = $this->itemGroupModel->whereIn('item_group_id', $idArray);
        }

        // Filter by search keyword if provided
        if ($search) {
            $query = $this->itemGroupModel->groupStart() // Start a group for OR conditions
                ->like('item_group_short_name', $search)
                ->orLike('item_group_name', $search)
                ->groupEnd(); // End the group
        }
        if ($limit > 0) {
            $items = $query->limit($limit)->findAll();
        } else {
            $items = $query->findAll(); // Fetch all records if limit is 0
        }
        if (empty($items)) {
            $response = [
                'status' => 200,
                'error' => 404,
                'message' => 'No data found.',
                'data' => [],
            ];
            return $this->respond($response);
        }
        foreach ($items as &$item) {
            $item['ItemValues'] = $this->itemModel->getValuesByItemgroup($item['item_group_id']);
        }
        $transformData = array_map([$this->itemGroupModel, 'transformData'], $items);

        $response = [
            'status' => 200,
            'error' => null,
            'message' => null,
            'data' => $transformData,

        ];
        return $this->respond($response);

    }
    // Method to retrieve a single user
    public function show($uuid = null)
    {
        if ($uuid === null) {
            return $this->failValidationErrors('Id is required', 404);
        }
// Find the user by UUID
        //$item = $this->itemGroupModel->where('uuid', $uuid)->first();

        $query = $this->itemGroupModel->select('ams_item_group_mst_t.*, com_lookup_values_mst_t.lookup_value as category_lookup_value'); // Select specific fields
        $query->join('com_lookup_values_mst_t', 'ams_item_group_mst_t.category_lookup_id = com_lookup_values_mst_t.lookup_value_id', 'inner'); // Add a join with the item_groups table
        $query->where('ams_item_group_mst_t.uuid', $uuid);
        $item = $query->first();

        if (empty($item)) {
            return $this->failNotFound('Data not found');
        }

        if ($item) {
            $item['ItemValues'] = $this->itemModel->getValuesByItemgroup($item['item_group_id']);
            $item = $this->itemGroupModel->transformData($item);
            $response = [
                'status' => 200,
                'error' => null,
                'message' => null,
                'data' => $item,
            ];
            return $this->respond($response);
        }

//return $this->failNotFound('User not found');
    }

    public function create()
    {
// Get the JSON data from the request
        $input = $this->request->getJSON();
        if ($input) {
            // Prepare the data for insertion
            $data = [
                'category_lookup_id' => $input->category_lookup_id ?? '', // Default to empty string if not provided
                'item_group_short_name' => $input->item_group_short_name ?? '', // Default to empty string if not provided
                'item_group_name' => $input->item_group_name ?? '', // Default to empty string if not provided
                //  'lock_status' => $input->lock_status ?? 'Y', // Default to empty string if not provided
                //  'record_modified_count' => $input->record_modified_count ?? 0, // Default to 0 if not provided
                //  'machine_name' => $input->machine_name ?? '', // Default to empty string if not provided
                //  'usrlock' => $input->usrlock ?? 0, // Default to empty string if not provided
                'status_flag' => $input->status_flag ?? 'Active', // Default to 'Active' if not provided
                //  'entered_by' => $input->entered_by ?? 1, // Default to '1', or replace with actual user data
                //  'modified_by' => $input->modified_by ?? 0, // Default to '0' for future update operations
            ];

            // Validate the data before inserting
            if (!$this->itemGroupModel->validate($data)) {
                // If validation fails, return the validation errors
                return $this->fail($this->itemGroupModel->errors());
            }

            // Check if item_group_short_name or item_group_name already exists
            $existingItem = $this->itemGroupModel->where('item_group_short_name', $data['item_group_short_name'])
                ->orWhere('item_group_name', $data['item_group_name'])
                ->first();

            if ($existingItem) {
                return $this->fail([
                    'message' => 'An item group with the same short name or name already exists.',
                    'existing_data' => $existingItem,
                ]);
            }
            // Insert the data into the database
            $data['uuid'] = \Ramsey\Uuid\Uuid::uuid4()->toString(); // Generate a UUID
            helper('jwt_helper');
            $enteredBy = getUserInfoFromToken($this->request);
            $data['entered_by'] = $enteredBy;
            if ($this->itemGroupModel->insert($data)) {
                // Return success response
                return $this->respond([
                    'status' => 201,
                    'error' => null,
                    'message' => 'Data inserted successfully',
                    'data' => $data,
                ]);
            } else {
                // $db = \Config\Database::connect();
                // $error = $db->error(); // Get the last database error
                // return $this->fail([
                //     'status' => 500,
                //     'error' => $error['code'],
                //     'message' => $error['message'],
                //     'data' => $data,
                // ]);
                // // Return failure response if insert fails
                return $this->fail('Failed to insert data');
            }
        } else {
            // Handle the case where no valid JSON data is received
            return $this->fail('Invalid JSON format');
        }
    }

    public function update($uuid = null)
    {
        if ($uuid === null) {
            return $this->failValidationErrors('Id is required');
        }

// Validation Rules with Custom Messages
        $validationRules = [
            'item_group_short_name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Item group short name is required.',
                ],
            ],
            'item_group_name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Item group name is required.',
                ],
            ],

            'category_lookup_id' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Category lookup ID is required.',
                ],
            ],

        ];

// Validate the input data
        if (!$this->validate($validationRules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

// Find the user by UUID
        $item = $this->itemGroupModel->where('uuid', $uuid)->first();

        if (!$item) {
            return $this->failNotFound('Data not found');
        }
        $input = $this->request->getJSON();
// Prepare the data for updating
        $updateData = [
            'category_lookup_id' => $input->category_lookup_id ?? null,
            'item_group_short_name' => $input->item_group_short_name ?? null,
            'item_group_name' => $input->item_group_name ?? null,
        ];

        if (($input->item_group_short_name != $item['item_group_short_name']) || ($input->item_group_name != $item['item_group_name'])) {
            $duplicateCheck = $this->itemGroupModel
                ->groupStart()
                ->where('item_group_short_name', $input->item_group_short_name)
                ->orWhere('item_group_name', $input->item_group_name)
                ->groupEnd()
                ->where('item_group_id !=', $item['item_group_id'])
                ->first();

            if ($duplicateCheck) {
                return $this->fail([
                    'message' => 'An item group with the same short name or name already exists.',
                    'existing_data' => $duplicateCheck,
                ]);
            }
        }

// Perform the update
        helper('jwt_helper');
        $enteredBy = getUserInfoFromToken($this->request);
        $updateData['modified_by'] = $enteredBy;

        if ($this->itemGroupModel->update($item['item_group_id'], $updateData)) {
            helper('activity_helper');

            $userAgent = $this->request->getUserAgent()->getAgentString();
            $ipAddress = $this->request->getIPAddress();

            logActivity([
                'user_id' => $item['item_group_id'],
                'uuid' => $uuid,
                'action' => 'update',
                'section_tbl' => 'ams_item_group_mst_t',
                'description' => "Item group master with UUID $uuid was updated.",
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_by' => $enteredBy,
            ]);

            return $this->respond([
                'status' => 200,
                'message' => 'Item group updated successfully',
                'data' => $updateData,
            ]);
        }

// Return failure if the update was unsuccessful
        return $this->fail('Failed to update item group');
    }

    // Delete a user (soft delete is not used, so this will remove the user)
    public function delete($uuid = null)
    {
        if ($uuid === null) {
            return $this->failValidationErrors('ID is required', 404);
        }

// Find the user by UUID
        $item = $this->itemGroupModel->where('uuid', $uuid)->first();

        if (!$item) {
            return $this->failNotFound('Data not found');
        }

// Perform a soft delete using the primary key (user_id)
        if ($this->itemGroupModel->delete($item['item_group_id'])) {
            helper('activity_helper');
            helper('jwt_helper');
            $userId = getUserInfoFromToken($this->request);
            $userAgent = $this->request->getUserAgent()->getAgentString();
            $ipAddress = $this->request->getIPAddress();

            logActivity([
                'user_id' => $item['item_group_id'],
                'uuid' => $uuid,
                'action' => 'delete',
                'section_tbl' => 'ams_item_group_mst_t',
                'description' => "Item group master with UUID $uuid was soft-deleted.",
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_by' => $userId,
            ]);

            return $this->respondDeleted(['status' => 'Data soft-deleted successfully']);
        }
        return $this->fail('Data deletion failed');
    }
}
