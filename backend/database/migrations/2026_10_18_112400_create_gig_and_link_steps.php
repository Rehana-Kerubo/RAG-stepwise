<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gigs', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('client')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::table('steps', function (Blueprint $table) {
            $table->foreignId('gig_id')->nullable()->after('id')
                  ->constrained('gigs')->cascadeOnDelete();
            $table->string('input_type')->default('confirm');
            $table->json('input_config')->nullable();
        });

        Schema::table('onboarding_sessions', function (Blueprint $table) {
            $table->foreignId('gig_id')->nullable()->after('id')
                  ->constrained('gigs')->cascadeOnDelete();
        });

        // Everything that exists today belongs to the POS gig
        $gigId = DB::table('gigs')->insertGetId([
            'slug'        => 'pos-merchant-onboarding',
            'name'        => 'POS Merchant Onboarding',
            'client'      => 'Safaricom M-PESA / POS',
            'description' => 'Onboard an SME merchant onto a POS device and an M-PESA Till or Paybill.',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        DB::table('steps')->update(['gig_id' => $gigId]);
        DB::table('onboarding_sessions')->update(['gig_id' => $gigId]);

        // Match what the wizard currently hard-codes by step number
        DB::table('steps')->whereIn('order_number', [1, 2, 3])->update(['input_type' => 'photo']);
        DB::table('steps')->where('order_number', 7)->update(['input_type' => 'text']);
        DB::table('steps')->whereIn('order_number', [9, 13])->update(['input_type' => 'select']);
    }

    public function down(): void
    {
        Schema::table('onboarding_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gig_id');
        });

        Schema::table('steps', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gig_id');
            $table->dropColumn(['input_type', 'input_config']);
        });

        Schema::dropIfExists('gigs');
    }
};