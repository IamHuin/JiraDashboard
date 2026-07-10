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
        Schema::create('jira_slsx_ratios', function (Blueprint $table) {
            $table->id();

            $table->string('period')->comment('Tháng/năm thống kê dạng chuỗi m-Y');
            $table->string('project_name')->default('')->comment('Tên dự án trên Jira');
            $table->string('user_name')->comment('Tên người dùng: causer hoặc assignee');
            $table->string('display_name')->nullable()->comment('Tên hiển thị');
            $table->decimal('slsx_sum', 10, 3)->default(0)->comment('Tổng SLSX');
            $table->string('standard', 100)->comment('Ra Tiêu chuẩn');
            $table->integer('slsx_nltc_ratio')->default(0)->comment('Tỷ lệ % SLSX so với NLTC');

            $table->index(['period', 'project_name'], 'idx_period_project');

            $table->unique(['user_name', 'period', 'project_name'], 'uk_user_period_project_slsx');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jira_slsx_ratios');
    }
};