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
        Schema::table('karya_tulis', function (Blueprint $table) {
            $table->dropColumn(['document_1', 'document_2', 'document_3', 'document_4', 'document_5', 'document_6', 'document_7', 'document_8', 'document_9', 'document_10']);
            $table->longText('abstrak')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('karya_tulis', function (Blueprint $table) {
            //
        });
    }
};
