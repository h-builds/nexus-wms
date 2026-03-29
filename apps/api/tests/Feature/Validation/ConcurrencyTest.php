<?php

declare(strict_types=1);

namespace Tests\Feature\Validation;

use App\Models\User;
use App\Modules\Inventory\Application\Services\InternalStockMutationService;
use App\Modules\Inventory\Domain\Exceptions\OptimisticLockException;
use App\Modules\Locations\Infrastructure\Persistence\Eloquent\LocationModel;
use App\Modules\Product\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private $operator;
    private string $productId;
    private string $locationId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = (new User())->forceFill([
            'id' => Str::uuid()->toString(),
            'name' => 'Op', 'email' => 'op@test.com', 'role' => 'operator',
        ]);
        $this->operator->setKeyType('string');
        $this->operator->setIncrementing(false);

        $this->productId = Str::uuid()->toString();
        $this->locationId = Str::uuid()->toString();

        ProductModel::create([
            'id' => $this->productId, 'sku' => 'CONC-001',
            'name' => 'Concurrency Product', 'category' => 'test', 'unit_of_measure' => 'unit',
        ]);

        LocationModel::create([
            'id' => $this->locationId, 'warehouse_code' => 'WH1',
            'zone' => 'A', 'aisle' => '01', 'rack' => 'R1', 'level' => 'L1', 'bin' => 'B1',
            'label' => 'WH1-CONC-01', 'is_blocked' => false,
        ]);


        $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'toLocationId' => $this->locationId,
            'type' => 'receipt',
            'quantity' => 100,
        ])->assertStatus(201);
    }

    public function test_optimistic_lock_conflict_returns_409_and_preserves_data(): void
    {
        $stockBefore = DB::table('stock_items')
            ->where('product_id', $this->productId)
            ->where('location_id', $this->locationId)->first();

        $this->assertNotNull($stockBefore);
        $versionBefore = $stockBefore->version;

        // Simulate concurrent version conflict via mock
        $mock = \Mockery::mock(InternalStockMutationService::class);
        $mock->shouldReceive('applyMovement')
            ->andThrow(OptimisticLockException::forStockItem('fake'));
        $this->app->instance(InternalStockMutationService::class, $mock);

        $response = $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'fromLocationId' => $this->locationId,
            'type' => 'picking',
            'quantity' => 5,
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('error.code', 'conflict');

        // Transaction rollback must leave stock completely unchanged
        $stockAfter = DB::table('stock_items')
            ->where('product_id', $this->productId)
            ->where('location_id', $this->locationId)->first();

        $this->assertEquals($stockBefore->quantity_available, $stockAfter->quantity_available);
        $this->assertEquals($versionBefore, $stockAfter->version);
    }

    public function test_version_increments_on_successful_mutation(): void
    {
        $stockBefore = DB::table('stock_items')
            ->where('product_id', $this->productId)
            ->where('location_id', $this->locationId)->first();

        $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'fromLocationId' => $this->locationId,
            'type' => 'picking',
            'quantity' => 1,
        ])->assertStatus(201);

        $stockAfter = DB::table('stock_items')
            ->where('product_id', $this->productId)
            ->where('location_id', $this->locationId)->first();

        $this->assertEquals($stockBefore->version + 1, $stockAfter->version);
    }
}
