# Gearman Saga

Usage:
```php
// Create client.
$client = new GearmanSaga\GearmanSaga($gearmanClient);

// Add saga (most likely in a loop)
$client->addSaga(function() {
    
    $response = yield ['api_request', 'http://...' ];
    
    $model = yield ['mappings', $response ];
    
    $ok = yield ['add_to_database', $model ];
    
    if (!$ok) {
        yield ['rollback', $model];
    }
});

// Run the gearman process.
$client->run();
```
