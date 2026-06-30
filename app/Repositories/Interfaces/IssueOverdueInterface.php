<?php

namespace App\Repositories\Interfaces;

interface IssueOverdueInterface
{
    public function updateOrCreateIssue(array $data): void;

}