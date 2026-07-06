<?php

namespace App\Http\Controllers\Auth;

use App\DTO\Auth\AuthDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AuthRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Exception;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function Login(AuthRequest $request): JsonResponse
    {
        $dto = AuthDTO::login($request->input('username'), $request->input('password'));

        try {
            $result = $this->authService->handleLogin($dto);

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Login failed',
                'message' => $e->getMessage(),
            ], 401);
        }
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