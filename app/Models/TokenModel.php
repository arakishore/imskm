<?php

namespace App\Models;

use CodeIgniter\Model;

class TokenModel extends Model
{
    protected $table = 'token_generated'; // Table name
    protected $primaryKey = 'id'; // Primary key (assuming there's an `id` column)

    protected $useAutoIncrement = true; // Auto-increment for the primary key
    protected $returnType = 'array'; // Return results as an array

    // Allowed fields for mass assignment
    protected $allowedFields = ['token', 'created_at'];

    // Use timestamps
    protected $useTimestamps = true; // Automatically manage created_at and updated_at fields
    protected $createdField = 'created_at'; // Name of the created_at field

    // You can add validation rules if needed
    protected $validationRules = [
        'token' => 'required|string|max_length[500]',
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;
    public function isTokenValid($token)
    {
        $result = $this->where('token', $token)->first();
        return $result !== null;
    }
    public function deleteToken($token)
    {
        return $this->where('token', $token)->delete();
    }

}
