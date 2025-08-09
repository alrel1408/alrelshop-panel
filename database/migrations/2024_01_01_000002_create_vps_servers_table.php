<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vps_servers', function (Blueprint $table) {
            $table->id();
            $table->string('server_key', 50)->unique();
            $table->string('name', 100);
            $table->string('hostname')->nullable();
            $table->string('ip_address', 45);
            $table->string('location', 100)->nullable();
            $table->string('provider', 50)->nullable();
            $table->integer('ssh_port')->default(22);
            $table->string('ssh_user', 50)->default('root');
            $table->text('ssh_key_path')->nullable();
            $table->enum('status', ['online', 'offline', 'maintenance'])->default('online');
            $table->integer('max_accounts')->default(100);
            $table->integer('current_accounts')->default(0);
            $table->timestamps();

            $table->index(['status']);
            $table->index(['server_key', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('vps_servers');
    }
};
