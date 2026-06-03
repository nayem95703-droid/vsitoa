<?php

namespace App\Controllers\Api;

use Core\Request;
use Core\Response;

class AdController
{
    public function getAds(Request $request, Response $response): void
    {
        (new \App\Controllers\AdController())->getAds($request, $response);
    }

    public function viewAd(Request $request, Response $response, string $id = null): void
    {
        // The underlying controller reads $request->param('id')
        (new \App\Controllers\AdController())->viewAd($request, $response);
    }

    public function completeView(Request $request, Response $response): void
    {
        (new \App\Controllers\AdController())->completeAdView($request, $response);
    }

    public function getAd(Request $request, Response $response, string $id = null): void
    {
        $response->json([
            'success' => false,
            'message' => 'Not implemented'
        ], 501);
    }
}
