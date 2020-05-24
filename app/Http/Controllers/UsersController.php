<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Auth;

class UsersController extends Controller
{
    public function __construct()
    {
        // except 黑名单排除不需要登录的，其余都需要登录
        $this->middleware('auth', [
            'except' => ['show', 'create', 'store']
        ]);

        // only 白名单设定注册必须为 游客模式（非登录）
        $this->middleware('guest', [
            'only' => ['create', 'store']
        ]);
    }

    public function create()
    {
        return view('users.create');
    }

    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    public function store(Request $request)
    {
        $this->validate($request,[
            'name' => 'required|unique:users|max:50',
            'email' =>'required|email|unique:users|max:255',
            'password' => 'required|confirmed|min:6'
        ], [
            'name.required' => '名字都不写，想上天吗？'
        ]);
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);
        
        Auth::login($user);
        session()->flash('success', '欢迎，您将在这里开启一段新的旅程~');
        return redirect()->route('users.show', $user);
    }

    public function edit(User $user)
    {
        // authorize 方法接收两个参数，第一个为授权策略的名称，第二个为进行授权验证的数据。
        $this->authorize('update', $user);
        return view('users.edit', compact('user'));
    }

    public function update(User $user, Request $request)
    {
        $this->authorize('update', $user);
        $this->validate($request, [
            'name' => 'required|max:50',
            'password' => 'nullable|confirmed|min:6'
        ]);

        $data = [];
        $data['name'] = $request->name;
        if ($request->password) {
            $data['password'] = bcrypt($request->password);
        }
        $user->update($data);

        session()->flash('success', '个人资料更新成功！');

        return redirect()->route('users.show', $user);
    }
}
