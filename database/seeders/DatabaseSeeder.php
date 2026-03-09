<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $guardName = config('auth.defaults.guard', 'web');
        Role::findOrCreate('admin', $guardName);
        Role::findOrCreate('vendor', $guardName);
        Role::findOrCreate('customer', $guardName);

        // User::factory(10)->create();

        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $testUser->syncRoles(['customer']);

        $adminEmail = strtolower(trim((string) env('ADMIN_EMAIL', '')));
        $adminUser = $adminEmail !== ''
            ? User::whereRaw('LOWER(email) = ?', [$adminEmail])->first()
            : null;

        if ($adminUser) {
            $adminUser->syncRoles(['admin']);
        }
    }
}
