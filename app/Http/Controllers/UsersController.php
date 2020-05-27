<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Auth;
use Mail;

class UsersController extends Controller
{
    public function __construct()
    {
        // except 黑名单排除不需要登录的，其余都需要登录
        $this->middleware('auth', [
            'except' => ['show', 'create', 'store', 'index', 'confirmEmail']
        ]);

        // only 白名单设定注册必须为 游客模式（非登录）
        $this->middleware('guest', [
            'only' => ['create', 'store']
        ]);
    }

    public function index()
    {
        $users = User::paginate(10); // 分页，每页10条
        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function show(User $user)
    {
        $statuses = $user->statuses()
                            ->orderBy('created_at', 'desc')
                            ->paginate(10);
        return view('users.show', compact('user', 'statuses'));
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
        
        // Auth::login($user); // 把之前用户注册成功之后进行的登录操作 换成 以下激活邮箱发送操作
        $this->sendEmailConfirmationTo($user);
        session()->flash('success', '验证邮件已发送到你的注册邮箱上，请注意查收。');
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

    public function destroy(User $user)
    {
        $this->authorize('destroy', $user);
        $user->delete();
        session()->flash('success', '成功删除用户！');
        return back();
    }

    // 发送激活
    protected function sendEmailConfirmationTo($user)
    {
        $view = 'emails.confirm'; // 邮件用的视图
        $data = compact('user');  // 视图要的数组数据
        $from = '123@qq.com';     // 发件人邮箱
        $name = 'andy';           // 发件人姓名
        $to = $user->email;       // 收件人邮箱
        $subject = '邮件标题：感谢注册哟！请完成激活哈！'; // 邮件标题

        // Mail::send($view, $data, function($message) use ($from, $name, $to, $subject) {
        //     $message->from($from, $name)->to($to)->subject($subject);
        // });
        
        // 因为在 .env 中配置了 MAIL_FROM_ADDRESS MAIL_FROM_NAME，因此不再需要使用 from 方法：
        Mail::send($view, $data, function($message) use ($to, $subject) {
            $message->to($to)->subject($subject);
        });
    }

    // 激活
    public function confirmEmail($token)
    {
        // firstOrFail 方法来取出第一个用户，在查询不到指定用户时将返回一个 404 响应
        $user = User::where('activation_token', $token)->firstOrFail();

        $user->activated = true;
        $user->activation_token = null;
        $user->save();

        Auth::login($user);
        session()->flash('success', '恭喜你，激活成功');
        return redirect()->route('users.show', $user);
    }

    // 博主列表
    public function followings(User $user)
    {
        $users = $user->followings()->paginate(10);
        $title = $user->name . '关注的人';
        return view('users.show_follow', compact('users', 'title'));
    }

    // 粉丝列表
    public function followers(User $user)
    {
        $users = $user->followers()->paginate(10);
        $title = $user->name . '的粉丝';
        return view('users.show_follow', compact('users', 'title'));
    }
}
