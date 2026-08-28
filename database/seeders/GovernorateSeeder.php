<?php

namespace Database\Seeders;

use App\Models\Governorate;
use Illuminate\Database\Seeder;

/**
 * The eighteen Iraqi governorates, in the order they are usually listed.
 * Seeded once; from then on the dashboard owns the list (add, rename, hide).
 */
class GovernorateSeeder extends Seeder
{
    public const GOVERNORATES = [
        ['name' => 'بغداد', 'code' => 'BGD'],
        ['name' => 'نينوى', 'code' => 'NNV'],
        ['name' => 'البصرة', 'code' => 'BSR'],
        ['name' => 'أربيل', 'code' => 'ERB'],
        ['name' => 'السليمانية', 'code' => 'SUL'],
        ['name' => 'دهوك', 'code' => 'DHK'],
        ['name' => 'كركوك', 'code' => 'KRK'],
        ['name' => 'الأنبار', 'code' => 'ANB'],
        ['name' => 'بابل', 'code' => 'BBL'],
        ['name' => 'كربلاء', 'code' => 'KRB'],
        ['name' => 'النجف', 'code' => 'NJF'],
        ['name' => 'الديوانية', 'code' => 'QDS'],
        ['name' => 'ذي قار', 'code' => 'DHQ'],
        ['name' => 'ميسان', 'code' => 'MYS'],
        ['name' => 'المثنى', 'code' => 'MTH'],
        ['name' => 'واسط', 'code' => 'WST'],
        ['name' => 'صلاح الدين', 'code' => 'SLD'],
        ['name' => 'ديالى', 'code' => 'DYL'],
    ];

    public function run(): void
    {
        foreach (self::GOVERNORATES as $index => $governorate) {
            // keyed on the code, which the dashboard cannot edit into a clash:
            // a governorate renamed by an admin stays renamed on the next deploy
            Governorate::firstOrCreate(
                ['code' => $governorate['code']],
                [
                    'name' => $governorate['name'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
