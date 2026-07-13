<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Thêm cột display_name cho jira_bug_ratios (nếu chưa có)
        if (Schema::hasTable('jira_bug_ratios') && !Schema::hasColumn('jira_bug_ratios', 'display_name')) {
            Schema::table('jira_bug_ratios', function (Blueprint $table) {
                // Thêm display_name vào sau user_name
                $table->string('display_name')->nullable()->comment('Tên hiển thị')->after('user_name');
            });
        }

        if (Schema::hasTable('jira_overdues') && !Schema::hasColumn('jira_overdues', 'display_name')) {
            Schema::table('jira_overdues', function (Blueprint $table) {
                $table->string('display_name')->nullable()->comment('Tên hiển thị');
            });
        }

        if (Schema::hasTable('jira_usbudgets') && !Schema::hasColumn('jira_usbudgets', 'display_name')) {
            Schema::table('jira_usbudgets', function (Blueprint $table) {
                $table->string('display_name')->nullable()->comment('Tên hiển thị');
            });
        }

        if (Schema::hasTable('jira_nltc') && Schema::hasColumn('jira_nltc', 'project')) {
            Schema::table('jira_nltc', function (Blueprint $table) {
                // Đổi tên cột
                $table->renameColumn('project', 'project_name');
                $table->renameColumn('username', 'user_name');
            });

            try {
                Schema::table('jira_nltc', function (Blueprint $table) {
                    $table->dropUnique('uk_jira_nltc_all');
                });
            } catch (\Exception $e) {
            }

            Schema::table('jira_nltc', function (Blueprint $table) {
                $table->unique(['period', 'project_name', 'user_name', 'display_name', 'role', 'level', 'standard'], 'uk_jira_nltc_all_new');
            });
        }
        
    }

    public function down(): void
    {
    }
};
