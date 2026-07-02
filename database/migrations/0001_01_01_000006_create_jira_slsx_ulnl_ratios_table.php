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
        Schema::create('jira_slsx_ulnl_ratios', function (Blueprint $table) {
            $table->id();

            $table->string('period')->comment('Tháng/năm thống kê dạng chuỗi m-Y');
            $table->string('project_name')->nullable()->comment('Tên dự án trên Jira');
            $table->string('user_name')->comment('Tên người dùng: causer hoặc assignee');
            $table->decimal('ulnl_sum', 10, 3)->default(0)->comment('Tổng ULNL');
            $table->decimal('slsx_sum', 10, 3)->default(0)->comment('Tổng SLSX');
            $table->decimal('slsx_vs_ulnl_ratio', 5, 2)->default(0)->comment('Tỷ lệ % SLSX so với ULNL');

            $table->index(['period', 'project_name'], 'idx_period_project');
            $table->index(['period', 'user_name', 'project_name'], 'idx_period_user_project');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jira_slsx_ulnl_ratios');
    }
};
