<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

try {
    $user = User::updateOrCreate(
        ['email' => 'admin@hrms.com'],
        [
            'name' => 'Admin User',
            'password' => Hash::make('password123'),
        ]
    );
    
    echo "✅ SUCCESS!\n";
    echo "====================\n";
    echo "User Email: admin@hrms.com\n";
    echo "Password: password123\n";
    echo "====================\n";
    echo "You can now login!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

