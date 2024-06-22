<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class Tenant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'domain',
        'token'
    ];

    public function apiRequest($api_endpoint, $query = [], $method = 'GET') {
        $url = $this->domain . $api_endpoint;

        if (in_array($method, ['GET', 'DELETE','POST','PUT']) && !is_null($query)) {
            $httpClient = Http::withHeader('X-Shopify-Access-Token', $this->token);
            switch ($method) {
                case 'GET':
                    $response = $httpClient->get($url, $query);
                    break;
                case 'DELETE':
                    $response = $httpClient->delete($url, $query);
                    break;
                case 'POST':
                    $response = $httpClient->post($url, $query);
                    break;
                case 'PUT':
                    $response = $httpClient->put($url, $query);
                    break;
            }

            if ($response->ok()) {
                return $response->body();
            } else {
                return $response->body();
            }
        }

        return $url;
    }
}
