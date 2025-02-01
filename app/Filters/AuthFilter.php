<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Config\Services;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $key = getenv('JWT_SECRET');
        $response = [
            'status'=> 401,
            'error'=> true,
            'message' => null,
            'data' => null,
            
        ];
        if (!$key) {
            $response['message']['msg1'] = 'JWT secret key not found';
            $response['error'] = 500;
            return Services::response()->setStatusCode(500)->setJSON($response);
        }

        $authHeader = $request->getServer('HTTP_AUTHORIZATION');

        if (!$authHeader) {
            $response['message']['msg1'] = 'No token provided';
            return Services::response()->setStatusCode(401)->setJSON($response);
        }

        $arr = explode(' ', $authHeader);

        if (count($arr) !== 2 || strtolower($arr[0]) !== 'bearer' || empty($arr[1])) {
            $response['message']['msg1'] = 'Malformed token';
            return Services::response()->setStatusCode(401)->setJSON($response);
        }

        $token = $arr[1];
        $tokenModel = new \App\Models\TokenModel();
        if (!$tokenModel->isTokenValid($token)) {
            $response['message']['msg1'] = 'Token is invalid or expired';
            return Services::response()->setStatusCode(401)->setJSON($response);
        }
        try {
            //$decoded = JWT::decode($token, $key, ['HS256']);
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            // Store the decoded data in a shared service instance
            $jwtData = Services::jwtData();
            $jwtData->user = $decoded;

        } catch (ExpiredException $e) {
            $response['message']['msg1'] = 'Token has expired';
            return Services::response()->setStatusCode(401)->setJSON($response);
        } catch (SignatureInvalidException $e) {
            $response['message']['msg1'] = 'Invalid token signature';
            return Services::response()->setStatusCode(401)->setJSON($response);
        } catch (\Exception $e) {
            $response['message']['msg1'] = 'Invalid token';
            return Services::response()->setStatusCode(401)->setJSON($response);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed after the response
    }
}
