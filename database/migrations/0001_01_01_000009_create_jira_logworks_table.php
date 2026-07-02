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
        Schema::create('jira_logworks', function (Blueprint $table) {
            $table->id();

            $table->string('key')->unique()->comment('Mã định danh duy nhất của Issue trên Jira (Ví dụ: PROJ-123)');
            $table->string('period', 10)->comment('Tháng/năm thống kê dạng chuỗi (Ví dụ: 06-2026)');
            $table->string('project_name', 150)->nullable()->comment('Tên hiển thị đầy đủ của dự án trên Jira');
            $table->text('summary')->comment('Tiêu đề / Nội dung tóm tắt của công việc');
            $table->string('issuetype', 50)->comment('Loại công việc (Sub-task, Story, Milestone, Bug...)');
            $table->string('assignee', 100)->nullable()->comment('Tên người được giao thực hiện công việc');
            $table->string('status', 50)->comment('Trạng thái công việc sau tính toán logic (Done, In Progress hoặc Overdue)');
            $table->date('enddate')->nullable()->comment('Hạn chót hoàn thành công việc (Y-m-d)');
            $table->string('statusText', 50)->comment('Trạng thái công việc thực tế');

            $table->timestamps();

            $table->index(['period', 'project_name', 'issuetype', 'status'], 'idx_overdue_report_summary');
            $table->index(['period', 'project_name', 'assignee', 'status'], 'idx_overdue_assignee_summary');
            $table->index(['period', 'project_name', 'issuetype', 'assignee', 'status'], 'idx_overdue_issuetype_assignee_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jira_logworks');
    }
};