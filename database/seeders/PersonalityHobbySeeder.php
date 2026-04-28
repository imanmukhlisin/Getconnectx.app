<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tag;

class PersonalityHobbySeeder extends Seeder
{
    public function run(): void
    {
        $hobbies = [
            'Gaming', 'Hiking', 'Photography', 'Cooking', 'Traveling', 
            'Reading', 'Fitness', 'Music', 'Coding', 'Art', 
            'Fashion', 'Movies', 'Sports', 'Yoga', 'Writing'
        ];

        foreach ($hobbies as $hobby) {
            Tag::firstOrCreate([
                'name' => $hobby,
                'type' => 'personality_hobbies'
            ]);
        }
        
        $personalities = [
            'Introvert', 'Extrovert', 'Analytical', 'Creative', 'Organized',
            'Risk Taker', 'Problem Solver', 'Team Player', 'Leader', 'Empathetic'
        ];

        foreach ($personalities as $personality) {
            Tag::firstOrCreate([
                'name' => $personality,
                'type' => 'personality'
            ]);
        }
    }
}
