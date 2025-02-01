<?php
namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;

class UserController extends ResourceController
{
    protected $userModel;
    protected $format = 'json';
    public function __construct()
    {
        $this->userModel = new UserModel();
    }
    public function index()
    {

// Get the limit, ids, and search keyword from the query parameters
        $limit = (int) $this->request->getGet('limit') ?: 10; // Default limit is 10
        $ids = $this->request->getGet('ids'); // Expecting a comma-separated list of IDs
        $search = $this->request->getGet('search'); // Search keyword

// Initialize query
        $query = clone $this->userModel;

// Filter by IDs if provided
        if ($ids) {
            $idArray = explode(',', $ids);
            $query = $this->userModel->whereIn('user_id', $idArray);
        }

// Filter by search keyword if provided
        if ($search) {
            $query = $this->userModel->groupStart() // Start a group for OR conditions
                ->like('firstname', $search)
                ->orLike('lastname', $search)
                ->orLike('email', $search)
                ->orLike('address', $search)
                ->groupEnd(); // End the group
        }

// Apply limit
        $users = $query->limit($limit)->findAll();
        if (empty($users)) {
            $response = [
                'status' => 200,
                'error' => 404,
                'message' => 'No users found.',
                'data' => [],
            ];
            return $this->respond($response);
        }
// Return response

        $transformedUsers = array_map([$this->userModel, 'transformData'], $users);

// Return response
        $response = [
            'status' => 200,
            'error' => null,
            'message' => null,
            'data' => $transformedUsers,

        ];
        return $this->respond($response, 200);

    }

// Method to update an existing user
    public function profileupdate()
    {

// Validation Rules with Custom Messages
        $validationRules = [
            'lastname' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'The Last Name is required.',
                ],
            ],
            'firstname' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'The First Name is required.',
                ],
            ],

            'user_mobile' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'The Mobile Number is required.',
                ],
            ],
            'email' => [
                'rules' => 'required|valid_email',
                'errors' => [
                    'required' => 'The Email is required.',
                    'valid_email' => 'The Email is not valid.',
                ],
            ],

        ];

// Validate the input data
        if (!$this->validate($validationRules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

// Get the input data as JSON
        $data = $this->request->getJSON(true);
        helper('jwt_helper');
        $userId = getUserInfoFromToken($this->request);

        $userModel = new UserModel();
        $user = $this->userModel->where('uuid', $userId)->first();

        if (!$user) {
            return $this->failNotFound('User not found');
        }
// Update the user record
        if ($userModel->update($user['user_id'], $data)) {
            helper('activity_helper');
            helper('jwt_helper');
            $userId = getUserInfoFromToken($this->request);
            $userAgent = $this->request->getUserAgent()->getAgentString();
            $ipAddress = $this->request->getIPAddress();

            logActivity([
                'user_id' => $user['user_id'],
                'uuid' => $userId,
                'section_tbl' => 'user_m',
                'action' => 'updated',
                'description' => "User with UUID $userId was updated.",
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_by' => $userId,
            ]);

            return $this->respondUpdated(['message' => 'User updated successfully']);
        } else {
            return $this->fail('User update failed');
        }
    }

// Method to update an existing user
    public function update($uuid = null)
    {
        if ($uuid === null) {
            return $this->failValidationErrors('User ID is required');
        }

// Validation Rules with Custom Messages
        $validationRules = [

            'lastname' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'The Last Name is required.',
                ],
            ],
            'firstname' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'The First Name is required.',
                ],
            ],

            'user_mobile' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'The Mobile Number is required.',
                ],
            ],
            'email' => [
                'rules' => 'required|valid_email',
                'errors' => [
                    'required' => 'The Email is required.',
                    'valid_email' => 'The Email is not valid.',
                ],
            ],

        ];

// Validate the input data
        if (!$this->validate($validationRules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
// Find the user by UUID
        $user = $this->userModel->where('uuid', $uuid)->first();

        if (!$user) {
            return $this->failNotFound('User not found');
        }
// Get the input data as JSON
        $data = $this->request->getJSON(true);

// Update the user record
        if ($this->userModel->update($user['user_id'], $data)) {
            helper('activity_helper');
            helper('jwt_helper');
            $userId = getUserInfoFromToken($this->request);
            $userAgent = $this->request->getUserAgent()->getAgentString();
            $ipAddress = $this->request->getIPAddress();

            logActivity([
                'user_id' => $user['user_id'],
                'uuid' => $userId,
                'section_tbl' => 'user_m',
                'action' => 'updated',
                'description' => "User with UUID $uuid was updated.",
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_by' => $userId,
            ]);

            return $this->respondUpdated(['message' => 'User updated successfully']);
        } else {
            return $this->fail('User update failed');
        }
    }

// Method to create a new user
    public function create()
    {
        helper('jwt_helper');
        $enteredBy = getUserInfoFromToken($this->request);
//$userModel = new UserModel();
// Get the input data as JSON
        $data = $this->request->getJSON(true);

        $validationRules = [
            'username' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'The username is required.',
                ],
            ],
            'pass_crypted' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'The password is required.',
                ],
            ],
            'lastname' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'The Last Name is required.',
                ],
            ],
            'firstname' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'The First Name is required.',
                ],
            ],

            'user_mobile' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'The Mobile Number is required.',
                ],
            ],
            'email' => [
                'rules' => 'required|valid_email',
                'errors' => [
                    'required' => 'The Email is required.',
                    'valid_email' => 'The Email is not valid.',
                ],
            ],

        ];
// Validate the input data
        if (!$this->validate($validationRules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
// Check if the username already exists
        if ($this->userModel->usernameExists($data['username'])) {
            return $this->fail('Username already exists', 409);
        }
// Check if the username already exists
        if ($this->userModel->emailExists($data['email'])) {
            return $this->fail('email already exists', 409);
        }
        $data['uuid'] = \Ramsey\Uuid\Uuid::uuid4()->toString(); // Generate a UUID
        $data['pass_crypted'] = password_hash($data['pass_crypted'], PASSWORD_BCRYPT);
        $user = $this->userModel->insertUser($data, $enteredBy);

        if ($user) {
            $user = $this->userModel->findByUuid($data['uuid']);
            $response = [
                'status' => 200,
                'error' => null,
                'message' => '',
                'data' => $user,

            ];
            return $this->respond($response, 200);
//kkishorereturn $this->respondCreated($user);

        }

        return $this->fail('User creation failed');
    }
    public function changePassword()
    {
        helper('jwt_helper');
        $userId = getUserInfoFromToken($this->request);

        $data = $this->request->getJSON(true);
// Validate the input
        if (!isset($data['old_password']) || !isset($data['new_password'])) {
            return $this->failValidationErrors('Required fields are missing');
        }

        $oldPassword = $data['old_password'];
        $newPassword = $data['new_password'];

// Fetch the user by ID
// $userModel = new UserModel();
        $user = $this->userModel->find($userId);

        if (!$user) {
            return $this->failNotFound('User not found');
        }

// Verify the old password
        if (!password_verify($oldPassword, $user['pass_crypted'])) {
            return $this->failValidationErrors('Old password is incorrect');
        }

// Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

// Update the user's password
        $this->userModel->update($userId, ['pass_crypted' => $hashedPassword, 'MODIFIED_BY' => $userId]);

        return $this->respond(['message' => 'Password updated successfully']);
    }
// Delete a user (soft delete is not used, so this will remove the user)
    public function delete($uuid = null)
    {
        if ($uuid === null) {
            return $this->failValidationErrors('User ID is required', 404);
        }

// Find the user by UUID
        $user = $this->userModel->where('uuid', $uuid)->first();

        if (!$user) {
            return $this->failNotFound('User not found');
        }

// Perform a soft delete using the primary key (user_id)
        if ($this->userModel->delete($user['user_id'])) {
            helper('activity_helper');
            helper('jwt_helper');
            $userId = getUserInfoFromToken($this->request);
            $userAgent = $this->request->getUserAgent()->getAgentString();
            $ipAddress = $this->request->getIPAddress();

            logActivity([
                'user_id' => $user['user_id'],
                'uuid' => $uuid,
                'section_tbl' => 'user_m',
                'action' => 'delete',
                'description' => "User with UUID $uuid was soft-deleted.",
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_by' => $userId,
            ]);

            return $this->respondDeleted(['status' => 'User soft-deleted successfully']);
        }
        return $this->fail('User deletion failed');
    }
// Method to retrieve a single user
    public function show($uuid = null)
    {
        if ($uuid === null) {
            return $this->failValidationErrors('User ID is required', 404);
        }
// Find the user by UUID
        $user = $this->userModel->where('uuid', $uuid)->first();
        $user = $this->userModel->transformData($user);

        if ($user) {
            $response = [
                'status' => 200,
                'error' => null,
                'message' => null,
                'data' => $user,
            ];
            return $this->respond($response);
        }

        if (!$user) {
            return $this->failNotFound('User not found');
        }

//return $this->failNotFound('User not found');
    }
}
