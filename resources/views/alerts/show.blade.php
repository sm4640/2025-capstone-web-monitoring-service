<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>알람 상세 페이지</title>
    <style>
        body { font-family:sans-serif; margin:40px; }
        h1 { text-align:center; margin-bottom:30px; }
        .box {
            width: fit-content;
            max-width: 100%;
            box-sizing: border-box;
            border:1px solid #2f3b46;
            background:#eee;
            padding:12px;
            margin:10px 0;
            overflow-x: auto;
        }
        .btn {
            display:inline-block;
            border:1px solid #2f3b46;
            padding:10px 30px;
            margin-right:15px;
            background:#ddd;
        }
        .feedback-input {
            width:280px; padding:8px; border:1px solid #2f3b46;
        }
        a.back { display:inline-block; margin-bottom:15px; }
        .error { color:red; margin-top:10px; }
        pre {
            white-space: pre-wrap;
            word-break: break-all;
            margin: 0;
        }
    </style>
</head>
<body>

<a class="back" href="{{ route('alerts.index') }}">&laquo; 알람 목록으로</a>

@if($alert->status === 'unseen')
    <h1>안 본 알람 상세 페이지</h1>
@elseif($alert->status === 'in_progress')
    <h1>수행중 알람 상세 페이지</h1>
@else
    <h1>수행된 알람 상세 페이지</h1>
@endif

<div class="box">
    <strong>알람 제목</strong><br>
    {{ $alert->name }}
</div>

<div class="box">
    <strong>알람 정보</strong><br><br>
    <div>심각도(severity): {{ $alert->severity }}</div>
    <div>발생 인스턴스(instance): {{ $alert->instance }}</div>
</div>

<div class="box" style="min-height:100px;">
    <strong>알람 상세 내용</strong><br><br>
    {!! nl2br(e($alert->summary)) !!}
</div>

@php
    $labels = $alert->labels ?? [];
    $annotations = $alert->annotations ?? [];
@endphp

@if(!empty($labels) || !empty($annotations))
    <div class="box">
        <strong>원본 Alert Labels / Annotations</strong><br><br>

        @if(!empty($labels))
            <div><strong>Labels</strong></div>
            <pre>{{ json_encode($labels, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            <br>
        @endif

        @if(!empty($annotations))
            <div><strong>Annotations</strong></div>
            <pre>{{ json_encode($annotations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        @endif
    </div>
@endif

@if($alert->status === 'unseen')
    {{-- 3페이지 --}}
    <div class="box" style="min-height:150px;">
        <strong>알람 해결 계획</strong><br><br>
        
        @if($latestPlan)
            @php
                $planJson = $latestPlan->plan_json; // AlertLog 액세서
            @endphp

            @if($planJson)
                {{-- JSON이면 예쁘게 출력 --}}
                <pre>{{ json_encode($planJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            @else
                {{-- 혹시 예전 데이터가 그냥 텍스트인 경우 대비 --}}
                {!! nl2br(e($latestPlan->plan)) !!}
            @endif
        @else
            (해결 계획이 아직 없습니다.)
        @endif
    </div>

    @php
        // 최신 계획 로그는 위에서 이미 보여줬으니, 히스토리에서 제외
        $historyLogs = $logs;
        if ($latestPlan) {
            $historyLogs = $historyLogs->where('id', '!=', $latestPlan->id);
        }

        // 거절 이력이 하나라도 있는지 확인 (feedback 존재 여부)
        $hasHistory = $historyLogs->whereNotNull('feedback')->count() > 0;
    @endphp

    @if($hasHistory)
        @php
            $planCount = 0;
            $feedbackCount = 0;
        @endphp

        @foreach($historyLogs as $log)
            @if($log->plan)
                <div class="box" style="min-height:150px;">
                    <strong>이전 알람 해결 계획{{ ++$planCount }}</strong><br><br>
                    @php
                        $planJson = $log->plan_json;
                    @endphp

                    @if($planJson)
                        {{-- JSON이면 예쁘게 출력 --}}
                        <pre>{{ json_encode($planJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    @else
                        {{-- 혹시 예전 데이터가 그냥 텍스트인 경우 대비 --}}
                        {!! nl2br(e($log->plan)) !!}
                    @endif
                </div>
            @endif

            @if($log->feedback)
                <div class="box">
                    <strong>{{ ++$feedbackCount }}번째 거절 피드백</strong><br><br>
                    {!! nl2br(e($log->feedback)) !!}
                </div>
            @endif
        @endforeach
    @endif

    <form method="post" action="{{ route('alerts.action', $alert->id) }}">
        @csrf
        <button type="submit" name="decision" value="approved" class="btn">수락</button>
        <button type="submit" name="decision" value="rejected" class="btn">거절</button>
        <input type="text" name="feedback" class="feedback-input"
               placeholder="거절 시 피드백 입력 필수">
        @error('feedback')
            <div class="error">{{ $message }}</div>
        @enderror
    </form>

@elseif($alert->status === 'in_progress')
    {{-- 4페이지 --}}
    <div class="box" style="min-height:150px;">
        <strong>알람 해결 계획</strong><br><br>
        @if($latestPlan)
            @php
                $planJson = $latestPlan->plan_json;
            @endphp

            @if($planJson)
                <pre>{{ json_encode($planJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            @else
                {!! nl2br(e($latestPlan->plan)) !!}
            @endif
        @else
            (해결 계획이 아직 없습니다.)
        @endif
    </div>

    <div class="box">
        <strong>상태</strong><br>
        해결 계획대로 수행 중
    </div>

@else
    {{-- 5페이지 --}}
    @php
        $planCount = 0;
        $feedbackCount = 0;
    @endphp

    @foreach($logs as $log)
        @if($log->plan)
            <div class="box" style="min-height:150px;">
                <strong>알람 해결 계획{{ ++$planCount }}</strong><br><br>
                @php
                    $planJson = $log->plan_json; // 모델 accessor에서 파싱된 값
                @endphp

                @if($planJson)
                    {{-- JSON이면 예쁘게 출력 --}}
                    <pre>{{ json_encode($planJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @else
                    {{-- 혹시 예전 데이터가 그냥 텍스트인 경우 대비 --}}
                    {!! nl2br(e($log->plan)) !!}
                @endif
            </div>
        @endif

        @if($log->feedback)
            <div class="box">
                <strong>{{ ++$feedbackCount }}번째 거절 피드백</strong><br><br>
                {!! nl2br(e($log->feedback)) !!}
            </div>
        @endif

        @if($log->result)
            @php
                $resultJson = $log->result_json;
            @endphp

            <div class="box">
                <strong>알람 해결 후 결과</strong><br><br>

                @if($resultJson)
                    {{-- JSON이면 예쁘게 출력 --}}
                    <pre>{{ json_encode($resultJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @else
                    {{-- 혹시 예전 데이터가 그냥 텍스트인 경우 대비 --}}
                    {!! nl2br(e($log->result)) !!}
                @endif
            </div>
        @endif
    @endforeach
@endif

</body>
</html>
