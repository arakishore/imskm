<?php
namespace App\Models;

use CodeIgniter\Model;

class UserLoginLogModel extends Model
{
    protected $table = 'user_login_log';
    protected $primaryKey = 'id'; // Assuming there's a primary key
    protected $allowedFields = [
        'user_id',
        'username',
        'login_time',
        'ip_address',
        'browser',
        'os',
        'device',
        'status_flag'
    ];
}
