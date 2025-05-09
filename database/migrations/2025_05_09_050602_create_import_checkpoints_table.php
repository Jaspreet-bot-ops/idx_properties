<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('import_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->string('import_type')->unique();
            $table->text('next_url')->nullable();
            $table->integer('total_processed')->default(0);
            $table->timestamp('last_run_at')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('import_checkpoints');
    }
};
