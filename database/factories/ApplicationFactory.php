<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApplicationFactory extends Factory
{
    private static array $companies = [
        'Google', 'Meta', 'Apple', 'Amazon', 'Microsoft', 'Netflix', 'Spotify',
        'Airbnb', 'Stripe', 'Shopify', 'Figma', 'Notion', 'Linear', 'Vercel',
        'Cloudflare', 'Atlassian', 'Salesforce', 'HubSpot', 'Twilio', 'Datadog',
        'GitHub', 'GitLab', 'HashiCorp', 'Elastic', 'MongoDB', 'Supabase',
        'PlanetScale', 'Fly.io', 'Railway', 'Render', 'DigitalOcean', 'Linode',
        'Grab', 'Gojek', 'Tokopedia', 'Bukalapak', 'Traveloka', 'OVO', 'Dana',
        'Xendit', 'Midtrans', 'Koinworks', 'Ajaib', 'Bibit', 'Stockbit',
    ];

    private static array $roles = [
        'Frontend Engineer', 'Backend Engineer', 'Full Stack Developer',
        'Software Engineer', 'Senior Software Engineer', 'Staff Engineer',
        'React Developer', 'Vue.js Developer', 'Node.js Developer',
        'Laravel Developer', 'PHP Developer', 'Python Developer',
        'DevOps Engineer', 'Site Reliability Engineer', 'Platform Engineer',
        'Mobile Developer', 'iOS Developer', 'Android Developer',
        'Data Engineer', 'ML Engineer', 'AI Engineer',
        'Engineering Manager', 'Tech Lead', 'Principal Engineer',
    ];

    private static array $locations = [
        'Remote', 'San Francisco, CA', 'New York, NY', 'Seattle, WA',
        'Austin, TX', 'London, UK', 'Berlin, Germany', 'Amsterdam, Netherlands',
        'Singapore', 'Manila, Philippines', 'Jakarta, Indonesia', 'Bangkok, Thailand',
        'Toronto, Canada', 'Sydney, Australia', 'Tokyo, Japan',
    ];

    public function definition(): array
    {
        $status      = fake()->randomElement(['wishlist', 'applied', 'phone_screen', 'interview', 'offer', 'rejected']);
        $appliedDate = in_array($status, ['applied', 'phone_screen', 'interview', 'offer', 'rejected'])
            ? fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d')
            : null;

        $salaryMin = fake()->boolean(60) ? fake()->randomElement([60000, 70000, 80000, 90000, 100000, 120000, 140000, 160000]) : null;
        $salaryMax = $salaryMin ? $salaryMin + fake()->randomElement([10000, 20000, 30000, 40000]) : null;

        return [
            'user_id'         => User::factory(),
            'company'         => fake()->randomElement(self::$companies),
            'role'            => fake()->randomElement(self::$roles),
            'job_url'         => fake()->boolean(70) ? fake()->url() : null,
            'status'          => $status,
            'priority'        => fake()->randomElement(['low', 'medium', 'high']),
            'applied_date'    => $appliedDate,
            'deadline'        => fake()->boolean(30) ? fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d') : null,
            'salary_min'      => $salaryMin,
            'salary_max'      => $salaryMax,
            'salary_currency' => $salaryMin ? fake()->randomElement(['USD', 'EUR', 'GBP', 'SGD', 'PHP']) : 'USD',
            'location'        => fake()->randomElement(self::$locations),
            'work_type'       => fake()->randomElement(['remote', 'onsite', 'hybrid']),
            'employment_type' => fake()->randomElement(['full_time', 'part_time', 'contract', 'freelance']),
            'notes'           => fake()->boolean(50) ? fake()->sentences(2, true) : null,
        ];
    }
}
