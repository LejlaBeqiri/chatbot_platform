<?php

namespace App\Http\Controllers\User\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\KnowledgeBase;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        if ($this->authUser()->hasRole('admin')) {
            return $this->success(data: [
                'total_conversations'   => Conversation::count(),
                'total_active_agents'   => Chatbot::count(),
                'total_knowledge_bases' => KnowledgeBase::count(),
                'total_tenants'         => Tenant::count(),
            ]);
        }

        return $this->success(data: [
            'total_conversations'   => Conversation::where('tenant_id', $this->authUser()->tenant->id)->count(),
            'total_active_agents'   => Chatbot::where('tenant_id', $this->authUser()->tenant->id)->where('is_active', true)->count(),
            'total_knowledge_bases' => KnowledgeBase::where('tenant_id', $this->authUser()->tenant->id)->count(),
        ]);
    }

    public function conversationHistory(Request $request)
    {
        $query = Conversation::query();

        if (! $this->authUser()->hasRole('admin')) {
            $query->where('tenant_id', $this->authUser()->tenant->id);
        }

        $conversations = $query
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'date'  => $item->date,
                    'total' => $item->total,
                ];
            });

        return $this->success($conversations);
    }
}
