<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            // The secret part of the private link a client is sent.
            $table->string('token', 64)->unique();
            $table->string('sent_to');
            $table->string('project_slug')->nullable();
            $table->string('name')->nullable();
            $table->string('role')->nullable();
            $table->text('quote')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
