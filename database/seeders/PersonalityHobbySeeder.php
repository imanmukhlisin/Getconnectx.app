<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tag;

/**
 * PersonalityHobbySeeder
 *
 * Data diselaraskan dengan ConnectX-Wordings.csv (row 800-815).
 * Tipe tag = 'personality_hobbies', menggunakan `code` sebagai ID stable (ph_1, ph_2, ...)
 * agar konsisten dengan API Contract CON-59 dan ProfileResource.
 */
class PersonalityHobbySeeder extends Seeder
{
    public function run(): void
    {
        // ── Personality & Hobbies (dari CSV row 800–815, diurutkan A-Z) ──
        $tags = [
            ['code' => 'ph_1',  'name' => 'Avid Reader',       'name_id' => 'Pembaca Rajin'],
            ['code' => 'ph_2',  'name' => 'Coffee Enthusiast', 'name_id' => 'Pecinta Kopi'],
            ['code' => 'ph_3',  'name' => 'Competitive',       'name_id' => 'Kompetitif'],
            ['code' => 'ph_4',  'name' => 'Connector',         'name_id' => 'Penghubung'],
            ['code' => 'ph_5',  'name' => 'Creative',          'name_id' => 'Kreatif'],
            ['code' => 'ph_6',  'name' => 'Data-Driven',       'name_id' => 'Berbasis Data'],
            ['code' => 'ph_7',  'name' => 'Goal-Oriented',     'name_id' => 'Berorientasi Tujuan'],
            ['code' => 'ph_8',  'name' => 'Guitar Player',     'name_id' => 'Pemain Gitar'],
            ['code' => 'ph_9',  'name' => 'High-Energy',       'name_id' => 'Berenergi Tinggi'],
            ['code' => 'ph_10', 'name' => 'Hustler',           'name_id' => 'Hustler'],
            ['code' => 'ph_11', 'name' => 'Marathon Runner',   'name_id' => 'Pelari Maraton'],
            ['code' => 'ph_12', 'name' => 'Metrics-Obsessed',  'name_id' => 'Terobsesi Metrik'],
            ['code' => 'ph_13', 'name' => 'Mindful',           'name_id' => 'Penuh Kesadaran'],
            ['code' => 'ph_14', 'name' => 'Problem Solver',    'name_id' => 'Penyelesai Masalah'],
            ['code' => 'ph_15', 'name' => 'Systems Thinker',   'name_id' => 'Berpikir Sistematis'],
            ['code' => 'ph_16', 'name' => 'Visionary',         'name_id' => 'Visioner'],
        ];

        foreach ($tags as $tag) {
            Tag::updateOrCreate(
                ['code' => $tag['code'], 'type' => 'personality_hobbies'],
                ['name' => $tag['name']]
            );
        }
    }
}
