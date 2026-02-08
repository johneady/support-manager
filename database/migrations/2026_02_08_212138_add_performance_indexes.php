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
        Schema::table('tickets', function (Blueprint $table) {
            $table->index('status');
            $table->index('user_id');
            $table->index('priority');
            $table->index('ticket_category_id');
        });

        Schema::table('ticket_replies', function (Blueprint $table) {
            $table->index(['ticket_id', 'is_from_admin', 'id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('is_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['ticket_category_id']);
        });

        Schema::table('ticket_replies', function (Blueprint $table) {
            $table->dropIndex(['ticket_id', 'is_from_admin', 'id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_admin']);
        });
    }
};
