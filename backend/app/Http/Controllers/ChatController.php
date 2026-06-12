<?php

namespace App\Http\Controllers;

use App\Events\MessageDeleted;
use App\Events\MessageSent;
use App\Events\MessageUpdated;
use App\Models\ChatMessage;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ChatController extends Controller
{
    /**
     * Phục vụ file ảnh đính kèm chat (public, tên file dạng hash)
     */
    public function serveAttachment(string $filename): Response
    {
        $safeName = basename($filename);
        $path = 'uploads/chat/' . $safeName;

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $downloadName = $safeName;
        return Storage::disk('public')->response($path, $downloadName);
    }

    /**
     * Lấy danh sách cuộc trò chuyện (Cho Admin)
     */
    public function getConversations()
    {
        return Conversation::with(['user'])
            ->withCount([
                'messages as unread_count' => function ($query) {
                    $query->where('is_read', false)
                        ->where('sender_id', '!=', Auth::id());
                },
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Lấy lịch sử tin nhắn của một cuộc trò chuyện
     */
    public function getMessages($conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);
        
        // Kiểm tra quyền (Nếu là user thì phải là conversation của họ)
        if (Auth::user()->role !== 'admin' && $conversation->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->markConversationRead($conversationId);

        return ChatMessage::with('sender')
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Lấy cuộc trò chuyện của User hiện tại
     */
    public function getUserConversation()
    {
        $user = Auth::user();
        $conversation = Conversation::firstOrCreate(
            ['user_id' => $user->id]
        );

        // Lấy tin nhắn tách biệt để tránh vòng lặp JSON (Recursion)
        $messages = ChatMessage::with('sender')
            ->where('conversation_id', $conversation->id)
            ->orderBy('created_at', 'asc')
            ->take(50)
            ->get();

        $this->markConversationRead($conversation->id);

        return response()->json([
            'id' => $conversation->id,
            'messages' => $messages,
        ]);
    }

    /**
     * Gửi tin nhắn (hỗ trợ text và hình ảnh base64)
     */
    public function sendMessage(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'message' => 'nullable|string',
            'conversation_id' => 'nullable|integer',
            'attachments_base64' => 'nullable|array',
            'attachments_base64.*' => 'nullable|string',
            'attachment_names' => 'nullable|array',
            'attachment_names.*' => 'nullable|string|max:255',
        ]);

        $conversationId = $request->conversation_id;

        if (!$conversationId) {
            $conversation = Conversation::firstOrCreate(['user_id' => $user->id]);
            $conversationId = $conversation->id;
        } else {
            $conversation = Conversation::find($conversationId);
            if (!$conversation) {
                if ($user->role === 'admin') {
                    abort(404);
                }
                $conversation = Conversation::firstOrCreate(['user_id' => $user->id]);
                $conversationId = $conversation->id;
            }
        }

        if ($user->role !== 'admin' && $conversation->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $attachments = array_values(array_filter($request->input('attachments_base64', [])));
        $text = trim((string) ($request->message ?? ''));

        if ($text === '' && empty($attachments)) {
            return response()->json(['message' => 'Vui lòng nhập tin nhắn hoặc chọn tệp đính kèm.'], 422);
        }

        // Chỉ gửi text, không có ảnh
        if (empty($attachments)) {
            $message = ChatMessage::create([
                'conversation_id' => $conversationId,
                'sender_id' => $user->id,
                'message' => $text,
                'is_read' => false,
                'attachment_path' => null,
                'attachment_name' => null,
            ]);

            $conversation->update([
                'last_message' => $text,
                'last_message_at' => now(),
            ]);

            try {
                broadcast(new MessageSent($message))->toOthers();
            } catch (\Exception $e) {
                \Log::error('Lỗi broadcast tin nhắn: ' . $e->getMessage());
            }

            return response()->json([
                'status' => 'success',
                'message' => $message->load('sender'),
            ]);
        }

        $createdMessages = [];

        $attachmentNames = $request->input('attachment_names', []);

        foreach ($attachments as $index => $base64) {
            $originalName = $attachmentNames[$index] ?? null;
            $saved = $this->saveChatAttachment($base64, $originalName);
            $attachmentPath = $saved['path'] ?? null;
            $attachmentName = $saved['name'] ?? null;

            $message = ChatMessage::create([
                'conversation_id' => $conversationId,
                'sender_id' => $user->id,
                'message' => $index === 0 ? $text : '',
                'is_read' => false,
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
            ]);

            $message->load('sender');
            $createdMessages[] = $message;

            try {
                broadcast(new MessageSent($message))->toOthers();
            } catch (\Exception $e) {
                \Log::error('Lỗi broadcast tin nhắn: ' . $e->getMessage());
            }
        }

        $lastMessage = $text ?: $this->previewFromAttachmentNames($attachmentNames, count($attachments));

        $conversation->update([
            'last_message' => $lastMessage,
            'last_message_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $createdMessages[count($createdMessages) - 1],
            'messages' => $createdMessages,
        ]);
    }

    public function updateMessage(Request $request, int $id)
    {
        $message = ChatMessage::with('conversation')->findOrFail($id);
        $this->authorizeOwnMessage($message);

        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $message->update(['message' => $request->message]);
        $this->syncConversationLastMessage($message->conversation);

        try {
            broadcast(new MessageUpdated($message))->toOthers();
        } catch (\Exception $e) {
            \Log::error('Lỗi broadcast cập nhật tin nhắn: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => $message->fresh()->load('sender'),
        ]);
    }

    public function destroyMessage(int $id)
    {
        $message = ChatMessage::with('conversation')->findOrFail($id);
        $this->authorizeOwnMessage($message);

        $conversation = $message->conversation;
        $conversationId = $message->conversation_id;
        $messageId = $message->id;

        if ($message->attachment_path) {
            Storage::disk('public')->delete($message->attachment_path);
        }

        $message->delete();
        $this->syncConversationLastMessage($conversation);

        try {
            broadcast(new MessageDeleted($messageId, $conversationId))->toOthers();
        } catch (\Exception $e) {
            \Log::error('Lỗi broadcast xóa tin nhắn: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'id' => $messageId,
            'conversation_id' => $conversationId,
        ]);
    }

    public function destroyConversations(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:chat_conversations,id',
        ]);

        $ids = array_values(array_unique($request->input('ids', [])));

        $messages = ChatMessage::whereIn('conversation_id', $ids)
            ->whereNotNull('attachment_path')
            ->get(['attachment_path']);

        foreach ($messages as $message) {
            Storage::disk('public')->delete($message->attachment_path);
        }

        Conversation::whereIn('id', $ids)->delete();

        return response()->json([
            'status' => 'success',
            'deleted_ids' => $ids,
        ]);
    }

    private function authorizeOwnMessage(ChatMessage $message): void
    {
        $user = Auth::user();

        if ((int) $message->sender_id !== (int) $user->id) {
            abort(403, 'Chỉ được sửa/xóa tin nhắn của chính bạn');
        }

        $conversation = $message->conversation;
        if ($user->role !== 'admin' && (int) $conversation->user_id !== (int) $user->id) {
            abort(403);
        }
    }

    private function syncConversationLastMessage(Conversation $conversation): void
    {
        $latest = ChatMessage::where('conversation_id', $conversation->id)
            ->orderByDesc('created_at')
            ->first();

        if (!$latest) {
            $conversation->update([
                'last_message' => null,
                'last_message_at' => now(),
            ]);
            return;
        }

        $preview = $latest->message;
        if (!$preview && $latest->attachment_path) {
            $preview = $this->previewFromAttachmentNames(
                $latest->attachment_name ? [$latest->attachment_name] : [],
                1
            );
        }

        $conversation->update([
            'last_message' => $preview,
            'last_message_at' => $latest->created_at,
        ]);
    }

    private function markConversationRead(int $conversationId): void
    {
        ChatMessage::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    private function saveChatAttachment(?string $base64, ?string $originalName = null): ?array
    {
        if (!$base64 || !preg_match('/^data:([^;]+);base64,/', $base64, $m)) {
            return null;
        }

        $data = base64_decode(preg_replace('/^data:[^;]+;base64,/', '', $base64));
        if ($data === false) {
            return null;
        }

        $ext = $this->guessAttachmentExtension($m[1], $originalName);
        $path = 'uploads/chat/' . md5($data . ($originalName ?? '')) . '.' . $ext;

        if (!Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path, $data);
        }

        return [
            'path' => $path,
            'name' => $originalName ?: basename($path),
        ];
    }

    private function guessAttachmentExtension(string $mime, ?string $originalName): string
    {
        if ($originalName && preg_match('/\.([a-z0-9]{1,10})$/i', $originalName, $match)) {
            return strtolower($match[1]);
        }

        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'text/plain' => 'txt',
            'application/zip' => 'zip',
        ];

        if (isset($map[$mime])) {
            return $map[$mime];
        }

        if (str_starts_with($mime, 'image/')) {
            $part = substr($mime, 6);
            return $part === 'jpeg' ? 'jpg' : $part;
        }

        return 'bin';
    }

    private function previewFromAttachmentNames(array $names, int $count): string
    {
        if ($count === 1 && !empty($names[0])) {
            $name = $names[0];
            $isImage = (bool) preg_match('/\.(jpe?g|png|gif|webp|bmp|svg)$/i', $name);
            return ($isImage ? '[Hình ảnh] ' : '[Tệp] ') . $name;
        }

        return $count > 1 ? "[Đính kèm] ({$count})" : '[Đính kèm]';
    }
}
