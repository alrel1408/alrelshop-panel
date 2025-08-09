<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique();
            $table->string('email', 100)->unique();
            $table->string('password');
            $table->string('full_name', 100);
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->enum('role', ['admin', 'user'])->default('user');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->string('reset_token', 100)->nullable();
            $table->timestamp('reset_expires')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();

            $table->index(['email', 'status']);
            $table->index(['username', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
