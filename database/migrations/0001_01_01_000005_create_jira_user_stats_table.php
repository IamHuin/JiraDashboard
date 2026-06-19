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
        Schema::create('jira_user_stats', function (Blueprint $table) {
            $table->id();

            $table->date('period')->comment('Tháng/năm thống kê');
            $table->string('project_name')->nullable()->comment('Tên dự án trên Jira');
            $table->string('user_name')->comment('Tên người dùng: causer hoặc assignee');
            $table->integer('subtask_count')->default(0)->comment('Tổng Sub-task');
            $table->integer('bug_count')->default(0)->comment('Tổng Bug');
            $table->decimal('bug_percent', 5, 2)->default(0)->comment('Tỷ lệ % Bug');
            $table->integer('ulnl_count')->default(0)->comment('Tổng số ULNL');
            $table->integer('slsx_count')->default(0)->comment('Tổng SLSX');
            $table->decimal('slsx_vs_ulnl_ratio', 5, 2)->default(0)->comment('Tỷ lệ % SLSX so với ULNL');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jira_user_stats');
    }
};
