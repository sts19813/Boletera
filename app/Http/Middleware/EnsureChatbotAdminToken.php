<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureChatbotAdminToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = (string) config('services.chatbot.admin_token');

        if ($expectedToken === '') {
            return response()->json([
                'message' => 'CHATBOT_ADMIN_TOKEN no configurado.',
            ], 503);
        }

        $providedToken = (string) (
            $request->header('X-Chatbot-Token')
            ?? $request->bearerToken()
            ?? ''
        );

        if ($providedToken === '' || !hash_equals($expectedToken, $providedToken)) {
            return response()->json([
                'message' => 'No autorizado.',
            ], 401);
        }

        return $next($request);
    }
}
