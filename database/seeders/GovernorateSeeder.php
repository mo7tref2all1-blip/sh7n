<?php

namespace Database\Seeders;

use App\Models\Governorate;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class GovernorateSeeder extends Seeder
{
    /** The 27 Egyptian governorates. */
    private const GOVERNORATES = [
        ['code' => 'CAI', 'name_ar' => 'القاهرة', 'name_en' => 'Cairo'],
        ['code' => 'GIZ', 'name_ar' => 'الجيزة', 'name_en' => 'Giza'],
        ['code' => 'ALX', 'name_ar' => 'الإسكندرية', 'name_en' => 'Alexandria'],
        ['code' => 'DKH', 'name_ar' => 'الدقهلية', 'name_en' => 'Dakahlia'],
        ['code' => 'BHR', 'name_ar' => 'البحيرة', 'name_en' => 'Beheira'],
        ['code' => 'FYM', 'name_ar' => 'الفيوم', 'name_en' => 'Faiyum'],
        ['code' => 'GHR', 'name_ar' => 'الغربية', 'name_en' => 'Gharbia'],
        ['code' => 'ISM', 'name_ar' => 'الإسماعيلية', 'name_en' => 'Ismailia'],
        ['code' => 'MNF', 'name_ar' => 'المنوفية', 'name_en' => 'Monufia'],
        ['code' => 'MNY', 'name_ar' => 'المنيا', 'name_en' => 'Minya'],
        ['code' => 'QLY', 'name_ar' => 'القليوبية', 'name_en' => 'Qalyubia'],
        ['code' => 'WAD', 'name_ar' => 'الوادي الجديد', 'name_en' => 'New Valley'],
        ['code' => 'SUZ', 'name_ar' => 'السويس', 'name_en' => 'Suez'],
        ['code' => 'ASN', 'name_ar' => 'أسوان', 'name_en' => 'Aswan'],
        ['code' => 'AST', 'name_ar' => 'أسيوط', 'name_en' => 'Asyut'],
        ['code' => 'BNS', 'name_ar' => 'بني سويف', 'name_en' => 'Beni Suef'],
        ['code' => 'PTS', 'name_ar' => 'بورسعيد', 'name_en' => 'Port Said'],
        ['code' => 'DAM', 'name_ar' => 'دمياط', 'name_en' => 'Damietta'],
        ['code' => 'SHR', 'name_ar' => 'الشرقية', 'name_en' => 'Sharqia'],
        ['code' => 'SOH', 'name_ar' => 'سوهاج', 'name_en' => 'Sohag'],
        ['code' => 'QNA', 'name_ar' => 'قنا', 'name_en' => 'Qena'],
        ['code' => 'KFS', 'name_ar' => 'كفر الشيخ', 'name_en' => 'Kafr El Sheikh'],
        ['code' => 'MAT', 'name_ar' => 'مطروح', 'name_en' => 'Matrouh'],
        ['code' => 'LUX', 'name_ar' => 'الأقصر', 'name_en' => 'Luxor'],
        ['code' => 'BSA', 'name_ar' => 'شمال سيناء', 'name_en' => 'North Sinai'],
        ['code' => 'JSA', 'name_ar' => 'جنوب سيناء', 'name_en' => 'South Sinai'],
        ['code' => 'RSA', 'name_ar' => 'البحر الأحمر', 'name_en' => 'Red Sea'],
    ];

    public function run(): void
    {
        foreach (self::GOVERNORATES as $gov) {
            $governorate = Governorate::firstOrCreate(['code' => $gov['code']], $gov);

            // A couple of default zones per governorate so pricing/dropdowns have something to show.
            Zone::firstOrCreate(
                ['governorate_id' => $governorate->id, 'name_ar' => $governorate->name_ar.' - المدينة'],
                ['delivery_days_estimate' => 1]
            );
            Zone::firstOrCreate(
                ['governorate_id' => $governorate->id, 'name_ar' => $governorate->name_ar.' - المراكز والقرى'],
                ['delivery_days_estimate' => 2]
            );
        }
    }
}
