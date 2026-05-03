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

            [
                'name' => 'new_message',
                'type' => 'push',
                'title' => [
                    'en' => '💬 New message from [sender_name]',
                    'id' => '💬 Pesan baru dari [sender_name]',
                ],
                'body' => [
                    'en' => '[message]',
                    'id' => '[message]',
                ],
            ],
            [
                'name' => 'new_match',
                'type' => 'push',
                'title' => [
                    'en' => "🎉 It's a Match!",
                    'id' => '🎉 Kamu Match!',
                ],
                'body' => [
                    'en' => 'You and [name] have connected. Start the conversation now!',
                    'id' => 'Kamu dan [name] sudah terhubung. Mulai obrolan sekarang!',
                ],
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                ['name' => $template['name']],
                $template
            );
        }
    }
}
