<?php

namespace App\Controllers\Api;

use Core\Request;
use Core\Response;

class AdvisorController
{
    public function getMyAds(Request $request, Response $response): void
    {
        (new \App\Controllers\AdvisorController())->getMyAds($request, $response);
    }

    public function createAd(Request $request, Response $response): void
    {
        (new \App\Controllers\AdvisorController())->createAd($request, $response);
    }

    public function updateAd(Request $request, Response $response, string $id = null): void
    {
        (new \App\Controllers\AdvisorController())->updateAd($request, $response);
    }

    public function deleteAd(Request $request, Response $response, string $id = null): void
    {
        (new \App\Controllers\AdvisorController())->deleteAd($request, $response);
    }

    public function pauseAd(Request $request, Response $response, string $id = null): void
    {
        (new \App\Controllers\AdvisorController())->pauseAd($request, $response);
    }

    public function resumeAd(Request $request, Response $response, string $id = null): void
    {
        (new \App\Controllers\AdvisorController())->resumeAd($request, $response);
    }

    public function getAdStats(Request $request, Response $response, string $id = null): void
    {
        (new \App\Controllers\AdvisorController())->getAdStats($request, $response);
    }
}
