# Gearman Saga

Usage:
```php
$client = new GearmanSaga\GearmanSaga($gearmanClient);
$client->addSaga(function() {
    yield $response = ['api_request', 'http://...' ];
    yield $model = ['mappings', $response ];
    yield $ok = ['add_to_database', $model ];
    if (!$ok) {
        yield ['rollback', $model];
    }
});

```
