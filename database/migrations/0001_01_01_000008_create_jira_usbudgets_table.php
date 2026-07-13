<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jira_usbudgets', function (Blueprint $table) {
            $table->id();

            $table->string('key')->unique()->comment('Mã định danh duy nhất của Issue trên Jira (Ví dụ: PROJ-123)');
            $table->string('period', 10)->comment('Tháng/năm thống kê dạng chuỗi (Ví dụ: 06-2026)'); // Giới hạn 10 ký tự là dư dả cho 'mm-YYYY'
            $table->string('project_name', 150)->nullable()->comment('Tên hiển thị đầy đủ của dự án trên Jira'); // Thu gọn độ dài để tối ưu index
            $table->text('summary')->comment('Tiêu đề / Nội dung tóm tắt của công việc');
            $table->string('issuetype', 50)->comment('Loại công việc (Sub-task, Story, Milestone, Bug...)');
            $table->string('assignee', 100)->nullable()->comment('Tên người được giao thực hiện công việc');
            $table->string('display_name')->nullable()->comment('Tên hiển thị');
            $table->string('status', 50)->comment('Trạng thái công việc sau tính toán logic (Done, In Progress hoặc Overdue)');
            $table->string('slsx')->nullable()->comment('Sản lượng sản xuất của loại Story');
            $table->string('sumSLSXSubTask')->nullable()->comment('Tổng sản lượng sản xuất');
            $table->string('ratioSLSX')->nullable()->comment('Sự chênh lệch sản lượng sản xuất');
            $table->timestamps();

            $table->index(['period', 'project_name'], 'idx_period_project');
            $table->index(['period', 'project_name', 'assignee'], 'idx_assignee_summary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jira_usbudgets');
    }
};