<?php
// app/Events/ClearanceRequestStatusChanged.php
namespace App\Events;

use App\Models\DocumentRequest;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClearanceRequestStatusChanged implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public DocumentRequest $request)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.dashboard'),
            new PrivateChannel('resident.' . $this->request->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'clearance.status-changed';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->request->id,
            'status' => $this->request->status,
        ];
    }
}