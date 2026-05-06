<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('price_list_items')) {
            return;
        }

        if (!Schema::hasColumn('price_list_items', 'governorate_id')) {
            Schema::table('price_list_items', function (Blueprint $table) {
                $table->unsignedBigInteger('governorate_id')->nullable()->after('price_list_id');
            });

            if (Schema::hasColumn('price_list_items', 'governorate') && Schema::hasTable('governorates')) {
                $governorateMap = DB::table('governorates')
                    ->select('id', 'name_ar')
                    ->get()
                    ->mapWithKeys(fn ($gov) => [$this->normalizeGovernorate((string) $gov->name_ar) => (int) $gov->id])
                    ->all();

                $legacyAliases = [
                    'cairo' => 'القاهرة',
                    'giza' => 'الجيزة',
                    'alexandria' => 'الأسكندرية',
                    'dakahlia' => 'الدقهلية',
                    'redsea' => 'البحر الأحمر',
                    'red_sea' => 'البحر الأحمر',
                    'beheira' => 'البحيرة',
                    'fayoum' => 'الفيوم',
                    'gharbia' => 'الغربية',
                    'ismailia' => 'الإسماعيلية',
                    'monufia' => 'المنوفية',
                    'menoufia' => 'المنوفية',
                    'minya' => 'المنيا',
                    'qalyubia' => 'القليوبية',
                    'newvalley' => 'الوادي الجديد',
                    'new_valley' => 'الوادي الجديد',
                    'suez' => 'السويس',
                    'aswan' => 'اسوان',
                    'assiut' => 'اسيوط',
                    'beni_suef' => 'بني سويف',
                    'benisuef' => 'بني سويف',
                    'portsaid' => 'بورسعيد',
                    'port_said' => 'بورسعيد',
                    'damietta' => 'دمياط',
                    'sharqia' => 'الشرقية',
                    'south_sinai' => 'جنوب سيناء',
                    'southsinai' => 'جنوب سيناء',
                    'kafr_el_sheikh' => 'كفر الشيخ',
                    'kafrelsheikh' => 'كفر الشيخ',
                    'matrouh' => 'مطروح',
                    'luxor' => 'الأقصر',
                    'qena' => 'قنا',
                    'north_sinai' => 'شمال سيناء',
                    'northsinai' => 'شمال سيناء',
                    'sohag' => 'سوهاج',
                    'mansoura' => 'منصورة',
                ];

                foreach (DB::table('price_list_items')->select('id', 'governorate')->get() as $row) {
                    $legacy = $this->normalizeGovernorate((string) ($row->governorate ?? ''));
                    $mappedName = $legacyAliases[$legacy] ?? null;
                    $governorateId = $governorateMap[$legacy]
                        ?? ($mappedName ? ($governorateMap[$this->normalizeGovernorate($mappedName)] ?? null) : null);

                    if ($governorateId) {
                        DB::table('price_list_items')
                            ->where('id', $row->id)
                            ->update(['governorate_id' => $governorateId]);
                    }
                }
            }

            Schema::table('price_list_items', function (Blueprint $table) {
                $table->index('governorate_id', 'pli_gov_idx');
            });
        }

        if (!Schema::hasColumn('price_list_items', 'return_price')) {
            Schema::table('price_list_items', function (Blueprint $table) {
                $table->decimal('return_price', 15, 2)->nullable()->after('price');
            });
        }
    }

    public function down(): void
    {
        // Repair migration is intentionally irreversible.
    }

    private function normalizeGovernorate(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['-', '_'], ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?: $value;

        return $value;
    }
};
