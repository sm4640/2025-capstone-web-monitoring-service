<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\AlertLog;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index()
    {
        $unseen = Alert::where('status', 'unseen')
                       ->orderByDesc('created_at')
                       ->get();

        $inProgress = Alert::where('status', 'in_progress')
                           ->orderByDesc('created_at')
                           ->get();

        $done = Alert::where('status', 'done')
                     ->orderByDesc('created_at')
                     ->get();

        return view('alerts.index', compact('unseen', 'inProgress', 'done'));
    }

    public function show(int $id)
    {
        $alert = Alert::with('logs')->findOrFail($id);

        $latestPlan = $alert->logs()
            ->whereNotNull('plan')
            ->orderByDesc('created_at')
            ->first();

        $logs = $alert->logs()->orderBy('created_at')->get();

        return view('alerts.show', compact('alert', 'latestPlan', 'logs'));
    }
}
