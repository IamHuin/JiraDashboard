<?php

namespace App\Repositories\Interfaces;

interface JiraNltcInterface
{
    /**
     * Upsert data into jira_nltc table
     *
     * @param array $data
     * @return int
     */
    public function upsertData(array $data): int;
}
