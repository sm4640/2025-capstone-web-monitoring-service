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

        <button type="button" id="verify-plan-btn" class="btn">검증</button>
        <span id="verify-plan-result" style="margin-left:10px; font-weight:bold;"></span>

        @error('feedback')
            <div class="error">{{ $message }}</div>
        @enderror
    </form>

    <div style="margin-top:8px; font-size:12px; color:#555;">
    검증은 최대 10분 정도 소요될 수 있으며, 페이지를 새로고침하면 검증 결과가 사라집니다.
    </div>

    <button type="button" id="verify-plan-toggle" class="btn" style="display:none; margin-top:10px;">
        자세히
    </button>
    <div id="verify-plan-detail" class="box" style="display:none; max-width:600px; margin-top:8px;"></div>

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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn        = document.getElementById('verify-plan-btn');
    const resultSpan = document.getElementById('verify-plan-result');
    const toggleBtn  = document.getElementById('verify-plan-toggle');
    const detailBox  = document.getElementById('verify-plan-detail');

    if (!btn || !resultSpan) {
        return;
    }

    // HTML escape helper
    function escapeHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // 토글 버튼 클릭 시 상세 박스 열고 닫기
    if (toggleBtn && detailBox) {
        toggleBtn.addEventListener('click', function () {
            const visible = detailBox.style.display !== 'none';
            detailBox.style.display = visible ? 'none' : 'block';
            toggleBtn.textContent = visible ? '자세히' : '닫기';
        });
    }

    btn.addEventListener('click', async function () {
        const originalText = btn.textContent;
        btn.disabled = true;
        // 검증 중 표시 + 최대 10분 안내
        btn.textContent = '검증 중... (최대 10분 소요)';
        resultSpan.textContent = '';

        // 이전 상세 결과 숨기기
        if (toggleBtn && detailBox) {
            toggleBtn.style.display = 'none';
            detailBox.style.display = 'none';
            detailBox.innerHTML = '';
            toggleBtn.textContent = '자세히';
        }

        try {
            const res = await fetch('{{ route('api.alerts.verify-plan', $alert->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({})
            });

            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }

            const data = await res.json();

            // 한 줄 요약
            let prefix = '';
            if (data.verdict === 'good') {
                prefix = 'OK: ';
            } else if (data.verdict === 'bad') {
                prefix = 'NOT OK: ';
            } else {
                prefix = 'UNCERTAIN: ';
            }

            resultSpan.textContent = prefix + (data.short_comment || '');

            // 상세 정보 박스 내용 구성 (verdict, score, reason 등)
            if (toggleBtn && detailBox) {
                let html = '<strong>Verification Details</strong><br><br>';
                html += '<div><strong>Verdict:</strong> ' + (escapeHtml(data.verdict) || '-') + '</div>';

                if (typeof data.score === 'number') {
                    html += '<div><strong>Confidence score:</strong> ' + data.score.toFixed(2) + '</div>';
                }

                if (data.reason) {
                    html += '<div style="margin-top:8px;"><strong>Reason:</strong><br>' +
                        escapeHtml(data.reason).replace(/\n/g, '<br>') +
                        '</div>';
                }

                detailBox.innerHTML = html;
                toggleBtn.style.display = 'inline-block'; // 토글 버튼 보이게
            }
        } catch (e) {
            console.error(e);
            resultSpan.textContent = 'Verification failed. Please try again.';
        } finally {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    });
});
</script>


</body>
</html>
