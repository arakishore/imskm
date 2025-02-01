<?php
namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'user_m';
    protected $primaryKey = 'user_id';
    protected $useSoftDeletes = true; // Enable soft deletes

    protected $allowedFields = [
        'uuid', 'username','is_employee', 'login', 'pass_crypted', 'gender', 'lastname', 'firstname', 
        'address', 'zip', 'fk_city', 'fk_state', 'fk_country', 'birth', 
        'user_mobile', 'email', 'idpers1', 'idpers2', 'datelastlogin', 
        'datepreviouslogin', 'iplastlogin', 'ippreviouslogin', 'photo', 
        'fk_location_id', 'ENTERED_BY', 'MODIFIED_BY', 'created_at', 'updated_at'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at'; // or any other field name

    public function getUserWithCustomFieldsUserName($id)
    {
        $user = $this->select([
            'user_m.*',
            'com_state_mst_t.STATE_NAME AS state',
            'com_city_mst_t.CITY_NAME AS city',
            'com_location_mst_t.LOCATION_NAME AS locationName',
            'com_country_mst_t.COUNTRY_NAME AS country'
        ])
        ->join('com_state_mst_t', 'com_state_mst_t.STATE_ID  = user_m.fk_state', 'left')
        ->join('com_city_mst_t', 'com_city_mst_t.CITY_ID  = user_m.fk_city', 'left')
        ->join('com_location_mst_t', 'com_location_mst_t.LOCATION_ID  = user_m.fk_location_id', 'left')
        ->join('com_country_mst_t', 'com_country_mst_t.COUNTRY_ID  = user_m.fk_country', 'left')
        ->where('user_m.user_id', $id)
        ->first();

        return $this->transformData($user);
    }
    public function getUserWithCustomFields($id)
    {
        $user = $this->select([
            'user_m.*',
            'com_state_mst_t.STATE_NAME AS state',
            'com_city_mst_t.CITY_NAME AS city',
            'com_location_mst_t.LOCATION_NAME AS locationName',
            'com_country_mst_t.COUNTRY_NAME AS country'
        ])
        ->join('com_state_mst_t', 'com_state_mst_t.STATE_ID  = user_m.fk_state', 'left')
        ->join('com_city_mst_t', 'com_city_mst_t.CITY_ID  = user_m.fk_city', 'left')
        ->join('com_location_mst_t', 'com_location_mst_t.LOCATION_ID  = user_m.fk_location_id', 'left')
        ->join('com_country_mst_t', 'com_country_mst_t.COUNTRY_ID  = user_m.fk_country', 'left')
        ->where('user_m.user_id', $id)
        ->first();
        return $this->transformData($user);
        
    }
// Method to find a user by UUID
public function findByUuid($uuid)
{
    $user = $this->select([
        'user_m.*',
        'com_state_mst_t.STATE_NAME AS state',
        'com_city_mst_t.CITY_NAME AS city',
        'com_location_mst_t.LOCATION_NAME AS locationName',
        'com_country_mst_t.COUNTRY_NAME AS country'
    ])
    ->join('com_state_mst_t', 'com_state_mst_t.STATE_ID  = user_m.fk_state', 'left')
    ->join('com_city_mst_t', 'com_city_mst_t.CITY_ID  = user_m.fk_city', 'left')
    ->join('com_location_mst_t', 'com_location_mst_t.LOCATION_ID  = user_m.fk_location_id', 'left')
    ->join('com_country_mst_t', 'com_country_mst_t.COUNTRY_ID  = user_m.fk_country', 'left')
    ->where('user_m.uuid', $uuid)
    ->first();
    return $this->transformData($user);
}
    // Private method to transform user data into custom format
    public function transformData($user)
    {
        if (!$user) {
            return null;
        }

        return [
            'uuid'           => $user['uuid'],
            'user_id'        => $user['user_id'],
            'userName'       => $user['username'],
            'gender'         => $user['gender'],
            'firstName'      => $user['firstname'],
            'lastName'       => $user['lastname'],
            'address'        => $user['address'],
            'city'           => '',
            'postalCode'     => $user['zip'],
            'state'          => '',
            'country'        => '',
            'birthDate'      => $user['birth'],
            'mobileNumber'   => $user['user_mobile'],
            'emailAddress'   => $user['email'],
            'lastLoginDate'  => $user['datelastlogin'],
            'lastLoginIP'    => $user['iplastlogin'],
            'profilePhoto'   => $user['photo'],
            'locationName'   => ''
        ];
    }

    // Method to update an existing user
    public function updateUser($id, $data, $modifiedBy)
    {
        $data['MODIFIED_BY'] = $modifiedBy;
        $this->update($id, $data);
        return $this->transformData($this->find($id));
    }
    // Insert a new record
    public function insertUser($data, $userId)
    {
        $data['ENTERED_BY'] = $userId; // Set the user who is entering the record
        return $this->insert($data);
    }
     // Method to check if a username exists
     public function usernameExists($username)
     {
         return $this->where('username', $username)->first() !== null;
     }
      // Method to check if a username exists
    public function emailExists($email)
    {
        return $this->where('email', $email)->first() !== null;
    }
}
