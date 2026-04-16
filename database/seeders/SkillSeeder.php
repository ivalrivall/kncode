<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            // Programming Languages
            'PHP',
            'JavaScript',
            'TypeScript',
            'Python',
            'Java',
            'Go',
            'Rust',
            'Ruby',
            'C#',
            'C++',
            'Swift',
            'Kotlin',
            'Dart',

            // Frontend
            'React',
            'Vue.js',
            'Angular',
            'Next.js',
            'Nuxt.js',
            'Svelte',
            'Laravel',
            'CodeIgniter',
            'Django',
            'Flask',
            'Spring Boot',
            'Express.js',
            'Node.js',

            // Mobile Development
            'React Native',
            'Flutter',
            'Android Development',
            'iOS Development',
            'Xamarin',

            // Database
            'MySQL',
            'PostgreSQL',
            'MongoDB',
            'Redis',
            'Elasticsearch',
            'Firebase',
            'SQLite',

            // DevOps & Cloud
            'Docker',
            'Kubernetes',
            'AWS',
            'Google Cloud',
            'Azure',
            'CI/CD',
            'Linux',
            'Nginx',
            'Apache',

            // Design
            'UI/UX Design',
            'Figma',
            'Adobe XD',
            'Sketch',
            'Photoshop',
            'Illustrator',
            'Graphic Design',

            // Other
            'GraphQL',
            'REST API',
            'Microservices',
            'Agile/Scrum',
            'Git',
            'Machine Learning',
            'Data Science',
            'Blockchain',
            'WordPress',
            'Shopify',
        ];

        foreach ($skills as $skillName) {
            Skill::firstOrCreate(['name' => $skillName]);
        }
    }
}
