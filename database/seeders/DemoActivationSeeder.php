<?php

namespace Database\Seeders;

use App\Enums\ActivationStatus;
use App\Models\ActivationCode;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DemoActivationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $product = Product::where('code', 'PRODUCT-001')->first();

        if ($product) {
            $demoCodes = [
                'DEMO-2026-RIHL-0001',
                'DEMO-2026-RIHL-0002',
                'DEMO-2026-RIHL-0003',
                'DEMO-2026-RIHL-0004',
                'DEMO-2026-RIHL-0005',
            ];

            foreach ($demoCodes as $code) {
                ActivationCode::updateOrCreate(
                    ['code' => $code],
                    [
                        'product_id' => $product->id,
                        'status' => ActivationStatus::AVAILABLE,
                        'expires_at' => now()->addMonths(6),
                    ]
                );
            }
        }
    }
}
