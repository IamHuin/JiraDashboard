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
        Schema::create('jira_nltc', function (Blueprint $table) {
            $table->id();
            
            $table->string('period', 20)->comment('Tháng thống kê');
            $table->string('project', 150)->comment('Dự án');
            $table->string('username', 100)->comment('Username');
            $table->string('display_name', 150)->comment('Tên');
            $table->string('role', 100)->comment('Role');
            $table->string('level', 50)->comment('Level');
            $table->string('standard', 100)->comment('Ra Tiêu chuẩn');
            
            $table->unique(['period', 'project', 'username', 'display_name', 'role', 'level', 'standard'], 'uk_jira_nltc_all');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jira_nltc');
    }
};
