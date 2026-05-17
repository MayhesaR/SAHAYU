<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->string('category')->nullable()->change();
            $table->foreignId('raw_material_category_id')->nullable()->constrained('raw_material_categories')->nullOnDelete();
        });

        // Data Migrator: Migrate existing string-based categories into the new relational categories table
        $materials = DB::table('materials')->get();
        $insertedCategories = [];

        foreach ($materials as $material) {
            if (!empty($material->category)) {
                $key = $material->company_id . '-' . $material->category;
                if (!isset($insertedCategories[$key])) {
                    $existingId = DB::table('raw_material_categories')
                        ->where('company_id', $material->company_id)
                        ->where('name', $material->category)
                        ->value('id');

                    if ($existingId) {
                        $insertedCategories[$key] = $existingId;
                    } else {
                        $categoryId = DB::table('raw_material_categories')->insertGetId([
                            'company_id' => $material->company_id,
                            'name' => $material->category,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $insertedCategories[$key] = $categoryId;
                    }
                }

                DB::table('materials')->where('id', $material->id)->update([
                    'raw_material_category_id' => $insertedCategories[$key]
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('raw_material_category_id');
        });
    }
};
