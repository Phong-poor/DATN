<?php

namespace App\Events;

use App\Models\DatHang;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    /**
     * Create a new event instance.
     */
    public function __construct(DatHang $order)
    {
        $this->order = $order;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $userId = $this->order->id_khachhang ?? $this->order->user_id;
        return [
            new PrivateChannel('user.' . $userId),
            new Channel('user-orders.' . $userId),
            new Channel('admin-orders'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.status.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id_dathang' => $this->order->id_dathang,
            'trangthai' => $this->order->trangthai,
            'trang_thai_thanh_toan' => $this->order->trang_thai_thanh_toan,
            'updated_at' => $this->order->updated_at?->toDateTimeString(),
            'status_history' => $this->order->du_lieu_thanh_toan['status_history'] ?? [],
            'message' => 'Trạng thái đơn hàng #' . $this->order->id_dathang . ' đã thay đổi thành ' . $this->order->trangthai
        ];
    }
}
