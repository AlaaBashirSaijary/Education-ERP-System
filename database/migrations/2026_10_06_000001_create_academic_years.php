<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();            // e.g. 2026/2027
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->timestamps();
        });

        // One row per student per year: the class history. students.school_class_id stays the *current* class.
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'academic_year_id']);
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
        });
        Schema::table('fees', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
        });
        Schema::table('students', function (Blueprint $table) {
            $table->date('graduated_at')->nullable();
        });

        $this->backfill();
    }

    /** Existing data goes into the year that contains today, which becomes the current year. */
    private function backfill(): void
    {
        $now = now();
        $start = $now->month >= 8 ? $now->year : $now->year - 1;

        $yearId = DB::table('academic_years')->insertGetId([
            'name' => $start.'/'.($start + 1), 'starts_on' => "$start-09-01", 'ends_on' => ($start + 1).'-06-30',
            'is_current' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
        foreach ([['الفصل الأول', "$start-09-01", ($start + 1).'-01-31'], ['الفصل الثاني', ($start + 1).'-02-01', ($start + 1).'-06-30']] as [$name, $from, $to]) {
            DB::table('terms')->insert(['academic_year_id' => $yearId, 'name' => $name, 'starts_on' => $from, 'ends_on' => $to, 'created_at' => $now, 'updated_at' => $now]);
        }

        DB::table('exams')->update(['academic_year_id' => $yearId]);
        DB::table('fees')->update(['academic_year_id' => $yearId]);

        DB::table('students')->orderBy('id')->select('id', 'school_class_id')->each(function ($s) use ($yearId, $now) {
            DB::table('enrollments')->insert(['student_id' => $s->id, 'academic_year_id' => $yearId,
                'school_class_id' => $s->school_class_id, 'created_at' => $now, 'updated_at' => $now]);
        });
    }

    public function down(): void
    {
        Schema::table('students', fn (Blueprint $t) => $t->dropColumn('graduated_at'));
        Schema::table('fees', function (Blueprint $t) {
            $t->dropConstrainedForeignId('academic_year_id');
        });
        Schema::table('exams', function (Blueprint $t) {
            $t->dropConstrainedForeignId('term_id');
            $t->dropConstrainedForeignId('academic_year_id');
        });
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('terms');
        Schema::dropIfExists('academic_years');
    }
};
