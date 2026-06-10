<?php

declare(strict_types=1);

namespace Tests\Feature\Outbox;

use App\Modules\Events\Application\Services\OutboxDispatcher;
use App\Modules\Locations\Application\Actions\CreateLocationAction;
use App\Modules\Locations\Application\DTOs\CreateLocationDTO;
use App\Modules\Locations\Domain\Entities\Location;
use App\Modules\Locations\Infrastructure\Persistence\Eloquent\LocationModel;
use App\Modules\Product\Application\Actions\CreateProductAction;
use App\Modules\Product\Application\DTOs\CreateProductPayload;
use App\Modules\Product\Domain\Entities\Product;
use App\Modules\Product\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class OutboxIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_failing_transaction_emits_no_events(): void
    {
        $product = ProductModel::create([
            'id' => Str::uuid()->toString(),
            'sku' => 'TEST-OUTBOX-1',
            'name' => 'Test Product',
            'category' => 'test',
            'unit_of_measure' => 'unit',
        ]);

        $location = LocationModel::create([
            'id' => Str::uuid()->toString(),
            'warehouse_code' => 'WH1',
            'zone' => 'A',
            'aisle' => '01',
            'rack' => 'R1',
            'level' => 'L1',
            'bin' => 'B2',
            'label' => 'WH1-A-01-R1-L1-B2',
            'is_blocked' => false,
        ]);

        $this->postJson('/api/movements', [
            'productId' => $product->id,
            'toLocationId' => $location->id,
            'type' => 'receipt',
            'quantity' => 10,
        ])->assertStatus(201);

        $outboxCountAfterFirst = \App\Modules\Events\Infrastructure\Persistence\Eloquent\EventOutboxModel::count();
        $this->assertGreaterThan(0, $outboxCountAfterFirst);

        $mock = \Mockery::mock(\App\Modules\Inventory\Application\Services\InternalStockMutationService::class);
        $mock->shouldReceive('applyMovement')->andThrow(\App\Modules\Inventory\Domain\Exceptions\OptimisticLockException::forStockItem('fake_id'));
        $this->app->instance(\App\Modules\Inventory\Application\Services\InternalStockMutationService::class, $mock);

        $payload = [
            'productId' => $product->id,
            'fromLocationId' => $location->id,
            'type' => 'picking',
            'quantity' => 2,
        ];

        $response = $this->postJson('/api/movements', $payload);
        $response->assertStatus(409);

        $outboxCountAfterFailure = \App\Modules\Events\Infrastructure\Persistence\Eloquent\EventOutboxModel::count();
        $this->assertEquals($outboxCountAfterFirst, $outboxCountAfterFailure);
    }

    public function test_nested_transaction_rollback_prevents_dispatch(): void
    {
        \Illuminate\Support\Facades\Event::fake([\App\Modules\Events\Application\Services\BroadcastableOutboxEvent::class]);

        $action = $this->app->make(CreateProductAction::class);

        try {
            DB::transaction(function () use ($action) {
                // Call the action, which uses its own internal DB::transaction
                $action->execute(new CreateProductPayload(
                    sku: 'TEST-NESTED-1',
                    name: 'Nested Product',
                    category: 'test',
                    unitOfMeasure: 'unit',
                    attributes: [],
                    actorId: 'system'
                ));

                // Force outer transaction to fail
                throw new \Exception("Outer transaction failure");
            });
        } catch (\Exception $e) {
            $this->assertEquals("Outer transaction failure", $e->getMessage());
        }

        // Verify that the outbox record rolled back
        $outboxCount = \App\Modules\Events\Infrastructure\Persistence\Eloquent\EventOutboxModel::where('event_type', 'product.created')->count();
        $this->assertEquals(0, $outboxCount);

        // Verify that the trailing dispatcher never fired
        \Illuminate\Support\Facades\Event::assertNotDispatched(\App\Modules\Events\Application\Services\BroadcastableOutboxEvent::class);
    }
}
