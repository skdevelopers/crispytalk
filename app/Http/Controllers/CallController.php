<?php

namespace App\Http\Controllers;

use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use App\Events\CallInitiated;
use App\Events\CallAccepted;
use App\Events\CallRejected;
use App\Events\CallEnded;

class CallController extends Controller
{
    /**
     * Initiate a new call.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function initiate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'callee_id' => 'required|exists:users,id',
            'call_type' => 'required|in:audio,video',
        ]);

        $data['caller_id'] = $request->user()->id;
        $data['status'] = 'pending';

        $call = Call::create($data);

        // Broadcast call initiated event
        event(new CallInitiated($call));

        return response()->json(['call' => $call, 'message' => 'Call initiated'], 201);
    }

    /**
     * Accept a call.
     *
     * Only the callee can accept the call.
     *
     * @param Request $request
     * @param int $id  Call ID
     * @return JsonResponse
     */
    public function accept(Request $request, int $id): JsonResponse
    {
        $call = Call::findOrFail($id);

        if ($call->callee_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $call->update([
            'status' => 'accepted',
            'started_at' => now(),
        ]);

        // Broadcast call accepted event
        event(new CallAccepted($call));

        return response()->json(['call' => $call, 'message' => 'Call accepted'], 200);
    }

    /**
     * Reject a call.
     *
     * Only the callee can reject the call.
     *
     * @param Request $request
     * @param int $id  Call ID
     * @return JsonResponse
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $call = Call::findOrFail($id);

        if ($call->callee_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $call->update(['status' => 'rejected']);

        // Broadcast call rejected event
        event(new CallRejected($call));

        return response()->json(['call' => $call, 'message' => 'Call rejected'], 200);
    }

    /**
     * End a call.
     *
     * Both caller and callee can end an active call.
     *
     * @param Request $request
     * @param int $id  Call ID
     * @return JsonResponse
     */
    public function end(Request $request, int $id): JsonResponse
    {
        $call = Call::findOrFail($id);

        if ($call->caller_id !== $request->user()->id && $call->callee_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $call->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);

        // Broadcast call ended event
        event(new CallEnded($call));

        return response()->json(['call' => $call, 'message' => 'Call ended'], 200);
    }

    /**
     * Get details of a specific call.
     *
     * @param int $id  Call ID
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $call = Call::with(['caller:id,name', 'callee:id,name'])->findOrFail($id);
        return response()->json($call);
    }

    /**
     * List call history for the authenticated user.
     *
     * Uses caching and pagination for performance.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Cache call history for 5 minutes
        $calls = Cache::remember('calls_' . $userId, now()->addMinutes(5), function () use ($userId) {
            return Call::where(function ($query) use ($userId) {
                $query->where('caller_id', $userId)
                    ->orWhere('callee_id', $userId);
            })->orderBy('created_at', 'desc')->paginate(10);
        });

        return response()->json($calls);
    }
}
