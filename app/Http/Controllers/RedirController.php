<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Tenant;

class RedirController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $apiKey = env('SHOPIFY_API_KEY');
        $apiSecretKey = env('SHOPIFY_API_SECRET');
        $parameters = $request->all();
        unset($parameters['hmac']);
        ksort($parameters);
        $hmac = $request->input('hmac');
        $newHmac = hash_hmac('sha256', http_build_query($parameters), $apiSecretKey);

        if (hash_equals($hmac, $newHmac)) {
            $shopUrl = 'https://' . $request->input('shop');
            $accessTokenEndPoint = $shopUrl . '/admin/oauth/access_token';
            $data = [
                'client_id' => $apiKey,
                'client_secret' => $apiSecretKey,
                'code' => $request->input('code')
            ];
            $response = Http::post($accessTokenEndPoint, $data);
            $responseArray = json_decode($response->body(), true);
            if (isset($responseArray['access_token'])) {
                Tenant::upsert(
                    [
                        [
                            'domain' => $shopUrl,
                            'token' => $responseArray['access_token']
                        ]
                    ],
                    ['domain'],
                    ['token']
                );
            }
            
            return redirect()->away(env('NGROK_URL').'/');
        } else {
            return 'ERROR';
        }
    }
}
