<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogWebController extends Controller
{
    /**
     * Display the regulatory audit log trail (Accessible only by ADMIN and OPERATOR).
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Strict RBAC: Customers cannot view audit logs to preserve privacy
        if (! $user || $user->isCustomer()) {
            abort(403, 'Akses Ditolak: Jejak audit sistem dan aktivitas pengguna hanya dapat diakses oleh Administrator dan Operator.');
        }

        $query = AuditLog::with('user:id,name,email,role')->orderByDesc('created_at');

        // Filter by Action
        if ($request->filled('action')) {
            $query->where('action', $request->query('action'));
        }

        // Filter by User
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        // Filter by Search Keyword (IP, Entity Type, etc.)
        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('entity_type', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uQ) use ($search) {
                      $uQ->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        $actions = AuditLog::select('action')->distinct()->pluck('action');
        $users = User::orderBy('name')->get(['id', 'name', 'email', 'role']);

        return view('audit.index', [
            'logs' => $logs,
            'actions' => $actions,
            'users' => $users,
            'filters' => $request->only(['action', 'user_id', 'search']),
        ]);
    }
}
