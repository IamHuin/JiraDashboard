<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\JiraNltcInterface;
use Illuminate\Support\Facades\DB;

class JiraNltcRepository implements JiraNltcInterface
{
    /**
     * @inheritDoc
     */
    public function upsertData(array $data): int
    {
        return DB::table('jira_nltc')->upsert(
            $data, 
            ['period', 'project_name', 'user_name', 'display_name', 'role', 'level', 'standard']
        );
    }
}
