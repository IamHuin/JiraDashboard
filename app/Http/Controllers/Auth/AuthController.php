<?php

namespace App\Http\Controllers\Auth;

use App\DTO\Auth\AuthDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AuthRequest;
use App\Models\User;
use App\Services\Ping\ConnectJiraService;
use App\Services\Sync\SyncIssueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;

class AuthController extends Controller
{
    protected $jira;
    protected $syncService;

    public function __construct(ConnectJiraService $jira, SyncIssueService $syncService)
    {
        $this->jira = $jira;
        $this->syncService = $syncService;
    }

    public function Login(AuthRequest $request): JsonResponse
    {
        $dto = AuthDTO::login($request->input('username'), $request->input('password'));

        $url = "/rest/api/2/myself";
        $userData = $this->jira->connectToJira($dto, $url);

        if (!empty($userData['error'])) {
            return response()->json([
                'error' => 'Login failed',
                'message' => $userData['error'],
            ], 401);
        }

        $user = User::updateOrCreate(
            ['jira_username' => $dto->jira_username],
            [
                'jira_password' => Crypt::encryptString($dto->jira_password),
                'jira_display_name' => $userData['displayName']
            ]
        );

        $token = auth()->login($user);

        try {
            $this->syncService->syncAndFetchProjects();
        } catch (\Exception $e) {
            \Log::error("Lỗi đồng bộ dự án trực tiếp khi Login: " . $e->getMessage());
        };

        return response()->json([
            'token' => $token,
            'display_name' => $userData['displayName'] ?? $userData['name'] ?? 'unknown',
        ]);
    }

    public function Logout(): JsonResponse
    {
        auth()->logout();

        return response()->json([
            'success' => true,
            'message' => 'User has been logged out'
        ]);
    }

    public function Refresh(): JsonResponse
    {
        $newToken = auth()->refresh();

        return response()->json([
            'success' => true,
            'token' => $newToken,
        ]);
    }
}