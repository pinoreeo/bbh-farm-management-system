<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animal_pens', function (Blueprint $table) {
            $table->string('colony_code', 100)->nullable()->after('pen_code');
            $table->string('colony_name')->nullable()->after('colony_code');
            $table->string('colony_phase', 100)->default('koloni_kawin')->after('colony_type');
            $table->boolean('is_active')->default(true)->after('capacity');

            $table->index('colony_phase', 'idx_animal_pens_colony_phase');
        });

        Schema::table('animals', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('tag_number');
            $table->string('male_role', 30)->nullable()->after('sex');
            $table->foreignId('current_pen_id')->nullable()->after('birth_place')->constrained('animal_pens')->nullOnDelete();
            $table->string('reproductive_status', 50)->default('kosong')->after('current_pen_id');
            $table->date('status_date')->nullable()->after('reproductive_status');
            $table->string('exit_status', 50)->nullable()->after('life_status');
            $table->date('exit_date')->nullable()->after('exit_status');
            $table->string('exit_reason')->nullable()->after('exit_date');

            $table->index('reproductive_status', 'idx_animals_reproductive_status');
            $table->index('male_role', 'idx_animals_male_role');
        });

        Schema::table('breed_periods', function (Blueprint $table) {
            $table->date('mating_date')->nullable()->after('start_date');
            $table->date('expected_birth_date')->nullable()->after('mating_date');
            $table->string('inbreeding_policy', 30)->default('block_high_risk')->after('status');
        });

        Schema::table('breed_females', function (Blueprint $table) {
            $table->date('mating_date')->nullable()->after('entry_date');
            $table->date('expected_birth_date')->nullable()->after('mating_date');
            $table->string('cycle_stage', 50)->default('kawin')->after('expected_birth_date');
            $table->string('inbreeding_status', 30)->default('unchecked')->after('cycle_stage');
            $table->string('inbreeding_note')->nullable()->after('inbreeding_status');
        });

        Schema::table('breed_offsprings', function (Blueprint $table) {
            $table->string('offspring_grade', 50)->nullable()->after('birth_weight_kg');
        });

        Schema::table('med_treatments', function (Blueprint $table) {
            $table->text('symptoms')->nullable()->after('treatment_date');
            $table->text('diagnosis')->nullable()->after('symptoms');
            $table->string('handled_by')->nullable()->after('action_category');
            $table->date('next_control_date')->nullable()->after('handled_by');
        });

        Schema::create('animal_pen_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->foreignId('from_pen_id')->nullable()->constrained('animal_pens')->nullOnDelete();
            $table->foreignId('to_pen_id')->constrained('animal_pens')->cascadeOnDelete();
            $table->date('movement_date');
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['animal_id', 'movement_date'], 'idx_pen_movements_animal_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_pen_movements');

        Schema::table('med_treatments', function (Blueprint $table) {
            $table->dropColumn(['symptoms', 'diagnosis', 'handled_by', 'next_control_date']);
        });

        Schema::table('breed_offsprings', function (Blueprint $table) {
            $table->dropColumn('offspring_grade');
        });

        Schema::table('breed_females', function (Blueprint $table) {
            $table->dropColumn([
                'mating_date',
                'expected_birth_date',
                'cycle_stage',
                'inbreeding_status',
                'inbreeding_note',
            ]);
        });

        Schema::table('breed_periods', function (Blueprint $table) {
            $table->dropColumn(['mating_date', 'expected_birth_date', 'inbreeding_policy']);
        });

        Schema::table('animals', function (Blueprint $table) {
            $table->dropIndex('idx_animals_reproductive_status');
            $table->dropIndex('idx_animals_male_role');
            $table->dropConstrainedForeignId('current_pen_id');
            $table->dropColumn([
                'photo_path',
                'male_role',
                'reproductive_status',
                'status_date',
                'exit_status',
                'exit_date',
                'exit_reason',
            ]);
        });

        Schema::table('animal_pens', function (Blueprint $table) {
            $table->dropIndex('idx_animal_pens_colony_phase');
            $table->dropColumn(['colony_code', 'colony_name', 'colony_phase', 'is_active']);
        });
    }
};
