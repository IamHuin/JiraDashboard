<?php

namespace App\Http\Controllers\Manager;

use App\DTO\Manager\ManagerDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\ManagerRequest;
use App\Services\Manager\ManagerService;
use Illuminate\Http\JsonResponse;

class ManagerController extends Controller
{
    public function __construct(protected ManagerService $managerService)
    {
    }

    public function getListUsers(ManagerRequest $request): JsonResponse
    {
        $dto = ManagerDTO::fromArray($request->validated());

        $result = $this->managerService->getListUsers($dto);

        return response()->json($result);
    }

    public function updateUser(ManagerRequest $request): JsonResponse
    {
        $dto = ManagerDTO::fromArray($request->validated());

        $result = $this->managerService->updateUser($dto);

        return response()->json($result);
    }
}