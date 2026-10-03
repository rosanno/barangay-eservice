<?php
// app/Events/ClearanceRequestSubmitted.php
namespace App\Events;

use App\Models\DocumentRequest;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClearanceRequestSubmitted implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public DocumentRequest $request)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin.dashboard')];
    }

    public function broadcastAs(): string
    {
        return 'clearance.submitted';
    }

    // Send only what the dashboard needs, not the whole model
    public function broadcastWith(): array
    {
        return [
            'id' => $this->request->id,
            'resident' => $this->request->resident->full_name,
            'type' => $this->request->type,
            'status' => $this->request->status,
            'submitted_at' => $this->request->created_at->toIso8601String(),
        ];
    }
}