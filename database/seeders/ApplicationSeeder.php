<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\InterviewRound;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    public function run(): void
    {
        // Use existing test user or create one
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => bcrypt('password')]
        );

        $this->command->info("Seeding 100 applications for {$user->email}...");

        // Create 100 applications belonging to this user
        $applications = Application::factory(100)
            ->create(['user_id' => $user->id]);

        // Add 1-4 interview rounds to applications with 'interview' status
        $interviewApps = $applications->where('status', 'interview');
        foreach ($interviewApps as $app) {
            InterviewRound::factory(rand(1, 4))->create([
                'application_id' => $app->id,
            ]);
        }

        $this->command->info("Done! Created {$applications->count()} applications, {$interviewApps->count()} with interview rounds.");
    }
}
