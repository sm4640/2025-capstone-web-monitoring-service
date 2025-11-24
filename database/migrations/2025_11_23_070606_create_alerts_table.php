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
        Schema::create('alerts', function (Blueprint $table) {
            $table->bigIncrements('id');      // 순차 증가 id
            $table->string('name');           // 알람 이름
            $table->string('severity');       // 심각도
            $table->string('instance');       // 발생 인스턴스
            $table->text('summary');          // 요약/상세
            $table->text('callback_url');     // n8n wait노드 콜백 url
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->string('status')->default('unseen'); 
            // unseen / in_progress / done
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
