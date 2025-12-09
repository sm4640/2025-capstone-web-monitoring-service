<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\AlertLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AlertApiController extends Controller
{
    public function storePlan(Request $request)
    {
        $data = $request->validate([
            'alert'                 => 'required|array',
            'alert.id'              => 'nullable|integer',
            'alert.name'            => 'required|string',
            'alert.severity'        => 'required|string',
            'alert.instance'        => 'required|string',
            'alert.summary'         => 'required|string',
            'alert.labels'          => 'nullable|array',
            'alert.annotations'     => 'nullable|array',

            'feedback'              => 'nullable|string',
            'plan'                  => 'required|array',
            'callbackUrl'           => 'required|url',
        ]);

        $alertInput   = $data['alert'];
        $labels       = $alertInput['labels'] ?? null;
        $annotations  = $alertInput['annotations'] ?? null;

        if (!empty($alertInput['id'])) {
            $alert = Alert::findOrFail($alertInput['id']);

            $alert->name         = $alertInput['name'];
            $alert->severity     = $alertInput['severity'];
            $alert->instance     = $alertInput['instance'];
            $alert->summary      = $alertInput['summary'];
            $alert->callback_url = $data['callbackUrl'];
            $alert->labels       = $labels;
            $alert->annotations  = $annotations;
            $alert->save();
        } else {
            $alert = Alert::create([
                'name'        => $alertInput['name'],
                'severity'    => $alertInput['severity'],
                'instance'    => $alertInput['instance'],
                'summary'     => $alertInput['summary'],
                'callback_url' => $data['callbackUrl'],
                'labels'       => $labels,
                'annotations'  => $annotations,
            ]);
        }

        $log = AlertLog::create([
            'alert_id' => $alert->id,
            'feedback' => $data['feedback'] ?? null,
            'plan'     => json_encode($data['plan'], JSON_UNESCAPED_UNICODE),
        ]);

        return response()->json([
            'message'  => 'Plan stored successfully.',
            'alert_id' => $alert->id,
            'log_id'   => $log->id,
            'status'   => $alert->status,
        ], 201);
    }

    public function storeResult(Request $request, int $alertId)
    {
        $alert = Alert::findOrFail($alertId);

        $data = $request->validate([
            'result' => 'required|array',
        ]);

        $log = $alert->logs()
            ->whereNotNull('plan')
            ->orderByDesc('created_at')
            ->first();

        $log->result = json_encode($data['result'], JSON_UNESCAPED_UNICODE);
        $log->save();

        $alert->status = 'done';
        $alert->save();

        return response()->json([
            'message'  => 'Result stored and alert marked as done.',
            'alert_id' => $alert->id,
            'log_id'   => $log->id,
            'status'   => $alert->status,
        ]);
    }

    public function verifyPlan(Request $request, Alert $alert)
    {
        $latestPlanLog = $alert->logs()
            ->whereNotNull('plan')
            ->orderByDesc('id')
            ->first();

        if (!$latestPlanLog) {
            return response()->json([
                'message' => 'No remediation plan to verify.',
            ], 400);
        }

        $planData = $latestPlanLog->plan_json ?? json_decode($latestPlanLog->plan, true);

        if (!is_array($planData)) {
            return response()->json([
                'message' => 'Plan is not valid JSON.',
            ], 400);
        }

        $payload = [
            'alert' => [
                'id'          => $alert->id,
                'name'        => $alert->name,
                'severity'    => $alert->severity,
                'instance'    => $alert->instance,
                'summary'     => $alert->summary,
                'labels'      => $alert->labels ?? [],
                'annotations' => $alert->annotations ?? [],
            ],
            'plan'  => $planData,
        ];

        if (empty($alert->callback_url)) {
            return response()->json([
                'message' => 'callback_url is not set for this alert.',
            ], 400);
        }

        $base = $alert->callback_url;
        $url  = $base . '/verify-plan';

        try {
            $res = Http::timeout(600)->post($url, $payload);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to call n8n verify workflow.',
                'error'   => $e->getMessage(),
            ], 500);
        }

        if (!$res->ok()) {
            return response()->json([
                'message' => 'n8n verify workflow returned an error.',
                'status'  => $res->status(),
                'body'    => $res->json(),
            ], 500);
        }

        // 여기서 **아무것도 저장 안 하고** n8n 결과만 그대로 프론트에 반환
        return response()->json($res->json());
    }

}
