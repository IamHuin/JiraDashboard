<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('jira_overdues')) {
            Schema::table('jira_overdues', function (Blueprint $table) {
                if (!Schema::hasColumn('jira_overdues', 'note')) {
                    $table->string('note')->nullable()->comment('Ghi chú');
                }
            });
        }
    }

    public function down(): void
    {
        // down
    }
};
