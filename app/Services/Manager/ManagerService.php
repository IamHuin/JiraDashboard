<?php

namespace App\Services\Manager;

use App\DTO\Manager\ManagerDTO;
use App\Repositories\Interfaces\ManagerInterface;

class ManagerService
{
    public function __construct(protected ManagerInterface $managerRepo) {}

    public function getListUsers(ManagerDTO $dto): array
    {
        $data = $this->managerRepo->getListUsers($dto);

        if (empty($data)) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy dữ liệu người dùng thành viên.',
                'data'    => []
            ];
        }

        return [
            'success' => true,
            'message' => 'Lấy danh sách thành viên thành công.',
            'data'    => $data
        ];
    }
}