<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ChatbotFlowException;
use App\Http\Controllers\Controller;
use App\Services\ChatbotFlowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DiseaseChatbotController extends Controller
{
    public function __construct(
        protected ChatbotFlowService $flow
    ) {}

    public function store(Request $request): JsonResponse
    {
        if ($disabled = $this->featureDisabledResponse()) {
            return $disabled;
        }

        $validated = $request->validate([
            'hospital_id' => ['required', 'integer'],
            'person_id' => ['nullable', 'uuid'],
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json(['status' => 401, 'message' => 'Unauthenticated'], 401);
        }

        try {
            $envelope = $this->flow->startSession(
                $user,
                (int) $validated['hospital_id'],
                $validated['person_id'] ?? null
            );

            return response()->json($envelope, 200);
        } catch (ChatbotFlowException $e) {
            return response()->json($e->envelope !== [] ? $e->envelope : [
                'status' => $e->statusCode,
                'message' => $e->getMessage(),
            ], $e->statusCode);
        } catch (HttpException $e) {
            return response()->json([
                'status' => $e->getStatusCode(),
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (ValidationException $e) {
            throw $e;
        }
    }

    public function reply(Request $request, string $id): JsonResponse
    {
        if ($disabled = $this->featureDisabledResponse()) {
            return $disabled;
        }

        $validated = $request->validate([
            'input' => ['required', 'array'],
            'input.type' => ['required', 'string'],
            'input.value' => ['nullable'],
            'client_message_id' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json(['status' => 401, 'message' => 'Unauthenticated'], 401);
        }

        try {
            $session = $this->flow->findOwnedSession($user, $id);
            $envelope = $this->flow->reply(
                $session,
                $validated['input'],
                $validated['client_message_id'] ?? null
            );

            return response()->json($envelope, 200);
        } catch (ChatbotFlowException $e) {
            return response()->json($e->envelope !== [] ? $e->envelope : [
                'status' => $e->statusCode,
                'code' => $e->codeKey,
                'message' => $e->getMessage(),
            ], $e->statusCode);
        } catch (HttpException $e) {
            return response()->json([
                'status' => $e->getStatusCode(),
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        }
    }

    public function show(Request $request, string $id): JsonResponse
    {
        if ($disabled = $this->featureDisabledResponse()) {
            return $disabled;
        }

        $user = $request->user();
        if (! $user) {
            return response()->json(['status' => 401, 'message' => 'Unauthenticated'], 401);
        }

        try {
            $session = $this->flow->findOwnedSession($user, $id);
            $envelope = $this->flow->show($session);

            return response()->json($envelope, 200);
        } catch (ChatbotFlowException $e) {
            return response()->json($e->envelope !== [] ? $e->envelope : [
                'status' => $e->statusCode,
                'code' => $e->codeKey,
                'message' => $e->getMessage(),
            ], $e->statusCode);
        } catch (HttpException $e) {
            return response()->json([
                'status' => $e->getStatusCode(),
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        }
    }

    public function restart(Request $request, string $id): JsonResponse
    {
        if ($disabled = $this->featureDisabledResponse()) {
            return $disabled;
        }

        $user = $request->user();
        if (! $user) {
            return response()->json(['status' => 401, 'message' => 'Unauthenticated'], 401);
        }

        try {
            $session = $this->flow->findOwnedSession($user, $id);
            $envelope = $this->flow->restart($session);

            return response()->json($envelope, 200);
        } catch (ChatbotFlowException $e) {
            return response()->json($e->envelope !== [] ? $e->envelope : [
                'status' => $e->statusCode,
                'code' => $e->codeKey,
                'message' => $e->getMessage(),
            ], $e->statusCode);
        } catch (HttpException $e) {
            return response()->json([
                'status' => $e->getStatusCode(),
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        }
    }

    private function featureDisabledResponse(): ?JsonResponse
    {
        if (config('chatbot.disease_enabled')) {
            return null;
        }

        return response()->json([
            'status' => 503,
            'code' => 'feature_disabled',
            'message' => 'The disease checker is not enabled.',
        ], 503);
    }
}
