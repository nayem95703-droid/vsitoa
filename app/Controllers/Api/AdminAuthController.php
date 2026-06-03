<?php

namespace App\Controllers\Api;

use Core\Request;
use Core\Response;

class AdminAuthController
{
    public function login(Request $request, Response $response): void
    {
        (new \App\Controllers\AuthController())->adminLogin($request, $response);
    }

    public function logout(Request $request, Response $response): void
    {
        (new \App\Controllers\AuthController())->adminLogout($request, $response);
    }
}
