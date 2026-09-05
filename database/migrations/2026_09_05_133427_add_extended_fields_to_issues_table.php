<?php

use App\Enums\IssuePriority;
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
        Schema::table('issues', function (Blueprint $table) {
            $table->string('priority')->default(IssuePriority::Normal->value)->after('severity');
            $table->date('start_date')->nullable()->after('priority');
            $table->date('due_date')->nullable()->after('start_date');
            $table->unsignedTinyInteger('percent_done')->default(0)->after('due_date');
            $table->decimal('estimated_hours', 5, 2)->nullable()->after('percent_done');
            $table->string('category')->nullable()->after('estimated_hours');
            $table->boolean('is_private')->default(false)->after('category');
            $table->foreignId('parent_id')->nullable()->after('is_private')->constrained('issues')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn([
                'priority',
                'start_date',
                'due_date',
                'percent_done',
                'estimated_hours',
                'category',
                'is_private',
            ]);
        });
    }
};
