<?php

namespace App\Http\Controllers\Manager;

use App\DTO\Manager\ManagerDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\ManagerRequest;
use App\Services\Manager\ManagerService;
use Illuminate\Http\JsonResponse;

class ManagerController extends Controller
{
    // PHP 8+ tự động khai báo và gán giá trị cho property qua constructor
    public function __construct(protected ManagerService $managerService) {}

    public function getListUsers(ManagerRequest $request): JsonResponse
    {
        $dto = ManagerDTO::fromArray($request->validated());

        $result = $this->managerService->getListUsers($dto);

        return response()->json($result);
    }
}