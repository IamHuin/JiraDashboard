<?php

namespace App\Services\Manager;

use App\DTO\Manager\ManagerDTO;
use App\Enums\PaginateEnum;
use App\Repositories\Interfaces\ManagerInterface;
use App\Services\Cache\DashboardCacheService;
use App\Services\Dashboard\PaginationService;

class ManagerService
{
    public function __construct(protected ManagerInterface $managerRepo, protected DashboardCacheService $cacheService, protected PaginationService $paginationService)
    {
    }

    public function getListUsers(ManagerDTO $dto): array
    {
        $page = (int)request('page', PaginateEnum::DEFAULT_PAGE);
        $perPage = (int)request('per_page', PaginateEnum::DEFAULT_PER_PAGE);

        $paginator = $this->managerRepo->getListUsers($dto, $perPage);


        if (empty($paginator)) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy dữ liệu người dùng thành viên.',
                'data' => ['details' => ['list' => [], 'meta' => []]]
            ];
        }

        $data = [
            'details' => $this->paginationService->format($paginator)
        ];

        return [
            'success' => true,
            'message' => 'Lấy danh sách thành viên thành công.',
            'data'    => $data
        ];
    }

    public function updateUser(ManagerDTO $dto): array
    {
        $data = $this->managerRepo->updateUser($dto);

        if (empty($data)) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy dữ liệu người dùng thành viên.',
                'data' => []
            ];
        }

        return [
            'success' => true,
            'message' => 'Cập nhật thông tin thành viên thành công.',
            'data' => $data
        ];
    }
}