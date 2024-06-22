<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;

class InstallController extends Controller
{
    private const SCOPES = 'read_products,write_products';
    private const ACCESS_MODE = 'offline';
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $shopUrl = 'https://' . $request->input('shop');
        $apiKey = env('SHOPIFY_API_KEY');
        $nonce = bin2hex(random_bytes(12));
        $oauthUrl = $shopUrl . '/admin/oauth/authorize?' . http_build_query(
            [
                'client_id' => $apiKey,
                'scope' => self::SCOPES,
                'redirect_uri' => env('NGROK_URL') . '/redir',
                'state' => $nonce,
                'grant_options[]' => self::ACCESS_MODE
            ]
        );
        return redirect()->away($oauthUrl);
    }
}
