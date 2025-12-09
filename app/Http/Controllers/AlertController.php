<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\AlertLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AlertController extends Controller
{
    public function index()
    {
        $unseen = Alert::where('status', 'unseen')
            ->withMax('logs as last_activity_at', 'updated_at')
            ->orderByDesc('last_activity_at')
            ->orderByDesc('updated_at')
            ->get();

        $inProgress = Alert::where('status', 'in_progress')
            ->withMax('logs as last_activity_at', 'updated_at')
            ->orderByDesc('last_activity_at')
            ->orderByDesc('updated_at')
            ->get();

        $done = Alert::where('status', 'done')
            ->withMax('logs as last_activity_at', 'updated_at')
            ->orderByDesc('last_activity_at')
            ->orderByDesc('updated_at')
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

    public function action(Request $request, int $id)
    {
        $alert = Alert::findOrFail($id);

        $data = $request->validate([
            'decision' => 'required|in:approved,rejected',
            'feedback' => 'nullable|string',
        ]);
        
        if ($data['decision'] === 'rejected' && empty($data['feedback'])) {
            return back()->withErrors([
                'feedback' => '거절 시 피드백을 반드시 입력해야 합니다.',
            ]);
        }

        $userId = session('user_id') ?? 'unknown';

        $payload = null;

        try {
            DB::transaction(function () use ($alert, $data, $userId, &$payload) {
                $latestPlanLog = $alert->logs()
                    ->whereNotNull('plan')
                    ->orderByDesc('created_at')
                    ->first();

                if ($latestPlanLog) {
                    $latestPlanLog->is_approve = $data['decision'] === 'approved';

                    if ($data['decision'] === 'rejected') {
                        $latestPlanLog->feedback = $data['feedback'];
                    }

                    $latestPlanLog->save();
                }

                $planData = null;
                if ($latestPlanLog && !empty($latestPlanLog->plan)) {
                    $decoded = json_decode($latestPlanLog->plan, true);
                    // JSON이 정상일 때만 배열로
                    $planData = (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
                }

                $decisionUtcIso = $latestPlanLog
                    ? $latestPlanLog->updated_at->clone()->setTimezone('UTC')->toIso8601String()
                    : now('UTC')->toIso8601String();

                $alertinfo = [
                    'id'          => $alert->id,
                    'name'        => $alert->name,
                    'severity'    => $alert->severity,
                    'instance'    => $alert->instance,
                    'summary'     => $alert->summary,
                    'labels'      => $alert->labels ?? null,
                    'annotations' => $alert->annotations ?? null,
                ];

                if ($data['decision'] === 'approved') {
                    $alert->status = 'in_progress';
                    $alert->save();

                    $payload = [
                        'alert'       => $alertinfo,
                        'alertLogId'  => $latestPlanLog ? $latestPlanLog->id : null,
                        'decision'    => 'approved',
                        'approvedBy'  => $userId,
                        'feedback'    => $data['feedback'],
                        'approvedAt'  => $decisionUtcIso,
                        'plan'        => $planData,
                    ];
                } else {
                    $payload = [
                        'alert'      => $alertinfo,
                        'decision'   => 'rejected',
                        'rejectedBy' => $userId,
                        'feedback'   => $data['feedback'],
                        'rejectedAt' => $decisionUtcIso,
                        'plan'       => $planData,
                    ];
                }

                if (!empty($alert->callback_url)) {
                    Http::timeout(5)->post($alert->callback_url, $payload);
                }
            });

        } catch (\Throwable $e) {
            Log::error('Alert decision transaction failed', [
                'alert_id' => $alert->id,
                'url'      => $alert->callback_url ?? null,
                'payload'  => $payload,
                'error'    => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('webhook_error', '자동 조치 모듈로 알람 결과 전송에 실패하여 작업이 취소되었습니다. 다시 시도해 주세요.');
        }

        return redirect()
            ->route('alerts.index')
            ->with('status', '알람 처리 및 자동 조치 전송이 완료되었습니다.')
            ->with('refresh_once', true);
    }
}