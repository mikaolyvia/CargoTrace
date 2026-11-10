<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * List audit logs (accessible by ADMIN and OPERATOR).
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user:id,name,email,role');

        if ($request->filled('action')) {
            $query->where('action', $request->query('action'));
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', 'LIKE', '%' . addcslashes($request->query('entity_type'), '%_') . '%');
        }

        $logs = $query->orderByDesc('created_at')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ], 200);
    }
}
