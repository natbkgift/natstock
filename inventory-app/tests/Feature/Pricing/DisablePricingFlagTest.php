<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(RefreshDatabase::class);

function makeUser(string $role = 'staff'): User
{
    return User::factory()->create(['role' => $role]);
}

function cleanupImportTmp(): void
{
    $tmpDir = storage_path('app/tmp');
    if (!is_dir($tmpDir)) {
        return;
    }

    foreach (scandir($tmpDir) ?: [] as $file) {
        if (in_array($file, ['.', '..'], true)) {
            continue;
        }

        @unlink($tmpDir . DIRECTORY_SEPARATOR . $file);
    }
}

beforeEach(function (): void {
    cleanupImportTmp();
});

afterEach(function (): void {
    cleanupImportTmp();
});

it('strips pricing payloads when the feature flag is disabled', function (): void {
    config()->set('inventory.enable_price', false);
    $user = makeUser('staff');
    actingAs($user);

    $category = Category::factory()->create();

    $createResponse = post(route('admin.products.store'), [
        'sku' => 'SKU-FLAG-01',
        'name' => 'สินค้าทดสอบ',
        'category_id' => $category->getKey(),
        'initial_qty' => 5,
        'expire_in_days' => 30,
        'reorder_point' => 2,
        'is_active' => 1,
        'cost_price' => 123.45,
        'sale_price' => 999.99,
    ]);

    $createResponse->assertRedirect(route('admin.products.index'));

    $product = Product::query()->firstOrFail();
    expect(number_format((float) $product->cost_price, 2, '.', ''))->toBe('0.00')
        ->and(number_format((float) $product->sale_price, 2, '.', ''))->toBe('0.00');

    $updateResponse = put(route('admin.products.update', $product), [
        'sku' => 'SKU-FLAG-01',
        'name' => 'สินค้าปรับปรุง',
        'category_id' => $category->getKey(),
        'qty' => 7,
        'reorder_point' => 3,
        'expire_date' => Carbon::now()->addDays(45)->toDateString(),
        'is_active' => 1,
        'cost_price' => 555.55,
        'sale_price' => 777.77,
    ]);

    $updateResponse->assertRedirect(route('admin.products.index'));

    $product->refresh();
    expect($product->name)->toBe('สินค้าปรับปรุง')
        ->and(number_format((float) $product->cost_price, 2, '.', ''))->toBe('0.00')
        ->and(number_format((float) $product->sale_price, 2, '.', ''))->toBe('0.00');
});

it('hides pricing UI and prevents valuation access while disabled', function (): void {
    config()->set('inventory.enable_price', false);
    $user = makeUser('staff');
    actingAs($user);

    $createPage = get(route('admin.products.create'));
    $createPage->assertOk();
    $createPage->assertDontSee('ราคาทุนต่อหน่วย');
    $createPage->assertSee('ระบบปิดการใช้งานราคาทุน/ราคาขายอยู่');

    $indexPage = get(route('admin.products.index'));
    $indexPage->assertOk();
    $indexPage->assertDontSee('ราคาทุน');

    $valuationResponse = get(route('admin.reports.valuation'));
    $valuationResponse->assertNotFound();
});

it('ignores pricing columns during import preview and removes them from exports when disabled', function (): void {
    config()->set('inventory.enable_price', false);
    $user = makeUser('admin');
    actingAs($user);

    $csvContent = implode("\n", [
        'sku,name,category,qty,cost_price,sale_price,expire_date,reorder_point,note,is_active',
        'SKU-IMPORT,ผ้าก๊อซ,อุปกรณ์,4,12.50,15.00,2025-12-31,1,ทดสอบ,1',
    ]);

    $file = UploadedFile::fake()->createWithContent('pricing.csv', $csvContent);
    $preview = post(route('admin.import.preview'), [
        'file' => $file,
        'duplicate_mode' => 'UPSERT',
        'auto_create_category' => '1',
    ]);

    $preview->assertNotFound();

    Product::factory()->lowStock()->create(['reorder_point' => 10, 'qty' => 3]);

    $export = get(route('admin.reports.low-stock', ['export' => 'csv']));
    $export->assertOk();
    $content = $export->streamedContent();
    expect($content)->not->toContain('cost_price')
        ->and($content)->not->toContain('ราคาทุน');
});

it('shows pricing UI and keeps values when the feature flag is enabled', function (): void {
    config()->set('inventory.enable_price', true);
    $user = makeUser('staff');
    actingAs($user);

    $createPage = get(route('admin.products.create'));
    $createPage->assertOk();
    $createPage->assertSee('ราคาทุนต่อหน่วย');
    $createPage->assertSee('ราคาขายต่อหน่วย');

    $category = Category::factory()->create();

    $createResponse = post(route('admin.products.store'), [
        'sku' => 'SKU-ENABLE-01',
        'name' => 'สินค้าเปิดราคา',
        'category_id' => $category->getKey(),
        'initial_qty' => 4,
        'expire_in_days' => 20,
        'reorder_point' => 1,
        'is_active' => 1,
        'cost_price' => 88.25,
        'sale_price' => 99.99,
    ]);

    $createResponse->assertRedirect(route('admin.products.index'));

    $product = Product::query()->where('sku', 'SKU-ENABLE-01')->firstOrFail();
    expect(number_format((float) $product->cost_price, 2, '.', ''))->toBe('88.25')
        ->and(number_format((float) $product->sale_price, 2, '.', ''))->toBe('99.99');

    $export = get(route('admin.reports.valuation', ['export' => 'csv']));
    $export->assertOk();
    $content = $export->streamedContent();
    expect($content)->toContain('ราคาทุนต่อหน่วย')
        ->and($content)->toContain('มูลค่ารวม');
});
