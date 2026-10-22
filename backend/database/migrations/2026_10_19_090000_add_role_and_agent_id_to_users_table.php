<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 'agent' by default: a new account gets the smaller set of permissions.
            $table->enum('role', ['agent', 'manager'])->default('agent')->after('email');

            // Which agent this login belongs to. Managers leave it empty.
            $table->foreignId('agent_id')->nullable()->after('role')
                ->constrained('agents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agent_id');
            $table->dropColumn('role');
        });
    }
};
