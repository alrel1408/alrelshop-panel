<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vpn_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('vps_server_id')->constrained()->onDelete('cascade');
            $table->enum('account_type', ['ssh', 'vmess', 'vless', 'trojan', 'shadowsocks', 'openvpn']);
            $table->string('username', 50);
            $table->string('password', 100);
            $table->string('uuid', 100)->nullable();
            $table->integer('port')->nullable();
            $table->string('path', 100)->nullable();
            $table->string('domain')->nullable();
            $table->date('expired_date');
            $table->enum('status', ['active', 'expired', 'suspended'])->default('active');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['vps_server_id', 'status']);
            $table->index(['expired_date']);
            $table->index(['username', 'vps_server_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('vpn_accounts');
    }
};
