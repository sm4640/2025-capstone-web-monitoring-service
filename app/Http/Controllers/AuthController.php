<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (session()->has('user_id')) {
            return redirect()->route('alerts.index');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'id'       => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::find($request->id);

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'login' => '아이디 또는 비밀번호가 올바르지 않습니다.',
            ])->withInput($request->only('id'));
        }

        session([
            'user_id'   => $user->id,
            'user_name' => $user->name,
        ]);

        return redirect()->route('alerts.index');
    }

    public function logout()
    {
        session()->flush();
        return redirect()->route('login.form');
    }
}
