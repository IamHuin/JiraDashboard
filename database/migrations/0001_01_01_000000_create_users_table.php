<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('jira_username')->unique();
            $table->string('jira_password');
            $table->foreignId('role_id')->constrained('roles');
            $table->json('jira_projects_json')->nullable();
            $table->json('jira_projects_role_json')->nullable();
            $table->string('jira_display_name')->nullable();
            $table->tinyInteger('super_admin')->default(0)->comment('0: Không phải Super admin, 1: Super admin');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};