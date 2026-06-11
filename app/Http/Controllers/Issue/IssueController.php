<?php

namespace App\Http\Controllers\Issue;

use App\Http\Controllers\Controller;
use App\Services\Jira\JiraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class IssueController extends Controller
{
    protected $jira;

    public function __construct(JiraService $jira)
    {
        $this->jira = $jira;
    }

    public function issues()
    {
        $user = Auth::user();
        $user->jira_password = Crypt::decryptString($user->jira_password);
        $jql = "project = BU1VICVTF4";
        $url = "/rest/api/2/search?jql=" . urlencode($jql);

        $userData = $this->jira->connectToJira($user, $url);

        return response()->json([
            'success' => true,
            'issues' => $userData
        ]);
    }
}
