<?php

namespace App\Notifications;

use App\Models\DocumentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentRequestStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public DocumentRequest $documentRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->documentRequest;

        return (new MailMessage)
            ->subject("Update on your document request {$request->tracking_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your request for \"{$request->documentType->name}\" is now: {$request->status->label()}.")
            ->when($request->status->value === 'rejected', fn ($mail) => $mail->line("Reason: {$request->rejection_reason}"))
            ->line("Tracking number: {$request->tracking_number}");
    }

    public function toArray(object $notifiable): array
    {
        $request = $this->documentRequest;

        return [
            'document_request_id' => $request->uuid,
            'tracking_number' => $request->tracking_number,
            'status' => $request->status->value,
            'message' => "Your document request {$request->tracking_number} is now {$request->status->label()}.",
        ];
    }
}
