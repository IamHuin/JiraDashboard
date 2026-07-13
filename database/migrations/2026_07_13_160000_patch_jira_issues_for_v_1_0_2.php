<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('jira_issues')) {
            Schema::table('jira_issues', function (Blueprint $table) {
                if (!Schema::hasColumn('jira_issues', 'display_name')) {
                    $table->string('display_name')->nullable()->comment('Tên hiển thị');
                }
                if (!Schema::hasColumn('jira_issues', 'causer_displayName')) {
                    $table->string('causer_displayName')->nullable()->comment('Tên hiển thị người gây ra lỗi');
                }
            });
        }
    }

    public function down(): void
    {
        // down
    }
};
