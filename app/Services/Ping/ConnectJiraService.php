<?php

namespace App\Services\Ping;

use Exception;
use GuzzleHttp\Client;

class ConnectJiraService
{
    protected $baseUri;
    protected $startAt;
    protected $maxResults;
    protected $client;

    public function __construct()
    {
        $this->baseUri = config('services.jira.base_uri', 'https://jira.viettelsoftware.com');
        $this->startAt = config('services.jira.start_at', 0);
        $this->maxResults = config('services.jira.max_results', 200);
    }

    protected function initClient($user)
    {
        if (!$this->client) {
            $this->client = new Client([
                'base_uri' => $this->baseUri,
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode("{$user->jira_username}:{$user->jira_password}"),
                    'Accept' => 'application/json',
                ],
            ]);
        }
        return $this->client;
    }

    public function connectToJira($user, $url)
    {
        $client = $this->initClient($user);
        try {
            $resp = $client->get($url);
            $body = (string)$resp->getBody();
            return json_decode($body, true) ?: [];
        } catch (Exception $e) {
            return ['issues' => [], 'total' => 0, 'error' => $e->getMessage()];
        }
    }
    
    public function connectToJiraAsync($user, $url)
    {
        $client = $this->initClient($user);
        return $client->getAsync($url);
    }
}