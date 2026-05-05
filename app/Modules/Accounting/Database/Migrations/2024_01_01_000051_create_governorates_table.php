<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governorates', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar', 100);
            $table->boolean('is_system')->default(true);   // true = محافظة افتراضية لا يمكن حذفها
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // ── بيانات المحافظات الافتراضية ──────────────────────────────
        $now = now();
        $govs = [
            ['القاهرة',       1],
            ['الجيزة',        2],
            ['الأسكندرية',    3],
            ['الدقهلية',      4],
            ['البحر الأحمر',  5],
            ['البحيرة',       6],
            ['الفيوم',        7],
            ['الغربية',       8],
            ['الإسماعلية',    9],
            ['المنوفية',      10],
            ['المنيا',        11],
            ['القليوبية',     12],
            ['الوادي الجديد', 13],
            ['السويس',        14],
            ['اسوان',         15],
            ['اسيوط',         16],
            ['بني سويف',      17],
            ['بورسعيد',       18],
            ['دمياط',         19],
            ['الشرقية',       20],
            ['جنوب سيناء',    21],
            ['كفر الشيخ',     22],
            ['مطروح',         23],
            ['الأقصر',        24],
            ['قنا',           25],
            ['شمال سيناء',    26],
            ['سوهاج',         27],
            ['منصورة',        28],
        ];

        DB::table('governorates')->insert(
            array_map(fn (array $g) => [
                'name_ar'    => $g[0],
                'is_system'  => true,
                'is_active'  => true,
                'sort_order' => $g[1],
                'created_at' => $now,
                'updated_at' => $now,
            ], $govs)
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('governorates');
    }
};
