<?php

namespace App\Controllers;

use App\Models\UserLoginLogModel;
use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\JWT;

class AuthController extends ResourceController
{
    protected $format = 'json';
    public function test()
    {
        return $this->respond(['message' => 'API is working'], 200);
    }
    public function login()
    {
        $loginSuccess = 0;
        $rules = [
            'username' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Uername is required.',
                ],
            ],
            'password' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Password is required.',
                ],
            ],
        ];
        $logData = [
            'username' => '', // Replace with the actual user ID
            'user_id' => 0, // Replace with the actual user ID
            'login_time' => date('Y-m-d H:i:s'),
            'ip_address' => $this->request->getIPAddress(),
            'browser' => $this->request->getUserAgent()->getBrowser(),
            'os' => $this->request->getUserAgent()->getPlatform(),
            'device' => '',
            'status_flag' => $loginSuccess ? 'success' : 'failure'
        ];
    
        // Log the login attempt
        $loginLogModel = new UserLoginLogModel();
        
        if (!$this->validate($rules)) {
            $loginLogModel->insert($logData);
            $response = [
                'status'=> 401,
                'error'=> 401,
                'message' => $this->validator->getErrors(),
                'data' => null
            ];
            return $this->respond($response);
            //return $this->failValidationErrors($this->validator->getErrors());
        }
        $username = $this->request->getVar('username');
        $pass_crypted = $this->request->getVar('password');
       
        // $json = $this->request->getJSON();
        // $username = $json->username;
        // $pass_crypted = $json->password;
        $userModel = new UserModel();
        $user = $userModel->where('username', $username)
                   ->where('status_flag', 'Active')
                   ->first();

        if ($user && password_verify($pass_crypted, $user['pass_crypted'])) {
            
            $key = getenv('JWT_SECRET');
            $timeToken = getenv('JWT_TIME_TO_LIVE');
            $iat = time();
            $exp = $iat + $timeToken;

            $payload = array(
                "iat" => $iat,
                "exp" => $exp,
                "uid" => $user['uuid'],
            );
            unset($user['pass_crypted']);
            $token = JWT::encode($payload, $key, 'HS256');

            $logData['username'] = $username;
            $logData['user_id'] = $user['user_id'];
            $logData['status_flag'] = 'success';
            $loginLogModel->insert($logData);

            $customInfo = [
                'uuid' => $user['uuid'],
                'user_id' => $user['user_id'],
                'userName' => $user['username'],
                'gender' => $user['gender'],
                'firstName' => $user['firstname'],
                'lastName' => $user['lastname'],
                'address' => $user['address'],
                'city' => $user['fk_city'],
                'postalCode' => $user['zip'],
                'state' => $user['fk_state'],
                'country' => $user['fk_country'],
                'birthDate' => $user['birth'],
                'mobileNumber' => $user['user_mobile'],
                'emailAddress' => $user['email'],
                'lastLoginDate' => $user['datelastlogin'],
                'lastLoginIP' => $user['iplastlogin'],
                'profilePhoto' => $user['photo'],
                'locationName' => $user['fk_location_id']
            ];

            $response = [
                'status'=> 200,
                'error'=> null,
                'message' => null,
                'data' => $customInfo,
                'access_token' => $token,
            ];

            $tokenModel = new \App\Models\TokenModel();
            $tokenData['token'] = $token;
            $tokenModel->insert($tokenData);

            return $this->respond($response);
            //return $this->respond(['token' => $token]);
        } else {
            // $response = [
            //     'status' => 0,
            //     'message' => 'Invalid username or password.',
            // ];
            // return $this->respond($response, 401);
            $logData['username'] = $username;
            $logData['status_flag'] = 'failure';
            $loginLogModel->insert($logData);
            $response = [
                'status'=> 401,
                'error'=> 401,
                'message' => [
                    'ms1'=> 'Invalid username or password.'
                ],
                'data' => null
            ];
            return $this->respond($response);
            //return $this->failUnauthorized('Invalid username or password.');
        }
    }
    public function logout()
    {
        // helper('jwt_helper');
        // $token = getUserInfoFromToken($this->request,'token');
        // $tokenModel = new \App\Models\TokenModel();
        // $tokenModel->deleteToken($token);
        $response = [
            'status'=> 200,
            'error'=> null,
            'message' => 'Logged out successfully',
            'data' => null,
            
        ];
        return $this->respond($response);
    }
    public function forgotPassword()
    {
        return $this->failUnauthorized('need to code here');
    }
}
