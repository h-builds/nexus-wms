import { describe, it, expect, beforeEach } from 'vitest'
import { EventInterpreter, type InterpretedState } from '../EventInterpreter'
import type { CanonicalEvent } from '../../stores/useEventIngestionStore'

function createEmptyState(): InterpretedState {
    return {
        inventoryByLocation: {},
        openIncidents: new Set<string>(),
        openIncidentsByLocation: {},
        totalMovementsProcessed: 0,
        processedEventIds: new Set<string>(),
        lastProcessedEventTime: null,
        driftedEvents: [],
    }
}

function createEvent(overrides: Partial<CanonicalEvent> & { eventId: string; eventType: string }): CanonicalEvent {
    return {
        eventVersion: 1,
        occurredAt: '2026-03-31T10:00:00Z',
        actorId: 'user_001',
        correlationId: 'corr_001',
        causationId: 'cause_001',
        payload: {},
        ...overrides,
    }
}

describe('EventInterpreter — Contract Alignment', () => {
    let interpreter: EventInterpreter
    let state: InterpretedState

    beforeEach(() => {
        interpreter = new EventInterpreter()
        state = createEmptyState()
    })

    describe('inventory.stock.relocated uses canonical fromLocationId/toLocationId', () => {
        it('subtracts from fromLocationId and adds to toLocationId', () => {
            state.inventoryByLocation['loc_001'] = 50
            state.inventoryByLocation['loc_002'] = 10

            const relocatedEvent = createEvent({
                eventId: 'evt_reloc_001',
                eventType: '.inventory.stock.relocated',
                payload: {
                    productId: 'prod_001',
                    fromLocationId: 'loc_001',
                    toLocationId: 'loc_002',
                    quantity: 5,
                },
            })

            interpreter.interpret(relocatedEvent, state)

            expect(state.inventoryByLocation['loc_001']).toBe(45)
            expect(state.inventoryByLocation['loc_002']).toBe(15)
        })

        it('does NOT react to non-canonical sourceLocationId/destinationLocationId', () => {
            state.inventoryByLocation['loc_001'] = 50
            state.inventoryByLocation['loc_002'] = 10

            const relocatedEventWithWrongFields = createEvent({
                eventId: 'evt_reloc_002',
                eventType: '.inventory.stock.relocated',
                payload: {
                    productId: 'prod_001',
                    sourceLocationId: 'loc_001',
                    destinationLocationId: 'loc_002',
                    quantity: 5,
                },
            })

            interpreter.interpret(relocatedEventWithWrongFields, state)

            expect(state.inventoryByLocation['loc_001']).toBe(50)
            expect(state.inventoryByLocation['loc_002']).toBe(10)
        })

        it('handles relocation from unknown location (initializes to zero)', () => {
            state.inventoryByLocation['loc_002'] = 10

            const relocatedEvent = createEvent({
                eventId: 'evt_reloc_003',
                eventType: '.inventory.stock.relocated',
                payload: {
                    productId: 'prod_001',
                    fromLocationId: 'loc_new',
                    toLocationId: 'loc_002',
                    quantity: 3,
                },
            })

            interpreter.interpret(relocatedEvent, state)

            expect(state.inventoryByLocation['loc_new']).toBe(0)
            expect(state.inventoryByLocation['loc_002']).toBe(13)
        })
    })

    describe('inventory.stock.adjusted uses canonical previousQuantity/newQuantity', () => {
        it('sets location inventory to newQuantity from canonical payload', () => {
            state.inventoryByLocation['loc_001'] = 100

            const adjustedEvent = createEvent({
                eventId: 'evt_adj_001',
                eventType: '.inventory.stock.adjusted',
                payload: {
                    productId: 'prod_001',
                    locationId: 'loc_001',
                    previousQuantity: 100,
                    newQuantity: 95,
                    reason: 'manual_adjustment',
                },
            })

            interpreter.interpret(adjustedEvent, state)

            expect(state.inventoryByLocation['loc_001']).toBe(95)
        })

        it('handles adjustment resulting in zero stock', () => {
            state.inventoryByLocation['loc_001'] = 5

            const adjustedEvent = createEvent({
                eventId: 'evt_adj_002',
                eventType: '.inventory.stock.adjusted',
                payload: {
                    productId: 'prod_001',
                    locationId: 'loc_001',
                    previousQuantity: 5,
                    newQuantity: 0,
                    reason: 'write_off',
                },
            })

            interpreter.interpret(adjustedEvent, state)

            expect(state.inventoryByLocation['loc_001']).toBe(0)
        })

        it('does NOT use deltaQuantity (non-canonical field)', () => {
            state.inventoryByLocation['loc_001'] = 100

            const adjustedEventWithDelta = createEvent({
                eventId: 'evt_adj_003',
                eventType: '.inventory.stock.adjusted',
                payload: {
                    productId: 'prod_001',
                    locationId: 'loc_001',
                    deltaQuantity: -5,
                },
            })

            interpreter.interpret(adjustedEventWithDelta, state)

            // Without canonical previousQuantity, expectedQuantity defaults to 0.
            // Local is 100, which != 0 → drift detected, adjustment refused.
            expect(state.inventoryByLocation['loc_001']).toBe(100)
            expect(state.driftedEvents.length).toBe(1)
        })
    })

    describe('inventory.stock.adjusted — drift detection (Phase 4.3.5B-R)', () => {
        it('applies adjustment when previousQuantity matches local state', () => {
            state.inventoryByLocation['loc_001'] = 100

            const event = createEvent({
                eventId: 'evt_drift_ok_001',
                eventType: '.inventory.stock.adjusted',
                payload: {
                    productId: 'prod_001',
                    locationId: 'loc_001',
                    previousQuantity: 100,
                    newQuantity: 95,
                    reason: 'manual_adjustment',
                },
            })

            interpreter.interpret(event, state)

            expect(state.inventoryByLocation['loc_001']).toBe(95)
            expect(state.driftedEvents.length).toBe(0)
        })

        it('detects drift when previousQuantity does NOT match local state', () => {
            state.inventoryByLocation['loc_001'] = 80

            const staleEvent = createEvent({
                eventId: 'evt_drift_stale_001',
                eventType: '.inventory.stock.adjusted',
                occurredAt: '2026-03-31T10:00:00Z',
                payload: {
                    productId: 'prod_001',
                    locationId: 'loc_001',
                    previousQuantity: 100,
                    newQuantity: 95,
                    reason: 'manual_adjustment',
                },
            })

            interpreter.interpret(staleEvent, state)

            expect(state.inventoryByLocation['loc_001']).toBe(80)

            expect(state.driftedEvents.length).toBe(1)
            expect(state.driftedEvents[0].eventId).toBe('evt_drift_stale_001')
            expect(state.driftedEvents[0].locationId).toBe('loc_001')
            expect(state.driftedEvents[0].expectedQuantity).toBe(100)
            expect(state.driftedEvents[0].localQuantity).toBe(80)
            expect(state.driftedEvents[0].attemptedNewQuantity).toBe(95)
        })

        it('refuses out-of-order delivery (T2 arrives before T1)', () => {
            state.inventoryByLocation['loc_001'] = 100

            // T1: 100 → 90 (this is the first adjustment, happens at T1)
            const eventT1 = createEvent({
                eventId: 'evt_t1',
                eventType: '.inventory.stock.adjusted',
                occurredAt: '2026-03-31T10:00:00Z',
                payload: {
                    productId: 'prod_001',
                    locationId: 'loc_001',
                    previousQuantity: 100,
                    newQuantity: 90,
                    reason: 'adjustment_t1',
                },
            })

            // T2: 90 → 85 (this happens after T1, but arrives first due to out-of-order)
            const eventT2 = createEvent({
                eventId: 'evt_t2',
                eventType: '.inventory.stock.adjusted',
                occurredAt: '2026-03-31T10:01:00Z',
                payload: {
                    productId: 'prod_001',
                    locationId: 'loc_001',
                    previousQuantity: 90,
                    newQuantity: 85,
                    reason: 'adjustment_t2',
                },
            })

            interpreter.interpret(eventT2, state)

            // T2 expects previousQuantity=90, but local is 100 → drift
            expect(state.inventoryByLocation['loc_001']).toBe(100)
            expect(state.driftedEvents.length).toBe(1)
            expect(state.driftedEvents[0].eventId).toBe('evt_t2')

            interpreter.interpret(eventT1, state)

            // T1 expects previousQuantity=100, local IS 100 → applies
            expect(state.inventoryByLocation['loc_001']).toBe(90)
            expect(state.driftedEvents.length).toBe(1)
        })

        it('applies adjustment from zero state when previousQuantity is 0', () => {
            // Location not yet in state (defaults to 0)

            const event = createEvent({
                eventId: 'evt_from_zero',
                eventType: '.inventory.stock.adjusted',
                payload: {
                    productId: 'prod_001',
                    locationId: 'loc_new',
                    previousQuantity: 0,
                    newQuantity: 50,
                    reason: 'initial_count',
                },
            })

            interpreter.interpret(event, state)

            expect(state.inventoryByLocation['loc_new']).toBe(50)
            expect(state.driftedEvents.length).toBe(0)
        })

        it('records multiple drift events deterministically', () => {
            state.inventoryByLocation['loc_001'] = 50

            const stale1 = createEvent({
                eventId: 'evt_stale_1',
                eventType: '.inventory.stock.adjusted',
                payload: {
                    productId: 'prod_001',
                    locationId: 'loc_001',
                    previousQuantity: 100,
                    newQuantity: 90,
                    reason: 'stale_1',
                },
            })

            const stale2 = createEvent({
                eventId: 'evt_stale_2',
                eventType: '.inventory.stock.adjusted',
                payload: {
                    productId: 'prod_001',
                    locationId: 'loc_001',
                    previousQuantity: 90,
                    newQuantity: 80,
                    reason: 'stale_2',
                },
            })

            interpreter.interpret(stale1, state)
            interpreter.interpret(stale2, state)

            expect(state.inventoryByLocation['loc_001']).toBe(50)
            expect(state.driftedEvents.length).toBe(2)
            expect(state.driftedEvents[0].eventId).toBe('evt_stale_1')
            expect(state.driftedEvents[1].eventId).toBe('evt_stale_2')
        })
    })

    describe('duplicate event protection', () => {
        it('ignores duplicate events based on processedEventIds', () => {
            state.inventoryByLocation['loc_001'] = 100

            const event = createEvent({
                eventId: 'evt_dup_001',
                eventType: '.inventory.stock.adjusted',
                payload: {
                    productId: 'prod_001',
                    locationId: 'loc_001',
                    previousQuantity: 100,
                    newQuantity: 90,
                    reason: 'test',
                },
            })

            interpreter.interpret(event, state)
            expect(state.inventoryByLocation['loc_001']).toBe(90)

            interpreter.interpret(event, state)
            expect(state.inventoryByLocation['loc_001']).toBe(90)
            expect(state.processedEventIds.size).toBe(1)
        })

        it('duplicate protection still works after drift repair', () => {
            state.inventoryByLocation['loc_001'] = 100

            const validEvent = createEvent({
                eventId: 'evt_dup_drift_001',
                eventType: '.inventory.stock.adjusted',
                payload: {
                    productId: 'prod_001',
                    locationId: 'loc_001',
                    previousQuantity: 100,
                    newQuantity: 90,
                    reason: 'test',
                },
            })

            interpreter.interpret(validEvent, state)
            expect(state.inventoryByLocation['loc_001']).toBe(90)
            expect(state.driftedEvents.length).toBe(0)

            // Replay same event — should be caught by duplicate protection, not drift
            interpreter.interpret(validEvent, state)
            expect(state.inventoryByLocation['loc_001']).toBe(90)
            expect(state.processedEventIds.size).toBe(1)
            expect(state.driftedEvents.length).toBe(0)
        })
    })
})
