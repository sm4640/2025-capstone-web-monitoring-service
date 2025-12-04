<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>알람 페이지</title>
    <style>
        body { font-family:sans-serif; margin:40px; }
        h1 { text-align:center; margin-bottom:40px; }
        h2 { margin-top:40px; }
        .top-bar { text-align:right; margin-bottom:10px; }
        .alert-list { margin-top:10px; }
        .alert-item {
            border:1px solid #2f3b46;
            padding:8px 16px;
            margin-bottom:8px;
            width:400px;
            background:#eee;
        }
        .alert-item a { text-decoration:none; color:#000; display:block; }
    </style>
</head>
<body>

<div class="top-bar">
    로그인 사용자: {{ session('user_name') ?? '알 수 없음' }}
    | <a href="{{ route('logout') }}">로그아웃</a>
</div>

<h1>알람 페이지</h1>

<section>
    <h2>안 본 알람 목록 조회</h2>
    <div class="alert-list">
        @forelse($unseen as $alert)
            <div class="alert-item">
                <a href="{{ route('alerts.show', $alert->id) }}">
                    [{{ $alert->id }}] {{ $alert->name }}
                    - {{ \Carbon\Carbon::parse($alert->last_activity_at ?? $alert->created_at)->diffForHumans() }}
                </a>
            </div>
        @empty
            <p>안 본 알람이 없습니다.</p>
        @endforelse
    </div>
</section>

<section>
    <h2>수행 중 알람 목록 조회</h2>
    <div class="alert-list">
        @forelse($inProgress as $alert)
            <div class="alert-item">
                <a href="{{ route('alerts.show', $alert->id) }}">
                    [{{ $alert->id }}] {{ $alert->name }}
                    - {{ \Carbon\Carbon::parse($alert->last_activity_at ?? $alert->created_at)->diffForHumans() }}
                </a>
            </div>
        @empty
            <p>수행 중인 알람이 없습니다.</p>
        @endforelse
    </div>
</section>

<section>
    <h2>수행된 알람 목록 조회</h2>
    <div class="alert-list">
        @forelse($done as $alert)
            <div class="alert-item">
                <a href="{{ route('alerts.show', $alert->id) }}">
                    [{{ $alert->id }}] {{ $alert->name }}
                    - {{ \Carbon\Carbon::parse($alert->last_activity_at ?? $alert->created_at)->diffForHumans() }}
                </a>
            </div>
        @empty
            <p>수행 완료된 알람이 없습니다.</p>
        @endforelse
    </div>
</section>

@if (session('webhook_error'))
    <script>
        alert(@json(session('webhook_error')));
    </script>
@endif

</body>
</html>

@if(session('refresh_once'))
    <script>
        // 세션 스토리지에 플래그를 남겨서 무한 새로고침 방지
        const KEY = 'alerts_index_refreshed_once';

        if (!sessionStorage.getItem(KEY)) {
            sessionStorage.setItem(KEY, '1');
            // 딱 한 번만 새로고침
            window.location.reload();
        } else {
            // 한 번 새로고침 했으면 플래그 제거(원하면)
            sessionStorage.removeItem(KEY);
        }
    </script>
@endif

@if (request()->routeIs('alerts.index'))
    <script>
        setInterval(function () {
            window.location.reload();
        }, 5000);
    </script>
@endif