<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>로그인 페이지</title>
    <style>
        body { font-family:sans-serif; text-align:center; margin-top:80px; }
        .field-row { margin:10px 0; }
        label { display:inline-block; width:80px; text-align:right; margin-right:10px; }
        input[type=text], input[type=password] {
            width:240px; padding:8px; border:1px solid #2f3b46;
        }
        button { padding:8px 24px; margin-top:20px; }
        .error { color:red; margin-top:10px; }
    </style>
</head>
<body>
<h1>로그인 페이지</h1>

<form method="post" action="{{ route('login') }}">
    @csrf
    <div class="field-row">
        <label for="id">아이디</label>
        <input type="text" id="id" name="id" value="{{ old('id') }}" placeholder="아이디">
    </div>

    <div class="field-row">
        <label for="password">비밀번호</label>
        <input type="password" id="password" name="password" placeholder="비밀번호">
    </div>

    <button type="submit">로그인</button>

    @error('login')
        <div class="error">{{ $message }}</div>
    @enderror
</form>
</body>
</html>
