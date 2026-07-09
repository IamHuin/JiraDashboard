<?php

namespace App\Repositories\Interfaces;

interface RoleInterface
{
    public function getListRoles();
    public function getDetailRole($id);
    public function createRole(array $data);
    public function updateRole($id, array $data);
    public function deleteRole($id);
}