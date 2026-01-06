<?php

namespace App\Http\Controllers\Common;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
class CommonController extends Controller
{
    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($result, $message)
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data'    => $result,
        ];


        return response()->json($response, 200);
    }


    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $errorMessages = [], $code = 400)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];
        if (!empty($errorMessages)) {
            $response['errors'] = $errorMessages;
        }


        return response()->json($response, $code);
    }
    public function searchlocation(Request $request) {
        $search = $request->search;
        $client = new Client([
            'timeout'  => 30,  // Timeout in seconds
            'allow_redirects' => [
                'max' => 10,  // Max redirections
            ],
        ]);
    
        try {
            $response = $client->request('GET', 'https://api.locationiq.com/v1/autocomplete', [
                'query' => [
                    'key' => env('LOCATION_IQ'),
                    'limit' => 6,
                    'q' => $search,
                ]
            ]);
    
            // Get the response body
            $body = json_decode($response->getBody()->getContents(), true);
    
            return response()->json($body);
    
        } catch (RequestException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

     
}