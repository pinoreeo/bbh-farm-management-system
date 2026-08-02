<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            if (! Schema::hasColumn('animals', 'origin_type')) {
                $table->string('origin_type', 50)->default('unknown')->after('is_impor');
            }

            if (! Schema::hasColumn('animals', 'origin_detail')) {
                $table->string('origin_detail')->nullable()->after('origin_type');
            }
        });

        DB::table('animals')
            ->where('is_impor', true)
            ->update(['origin_type' => 'import']);
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            if (Schema::hasColumn('animals', 'origin_detail')) {
                $table->dropColumn('origin_detail');
            }

            if (Schema::hasColumn('animals', 'origin_type')) {
                $table->dropColumn('origin_type');
            }
        });
    }
};
