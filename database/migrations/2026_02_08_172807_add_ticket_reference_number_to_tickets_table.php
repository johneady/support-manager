<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('tickets', 'ticket_reference_number')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->string('ticket_reference_number')->nullable()->unique()->after('id');
            });
        }

        // Populate the reference number for existing tickets
        $referenceFormat = fn ($id) => sprintf('TX-1138-%06d', $id);

        DB::table('tickets')
            ->whereNull('ticket_reference_number')
            ->orWhere('ticket_reference_number', '')
            ->chunkById(100, function ($tickets) use ($referenceFormat) {
                foreach ($tickets as $ticket) {
                    DB::table('tickets')
                        ->where('id', $ticket->id)
                        ->update([
                            'ticket_reference_number' => $referenceFormat($ticket->id),
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropUnique('tickets_ticket_reference_number_unique');
            $table->dropColumn('ticket_reference_number');
        });
    }
};
