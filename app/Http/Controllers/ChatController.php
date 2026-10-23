<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatRequest;
use App\Services\PhoneStoreChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ChatController extends Controller
{
    public function __construct(private PhoneStoreChatService $chat) {}

    public function store(ChatRequest $request): JsonResponse
    {
        try {
            return response()->json($this->chat->answer($request->validated('message')));
        } catch (RuntimeException $exception) {
            $status = $exception->getMessage() === 'rate_limit' ? 429 : 502;
            Log::warning('AI chat provider unavailable', ['reason' => $exception->getMessage()]);
            return response()->json(['message' => $status === 429 ? 'Dịch vụ AI đang quá tải. Vui lòng thử lại sau.' : 'Hiện chưa thể kết nối dịch vụ AI. Vui lòng thử lại sau.', 'configured' => true], $status);
        }
    }
}
