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
        Schema::create('jira_milestones', function (Blueprint $table) {
            $table->id();

            $table->string('period', 7)->index()->comment('Tháng/Năm báo cáo dạng mm-YYYY (Ví dụ: 07-2026)');
            $table->string('project_name')->comment('Tên đầy đủ của dự án trên Jira (Ví dụ: Viettel Family)');
            $table->string('ticket_code')->comment('Mã Jira Ticket chứa mốc (Ví dụ: VICPYC-3370)');
            $table->string('report_type', 20)->index()->comment('Phân loại lỗi để chia Tab: MISSING (Thiếu mốc) hoặc EXCEPTION (Mốc ngoại lệ)');
            $table->string('milestone_name')->comment('Tên mốc cụ thể dính lỗi (Mốc chuẩn bị thiếu HOẶC mốc lạ tự chế)');

            $table->timestamps();
            
            $table->unique(
                ['period', 'ticket_code', 'report_type', 'milestone_name'],
                'jira_milestones_unique_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jira_milestones');
    }
};