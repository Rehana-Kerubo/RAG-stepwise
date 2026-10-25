<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Which gigs each agent may start. A manager assigns them.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_gig', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('gig_id')->constrained('gigs')->cascadeOnDelete();

            // The manager who assigned it. Empty for rows backfilled below.
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();

            $table->unique(['agent_id', 'gig_id']);
        });

        // Backfill: every agent keeps the gigs they have already started,
        // dated from their first onboarding of that gig.
        DB::table('agent_gig')->insertUsing(
            ['agent_id', 'gig_id', 'assigned_at'],
            DB::table('onboarding_sessions')
                ->selectRaw('agent_id, gig_id, MIN(COALESCE(started_at, created_at))')
                ->whereNotNull('gig_id')
                ->groupBy('agent_id', 'gig_id')
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_gig');
    }
};
