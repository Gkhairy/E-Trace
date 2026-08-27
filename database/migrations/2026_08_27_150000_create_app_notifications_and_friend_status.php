<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Notifikasi aplikasi (in-app) per user.
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);           // order, friend, donation, community, ...
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('url')->nullable();     // tautan aksi (mis. /orders)
            $table->string('icon', 8)->nullable(); // emoji
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'read_at']);
        });

        // Permintaan pertemanan: perlu diterima dulu.
        Schema::table('friends', function (Blueprint $table) {
            $table->string('status', 12)->default('accepted')->after('friend_id'); // pending | accepted
            $table->index(['friend_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
        Schema::table('friends', function (Blueprint $table) {
            $table->dropIndex(['friend_id', 'status']);
            $table->dropColumn('status');
        });
    }
};
