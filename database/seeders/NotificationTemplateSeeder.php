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
            [
                'name' => 'linkedin_sync_complete',
                'type' => 'push',
                'title' => [
                    'en' => 'LinkedIn Sync Selesai! 🎉', // We can use english or mixed here
                    'id' => 'LinkedIn Sync Selesai! 🎉',
                ],
                'body' => [
                    'en' => 'Hi [name], your LinkedIn profile has been successfully retrieved and synchronized to ConnectX.',
                    'id' => 'Halo [nama], profil LinkedIn kamu sudah berhasil ditarik dan disinkronisasi ke ConnectX.',
                ],
            ],

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
            [
                'name' => 'premium_activated',
                'type' => 'push',
                'title' => [
                    'en' => '💎 ConnectX Pro Activated!',
                    'id' => '💎 ConnectX Pro Aktif!',
                ],
                'body' => [
                    'en' => 'Thank you for upgrading! Enjoy your unlimited swipes and advanced filters.',
                    'id' => 'Terima kasih sudah langganan! Nikmati unlimited swipes dan advanced filters sekarang.',
                ],
            ],
            [
                'name' => 'startup_application_received',
                'type' => 'push',
                'title' => [
                    'en' => '📄 New Application Received',
                    'id' => '📄 Lamaran Baru Masuk',
                ],
                'body' => [
                    'en' => '[applicant_name] has applied to join [startup_name].',
                    'id' => '[applicant_name] melamar untuk bergabung dengan [startup_name].',
                ],
            ],
            [
                'name' => 'startup_application_status',
                'type' => 'push',
                'title' => [
                    'en' => '📢 Application Status Updated',
                    'id' => '📢 Status Lamaran Diperbarui',
                ],
                'body' => [
                    'en' => 'Your application to [startup_name] is now [status].',
                    'id' => 'Lamaranmu ke [startup_name] sekarang berstatus [status].',
                ],
            ],
            [
                'name' => 'startup_invitation_received',
                'type' => 'push',
                'title' => [
                    'en' => '🤝 New Team Invitation',
                    'id' => '🤝 Undangan Tim Baru',
                ],
                'body' => [
                    'en' => 'You have been invited to join [startup_name] as a [role].',
                    'id' => 'Kamu diundang untuk bergabung dengan [startup_name] sebagai [role].',
                ],
            ]
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                ['name' => $template['name']],
                $template
            );
        }
    }
}
