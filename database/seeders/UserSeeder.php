<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Freelance;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'admin@example.com')->doesntExist()) {
            $admin = User::create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
        }

        if (User::where('email', 'company@example.com')->doesntExist()) {
            $companyUser = User::create([
                'name' => 'PT Teknologi Maju',
                'email' => 'company@example.com',
                'password' => Hash::make('password'),
                'role' => 'company',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            Company::create([
                'user_id' => $companyUser->id,
                'name' => 'PT Teknologi Maju',
                'description' => 'Perusahaan teknologi terkemuka',
                'industry' => 'Technology',
                'website' => 'https://teknologimaju.com',
                'location' => 'Jakarta',
                'is_verified' => true,
            ]);
        }

        if (User::where('email', 'john@example.com')->doesntExist()) {
            $freelanceUser1 = User::create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('password'),
                'role' => 'freelance',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            Freelance::create([
                'user_id' => $freelanceUser1->id,
                'fullname' => 'John Doe',
                'headline' => 'Full Stack Developer',
                'bio' => 'Pengalaman 5 tahun dalam pengembangan web',
                'experience_years' => 5,
                'hourly_rate' => 50.00,
                'availability' => 'available',
                'location' => 'Bandung',
                'rating_avg' => 4.80,
                'total_reviews' => 20,
            ]);
        }

        if (User::where('email', 'jane@example.com')->doesntExist()) {
            $freelanceUser2 = User::create([
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'password' => Hash::make('password'),
                'role' => 'freelance',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            Freelance::create([
                'user_id' => $freelanceUser2->id,
                'fullname' => 'Jane Smith',
                'headline' => 'UI/UX Designer',
                'bio' => 'Desainer profesional dengan pengalaman 3 tahun',
                'experience_years' => 3,
                'hourly_rate' => 35.00,
                'availability' => 'available',
                'location' => 'Jakarta',
                'rating_avg' => 4.50,
                'total_reviews' => 10,
            ]);
        }

        User::factory()->count(5)->company()->create();
        User::factory()->count(10)->freelance()->create();
    }
}
