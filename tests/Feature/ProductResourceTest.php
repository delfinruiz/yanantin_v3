<?php

use App\Models\Product;

beforeEach(function () {
    $this->initializeTenancy();
});

it('can create product in database', function () {
    $product = Product::factory()->create([
        'name' => 'Test Product',
    ]);

    expect(Product::count())->toBe(1)
        ->and($product->name)->toBe('Test Product');
});
