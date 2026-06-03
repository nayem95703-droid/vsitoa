<?php

namespace App\Controllers\Api;

use Core\Request;
use Core\Response;

class ReferralController
{
    public function getReferralInfo(Request $request, Response $response): void
    {
        (new \App\Controllers\ReferralController())->getReferralInfo($request, $response);
    }

    public function getReferrals(Request $request, Response $response): void
    {
        (new \App\Controllers\ReferralController())->getReferrals($request, $response);
    }

    public function getReferralEarnings(Request $request, Response $response): void
    {
        (new \App\Controllers\ReferralController())->getReferralEarnings($request, $response);
    }
}
