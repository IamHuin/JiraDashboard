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
    ) {
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
            $this->syncService->syncAndFetchProjects($user);
        } catch (Exception $e) {
            Log::error("Lỗi đồng bộ dự án trực tiếp khi Login (Vẫn cho phép login tiếp tục): " . $e->getMessage());
        }

        $responseData = [
            'token' => $token,
            'display_name' => $userData['displayName'] ?? $userData['name'] ?? 'unknown',
            'super_admin' => $user->super_admin ?? 0,
        ];

        // Dọn dẹp bộ nhớ giải phóng RAM
        unset($userData, $user, $token);

        return $responseData;
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
            'super_admin' => $user->super_admin ?? 0,
        ];
    }
}