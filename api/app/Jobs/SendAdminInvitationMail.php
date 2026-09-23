<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

class SendAdminInvitationMail implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(public string $email, public string $inviteUrl) {}

    public function handle(): void
    {
        Mail::raw(
            "Anda telah diundang untuk mengakses BBH Farm. Buat kata sandi melalui tautan berikut:\n\n{$this->inviteUrl}\n\nTautan berlaku selama 60 menit.",
            fn (Message $message): Message => $message
                ->to($this->email)
                ->subject('Undangan Akses Admin BBH Farm')
        );
    }
}
