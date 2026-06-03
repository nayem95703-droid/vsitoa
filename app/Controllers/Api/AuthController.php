<?php

namespace App\Controllers\Api;

use Core\Auth;
use Core\Request;
use Core\Response;

class AuthController
{
    public function register(Request $request, Response $response): void
    {
        (new \App\Controllers\AuthController())->register($request, $response);
    }

    public function login(Request $request, Response $response): void
    {
        (new \App\Controllers\AuthController())->login($request, $response);
    }

    public function logout(Request $request, Response $response): void
    {
        (new \App\Controllers\AuthController())->logout($request, $response);
    }

    public function forgotPassword(Request $request, Response $response): void
    {
        (new \App\Controllers\AuthController())->forgotPassword($request, $response);
    }

    public function resetPassword(Request $request, Response $response): void
    {
        (new \App\Controllers\AuthController())->resetPassword($request, $response);
    }

    public function verifyEmail(Request $request, Response $response): void
    {
        (new \App\Controllers\AuthController())->verifyEmail($request, $response);
    }

    public function resendVerification(Request $request, Response $response): void
    {
        (new \App\Controllers\AuthController())->resendVerification($request, $response);
    }
}
