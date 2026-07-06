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
        Schema::create('jira_projects', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Mã dự án trên Jira');
            $table->string('name')->comment('Tên dự án');
            $table->index('name', 'idx_projects_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jira_projects');
    }
};