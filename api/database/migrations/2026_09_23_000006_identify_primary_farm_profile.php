<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sys_farm_profiles', function (Blueprint $table): void {
            $table->unsignedTinyInteger('singleton_key')->nullable()->unique('uq_farm_profile_singleton');
        });

        $primaryId = DB::table('sys_farm_profiles')->orderBy('id')->value('id');
        if ($primaryId !== null) {
            DB::table('sys_farm_profiles')->where('id', $primaryId)->update(['singleton_key' => 1]);
        }
    }

    public function down(): void
    {
        Schema::table('sys_farm_profiles', function (Blueprint $table): void {
            $table->dropUnique('uq_farm_profile_singleton');
            $table->dropColumn('singleton_key');
        });
    }
};
