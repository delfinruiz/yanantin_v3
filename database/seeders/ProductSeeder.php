<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        Product::factory()->active()->create([
            'name' => 'Auriculares Inalámbricos',
            'description' => 'Auriculares Bluetooth con cancelación de ruido.',
            'price' => 500000,
            'stock' => 45,
        ]);

        Product::factory()->active()->create([
            'name' => 'Teclado Mecánico RGB',
            'description' => 'Teclado mecánico con switches Cherry MX y retroiluminación RGB.',
            'price' => 350000,
            'stock' => 0,
        ]);

        Product::factory()->active()->create([
            'name' => 'Monitor 27" 4K',
            'description' => 'Monitor IPS 27 pulgadas con resolución 4K UHD.',
            'price' => 4500000,
            'stock' => 12,
        ]);

        Product::factory()->active()->create([
            'name' => 'Mouse Ergonómico',
            'description' => 'Mouse vertical ergonómico para prevenir lesiones.',
            'price' => 180000,
            'stock' => 0,
        ]);

        Product::factory()->inactive()->create([
            'name' => 'Webcam HD 1080p',
            'description' => 'Webcam con micrófono integrado y enfoque automático.',
            'price' => 250000,
            'stock' => 30,
        ]);

        Product::factory()->count(15)->create();
    }
}
