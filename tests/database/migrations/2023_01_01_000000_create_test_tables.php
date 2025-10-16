<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->integer('age')->nullable();
            $table->string('city')->nullable();
            $table->timestamps();
        });

        Schema::create('test_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->unsignedBigInteger('user_id');
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('test_users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_posts');
        Schema::dropIfExists('test_users');
    }
};