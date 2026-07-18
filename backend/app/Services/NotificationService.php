<?php

namespace App\Services;

use App\Models\UserNotification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function notify(User $user, string $type, string $title, string $message, ?array $data = null): UserNotification
    {
        $notification = UserNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);

        try {
            Mail::raw($message, function ($mail) use ($user, $title) {
                $mail->to($user->email)->subject($title);
            });
        } catch (\Throwable) {
            // Email delivery is best-effort for MVP.
        }

        return $notification;
    }

    public function notifyAdmins(string $type, string $title, string $message, ?array $data = null): void
    {
        User::query()->where('role', 'admin')->each(function (User $admin) use ($type, $title, $message, $data) {
            $this->notify($admin, $type, $title, $message, $data);
        });
    }
}
