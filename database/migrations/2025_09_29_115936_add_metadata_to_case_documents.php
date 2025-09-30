<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('file_size')->nullable()->after('path');
            $table->string('mime_type')->nullable()->after('file_size');
        });
    }

    public function down(): void
    {
        Schema::table('case_documents', function (Blueprint $table) {
            $table->dropColumn(['file_size', 'mime_type']);
        });
    }
};
