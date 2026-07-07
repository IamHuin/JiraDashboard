<?php

namespace App\Services\Auth;

use App\DTO\Auth\AuthDTO;
use App\Repositories\Interfaces\UserInterface;
use App\Services\Ping\ConnectJiraService;
use App\Services\Sync\SyncIssueService;
use Exception;
use Illuminate\Support\Facades\Log;

class AuthService
{
    protected $jira;
    protected $syncService;
    protected $userRepo;

    public function __construct(
        ConnectJiraService $jira,
        SyncIssueService   $syncService,
        UserInterface      $userRepo
    )
    {
        $this->jira = $jira;
        $this->syncService = $syncService;
        $this->userRepo = $userRepo;
    }

    /**
     * Xử lý logic đăng nhập hệ thống qua Jira
     * @throws Exception
     */
    public function handleLogin(AuthDTO $dto): array
    {
        if ($dto->jira_username === 'admin') {
            return $this->handleSuperAdmin($dto);
        }

        $url = "/rest/api/2/myself";
        $userData = $this->jira->connectToJira($dto, $url);

        if (!empty($userData['error'])) {
            throw new Exception($userData['error']);
        }

        $user = $this->userRepo->updateOrCreateByJira($dto, $userData);

        $token = auth()->login($user);

        try {
            $this->syncService->syncAndFetchProjects();
        } catch (Exception $e) {
            Log::error("Lỗi đồng bộ dự án trực tiếp khi Login: " . $e->getMessage());
        }

        return [
            'token' => $token,
            'display_name' => $userData['displayName'] ?? $userData['name'] ?? 'unknown',
            'email' => $userData['emailAddress'] ?? 'unknown',
            'super_admin' => $user->super_admin ?? 0,
        ];
    }

    protected function handleSuperAdmin(AuthDTO $dto): array
    {
        $credentials = [
            'jira_username' => $dto->jira_username,
            'password' => $dto->jira_password,
        ];

        if (!$token = auth()->attempt($credentials)) {
            throw new Exception('Tài khoản hoặc mật khẩu admin không chính xác.');
        }

        $user = auth()->user();

        return [
            'token' => $token,
            'display_name' => $user->jira_display_name ?? 'Super Admin',
            'email' => $user->jira_email ?? 'admin@gmail.com',
            'super_admin' => $user->super_admin ?? 0,
        ];
    }
}