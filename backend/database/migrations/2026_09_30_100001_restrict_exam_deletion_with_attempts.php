<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Uma prova com tentativas não pode ser excluída: o histórico precisa ser
     * preservado. A API já responde 409 (ExamService::delete); a FK restritiva
     * garante a regra também fora dela.
     */
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropForeign(['exam_id']);
            $table->foreign('exam_id')->references('id')->on('exams')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropForeign(['exam_id']);
            $table->foreign('exam_id')->references('id')->on('exams')->cascadeOnDelete();
        });
    }
};
