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
        Schema::create('jira_overdues', function (Blueprint $table) {
            $table->id();

            $table->string('period', 10)->comment('Tháng/năm thống kê dạng chuỗi (Ví dụ: 06-2026)'); // Giới hạn 10 ký tự là dư dả cho 'mm-YYYY'
            $table->string('project_name', 150)->nullable()->comment('Tên hiển thị đầy đủ của dự án trên Jira'); // Thu gọn độ dài để tối ưu index
            $table->string('key')->unique()->comment('Mã định danh duy nhất của Issue trên Jira (Ví dụ: PROJ-123)');
            $table->text('summary')->comment('Tiêu đề / Nội dung tóm tắt của công việc');
            $table->string('project', 50)->nullable()->comment('Mã viết tắt (Project Key) của dự án trên Jira');
            $table->string('issue_type', 50)->comment('Loại công việc (Sub-task, Story, Milestone, Bug...)');
            $table->string('assignee', 100)->nullable()->comment('Tên người được giao thực hiện công việc');
            $table->date('enddate')->nullable()->comment('Hạn chót hoàn thành công việc (Y-m-d)');
            $table->string('status', 50)->comment('Trạng thái công việc sau tính toán logic (Done, In Progress hoặc Overdue)');

            $table->timestamps();

            $table->index(['status', 'enddate'], 'idx_status_enddate');
            $table->index(['period', 'project_name'], 'idx_period_project');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jira_overdues');
    }
};