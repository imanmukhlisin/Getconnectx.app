<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationTemplate;

class NotificationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'account_created',
                'type' => 'push',
                'title' => [
                    'en' => '✅ Account Created',
                    'id' => '✅ Akun Dibuat',
                ],
                'body' => [
                    'en' => 'Hi, [name] Welcome to ConnectX, Start connecting with builders aligned with what you want to build 🚀',
                    'id' => 'Hi, [nama] Selamat datang di ConnectX, Mulai temukan koneksi dengan orang yang sevisi denganmu 🚀',
                ],
            ],
            // Tambahkan template lain di sini nanti
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                ['name' => $template['name']],
                $template
            );
        }
    }
}
