<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        if ($request->getMethod() == 'POST') {
            /**@var Tenant */
            $tenant = Tenant::whereNotNull('domain')->first();
            $productInput = $request->input('product');

            Product::upsert(
                [
                    [
                        'sku' => $productInput['kode'],
                        'data' => json_encode($request->input())
                    ]
                ],
                ['sku'],
                ['data']
            );
            $product = Product::where('sku', $productInput['kode'])->first();
            //map product
            $dataProduct = [
                "product" => [
                    "title" => $productInput['nama'],
                    "body_html" => $productInput['deskripsi'],
                    "status" => $productInput['status'] == 'Enable' ? 'active' : 'draft',
                    "images" => $productInput['gambar'],
                    "variants" => [
                        [
                            "price" => $productInput['harga'],
                            "sku" => $productInput['kode'],
                            "title" => $productInput['nama'],
                            "weight" => $productInput['berat'],
                            "weight_unit" => 'kg'
                        ]
                    ]
                ]
            ];

            if ($product->shop_product_id)  {
                $graphQlQuery = "query {
                    products(first: 1, query: \"%s\") {
                        edges {
                            node {
                                legacyResourceId
                            }
                        }
                    }
                }";
                $graphQlQuery = sprintf($graphQlQuery, "sku:".$productInput['kode']);
                $checkProductArrayResponse = json_decode($tenant->apiRequest('/admin/api/2024-04/graphql.json', ['query' => $graphQlQuery], 'POST'), true);
                foreach ($checkProductArrayResponse['data']['products']['edges'] as $productEdge) {
                    foreach ($productEdge as $node) {
                        if ($node['legacyResourceId']) {
                            $product->shop_product_id = $node['legacyResourceId'];
                            $product->save();
                            break;
                        }
                    }

                    $dataProduct['id'] = $product->shop_product_id;
                    $responseArray = json_decode(
                        $tenant->apiRequest(
                            sprintf('/admin/api/2024-04/products/%s.json', $product->shop_product_id),
                            $dataProduct,
                            'PUT'
                        ),
                        true
                    );

                    return [
                        'success' => true
                    ];
                }
            }

            $responseArray = json_decode($tenant->apiRequest('/admin/api/2024-04/products.json', $dataProduct, 'POST'), true);
            if (isset($responseArray['product']['id'])) {
                $product->shop_product_id = $responseArray['product']['id'];
                $product->save();
            }

            return [
                'success' => true
            ];
        }

        return $request->getMethod();
    }
}
