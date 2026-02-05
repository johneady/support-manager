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
        Schema::table('faqs', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('question');
        });

        // Generate slugs for existing FAQs
        foreach (\App\Models\Faq::all() as $faq) {
            $faq->slug = \Illuminate\Support\Str::slug($faq->question);
            $faq->saveQuietly();
        }

        Schema::table('faqs', function (Blueprint $table) {
            $table->string('slug')->unique()->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
