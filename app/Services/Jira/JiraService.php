<?php

namespace App\Services\Jira;

use GuzzleHttp\Client;

class JiraService
{
    public function connectToJira($user, $url){
        
        $client = new Client([
            'base_uri' => 'https://jira.viettelsoftware.com',
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode("{$user->jira_username}:{$user->jira_password}"),
                'Accept' => 'application/json',
            ],
        ]);

        $resp = $client->get("{$url}");
        $status = $resp->getStatusCode();
        $body = (string)$resp->getBody();

        if ($resp->getStatusCode() !== 200) {
            return response()->json([
                'error' => "Jira trả HTTP {$status}: " . mb_substr($body, 0, 200),
            ], 401);
        }

        $userData = json_decode($body, true) ?: [];
        return $userData;
    }
}