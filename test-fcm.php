<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::whereNotNull('fcm_token')->orderBy('updated_at', 'desc')->first();
if ($user) {
    echo "Sending to: " . $user->email . " (Token: " . substr($user->fcm_token, 0, 15) . "...)\n";
    try {
        $messaging = app(\Kreait\Firebase\Contract\Messaging::class);
        $notification = \Kreait\Firebase\Messaging\Notification::create(
            'LinkedIn Sync Selesai! 🎉', 
            'Halo ' . ($user->name ?? 'Builder') . ', profil LinkedIn kamu sudah berhasil ditarik dan disinkronisasi ke ConnectX.'
        );
        $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $user->fcm_token)
            ->withNotification($notification)
            ->withData(['type' => 'linkedin_sync_complete', 'timestamp' => now()->toDateTimeString()]);
        
        $messaging->send($message);
        echo "✅ Push notification berhasil dikirim!\n";
    } catch (\Exception $e) {
        echo "❌ Gagal: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Tidak ada User yang memiliki FCM token di database.\n";
}
