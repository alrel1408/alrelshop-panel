<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value');
            $table->text('description')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['setting_key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('settings');
    }
};
