# Gearman Saga

#### Usage:
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


#### Forking into multiple sagas:

```php

// Create client.
$client = new GearmanSaga\GearmanSaga($gearmanClient);

// Add initial task to get a collection of items.
$client->addTask('get_list_of_somethings', [ 'page' => 0, 'per-page' => 100 ])->then(function(GearmanJob $job) {
    // Grab your data. 
    $data = json_decode($job->data());
    // Iterate and add a saga for each.
    foreach ($data->somethings as $something) {
    
        $client->addSaga(function() use ($something) {
            
            $model = yield ['mappings', $something ];
            
            $model = yield ['create_indexes', $something ];
            
            $ok = yield ['add_to_database', $model ];
            
            if (!$ok) {
                yield ['rollback', $model];
            }
            else {
                yield ['warm_cache', $model];
            }
        });
    }
    // Run them all in parallel.
    $client->run();
});
// Run the initial task.
$client->run();
```
