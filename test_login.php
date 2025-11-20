<?php
// Quick test script to check login response
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Simulate a login request
$request = Illuminate\Http\Request::create('/login', 'POST', [
    'password' => 'superpassword',
    '_token' => 'test'
]);

// Disable CSRF for this test
$app['Illuminate\Contracts\Debug\ExceptionHandler']->report(new Exception('Test'));

$response = $kernel->handle($request);

echo "Status Code: " . $response->getStatusCode() . "\n";
echo "Content-Type: " . $response->headers->get('Content-Type') . "\n";
echo "Body: " . $response->getContent() . "\n";
