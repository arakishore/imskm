<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!function_exists('getUserInfoFromToken')) {
    function getUserInfoFromToken($request,$return='uid')
    {
        try {
            // Get the authorization header
            $authHeader = $request->getServer('HTTP_AUTHORIZATION');
            //print_r($token);
            if (!$authHeader) {
                throw new \Exception('Authorization header not found');
            }
    
            // Extract the token from the header
            $token = explode(' ', $authHeader)[1];
            if (!$token) {
                throw new \Exception('Token not found in authorization header');
            }
    
            // Decode the token
            $key = getenv('JWT_SECRET');
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            // Return the user ID from the decoded token
            if($return == "uid"){
                return $decoded->uid; // Assuming the token contains the user_id
            }
            if($return == "token"){
                return $token; // Assuming the token contains the user_id
            }
    
        } catch (\UnexpectedValueException $e) {
            // Handle JWT-specific errors such as expired or invalid token
            return ['error' => 'Invalid token: ' . $e->getMessage()];
        } catch (\Exception $e) {
            // Handle other potential errors
            return ['error' => 'An error occurred: ' . $e->getMessage()];
        }
    }
}
