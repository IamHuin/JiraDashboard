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
        Schema::create('jira_issues', function (Blueprint $table) {
            $table->id();

            $table->string('key')->unique()->comment('Mã issue trên Jira');
            $table->string('project_name')->nullable()->comment('Tên dự án trên Jira');
            $table->string('summary')->nullable()->comment('Tiêu đề/tóm tắt issue trên Jira');
            $table->string('issuetype')->nullable()->comment('Loại issue: Bug hoặc Sub-task');
            $table->string('assignee')->nullable()->comment('Người được assign xử lý issue');
            $table->string('display_name')->nullable()->comment('Tên hiển thị');
            $table->string('causer')->nullable()->comment('Người gây ra lỗi');
            $table->string('causer_displayName')->nullable()->comment('Tên hiển thị người gây ra lỗi');
            $table->string('causer_category')->nullable()->comment('Loại gây ra lỗi');
            $table->string('slsx')->nullable()->comment('Sản lượng sản xuất');
            $table->string('status')->nullable()->comment('Trạng thái issue');
            $table->json('subtask_keys')->nullable()->comment('Danh sách các subtask keys nếu issue là Story');
            $table->timestamp('created_at_jira')->nullable()->comment('Thời gian created từ Jira');
            $table->timestamp('end_date_jira')->nullable()->comment('Thời gian end date từ Jira');

            $table->index(['project_name', 'created_at_jira'], 'idx_project_created_at');
            $table->index(['project_name', 'created_at_jira', 'assignee'], 'idx_issues_project_date_assignee');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jira_issues');
    }
};