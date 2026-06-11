<?php

namespace App\Http\Controllers\Auth;

use App\DTO\Auth\AuthDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AuthRequest;
use App\Models\User;
use App\Services\Jira\JiraService;
use Illuminate\Support\Facades\Crypt;

class AuthController extends Controller
{
    protected $jira;

    public function __construct(JiraService $jira)
    {
        $this->jira = $jira;
    }
    public function login(AuthRequest $request)
    {
        $dto = AuthDTO::login($request->input('username'), $request->input('password'));

        try {
            $url = "/rest/api/2/myself";
            $userData = $this->jira->connectToJira($dto, $url);

            $token = auth()->login(User::updateOrCreate(
                ['jira_username' => $dto->jira_username],
                ['jira_password' => Crypt::encryptString($dto->jira_password)]
            ));

            return response()->json([
                'token' => $token,
                'jira_user' => $userData,
                'display_name' => $userData['displayName'] ?? $userData['name'] ?? 'unknown',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Login failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function logout(){
        auth()->logout();

        return response()->json([
            'success' => true,
            'message' => 'User has been logged out'
        ]);
    }
    public function refresh(){
        $newToken = auth()->refresh();

        return response()->json([
            'success' => true,
            'token' => $newToken,
        ]);
    }
}
