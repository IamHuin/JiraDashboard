<?php

namespace App\Repositories\Interfaces;

interface IssueOverdueInterface
{
    public function upsertIssues(array $multipleData): void;

}