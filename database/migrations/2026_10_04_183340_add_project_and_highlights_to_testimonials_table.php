<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            // The project or company the testimonial is about, as typed in the admin area.
            $table->string('project')->nullable()->after('project_slug');
            // Phrases from the quote to draw attention to.
            $table->json('highlights')->nullable()->after('quote');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn(['project', 'highlights']);
        });
    }
};
