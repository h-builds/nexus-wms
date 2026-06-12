# Project Anatomy

```text
.
├── .ai
│   ├── AGENTS.md
│   ├── COMMIT_GUIDE.md
│   ├── CONTEXT.md
│   ├── CONTEXT_LOADING.md
│   ├── CURRENT_TASK.md
│   ├── DATA_GUARDRAILS.md
│   ├── ENTRYPOINT.md
│   ├── EVALS.md
│   ├── HOW_TO_TEST.md
│   ├── PROMPT_LIBRARY.md
│   ├── PR_TEMPLATE.md
│   ├── REVIEW_CHECKLIST.md
│   ├── RULES.md
│   ├── active
│   │   └── PLAN.md
│   └── archive
│       └── PLAN-2026-03-phases-0-4.5.md
├── .antigravity
│   └── rules
├── .codex
├── .editorconfig
├── .github
│   └── pull_request_template.md
├── .gitignore
├── CLAUDE.md
├── CODEREVIEWRULES.md
├── PENDING.md
├── README.md
├── TASK.md
├── anatomy.md
├── apps
│   ├── api
│   │   ├── .editorconfig
│   │   ├── .env
│   │   ├── .env.example
│   │   ├── .gitattributes
│   │   ├── .gitignore
│   │   ├── .phpunit.result.cache
│   │   ├── README.md
│   │   ├── app
│   │   │   ├── Http
│   │   │   │   ├── Controllers
│   │   │   │   │   └── Controller.php
│   │   │   │   ├── Middleware
│   │   │   │   │   └── IdempotencyMiddleware.php
│   │   │   │   └── Responses
│   │   │   │       └── PaginatedResponse.php
│   │   │   ├── Models
│   │   │   │   └── User.php
│   │   │   ├── Modules
│   │   │   │   ├── Audit
│   │   │   │   │   ├── Application
│   │   │   │   │   │   └── Services
│   │   │   │   │   │       └── AuditLogger.php
│   │   │   │   │   ├── Domain
│   │   │   │   │   │   ├── Entities
│   │   │   │   │   │   │   └── AuditLog.php
│   │   │   │   │   │   └── Enums
│   │   │   │   │   │       └── AuditActorType.php
│   │   │   │   │   ├── Infrastructure
│   │   │   │   │   │   └── Persistence
│   │   │   │   │   │       └── Eloquent
│   │   │   │   │   │           └── AuditLogModel.php
│   │   │   │   │   └── routes.php
│   │   │   │   ├── Events
│   │   │   │   │   ├── Application
│   │   │   │   │   │   ├── Exceptions
│   │   │   │   │   │   │   └── EventPublishingFailedException.php
│   │   │   │   │   │   └── Services
│   │   │   │   │   │       ├── BroadcastableOutboxEvent.php
│   │   │   │   │   │       ├── EventPublisher.php
│   │   │   │   │   │       └── OutboxDispatcher.php
│   │   │   │   │   ├── Domain
│   │   │   │   │   │   ├── DTOs
│   │   │   │   │   │   │   └── DomainEventPayload.php
│   │   │   │   │   │   └── Repositories
│   │   │   │   │   │       └── EventOutboxRepository.php
│   │   │   │   │   └── Infrastructure
│   │   │   │   │       ├── Exceptions
│   │   │   │   │       │   └── EventOutboxPersistenceFailedException.php
│   │   │   │   │       ├── Persistence
│   │   │   │   │       │   ├── Eloquent
│   │   │   │   │       │   │   └── EventOutboxModel.php
│   │   │   │   │       │   └── Repositories
│   │   │   │   │       │       └── EloquentEventOutboxRepository.php
│   │   │   │   │       └── Providers
│   │   │   │   │           └── EventServiceProvider.php
│   │   │   │   ├── Identity
│   │   │   │   │   └── routes.php
│   │   │   │   ├── Incidents
│   │   │   │   │   ├── Application
│   │   │   │   │   │   ├── Actions
│   │   │   │   │   │   │   ├── GetIncidentByIdAction.php
│   │   │   │   │   │   │   ├── GetIncidentsAction.php
│   │   │   │   │   │   │   ├── ReportIncidentAction.php
│   │   │   │   │   │   │   ├── UpdateIncidentMetadataAction.php
│   │   │   │   │   │   │   └── UpdateIncidentStatusAction.php
│   │   │   │   │   │   ├── DTOs
│   │   │   │   │   │   │   ├── IncidentReportedEventPayload.php
│   │   │   │   │   │   │   ├── IncidentStatusUpdatedEventPayload.php
│   │   │   │   │   │   │   ├── ReportIncidentDTO.php
│   │   │   │   │   │   │   ├── UpdateIncidentMetadataDTO.php
│   │   │   │   │   │   │   └── UpdateIncidentStatusDTO.php
│   │   │   │   │   │   └── Exceptions
│   │   │   │   │   │       ├── IdempotencyConflictException.php
│   │   │   │   │   │       ├── IncidentReportingFailedException.php
│   │   │   │   │   │       └── IncidentStatusUpdateFailedException.php
│   │   │   │   │   ├── Domain
│   │   │   │   │   │   ├── Entities
│   │   │   │   │   │   │   └── InventoryIncident.php
│   │   │   │   │   │   ├── Enums
│   │   │   │   │   │   │   ├── IncidentSeverity.php
│   │   │   │   │   │   │   ├── IncidentStatus.php
│   │   │   │   │   │   │   └── IncidentType.php
│   │   │   │   │   │   ├── Events
│   │   │   │   │   │   │   ├── IncidentReported.php
│   │   │   │   │   │   │   └── IncidentStatusUpdated.php
│   │   │   │   │   │   ├── Exceptions
│   │   │   │   │   │   │   ├── IncidentNotFound.php
│   │   │   │   │   │   │   ├── InvalidIncidentSeverity.php
│   │   │   │   │   │   │   ├── InvalidIncidentStatus.php
│   │   │   │   │   │   │   └── InvalidIncidentType.php
│   │   │   │   │   │   ├── Repositories
│   │   │   │   │   │   │   └── IncidentRepository.php
│   │   │   │   │   │   └── Services
│   │   │   │   │   │       └── IncidentValidator.php
│   │   │   │   │   ├── Infrastructure
│   │   │   │   │   │   ├── Http
│   │   │   │   │   │   │   ├── Controllers
│   │   │   │   │   │   │   │   └── IncidentController.php
│   │   │   │   │   │   │   ├── Requests
│   │   │   │   │   │   │   │   ├── ListIncidentsRequest.php
│   │   │   │   │   │   │   │   ├── ReportIncidentRequest.php
│   │   │   │   │   │   │   │   ├── UpdateIncidentMetadataRequest.php
│   │   │   │   │   │   │   │   └── UpdateIncidentStatusRequest.php
│   │   │   │   │   │   │   └── Resources
│   │   │   │   │   │   │       └── IncidentResource.php
│   │   │   │   │   │   ├── Persistence
│   │   │   │   │   │   │   ├── Eloquent
│   │   │   │   │   │   │   │   └── InventoryIncidentModel.php
│   │   │   │   │   │   │   └── Repositories
│   │   │   │   │   │   │       └── EloquentIncidentRepository.php
│   │   │   │   │   │   └── Providers
│   │   │   │   │   │       └── IncidentServiceProvider.php
│   │   │   │   │   └── routes.php
│   │   │   │   ├── Intelligence
│   │   │   │   │   ├── Application
│   │   │   │   │   │   ├── Actions
│   │   │   │   │   │   │   ├── AcknowledgeDecisionTraceAction.php
│   │   │   │   │   │   │   ├── ActUponDecisionTraceAction.php
│   │   │   │   │   │   │   ├── DismissDecisionTraceAction.php
│   │   │   │   │   │   │   ├── EvaluateOutboxEventAction.php
│   │   │   │   │   │   │   ├── GetDecisionTraceByIdAction.php
│   │   │   │   │   │   │   ├── GetDecisionTraceMetricsAction.php
│   │   │   │   │   │   │   └── ListDecisionTracesAction.php
│   │   │   │   │   │   ├── Agents
│   │   │   │   │   │   │   ├── AgentExecutor.php
│   │   │   │   │   │   │   ├── CanonicalEvent.php
│   │   │   │   │   │   │   ├── DecisionAgent.php
│   │   │   │   │   │   │   └── InventoryAnomalyAgent.php
│   │   │   │   │   │   ├── DTOs
│   │   │   │   │   │   │   ├── DecisionTraceListCriteria.php
│   │   │   │   │   │   │   ├── DecisionTraceMetrics.php
│   │   │   │   │   │   │   └── DecisionTraceSortOrder.php
│   │   │   │   │   │   └── Queries
│   │   │   │   │   │       └── DecisionTraceQueryService.php
│   │   │   │   │   ├── Domain
│   │   │   │   │   │   ├── Entities
│   │   │   │   │   │   │   └── DecisionTrace.php
│   │   │   │   │   │   ├── Enums
│   │   │   │   │   │   │   ├── AgentDomain.php
│   │   │   │   │   │   │   ├── TraceSeverity.php
│   │   │   │   │   │   │   ├── TraceStatus.php
│   │   │   │   │   │   │   └── TraceType.php
│   │   │   │   │   │   ├── Exceptions
│   │   │   │   │   │   │   ├── DecisionTraceNotFound.php
│   │   │   │   │   │   │   ├── InvalidAgentDomain.php
│   │   │   │   │   │   │   ├── InvalidStateTransitionException.php
│   │   │   │   │   │   │   ├── InvalidTraceSeverity.php
│   │   │   │   │   │   │   └── InvalidTraceType.php
│   │   │   │   │   │   └── Repositories
│   │   │   │   │   │       └── DecisionTraceRepository.php
│   │   │   │   │   ├── Infrastructure
│   │   │   │   │   │   ├── Http
│   │   │   │   │   │   │   ├── Controllers
│   │   │   │   │   │   │   │   └── DecisionTraceController.php
│   │   │   │   │   │   │   ├── Requests
│   │   │   │   │   │   │   │   ├── ActUponDecisionTraceRequest.php
│   │   │   │   │   │   │   │   ├── DismissDecisionTraceRequest.php
│   │   │   │   │   │   │   │   └── ListDecisionTracesRequest.php
│   │   │   │   │   │   │   └── Resources
│   │   │   │   │   │   │       └── DecisionTraceResource.php
│   │   │   │   │   │   ├── Listeners
│   │   │   │   │   │   │   └── EvaluateOutboxEventListener.php
│   │   │   │   │   │   ├── Persistence
│   │   │   │   │   │   │   ├── Eloquent
│   │   │   │   │   │   │   │   └── DecisionTraceModel.php
│   │   │   │   │   │   │   └── Repositories
│   │   │   │   │   │   │       └── EloquentDecisionTraceRepository.php
│   │   │   │   │   │   └── Providers
│   │   │   │   │   │       └── IntelligenceServiceProvider.php
│   │   │   │   │   └── routes.php
│   │   │   │   ├── Inventory
│   │   │   │   │   ├── Application
│   │   │   │   │   │   ├── Actions
│   │   │   │   │   │   │   ├── GetStockItemByIdAction.php
│   │   │   │   │   │   │   └── ListStockItemsAction.php
│   │   │   │   │   │   ├── DTOs
│   │   │   │   │   │   │   └── StockMutationDTO.php
│   │   │   │   │   │   └── Services
│   │   │   │   │   │       └── InternalStockMutationService.php
│   │   │   │   │   ├── Domain
│   │   │   │   │   │   ├── Entities
│   │   │   │   │   │   │   └── StockItem.php
│   │   │   │   │   │   ├── Enums
│   │   │   │   │   │   │   ├── InventoryStatus.php
│   │   │   │   │   │   │   └── MutationOperation.php
│   │   │   │   │   │   ├── Events
│   │   │   │   │   │   │   ├── StockAdjusted.php
│   │   │   │   │   │   │   ├── StockReceived.php
│   │   │   │   │   │   │   └── StockRelocated.php
│   │   │   │   │   │   ├── Exceptions
│   │   │   │   │   │   │   ├── OptimisticLockException.php
│   │   │   │   │   │   │   └── StockItemNotFound.php
│   │   │   │   │   │   ├── Repositories
│   │   │   │   │   │   │   └── StockItemRepository.php
│   │   │   │   │   │   ├── Services
│   │   │   │   │   │   │   └── InventoryValidator.php
│   │   │   │   │   │   └── ValueObjects
│   │   │   │   │   │       └── StockItemId.php
│   │   │   │   │   ├── Infrastructure
│   │   │   │   │   │   ├── Http
│   │   │   │   │   │   │   ├── Controllers
│   │   │   │   │   │   │   │   └── InventoryController.php
│   │   │   │   │   │   │   └── Resources
│   │   │   │   │   │   │       └── StockItemResource.php
│   │   │   │   │   │   ├── Persistence
│   │   │   │   │   │   │   └── Eloquent
│   │   │   │   │   │   │       ├── EloquentStockItemRepository.php
│   │   │   │   │   │   │       └── StockItemModel.php
│   │   │   │   │   │   └── Providers
│   │   │   │   │   │       └── InventoryServiceProvider.php
│   │   │   │   │   └── routes.php
│   │   │   │   ├── Locations
│   │   │   │   │   ├── Application
│   │   │   │   │   │   ├── Actions
│   │   │   │   │   │   │   ├── CreateLocationAction.php
│   │   │   │   │   │   │   ├── GetLocationByIdAction.php
│   │   │   │   │   │   │   ├── ListLocationsAction.php
│   │   │   │   │   │   │   └── UpdateLocationStatusAction.php
│   │   │   │   │   │   ├── DTOs
│   │   │   │   │   │   │   ├── CreateLocationDTO.php
│   │   │   │   │   │   │   ├── LocationCreatedEventPayload.php
│   │   │   │   │   │   │   ├── LocationListCriteria.php
│   │   │   │   │   │   │   ├── LocationStatusUpdatedEventPayload.php
│   │   │   │   │   │   │   └── UpdateLocationStatusDTO.php
│   │   │   │   │   │   └── Queries
│   │   │   │   │   │       └── LocationQueryService.php
│   │   │   │   │   ├── Domain
│   │   │   │   │   │   ├── Entities
│   │   │   │   │   │   │   └── Location.php
│   │   │   │   │   │   ├── Events
│   │   │   │   │   │   │   └── LocationCreated.php
│   │   │   │   │   │   ├── Exceptions
│   │   │   │   │   │   │   ├── DuplicateLocationLabel.php
│   │   │   │   │   │   │   ├── InvalidLocationPayload.php
│   │   │   │   │   │   │   └── LocationNotFound.php
│   │   │   │   │   │   └── Repositories
│   │   │   │   │   │       └── LocationRepository.php
│   │   │   │   │   ├── Infrastructure
│   │   │   │   │   │   ├── Http
│   │   │   │   │   │   │   ├── Controllers
│   │   │   │   │   │   │   │   └── LocationController.php
│   │   │   │   │   │   │   ├── Requests
│   │   │   │   │   │   │   │   ├── ListLocationsRequest.php
│   │   │   │   │   │   │   │   ├── StoreLocationRequest.php
│   │   │   │   │   │   │   │   └── UpdateLocationStatusRequest.php
│   │   │   │   │   │   │   └── Resources
│   │   │   │   │   │   │       └── LocationResource.php
│   │   │   │   │   │   ├── Persistence
│   │   │   │   │   │   │   └── Eloquent
│   │   │   │   │   │   │       ├── EloquentLocationQueryService.php
│   │   │   │   │   │   │       ├── EloquentLocationRepository.php
│   │   │   │   │   │   │       └── LocationModel.php
│   │   │   │   │   │   └── Providers
│   │   │   │   │   │       └── LocationsServiceProvider.php
│   │   │   │   │   └── routes.php
│   │   │   │   ├── Movements
│   │   │   │   │   ├── Application
│   │   │   │   │   │   ├── Actions
│   │   │   │   │   │   │   ├── GetMovementByIdAction.php
│   │   │   │   │   │   │   ├── GetMovementsAction.php
│   │   │   │   │   │   │   └── RegisterMovementAction.php
│   │   │   │   │   │   └── DTOs
│   │   │   │   │   │       ├── MovementCreatedEventPayload.php
│   │   │   │   │   │       ├── RegisterMovementDTO.php
│   │   │   │   │   │       ├── StockAdjustedEventPayload.php
│   │   │   │   │   │       ├── StockMovedEventPayload.php
│   │   │   │   │   │       ├── StockPickedEventPayload.php
│   │   │   │   │   │       └── StockReceivedEventPayload.php
│   │   │   │   │   ├── Domain
│   │   │   │   │   │   ├── Entities
│   │   │   │   │   │   │   └── InventoryMovement.php
│   │   │   │   │   │   ├── Enums
│   │   │   │   │   │   │   ├── AdjustmentReason.php
│   │   │   │   │   │   │   └── MovementType.php
│   │   │   │   │   │   ├── Exceptions
│   │   │   │   │   │   │   └── InvalidMovementType.php
│   │   │   │   │   │   ├── Repositories
│   │   │   │   │   │   │   └── MovementRepository.php
│   │   │   │   │   │   └── Services
│   │   │   │   │   │       └── MovementValidator.php
│   │   │   │   │   ├── Infrastructure
│   │   │   │   │   │   ├── Http
│   │   │   │   │   │   │   ├── Controllers
│   │   │   │   │   │   │   │   └── MovementController.php
│   │   │   │   │   │   │   ├── Requests
│   │   │   │   │   │   │   │   └── RegisterMovementRequest.php
│   │   │   │   │   │   │   └── Resources
│   │   │   │   │   │   │       └── MovementResource.php
│   │   │   │   │   │   ├── Persistence
│   │   │   │   │   │   │   ├── Eloquent
│   │   │   │   │   │   │   │   └── InventoryMovementModel.php
│   │   │   │   │   │   │   └── Repositories
│   │   │   │   │   │   │       └── EloquentMovementRepository.php
│   │   │   │   │   │   └── Providers
│   │   │   │   │   │       └── MovementServiceProvider.php
│   │   │   │   │   └── routes.php
│   │   │   │   └── Product
│   │   │   │       ├── Application
│   │   │   │       │   ├── Actions
│   │   │   │       │   │   ├── CreateProductAction.php
│   │   │   │       │   │   ├── GetProductByIdAction.php
│   │   │   │       │   │   └── ListProductsAction.php
│   │   │   │       │   └── DTOs
│   │   │   │       │       ├── CreateProductPayload.php
│   │   │   │       │       ├── ProductCreatedEventPayload.php
│   │   │   │       │       └── ProductView.php
│   │   │   │       ├── Domain
│   │   │   │       │   ├── Entities
│   │   │   │       │   │   └── Product.php
│   │   │   │       │   ├── Enums
│   │   │   │       │   │   └── UnitOfMeasure.php
│   │   │   │       │   ├── Events
│   │   │   │       │   │   └── ProductCreated.php
│   │   │   │       │   ├── Exceptions
│   │   │   │       │   │   ├── DuplicateSku.php
│   │   │   │       │   │   ├── InvalidUnitOfMeasure.php
│   │   │   │       │   │   └── ProductNotFound.php
│   │   │   │       │   ├── Repositories
│   │   │   │       │   │   └── ProductRepository.php
│   │   │   │       │   └── ValueObjects
│   │   │   │       │       └── ProductId.php
│   │   │   │       ├── Infrastructure
│   │   │   │       │   ├── Http
│   │   │   │       │   │   ├── Controllers
│   │   │   │       │   │   │   └── ProductController.php
│   │   │   │       │   │   ├── Requests
│   │   │   │       │   │   │   └── StoreProductRequest.php
│   │   │   │       │   │   └── Resources
│   │   │   │       │   │       └── ProductResource.php
│   │   │   │       │   ├── Persistence
│   │   │   │       │   │   └── Eloquent
│   │   │   │       │   │       ├── EloquentProductRepository.php
│   │   │   │       │   │       └── ProductModel.php
│   │   │   │       │   └── Providers
│   │   │   │       │       └── ProductServiceProvider.php
│   │   │   │       └── routes.php
│   │   │   └── Providers
│   │   │       └── AppServiceProvider.php
│   │   ├── artisan
│   │   ├── bootstrap
│   │   │   ├── app.php
│   │   │   ├── cache
│   │   │   │   ├── .gitignore
│   │   │   │   ├── packages.php
│   │   │   │   └── services.php
│   │   │   └── providers.php
│   │   ├── composer.json
│   │   ├── composer.lock
│   │   ├── config
│   │   │   ├── app.php
│   │   │   ├── auth.php
│   │   │   ├── broadcasting.php
│   │   │   ├── cache.php
│   │   │   ├── database.php
│   │   │   ├── filesystems.php
│   │   │   ├── logging.php
│   │   │   ├── mail.php
│   │   │   ├── queue.php
│   │   │   ├── reverb.php
│   │   │   ├── services.php
│   │   │   └── session.php
│   │   ├── database
│   │   │   ├── .gitignore
│   │   │   ├── database.sqlite
│   │   │   ├── factories
│   │   │   │   └── UserFactory.php
│   │   │   ├── migrations
│   │   │   │   ├── 0001_01_01_000000_create_users_table.php
│   │   │   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   │   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   │   │   ├── 2026_03_28_063718_create_products_table.php
│   │   │   │   ├── 2026_03_28_071730_create_locations_table.php
│   │   │   │   ├── 2026_03_29_045200_create_stock_items_table.php
│   │   │   │   ├── 2026_03_29_054218_create_event_outbox_table.php
│   │   │   │   ├── 2026_03_29_054221_create_inventory_movements_table.php
│   │   │   │   ├── 2026_03_29_064500_create_inventory_incidents_table.php
│   │   │   │   ├── 2026_03_29_132300_create_audit_logs_table.php
│   │   │   │   ├── 2026_03_30_230300_add_dispatched_at_to_event_outbox_table.php
│   │   │   │   └── 2026_04_02_030000_create_decision_traces_table.php
│   │   │   └── seeders
│   │   │       └── DatabaseSeeder.php
│   │   ├── package.json
│   │   ├── phpunit.xml
│   │   ├── public
│   │   │   ├── .htaccess
│   │   │   ├── favicon.ico
│   │   │   ├── hot
│   │   │   ├── index.php
│   │   │   └── robots.txt
│   │   ├── resources
│   │   │   ├── css
│   │   │   │   └── app.css
│   │   │   ├── js
│   │   │   │   ├── app.js
│   │   │   │   ├── bootstrap.js
│   │   │   │   └── echo.js
│   │   │   └── views
│   │   │       └── welcome.blade.php
│   │   ├── routes
│   │   │   ├── api.php
│   │   │   ├── channels.php
│   │   │   ├── console.php
│   │   │   └── web.php
│   │   ├── storage
│   │   │   ├── app
│   │   │   │   ├── .gitignore
│   │   │   │   ├── private
│   │   │   │   │   └── .gitignore
│   │   │   │   └── public
│   │   │   │       └── .gitignore
│   │   │   ├── framework
│   │   │   │   ├── .gitignore
│   │   │   │   ├── cache
│   │   │   │   │   ├── .gitignore
│   │   │   │   │   └── data
│   │   │   │   │       └── .gitignore
│   │   │   │   ├── sessions
│   │   │   │   │   └── .gitignore
│   │   │   │   ├── testing
│   │   │   │   │   └── .gitignore
│   │   │   │   └── views
│   │   │   │       ├── .gitignore
│   │   │   │       ├── 06babb44eb8699bbe0a652fd146e313e.php
│   │   │   │       ├── 79f8c42256a549dfbd105899b02dc780.php
│   │   │   │       └── ff14b00f364221c27c0979f25aa91b46.php
│   │   │   ├── logs
│   │   │   │   ├── .gitignore
│   │   │   │   └── laravel.log
│   │   │   └── pail
│   │   │       └── .gitignore
│   │   ├── tests
│   │   │   ├── Feature
│   │   │   │   ├── Audit
│   │   │   │   │   └── AuditLoggerTest.php
│   │   │   │   ├── Events
│   │   │   │   │   └── CrossSurfaceEventContractTest.php
│   │   │   │   ├── ExampleTest.php
│   │   │   │   ├── Incidents
│   │   │   │   │   ├── ReportIncidentTest.php
│   │   │   │   │   └── UpdateIncidentStatusTest.php
│   │   │   │   ├── Intelligence
│   │   │   │   │   └── DecisionTraceIntegrationTest.php
│   │   │   │   ├── Movements
│   │   │   │   │   └── RegisterMovementTest.php
│   │   │   │   ├── Outbox
│   │   │   │   │   ├── OutboxDispatcherTest.php
│   │   │   │   │   └── OutboxIntegrityTest.php
│   │   │   │   └── Validation
│   │   │   │       ├── ApiContractTest.php
│   │   │   │       ├── BlockedLocationTest.php
│   │   │   │       ├── ConcurrencyTest.php
│   │   │   │       ├── IdempotencyTest.php
│   │   │   │       ├── InboundFlowTest.php
│   │   │   │       ├── IncidentLifecycleTest.php
│   │   │   │       ├── PickingFlowTest.php
│   │   │   │       └── RelocationFlowTest.php
│   │   │   ├── TestCase.php
│   │   │   └── Unit
│   │   │       ├── ExampleTest.php
│   │   │       └── Intelligence
│   │   │           └── Agents
│   │   │               └── InventoryAnomalyAgentTest.php
│   │   └── vite.config.js
│   ├── field-agent-mobile
│   │   ├── index.html
│   │   ├── package.json
│   │   ├── src
│   │   │   ├── App.vue
│   │   │   ├── domains
│   │   │   │   ├── home
│   │   │   │   │   └── views
│   │   │   │   │       └── SyncQueueView.vue
│   │   │   │   ├── incidents
│   │   │   │   │   └── views
│   │   │   │   │       └── ReportIncident.vue
│   │   │   │   ├── inventory
│   │   │   │   │   └── views
│   │   │   │   │       └── ProductLookup.vue
│   │   │   │   └── movements
│   │   │   │       └── views
│   │   │   │           └── ExecuteMovement.vue
│   │   │   ├── main.ts
│   │   │   ├── offline
│   │   │   │   └── SyncQueue.ts
│   │   │   ├── pages
│   │   │   │   └── FieldHome.vue
│   │   │   ├── quasar-variables.sass
│   │   │   ├── router
│   │   │   │   └── index.ts
│   │   │   ├── services
│   │   │   │   └── api.ts
│   │   │   ├── stores
│   │   │   │   ├── useIncidentsStore.ts
│   │   │   │   ├── useInventoryStore.ts
│   │   │   │   ├── useLocationStore.ts
│   │   │   │   └── useMovementsStore.ts
│   │   │   └── types
│   │   │       └── domain.ts
│   │   ├── tsconfig.json
│   │   ├── tsconfig.tsbuildinfo
│   │   └── vite.config.ts
│   ├── orchestrator-twin
│   │   ├── .editorconfig
│   │   ├── .env
│   │   ├── .gitattributes
│   │   ├── .gitignore
│   │   ├── .oxlintrc.json
│   │   ├── .prettierrc.json
│   │   ├── .vscode
│   │   │   ├── extensions.json
│   │   │   └── settings.json
│   │   ├── README.md
│   │   ├── dist
│   │   │   ├── assets
│   │   │   │   ├── index-C_wTtdP6.css
│   │   │   │   └── index-DiVYceZB.js
│   │   │   ├── favicon.ico
│   │   │   └── index.html
│   │   ├── env.d.ts
│   │   ├── eslint.config.ts
│   │   ├── index.html
│   │   ├── package.json
│   │   ├── pnpm-lock.yaml
│   │   ├── pnpm-workspace.yaml
│   │   ├── public
│   │   │   └── favicon.ico
│   │   ├── src
│   │   │   ├── App.vue
│   │   │   ├── assets
│   │   │   │   ├── base.css
│   │   │   │   ├── logo.svg
│   │   │   │   └── main.css
│   │   │   ├── components
│   │   │   │   ├── layout
│   │   │   │   │   ├── BinCell.vue
│   │   │   │   │   ├── RackView.vue
│   │   │   │   │   └── ZoneView.vue
│   │   │   │   └── panels
│   │   │   │       ├── RecommendationsPanel.vue
│   │   │   │       └── SimulationPanel.vue
│   │   │   ├── domains
│   │   │   │   ├── events
│   │   │   │   │   ├── components
│   │   │   │   │   │   └── EventLogDebugger.vue
│   │   │   │   │   ├── services
│   │   │   │   │   │   ├── EventInterpreter.ts
│   │   │   │   │   │   └── __tests__
│   │   │   │   │   │       └── EventInterpreter.contract.test.ts
│   │   │   │   │   └── stores
│   │   │   │   │       ├── useEventIngestionStore.ts
│   │   │   │   │       └── useEventStateStore.ts
│   │   │   │   ├── heatmap
│   │   │   │   │   ├── index.ts
│   │   │   │   │   ├── service.ts
│   │   │   │   │   └── types.ts
│   │   │   │   ├── incidents
│   │   │   │   │   ├── index.ts
│   │   │   │   │   ├── mapper.ts
│   │   │   │   │   ├── service.ts
│   │   │   │   │   └── types.ts
│   │   │   │   ├── intelligence
│   │   │   │   │   ├── components
│   │   │   │   │   │   └── DecisionTracePanel.vue
│   │   │   │   │   └── stores
│   │   │   │   │       └── useDecisionTraceStore.ts
│   │   │   │   ├── layout
│   │   │   │   │   ├── components
│   │   │   │   │   │   └── WarehouseGrid.vue
│   │   │   │   │   ├── composables
│   │   │   │   │   │   └── useWarehouseGrid.ts
│   │   │   │   │   ├── index.ts
│   │   │   │   │   ├── mapper.ts
│   │   │   │   │   ├── service.ts
│   │   │   │   │   └── types.ts
│   │   │   │   ├── occupancy
│   │   │   │   │   ├── index.ts
│   │   │   │   │   ├── mapper.ts
│   │   │   │   │   ├── service.ts
│   │   │   │   │   └── types.ts
│   │   │   │   ├── recommendations
│   │   │   │   │   ├── index.ts
│   │   │   │   │   ├── service.ts
│   │   │   │   │   └── types.ts
│   │   │   │   ├── shared
│   │   │   │   │   ├── api.ts
│   │   │   │   │   ├── binState.ts
│   │   │   │   │   ├── safeRecord.ts
│   │   │   │   │   └── services
│   │   │   │   │       └── echo.ts
│   │   │   │   └── simulation
│   │   │   │       ├── index.ts
│   │   │   │       ├── service.ts
│   │   │   │       └── types.ts
│   │   │   ├── env.d.ts
│   │   │   ├── main.ts
│   │   │   └── stores
│   │   │       └── counter.ts
│   │   ├── tsconfig.app.json
│   │   ├── tsconfig.json
│   │   ├── tsconfig.node.json
│   │   ├── tsconfig.vitest.json
│   │   ├── vite.config.ts
│   │   └── vitest.config.ts
│   └── vapor-monitor
│       ├── .editorconfig
│       ├── .env
│       ├── .gitattributes
│       ├── .gitignore
│       ├── .oxlintrc.json
│       ├── .prettierrc.json
│       ├── .vscode
│       │   ├── extensions.json
│       │   └── settings.json
│       ├── README.md
│       ├── dist
│       │   ├── assets
│       │   │   ├── index-BUxsw3Ct.css
│       │   │   └── index-DBKz7Por.js
│       │   ├── favicon.ico
│       │   └── index.html
│       ├── env.d.ts
│       ├── eslint.config.ts
│       ├── index.html
│       ├── package.json
│       ├── pnpm-lock.yaml
│       ├── pnpm-workspace.yaml
│       ├── public
│       │   └── favicon.ico
│       ├── src
│       │   ├── App.vue
│       │   ├── assets
│       │   │   ├── base.css
│       │   │   ├── logo.svg
│       │   │   └── main.css
│       │   ├── domains
│       │   │   ├── events
│       │   │   │   ├── components
│       │   │   │   │   └── EventLogDebugger.vue
│       │   │   │   ├── services
│       │   │   │   │   ├── EventInterpreter.ts
│       │   │   │   │   └── __tests__
│       │   │   │   │       └── EventInterpreter.contract.test.ts
│       │   │   │   └── stores
│       │   │   │       ├── useEventIngestionStore.ts
│       │   │   │       └── useEventStateStore.ts
│       │   │   ├── incidents
│       │   │   │   ├── components
│       │   │   │   │   └── IncidentFeed.vue
│       │   │   │   └── views
│       │   │   │       └── IncidentView.vue
│       │   │   ├── intelligence
│       │   │   │   ├── components
│       │   │   │   │   └── DecisionTraceFeed.vue
│       │   │   │   └── stores
│       │   │   │       └── useDecisionTraceStore.ts
│       │   │   ├── inventory
│       │   │   │   └── views
│       │   │   │       └── InventoryView.vue
│       │   │   ├── locations
│       │   │   │   └── components
│       │   │   │       └── ZoneOccupancy.vue
│       │   │   ├── monitoring
│       │   │   │   └── stores
│       │   │   │       └── useMonitoringStore.ts
│       │   │   ├── movements
│       │   │   │   └── components
│       │   │   │       ├── InboundFeed.vue
│       │   │   │       └── OutboundFeed.vue
│       │   │   └── shared
│       │   │       └── safeRecord.ts
│       │   ├── main.ts
│       │   ├── services
│       │   │   └── echo.ts
│       │   └── stores
│       │       └── counter.ts
│       ├── tsconfig.app.json
│       ├── tsconfig.json
│       ├── tsconfig.node.json
│       ├── tsconfig.vitest.json
│       ├── vite.config.ts
│       └── vitest.config.ts
├── docs
│   ├── API_SPEC.md
│   ├── ARCHITECTURE.md
│   ├── DATA_DICTIONARY.md
│   ├── DECISIONS
│   │   ├── ADR-001-monorepo.md
│   │   ├── ADR-002-modular-monolith.md
│   │   ├── ADR-003-offline-first.md
│   │   └── ADR-004-event-driven-ui.md
│   ├── DOMAIN_MODEL.md
│   ├── EVENT_CATALOG.md
│   ├── FLOWS
│   │   ├── README.md
│   │   ├── demo-scenario-flow.md
│   │   ├── inbound-flow.md
│   │   ├── incident-flow.md
│   │   ├── integration-flow.md
│   │   ├── picking-flow.md
│   │   └── replenishment-flow.md
│   ├── SECURITY_MODEL.md
│   └── VALIDATION
│       ├── phase-1-validation.md
│       └── phase-3-validation.md
├── package.json
├── packages
│   ├── eslint-config-custom
│   │   ├── index.js
│   │   └── package.json
│   ├── event-contracts
│   │   ├── package.json
│   │   └── src
│   │       ├── incident-events.ts
│   │       ├── index.ts
│   │       ├── inventory-events.ts
│   │       └── movement-events.ts
│   ├── shared-schemas
│   │   ├── package.json
│   │   └── src
│   │       ├── incidents.ts
│   │       ├── index.ts
│   │       ├── inventory.ts
│   │       └── movements.ts
│   ├── shared-types
│   │   ├── package.json
│   │   └── src
│   │       ├── events.ts
│   │       ├── incidents.ts
│   │       ├── index.ts
│   │       ├── inventory.ts
│   │       ├── locations.ts
│   │       └── movements.ts
│   └── ui-tokens
│       ├── package.json
│       └── src
│           ├── accessibility.ts
│           ├── colors.ts
│           └── spacing.ts
├── pnpm-lock.yaml
├── pnpm-workspace.yaml
└── scripts
    ├── bootstrap.sh
    ├── dev.sh
    ├── seed-demo.sh
    └── test-all.sh

285 directories, 493 files
```
