<?php namespace App\Controllers;

use App\Models\ItemModel;
use CodeIgniter\RESTful\ResourceController;

class ItemController extends ResourceController
{
    protected $itemModel;
    protected $format = 'json';

    public function __construct()
    {
        $this->itemModel = new ItemModel();
    }

    public function index()
    {

        // Get the limit, ids, and search keyword from the query parameters
        $limit = (int) $this->request->getGet('limit') ?: 0; // Default limit is 0
        $ids = $this->request->getGet('ids'); // Expecting a comma-separated list of IDs
        $search = $this->request->getGet('search'); // Search keyword

// Clone the model to create a base query
        $query = $this->itemModel->select('ams_item_mst_t.*, ams_item_group_mst_t.item_group_short_name'); // Select specific fields
        $query->join('ams_item_group_mst_t', 'ams_item_mst_t.item_group_id = ams_item_group_mst_t.item_group_id', 'inner'); // Add a join with the item_groups table

// Filter by IDs if provided
        if ($ids) {
            $idArray = explode(',', $ids);
            $query->whereIn('ams_item_mst_t.item_id', $idArray);
        }

// Filter by search keyword if provided
        if ($search) {
            $query->groupStart() // Start a group for OR conditions
                ->like('ams_item_mst_t.item_short_name', $search)
                ->orLike('ams_item_mst_t.item_name', $search)
                ->groupEnd(); // End the group
        }

// Apply the limit
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

        $transformData = array_map([$this->itemModel, 'transformData'], $items);

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
        //$item = $this->itemModel->where('uuid', $uuid)->first();
        $item = $this->itemModel
            ->select('ams_item_mst_t.*, ams_item_group_mst_t.item_group_short_name')
            ->join('ams_item_group_mst_t', 'ams_item_group_mst_t.item_group_id = ams_item_mst_t.item_group_id')
            ->where('ams_item_mst_t.uuid', $uuid)
            ->first();

        if (empty($item)) {
            return $this->failNotFound('Data not found');
        }

        if ($item) {

            $item = $this->itemModel->transformData($item);
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
                'item_group_id' => $input->item_group_id ?? '', // Default to empty string if not provided
                'item_short_name' => $input->item_short_name ?? '', // Default to empty string if not provided
                'item_name' => $input->item_name ?? '', // Default to empty string if not provided
                'item_desc' => $input->item_desc ?? 'Y', // Default to empty string if not provided
                'item_uom_id' => $input->item_uom_id ?? 0, // Default to 0 if not provided
                'item_rate' => $input->item_rate ?? 0, // Default to empty string if not provided
                'vat_item' => $input->vat_item ?? 0, // Default to empty string if not provided
                'sp1' => $input->sp1 ?? '', // Default to empty string if not provided
                'dp1' => $input->dp1 ?? '', // Default to empty string if not provided
                'search_name' => $input->search_name ?? '', // Default to empty string if not provided
                'status_flag' => $input->status_flag ?? 'Active', // Default to 'Active' if not provided

            ];

            // Validate the data before inserting
            if (!$this->itemModel->validate($data)) {
                // If validation fails, return the validation errors
                return $this->fail($this->itemModel->errors());
            }

            // Check if item_short_name or item_name already exists
            $existingItem = $this->itemModel->where('item_short_name', $data['item_short_name'])
                ->orWhere('item_name', $data['item_name'])
                ->first();

            if ($existingItem) {
                return $this->fail([
                    'message' => 'An item with the same short name or name already exists.',
                    'existing_data' => $existingItem,
                ]);
            }
            // Insert the data into the database
            $data['uuid'] = \Ramsey\Uuid\Uuid::uuid4()->toString(); // Generate a UUID
            helper('jwt_helper');
            $enteredBy = getUserInfoFromToken($this->request);
            $data['entered_by'] = $enteredBy;
            if ($this->itemModel->insert($data)) {
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
// Parse the incoming PUT request body

// Validation Rules with Custom Messages
        $validationRules = [

            'item_short_name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Item short name is required.',
                ],
            ],
            'item_name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Item name is required.',
                ],
            ],

            'item_group_id' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Item group id is required.',
                ],
            ],

        ];

// Validate the input data
        if (!$this->validate($validationRules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

// Find the user by UUID
        $item = $this->itemModel->where('uuid', $uuid)->first();

        if (!$item) {
            return $this->failNotFound('Data not found');
        }

        $input = $this->request->getJSON();
        if (($input->item_short_name != $item['item_short_name']) || ($input->item_name != $item['item_name'])) {

            $duplicateCheck = $this->itemModel
                ->groupStart()
                ->where('item_short_name', $input->item_short_name)
                ->orWhere('item_name', $input->item_name)
                ->groupEnd()
                ->where('item_id !=', $item['item_id'])
                ->first();

            if ($duplicateCheck) {
                return $this->fail([
                    'message' => 'An item  with the same short name or name already exists.',
                    'existing_data' => $duplicateCheck,
                ]);
            }

        }

        helper('jwt_helper');
        $enteredBy = getUserInfoFromToken($this->request);
// Prepare the data for updating
        $updateData = [
            'item_group_id' => $input->item_group_id ?? null,
            'item_short_name' => $input->item_short_name ?? null,
            'item_name' => $input->item_name ?? null,
            'modified_by' => $enteredBy ?? null,
        ];

// Perform the update
        if ($this->itemModel->update($item['item_id'], $updateData)) {
            helper('activity_helper');

            $userAgent = $this->request->getUserAgent()->getAgentString();
            $ipAddress = $this->request->getIPAddress();

            logActivity([
                'user_id' => $item['item_id'],
                'uuid' => $uuid,
                'action' => 'update',
                'section_tbl' => 'ams_item_mst_t',
                'description' => "Item master with UUID $uuid was updated.",
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_by' => $enteredBy,
            ]);

            return $this->respond([
                'status' => 200,
                'message' => 'Item updated successfully',
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
        $item = $this->itemModel->where('uuid', $uuid)->first();

        if (!$item) {
            return $this->failNotFound('Data not found');
        }

// Perform a soft delete using the primary key (user_id)
        if ($this->itemModel->delete($item['item_id'])) {
            helper('activity_helper');
            helper('jwt_helper');
            $userId = getUserInfoFromToken($this->request);
            $userAgent = $this->request->getUserAgent()->getAgentString();
            $ipAddress = $this->request->getIPAddress();

            logActivity([
                'user_id' => $item['item_id'],
                'uuid' => $uuid,
                'action' => 'delete',
                'section_tbl' => 'ams_item_mst_t',
                'description' => "Item master with UUID $uuid was soft-deleted.",
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_by' => $userId,
            ]);

            return $this->respondDeleted(['status' => 'Data soft-deleted successfully']);
        }
        return $this->fail('Data deletion failed');
    }
}
