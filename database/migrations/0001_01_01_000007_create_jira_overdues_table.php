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

            $table->string('key')->unique()->comment('Mã định danh duy nhất của Issue trên Jira (Ví dụ: PROJ-123)');
            $table->string('period', 10)->comment('Tháng/năm thống kê dạng chuỗi (Ví dụ: 06-2026)'); // Giới hạn 10 ký tự là dư dả cho 'mm-YYYY'
            $table->string('project_name', 150)->nullable()->comment('Tên hiển thị đầy đủ của dự án trên Jira'); // Thu gọn độ dài để tối ưu index
            $table->text('summary')->comment('Tiêu đề / Nội dung tóm tắt của công việc');
            $table->string('issuetype', 50)->comment('Loại công việc (Sub-task, Story, Milestone, Bug...)');
            $table->string('assignee', 100)->nullable()->comment('Tên người được giao thực hiện công việc');
            $table->string('display_name')->nullable()->comment('Tên hiển thị');
            $table->string('status', 50)->comment('Trạng thái công việc: Overdue/ Warning');
            $table->string('statusText', 50)->comment('Trạng thái công việc thực tế');
            $table->string('statusLogWork', 50)->nullable()->comment('Trạng thái Log Work Overdue/ Warning/ Missing');
            $table->string('statusTextLogWork', 50)->nullable()->comment('Trạng thái Log Work thực tế');
            $table->date('enddate')->nullable()->comment('Hạn chót hoàn thành công việc (Y-m-d)');
            $table->timestamps();

            $table->index(['period', 'project_name', 'issuetype', 'status'], 'idx_overdue_report_summary');
            $table->index(['period', 'project_name', 'assignee', 'status'], 'idx_overdue_assignee_summary');
            $table->index(['period', 'project_name', 'issuetype', 'assignee', 'status'], 'idx_overdue_issuetype_assignee_status');
            
            $table->index(['period', 'project_name', 'issuetype', 'statusLogWork'], 'idx_overdue_report_logwork_summary');
            $table->index(['period', 'project_name', 'assignee', 'statusLogWork'], 'idx_overdue_assignee_logwork_summary');
            $table->index(['period', 'project_name', 'issuetype', 'assignee', 'statusLogWork'], 'idx_overdue_issuetype_assignee_statuslogwork');
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