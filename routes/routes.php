<?php

use App\Plugins\Http\Exceptions as Exception;

// Authentication middleware.
$router->before('GET|POST|PUT|DELETE', '.*', function() {
    if (!isset($_SERVER['HTTP_API_AUTH']) || !($_SERVER['HTTP_API_AUTH'] == 'Excercise_completed_2022')) {
        (new Exception\Unauthorized(['error' => 'Unauthenticated.']))->send();
        exit();
    }
});

$router->get('', App\Controllers\IndexController::class . '@index');

$router->post('api/facility', App\Controllers\FacilityController::class . '@create');
$router->get('api/facilities', App\Controllers\FacilityController::class . '@read');
$router->get('api/facilities/{id}',  App\Controllers\FacilityController::class . '@read');
$router->put('api/facility/{id}', App\Controllers\FacilityController::class . '@update');
$router->delete('api/facility/{id}', App\Controllers\FacilityController::class . '@delete');

$router->get('api/search', App\Controllers\FacilityController::class . '@search');