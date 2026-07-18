<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Barangay Admin',
            'email' => 'admin@barangay.local',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '09171234567',
        ]);

        $resident = User::create([
            'name' => 'Juan Dela Cruz',
            'email' => 'resident@barangay.local',
            'password' => Hash::make('password'),
            'role' => 'resident',
            'phone' => '09181234567',
            'address' => '123 Main St, Barangay Sample',
        ]);

        Announcement::create([
            'user_id' => $admin->id,
            'title' => 'Welcome to Smart Barangay Dashboard',
            'content' => 'This is your central hub for barangay announcements, service requests, and community reports.',
            'category' => 'general',
            'is_pinned' => true,
        ]);

        Announcement::create([
            'user_id' => $admin->id,
            'title' => 'Free Medical Check-up',
            'content' => 'Free health screening at the barangay hall this Saturday, 8 AM to 12 PM.',
            'category' => 'health',
            'is_pinned' => false,
        ]);

        Event::create([
            'user_id' => $admin->id,
            'title' => 'Barangay Clean-up Drive',
            'description' => 'Join us for a community clean-up drive. Bring gloves and trash bags.',
            'location' => 'Barangay Hall',
            'start_date' => now()->addDays(7)->setTime(7, 0),
            'end_date' => now()->addDays(7)->setTime(11, 0),
        ]);
    }
}
