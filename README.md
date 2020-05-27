# Laravel 微博项目练习

## 3&4 静态页面布局
- 页面布局（layouts|default|_header|_footer）

- 页面样式（安装bootstrap）
  - composer require laravel/ui:^1.0 --dev
  - php artisan ui bootstrap
  - npm config set registry=https://registry.npm.taobao.org
  - yarn config set registry 'https://registry.npm.taobao.org'
  - yarn install --no-bin-links
  - yarn add cross-env@6.0.3 (7.0.2版本需要更高的node版本匹配，所以用下低版本的cross-env)
  - npm run dev

- 静态文件浏览器缓存问题
  - webpack.mis.js 中加入`.version()`，然后 default.blade.php 引用css和js时用`mix()`函数

## 5 用户模型
  - Move user models to models folder
    - 记得搜索更新 `App\User` > `App\Models\User`
    - 记得更改 app/Models/User.php 中的命名空间 `namespace App;` > `namespace App\Models;`
  - 执行迁移文件
    - 记得在 .env 文件中填写数据库账号密码
    - 执行迁移
      ```
      php artisan migrate
      ```
    - 用 tinker 测试数据库
      ```
      php artisan tinker
      ```
      ```
      use App\Models\User
      User::create([])
      User::find(1)
      User::findOrFail(5)
      $user = User::first()
      $user->name = 'Mokey'
      $user->save()
      $user->update(['name'=>'Andy'])
      ```

## 6 用户注册
  - 6.2 显示用户信息 show
    - 路由（resource）
      ```
      Route::get('signup', 'UsersController@create')->name('signup');
      Route::resource('users', 'UsersController'); // resource路由相当于以下7个路由
      // Route::get('/users', 'UsersController@index')->name('users.index');
      // Route::get('/users/create', 'UsersController@create')->name('users.create');
      // Route::get('/users/{user}', 'UsersController@show')->name('users.show');
      // Route::post('/users', 'UsersController@store')->name('users.store');
      // Route::get('/users/{user}/edit', 'UsersController@edit')->name('users.edit');
      // Route::patch('/users/{user}', 'UsersController@update')->name('users.update');
      // Route::delete('/users/{user}', 'UsersController@destroy')->name('users.destroy');
      ```
    - 在 app/Models/User.php 模型中定义一个「返回头像」的方法
      ```
      public function gravatar($size = '100')
      {
          $hash = md5(strtolower(trim($this->attributes['email'])));
          return "http://www.gravatar.com/avatar/$hash?s=$size";
      }
      ```
  - 6.3 注册表单
    - resources/views/users/create.blade.php
      ```
      <input type="text" name="name" class="form-control" value="{{ old('name') }}">
      ```
  - 6.4 用户数据验证
    - [数据验证](https://learnku.com/docs/laravel/6.x/validation/5144)
      ```
      $this->validate($request,[
            'name' => 'required|unique:users|max:50',
            'email' =>'required|email|unique:users|max:255',
            'password' => 'required|confirmed|min:6'
        ], [
            'name.required' => '名字都不写，想上天吗？'
        ]);
      ```
    - csrf 跨站请求伪造：在 resources/views/users/create.blade.php 表单中加入 {{ csrf_field() }}
      ```
      <form method="POST" action="{{ route('users.store') }}">
        {{ csrf_field() }}
        <!-- <input type="hidden" name="_token" value="fhcxqT67dNowMoWsAHGGPJOAWJn8x5R5ctSwZrAq"> -->
      </form>
  - 6.5 注册失败，显示中文错误消息
    - 错误消息：resources/views/shared/_errors.blade.php
      ```
      @if (count($errors) > 0)
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
      @endif
      ```
    - 添加语言包
      - 安装语言包
        ```
        composer require "overtrue/laravel-lang:~3.0"
        ```
      - 在 config/app.php 中将：
        ```
        Illuminate\Translation\TranslationServiceProvider::class,
        ```
        替换为：
        ```
        Overtrue\LaravelLang\TranslationServiceProvider::class,
        ```
      - 在 config/app.php 中，将项目语言设置为中文
        ```
        'locale' => 'zh-CN',
        ```
  - 6.6 注册成功
    - 保存用户并重定向
      - app/Http/Controllers/UsersController.php
        ```
        $user = User::create([
              'name' => $request->name,
              'email' => $request->email,
              'password' => bcrypt($request->password),
          ]);

          session()->flash('success', '欢迎，您将在这里开启一段新的旅程~');
          return redirect()->route('users.show', [$user]);
        ```
    - 全局消息提示
      - resources/views/shared/_messages.blade.php
        ```
        @foreach (['danger', 'warning', 'success', 'info'] as $msg)
          @if(session()->has($msg))
            <div class="flash-message">
              <p class="alert alert-{{ $msg }}">
                {{ session()->get($msg) }}
              </p>
            </div>
          @endif
        @endforeach
        ```
  - 6.7 用 帮助函数 配置多个数据库（PostgreSQL）
    - 新建帮助文件：app/helpers.php
      ```
      <?php

      function get_db_config()
      {
          if (getenv('IS_IN_HEROKU')) {
              $url = parse_url(getenv("DATABASE_URL"));

              return $db_config = [
                  'connection' => 'pgsql',
                  'host' => $url["host"],
                  'database'  => substr($url["path"], 1),
                  'username'  => $url["user"],
                  'password'  => $url["pass"],
              ];
          } else {
              return $db_config = [
                  'connection' => env('DB_CONNECTION', 'mysql'),
                  'host' => env('DB_HOST', 'localhost'),
                  'database'  => env('DB_DATABASE', 'forge'),
                  'username'  => env('DB_USERNAME', 'forge'),
                  'password'  => env('DB_PASSWORD', ''),
              ];
          }
      }
      ```
    - 自动加载帮助文件：在 composer.json 中的 autoload 中加入："files": ["app/helper.php"]
      ```      
      "autoload": {
          "psr-4": {
              "App\\": "app/"
          },
          "classmap": [
              "database/seeds",
              "database/factories"
          ],
          "files": [
              "app/helpers.php"
          ]
      }
      ```
    - 运行以下命令进行重新加载文件即可
      ```
      composer dump-autoload
      ```
    - 修改 config/database.php 配置，达到「根据不同的环境，使用不同的数据库」的目的

## 7 会话管理
  - 7.2 创建会话（登录）
    - 创建会话控制器 php artisan make:controller SessionsController
    - 会话路由
      ```
      Route::get('login', 'SessionsController@create')->name('login');
      Route::post('login', 'SessionsController@store')->name('login');
      Route::delete('logout', 'SessionsController@destroy')->name('logout');
      ```
    - 创建登录视图 resources/views/sessions/create.blade.php
    - 创建登录会话 app/Http/Controllers/SessionsController.php
      ```
      public function store(Request $request)
      {
        $credentials = $this->validate($request, [
            'email' => 'required|email|max:255',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            session()->flash('success', '欢迎回来！');
            return redirect()->route('users.show', [Auth::user()]);
        } else {
            session()->flash('danger', '很抱歉，您的邮箱和密码不匹配');
            // 使用 withInput() 后模板里 old('email') 将能获取到上一次用户提交的内容
            return redirect()->back()->withInput();
        }
      }
      ```

  - 7.3 用户登录
    - 下拉菜单 resources/views/layouts/_header.blade.php
      ```
      <ul class="navbar-nav justify-content-end">
      @if (Auth::check())
        <li class="nav-item"><a class="nav-link" href="#">用户列表</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            {{ Auth::user()->name }}
          </a>
          <div class="dropdown-menu" aria-labelledby="navbarDropdown">
            <a class="dropdown-item" href="{{ route('users.show', Auth::user()) }}">个人中心</a>
            <a class="dropdown-item" href="#">编辑资料</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" id="logout" href="#">
              <form action="{{ route('logout') }}" method="POST">
                {{ csrf_field() }}
                {{ method_field('DELETE') }}
                <button class="btn btn-block btn-danger" type="submit" name="button">退出</button>
              </form>
            </a>
          </div>
        </li>
      @else
        <li class="nav-item"><a class="nav-link" href="{{ route('help') }}">帮助</a></li>
        <li class="nav-item" ><a class="nav-link" href="{{ route('login') }}">登录</a></li>
      @endif
      ```
    - form表单DELETE请求
      ```
      注意：{{ method_field('DELETE') }} 等于 <input type="hidden" name="_method" value="DELETE"> 这是由于浏览器不支持发送 DELETE 请求，因此我们需要使用一个隐藏域来伪造 DELETE 请求。
      ```
    - 集成 Bootstrap 的 JavaScript 库，在 resources/views/layouts/default.blade.php 中：
      ```
        <script src="{{ mix('js/app.js') }}"></script>
      </body>
      ```
      只有加载了 Bootstrap 的 js 库，下拉菜单「点击」才能正常工作
    - 注册后自动登录，在 app/Http/Controllers/UsersController.php 中：
      ```
      Auth::login($user);
      session()->flash('success', '欢迎，您将在这里开启一段新的旅程~');
      return redirect()->route('users.show', [$user]);
      ```
  - 7.4 退出
    - app/Http/Controllers/SessionsController.php
      ```
      public function destroy()
      {
          Auth::logout();
          session()->flash('success', '您已成功退出！');
          return redirect('login');
      }
      ```
  - 7.5 记住我
    - 登录页面 resources/views/sessions/create.blade.php
      ```
      <div class="form-group">
        <div class="form-check">
          <input type="checkbox" class="form-check-input" name="remember" id="exampleCheck1">
          <label class="form-check-label" for="exampleCheck1">记住我</label>
        </div>
      </div>
      ```
    - app/Http/Controllers/SessionsController.php
      ```
      if (Auth::attempt($credentials, $request->has('remember'))) {
           session()->flash('success', '欢迎回来！');
           return redirect()->route('users.show', [Auth::user()]);
       } else {
           session()->flash('danger', '很抱歉，您的邮箱和密码不匹配');
           return redirect()->back()->withInput();
       }
       ```
      - Auth::attempt() 方法可接收两个参数，第一个参数为需要进行用户身份认证的数组，第二个参数为是否为用户开启『记住我』功能的布尔值
      - 在 Laravel 的默认配置中，如果用户登录后没有使用『记住我』功能，则登录状态默认只会被记住两个小时。如果使用了『记住我』功能，则登录状态会被延长到五年。

## 8 用户CRUD
### 8.2 更新用户 PATCH
  - 相关路由
    ```
    Route::get('/users/{user}/edit', 'UsersController@edit')->name('users.edit');
    Route::patch('/users/{user}', 'UsersController@update')->name('users.update');
    ```
  - 视图 resources/views/users/edit.blade.php
    ```
    <form method="POST" action="{{ route('users.update', $user->id )}}">
      {{ method_field('PATCH') }}
      {{ csrf_field() }}
      ...
      <button type="submit" class="btn btn-primary">更新</button>
    </form>
    ```
  - form表单PATCH更新请求
    ```
    {{ method_field('PATCH') }} 等于 <input type="hidden" name="_method" value="PATCH">，由于浏览器不支持发送 PATCH 动作，因此我们需要在表单中添加一个隐藏域来伪造 PATCH 请求。
    ```
  - 更新用户 app/Http/Controllers/UsersController.php
    ```
    public function update(User $user, Request $request)
    {
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
    ```
### 8.3 权限系统
  - 必须先登录：app/Http/Controllers/UsersController.php
    ```
    public function __construct()
    {
        // except 黑名单排除不需要登录的，其余都需要登录
        $this->middleware('auth', [
            'except' => ['show', 'create', 'store']
        ]);
    }
    ```
  - 必须为游客：「登录」、「注册」必须为访客。
    - 「登录」时必须是游客 app/Http/Controllers/SessionsController.php
      ```
      public function __construct()
      {
          $this->middleware('guest', [
              'only' => ['create', 'store']
          ]);
      }
      ```
    - 「注册」时必须是游客 app/Http/Controllers/UsersController.php
      ```
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
      ```
    - 记得修改“违反仅限游客操作”的默认跳转：app/Providers/RouteServiceProvider.php
      ```
      // public const HOME = '/home';
      public const HOME = '/'; // 默认跳转由 '/home' 改为 '/'
      ```
  - 授权策略：自己才能比较自己
    - 1.创建授权策略
      ```
      php artisan make:policy UserPolicy
      ```
      - 编写策略文件
        ```
        // update 方法接收两个参数，第一个参数默认为当前登录用户实例，第二个参数则为要进行授权的用户实例
        // 使用授权策略时，我们 不需要 传递当前登录用户至该方法内，因为框架会自动加载当前登录用户，即不用传递 $currentUser
        public function update(User $currentUser, User $user)
        {
            return $currentUser->id === $user->id;
        }
        ```
    - 2.注册授权策略 (即根据模型找到策略)，在 app/Providers/AuthServiceProvider.php 中加入自动注册逻辑
      ```
      // 修改策略自动发现的逻辑
      Gate::guessPolicyNamesUsing(function ($modelClass) {
          // 动态返回模型对应的策略名称，如：// 'App\Models\User' => 'App\Policies\UserPolicy',
          return 'App\Policies\\'.class_basename($modelClass).'Policy';
      });
      ```
    - 3.使用授权策略 app/Http/Controllers/UsersController.php
      ```
      public function edit(User $user)
      {
          // authorize 方法接收两个参数，第一个为授权策略的名称，第二个为进行授权验证的数据。
          $this->authorize('update', $user);
          return view('users.edit', compact('user'));
      }

      public function update(User $user, Request $request)
      {
          $this->authorize('update', $user);
          ...
      }
      ```
  - 友好转向intended(), 在 app/Http/Controllers/SessionsController.php 中：
    ```
    public function store(Request $request)
    {
        $credentials = $this->validate($request, [
            'email' => 'required|email|max:255',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->has('remember'))) {
            // 登录成功
            session()->flash('success', '欢迎回来！');

            // 登录后友好转向 intended() 
            // 重定向到上一次请求尝试访问的页面上，并接收一个默认跳转地址参数，当上一次请求记录为空时，跳转到默认地址上。
            $default = route('users.show', Auth::user());
            return redirect()->intended($default);
        } else {
            // 登录失败
            session()->flash('danger', '很抱歉，您的邮箱和密码不匹配');
            // 使用 withInput() 后模板里 old('email') 将能获取到上一次用户提交的内容
            return redirect()->back()->withInput();
        }
    }
    ```
### 8.4 列出所有用户
  - 控制器 app/Http/Controllers/UsersController.php
    ```
    public function index()
    {
        $users = User::paginate(10); // 分页，每页10条
        return view('users.index', compact('users'));
    }
    ```
  - 列表视图 resources/views/users/index.blade.php
    ```
    @extends('layouts.default')
    @section('title', '所有用户')

    @section('content')
    <div class="offset-md-2 col-md-8">
      <h2 class="mb-4 text-center">所有用户</h2>
      <div class="list-group list-group-flush">
        @foreach ($users as $user)
          @include('users._user')
        @endforeach
      </div>

      <div class="mt-3">
        {!! $users->render() !!}
      </div>
    </div>
    @stop
    ```
  - 局部视图 resources/views/users/_user.blade.php
    ```
    <div class="list-group-item">
      <img class="mr-3" src="{{ $user->gravatar() }}" alt="{{ $user->name }}" width=32>
      <a href="{{ route('users.show', $user) }}">
        {{ $user->name }}
      </a>
    </div>
    ```
  - 分页
    渲染分页视图的代码必须使用 {!! !!} 语法，而不是 {{　}}，这样生成 HTML 链接才不会被转义.
    ```
    控制器中：
      $users = User::paginate(10); // 分页，每页10条
      return view('users.index', compact('users'));
    视图中：
      <div class="mt-3">
        {!! $users->render() !!}
      </div>
    ```
  - 填充假数据 (5步骤)
    - 1.模型工厂 (模型工厂造模型)
      database/factories/UserFactory.php
      ```
      <?php

      use App\Models\User;
      use Illuminate\Support\Str;
      use Faker\Generator as Faker;

      $factory->define(User::class, function (Faker $faker) {
          $date_time = $faker->date . ' ' . $faker->time;
          return [
              'name' => $faker->name,
              'email' => $faker->unique()->safeEmail,
              'email_verified_at' => now(),
              'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', // secret
              'remember_token' => Str::random(10),
              'created_at' => $date_time,
              'updated_at' => $date_time,
          ];
      });
      ```
      define 定义了一个指定数据模型（如此例子 User）的模型工厂
    - 2.创建填充文件
      ```
      php artisan make:seeder UsersTableSeeder
      ```
    - 3.编写填充文件（在seeder文件中用`factory()`调用模型工厂）
      ```
      <?php

      use Illuminate\Database\Seeder;
      use App\Models\User;

      class UsersTableSeeder extends Seeder
      {
          public function run()
          {
              $users = factory(User::class)->times(50)->make();
              User::insert($users->makeVisible(['password', 'remember_token'])->toArray());

              $user = User::find(1);
              $user->name = 'Summer';
              $user->email = 'summer@example.com';
              $user->save();
          }
      }
      ```
      makeVisible 方法临时显示 User 模型里指定的隐藏属性
    - 4.在 database/seeds/DatabaseSeeder.php 中调用填充文件
      ```
      public function run()
      {
          $this->call(UsersTableSeeder::class);
      }
      ```
    - 5.执行填充命令
      ```
      php artisan migrate:refresh
      php artisan db:seed

      或者
      php artisan migrate:refresh --seed // 这一句相当于上面的2句
      
      单独指定填充文件
      php artisan db:seed --class=UsersTableSeeder        
      ```
### 8.5 删除用户
  - 添加字段 (管理员字段：is_admin）
    - 1.生成迁移文件，用来添加字段
      ```
      php artisan make:migration add_is_admin_to_users_table --table=users
      ```
    - 2.编写迁移文件
      ```
      public function up()
      {
          // 增加 is_admin 字段
          Schema::table('users', function (Blueprint $table) {
              $table->boolean('is_admin')->after('password')->default(false);
          });
      }

      public function down()
      {
          // 删除 is_admin 字段
          Schema::table('users', function (Blueprint $table) {
              $table->dropColumn('is_admin');
          });
      }
      ```
    - 3.执行迁移文件 (记得要执行完了新迁移文件后，才能全部回滚 refresh)
      ```
      php artisan migrate
      ```
    - 4.将第一个用户设置为管理员 database/seeds/UsersTableSeeder.php
      ```
      public function run()
      {
          $users = factory(User::class)->times(50)->make();
          User::insert($users->makeVisible(['password', 'remember_token'])->toArray());

          $user = User::find(1);
          $user->name = 'Summer';
          $user->email = 'summer@example.com';
          $user->is_admin = true;
          $user->save();
      }
      ```
    - 5.重置数据库 并填充
      ```
      php artisan migrate:refresh --seed
      ```
  - destroy 删除动作
    - 1.定义「删除」授权策略 app/Policies/UserPolicy.php
      ```
      public function destroy(User $currentUser, User $user)
      {
          // 管理员才能删除 且 自己不能删除自己
          return $currentUser->is_admin && $currentUser->id !== $user->id;
      }
      ```
    - 2.模板中用 `@can 和 @endcan`调用「删除策略」：resources/views/users/_user.blade.php
      ```
      @can('destroy', $user)
        <form action="{{ route('users.destroy', $user) }}" method="post" class="float-right" onsubmit="return confirm('确定要删除该用户吗？')">
          {{ csrf_field() }}
          {{ method_field('DELETE') }}
          <button type="submit" class="btn btn-sm btn-danger delete-btn">删除</button>
        </form>
      @endcan
      ```
    - 3.控制器中用 `authorize()` 调用「删除策略」，并执行删除动作：app/Http/Controllers/UsersController.php
      ```
      public function destroy(User $user)
      {
          $this->authorize('destroy', $user);
          $user->delete();
          session()->flash('success', '成功删除用户！');
          return back();
      }
      ```
## 9 邮件发送
### 9.2 账户激活
  - 9.2.1 添加字段 (activation_token  activated)
    - 1.生成迁移文件 用来添加2个激活字段
      ```
      php artisan make:migration add_activation_to_users_table --table=users
      ```
    - 2.编写迁移文件
      ```
      public function up()
      {
          Schema::table('users', function (Blueprint $table) {
              $table->string('activation_token')->nullable();
              $table->boolean('activated')->default(false);
          });
      }

      public function down()
      {
          Schema::table('users', function (Blueprint $table) {
              $table->dropColumn('activation_token');
              $table->dropColumn('activated');
          });
      }
      ```
    - 3.执行迁移
      ```
      php artisan migrate
      ```
  - 9.2.2 模型监听 生成激活令牌
    - 1.监听 Model 的 creating 事件，在用户「注册」之前生成用户的激活令牌
      app/Models/User.php
      ```
      public static function boot()
      {
          parent::boot();

          static::creating(function ($user) {
              $user->activation_token = Str::random(10);
          });
      }
      ```
    - 2.在模型工厂中将假用户设为激活 database/factories/UserFactory.php
      ```
      $factory->define(User::class, function (Faker $faker) {
          $date_time = $faker->date . ' ' . $faker->time;
          return [
              'name' => $faker->name,
              'email' => $faker->unique()->safeEmail,
              'email_verified_at' => now(),
              'activated' => true,
              'password' => bcrypt('123456'), // password
              'remember_token' => Str::random(10),
              'created_at' => $date_time,
              'updated_at' => $date_time,
          ];
      });
      ```
    - 3.重置数据库 并填充
      ```
      php artisan migrate:refresh --seed
      ```
  - 9.2.3 发送邮件
    - 1.在 `.env` 中设置邮件驱动为 log
      ```
      MAIL_DRIVER=log
      ```
    - 2.激活路由 (激活链接) routes/web.php
      ```
      Route::get('signup/confirm/{token}', 'UsersController@confirmEmail')->name('confirm_email');
      ```
    - 3.激活邮件视图 resources/views/emails/confirm.blade.php
      ```
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="UTF-8">
        <title>注册确认链接</title>
      </head>
      <body>
        <h1>感谢您在 Weibo App 网站进行注册！</h1>

        <p>
          请点击下面的链接完成注册：
          <a href="{{ route('confirm_email', $user->activation_token) }}">
            {{ route('confirm_email', $user->activation_token) }}
          </a>
        </p>

        <p>
          如果这不是您本人的操作，请忽略此邮件。
        </p>
      </body>
      </html>
      ```
    - 4.登录时检查是否已激活 app/Http/Controllers/SessionsController.php
      ```
      if (Auth::attempt($credentials, $request->has('remember'))) {
          // 登录成功
          if (Auth::user()->activated) {
              // 已激活
              session()->flash('success', '欢迎回来！');    
              // 登录后友好转向 intended() 
              // 重定向到上一次请求尝试访问的页面上，并接收一个默认跳转地址参数，当上一次请求记录为空时，跳转到默认地址上。
              $default = route('users.show', Auth::user());
              return redirect()->intended($default);
          } else {
              // 未激活
              Auth::logout();
              session()->flash('warning', '您的账号未激活，请检查邮箱中的注册邮件进行激活。');
              return redirect('/');
          }
      } else {
          // 登录失败
          session()->flash('danger', '很抱歉，您的邮箱和密码不匹配');
          // 使用 withInput() 后模板里 old('email') 将能获取到上一次用户提交的内容
          return redirect()->back()->withInput();
      }
      ```
    - 5.发送邮件 app/Http/Controllers/UsersController.php
      ```
      // 注册
      public function store(Request $request)
      {
          ...
          // Auth::login($user); // 把之前用户注册成功之后进行的登录操作 换成 以下激活邮箱发送操作
          $this->sendEmailConfirmationTo($user);
          session()->flash('success', '验证邮件已发送到你的注册邮箱上，请注意查收。');
          return redirect()->route('users.show', $user);
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

          Mail::send($view, $data, function($message) use ($from, $name, $to, $subject) {
              $message->from($from, $name)->to($to)->subject($subject);
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
      ```
### 9.3 重置密码
  - 思路说明：重置密码控制器逻辑框架已经写好，我们只需配置4个路由、2个视图即可。
  - 1.重置密码路由
    ```
    Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
    Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
    Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
    Route::post('password/reset', 'Auth\ResetPasswordController@reset')->name('password.update');
    ```
  - 2.发送「重置密码」邮件表单视图 resources/views/auth/passwords/email.blade.php
    ```
    @extends('layouts.default')
    @section('title', '重置密码')

    @section('content')
    <div class="col-md-8 offset-md-2">
      <div class="card ">
        <div class="card-header"><h5>重置密码</h5></div>

        <div class="card-body">
          @if (session('status'))
          <div class="alert alert-success">
            {{ session('status') }}
          </div>
          @endif

          <form class="" method="POST" action="{{ route('password.email') }}">
            {{ csrf_field() }}

            <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
              <label for="email" class="form-control-label">邮箱地址：</label>

              <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required>

              @if ($errors->has('email'))
                <span class="form-text">
                  <strong>{{ $errors->first('email') }}</strong>
                </span>
              @endif
            </div>

            <div class="form-group">
              <button type="submit" class="btn btn-primary">
                发送密码重置邮件
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    @endsection
    ```
  - 3.重置密码表单视图 resources/views/auth/passwords/reset.blade.php
    ```
    @extends('layouts.default')
    @section('title', '更新密码')

    @section('content')
    <div class="offset-md-1 col-md-10">
      <div class="card">
        <div class="card-header">
            <h5>更新密码</h5>
        </div>

        <div class="card-body">
          <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group row">
              <label for="email" class="col-md-4 col-form-label text-md-right">Email 地址</label>

              <div class="col-md-6">
                <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ $email ?? old('email') }}" required autofocus>

                @if ($errors->has('email'))
                <span class="invalid-feedback" role="alert">
                  <strong>{{ $errors->first('email') }}</strong>
                </span>
                @endif
              </div>
            </div>

            <div class="form-group row">
              <label for="password" class="col-md-4 col-form-label text-md-right">密码</label>

              <div class="col-md-6">
                <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>

                @if ($errors->has('password'))
                <span class="invalid-feedback" role="alert">
                  <strong>{{ $errors->first('password') }}</strong>
                </span>
                @endif
              </div>
            </div>

            <div class="form-group row">
              <label for="password-confirm" class="col-md-4 col-form-label text-md-right">确认密码</label>

              <div class="col-md-6">
                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required>
              </div>
            </div>

            <div class="form-group row mb-0">
              <div class="col-md-6 offset-md-4">
                <button type="submit" class="btn btn-primary">
                  重置密码
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
    @endsection
    ```
### 9.4 生产环境发送邮件
  - 1.开启QQ邮箱的SMTP，并复制『授权码』，授权码将作为我们的密码使用
  - 2.邮箱配置
    ```
    MAIL_DRIVER=smtp
    MAIL_HOST=smtp.qq.com
    MAIL_PORT=25
    MAIL_USERNAME=844@qq.com
    MAIL_PASSWORD=etxknliajakrbced
    MAIL_ENCRYPTION=tls
    MAIL_FROM_ADDRESS=844@qq.com
    MAIL_FROM_NAME=andy
    ```
  - 3.在 app/Http/Controllers/UsersController.php 中，邮件发送的 from() 可以去掉了，因为 .env 中已经配置了
    ```
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
    ```
## 微博CRUD
### 10.2 微博模型
  - 1.创建模型的「迁移文件」
    ```
    php artisan make:migration create_statuses_table --create="statuses"
    ```
  - 2.编写迁移文件（加索引）
    ```
    public function up()
    {
        Schema::create('statuses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('content'); // 默认 notnull()
            $table->integer('user_id')->index(); // 因借助 user_id 查询指定用户的微博，加索引提供查询效率
            $table->index(['created_at']); // 根据创建时间来排序，加索引提高排序效率
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('statuses');
    }
    ```
    对应的sql语句
    ```
    CREATE TABLE `statuses` (
      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
      `user_id` int(11) NOT NULL,
      `created_at` timestamp NULL DEFAULT NULL,
      `updated_at` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `statuses_created_at_index` (`created_at`),
      KEY `statuses_user_id_index` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ```
  - 3.关联「微博」和「用户」（一对多）
    - app/Models/Status.php
      ```
      // 多对一 多条微博属于一个用户
      public function user()
      {
          return $this->belongsTo(User::class);
      }
      ```
    - app/Models/User.php
      ```
      // 一对多 一个用户拥有多条微博
      public function statuses()
      {
          return $this->hasMany(Status::class);
      }
      ```
    - 关联的好处
      ```
      // 关联之前创建微博
      App\Models\Status::create()
      // 关联之后创建微博，这样创建的微博会自动与用户进行关联
      $user->statuses()->create()
      ```
### 10.3 显示个人微博
  
  - 1.在个人页面显示微博 app/Http/Controllers/UsersController.php
    ```
    public function show(User $user)
    {
        $statuses = $user->statuses()
                            ->orderBy('created_at', 'desc')
                            ->paginate(10);
        return view('users.show', compact('user', 'statuses'));
    }
    ```
  
  - 2.微博局部视图 resources/views/statuses/_status.blade.php
    ```
    <li class="media mt-4 mb-4">
      <a href="{{ route('users.show', $user->id )}}">
        <img src="{{ $user->gravatar() }}" alt="{{ $user->name }}" class="mr-3 gravatar"/>
      </a>
      <div class="media-body">
        <h5 class="mt-0 mb-1">{{ $user->name }} <small> / {{ $status->created_at->diffForHumans() }}</small></h5>
        {{ $status->content }}
      </div>
    </li>
    ```
    - diffForHumans() 该方法的作用是将日期进行友好化处理。
  
  - 3.在 resources/views/users/show.blade.php 引用微博局部视图
    ```
    <div class="row">
      <div class="offset-md-2 col-md-8">
        <section class="user_info">
          @include('shared._user_info', ['user' => $user])
        </section>
        <section class="status">
          @if ($statuses->count() > 0)
            <ul class="list-unstyled">
              @foreach ($statuses as $status)
                @include('statuses._status')
              @endforeach
            </ul>
            <div class="mt-5">
              {!! $statuses->render() !!}
            </div>
          @else
            <p>没有数据！</p>
          @endif
        </section>
      </div>
    </div>
    ```
  
  - 4.造微博假数据 (4.4 服务容器)
    - 4.1 生成微博模型的「模型工厂」（模型工厂造模型）
      ```
      php artisan make:factory StatusFactory
      ```
    - 4.2 编写微博模型的「模型工厂」 database/factories/StatusFactory.php
      ```
      <?php

      use Faker\Generator as Faker;

      $factory->define(App\Models\Status::class, function (Faker $faker) {
          $date_time = $faker->date . ' ' . $faker->time;
          return [
              'content'   => $faker->text(),
              'created_at' => $date_time,
              'updated_at' => $date_time,
          ];
      });
      ```
    - 4.3 生成填充文件
      ```
      php artisan make:seeder StatusesTableSeeder
      ```
    - 4.4 编写填充文件（在填充文件中 调用 「微博模型工厂」来造模型，即填充数据）
      ```
      <?php

      use Illuminate\Database\Seeder;
      use App\Models\User;
      use App\Models\Status;

      class StatusesTableSeeder extends Seeder
      {
          public function run()
          {
              $user_ids = ['1','2','3'];
              // 通过 app() 或者 resolve() 来获取一个 Faker 容器 的实例
              // $faker = app(Faker\Generator::class);
              $faker = resolve(Faker\Generator::class);

              $statuses = factory(Status::class)->times(100)->make()->each(function ($status) use ($faker, $user_ids) {
                  $status->user_id = $faker->randomElement($user_ids);
              });

              Status::insert($statuses->toArray());
          }
      }
      ```
      - [服务容器](https://learnku.com/docs/laravel/6.x/container/5131#68be3c)
        - 上例中，通过 app() 或者 resolve() 来获取一个 Faker 容器 的实例
        - 「服务容器」：就是一个装载和解析服务的容器，服务指的是「类」或「接口」
        - 「绑定服务」：就是把服务绑定到容器中，几乎所有的服务容器绑定都会在 [服务提供者](https://learnku.com/docs/laravel/6.x/providers/5132) 中注册
            ```
            $this->app->bind('HelpSpot\API', function ($app) {
                return new HelpSpot\API($app->make('HttpClient'));
            });
            ```
        - 「解析实例」：就是通过 app() 或 resolve() 等从容器中得到「服务实例」
### 10.4 发布微博
  - 1.路由 resource(,,['only'=>[]])
    ```
    // 对 resource 传参 only 键指定只生成某几个动作的路由
    Route::resource('statuses', 'StatusesController', ['only' => ['store', 'destroy']]);
    ```
  - 2.控制器
    ```
    php artisan make:controller StatusesController
    ```
    ```
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'content' => 'required|max:140'
        ]);

        Auth::user()->statuses()->create([
            'content' => $request['content']
        ]);

        session()->flash('success', '发布成功');
        return redirect()->back();
    }
    ```
  - 3.发布微博的「局部表单视图」 resources/views/shared/_status_form.blade.php
    ```
    <form action="{{ route('statuses.store') }}" method="POST">
      @include('shared._errors')
      {{ csrf_field() }}
      <textarea class="form-control" rows="3" placeholder="聊聊新鲜事儿..." name="content">{{ old('content') }}</textarea>
      <div class="text-right">
          <button type="submit" class="btn btn-primary mt-3">发布</button>
      </div>
    </form>
    ```
  - 4.在首页引入局部表单视图 resources/views/static_pages/home.blade.php
    ```
    @extends('layouts.default')

    @section('content')
      @if (Auth::check())
        <div class="row">
          <div class="col-md-8">
            <section class="status_form">
              @include('shared._status_form')
            </section>
          </div>
          <aside class="col-md-4">
            <section class="user_info">
              @include('shared._user_info', ['user' => Auth::user()])
            </section>
          </aside>
        </div>
      @else
        <div class="jumbotron">
          <h1>Hello Laravel</h1>
          <p class="lead">
            你现在所看到的是 <a href="https://learnku.com/courses/laravel-essential-training">Laravel 入门教程</a> 的示例项目主页。
          </p>
          <p>
            一切，将从这里开始。
          </p>
          <p>
            <a class="btn btn-lg btn-success" href="{{ route('signup') }}" role="button">现在注册</a>
          </p>
        </div>
      @endif
    @stop
    ```
  - 5.修改模型可更新字段 app/Models/Status.php
    ```
    // 允许更新微博的 content 字段
    protected $fillable = ['content'];
    ```
### 10.5 首页微博列表
  - 1.在用户模型中 定义「动态流」方法 app/Models/User.php
    ```
    public function feed()
    {
        return $this->statuses()
                    ->orderBy('created_at', 'desc');
    }
    ```
  - 2.控制器中返回「动态流」数据 app/Http/Controllers/StaticPagesController.php
    ```
    public function home()
    {
        $feed_items = [];
        if (Auth::check()) {
            $feed_items = Auth::user()->feed()->paginate(10);
        }

        return view('static_pages/home', compact('feed_items'));
    }
    ```
  - 3.定义一个「动态流」局部视图 resources/views/shared/_feed.blade.php
    ```
    @if ($feed_items->count() > 0)
      <ul class="list-unstyled">
        @foreach ($feed_items as $status)
          @include('statuses._status',  ['user' => $status->user])
        @endforeach
      </ul>
      <div class="mt-5">
        {!! $feed_items->render() !!}
      </div>
    @else
      <p>没有数据！</p>
    @endif
    ```
  - 4.主页中包含「动态流」局部视图 resources/views/static_pages/home.blade.php
    ```
    @if (Auth::check())
      <div class="row">
        <div class="col-md-8">
          <section class="status_form">
            @include('shared._status_form')
          </section>
          <h4>微博列表</h4>
          <hr>
          @include('shared._feed')
        </div>
        <aside class="col-md-4">
          <section class="user_info">
            @include('shared._user_info', ['user' => Auth::user()])
          </section>
        </aside>
      </div>
    @else
    ```
### 10.6 删除微博
  - 1.删除的授权策略
    ```
    php artisan make:policy StatusPolicy
    ```
    ```
    // 只有自己才能删除自己的微博
    public function destroy(User $currentUser, Status $status)
    {
        return $currentUser->id === $status->user_id;
    }
    ```
  - 2.视图中的删除按钮 resources/views/statuses/_status.blade.php
    ```
    <li class="media mt-4 mb-4">
      <a href="{{ route('users.show', $user->id) }}">
        <img src="{{ $user->gravatar() }}" alt="{{ $user->name }}" class="mr-3 gravatar">
      </a>
      <div class="media-body">
        <h5 class="mt-0 mb-1">{{ $user->name }} <small> / {{ $status->created_at->diffForHumans() }}</small></h5>
        {{ $status->content }}
      </div>

      
      @can('destroy', $status)
        <form action="{{ route('statuses.destroy', $status->id) }}" method="POST" onsubmit="return confirm('您确定要删除此条微博吗？');">
          {{ csrf_field() }}
          {{ method_field('DELETE') }}
          <button type="submit" class="btn btn-sm btn-danger">删除</button>
        </form>
      @endcan
    </li>
    ```
  - 3.控制器中删除动作 app/Http/Controllers/StatusesController.php
    ```
    public function destroy(Status $status)
    {
        $this->authorize('destroy', $status);
        $status->delete();
        session()->flash('success', '微博已被成功删除！');
        return redirect()->back();
    }
    ```
## 11 粉丝关系
### 11.2 粉丝数据表
  - 1.生成一个「粉丝关系数据表」(即中间表followers)
    ```
    artisan make:migration create_followers_table --create="followers"
    ```
    ```
    public function up()
    {
        Schema::create('followers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user_id')->index(); // 加索引，获取「粉丝」列表要根据此字段查询
            $table->integer('follower_id')->index(); // 加索引，获取「关注人」列表要根据此字段查询
            $table->timestamps();
        });
    }
    ```
  - 2.执行迁移文件 (生成中间表)
      ```
      php artisan migrate
      ```
  - 3.关联博主和粉丝 (多对多) app/Models/User.php
    ```
    // 多对多关联语法：$this-> belongsToMany(关联表model，中间表表名，中间表中本model的关联ID，中间表中关联model的关联ID);
    // 本 model 为博主 （博主的粉丝列表）
    public function followers()
    {
        return $this->belongsToMany(User::class, 'followers', 'user_id', 'follower_id');
    }

    // 本 model 为粉丝 （粉丝的博主列表）
    public function followings()
    {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'user_id');
    }

    // 关注 (所谓关注，即把粉丝的ids 加到 博主列表 中去)
    public function follow($user_ids)
    {
        if ( !is_array($user_ids) ) {
            $user_ids = compact('user_ids');
        }
        $this->followings()->sync($user_ids, false); // false 参数表示添加关注人id数组时，不删除其他关注人id
    }

    // 取关 (所谓取关，即把粉丝的ids 从 博主列表 中减去)
    public function unfollow($user_ids)
    {
        if ( !is_array($user_ids) ) {
            $user_ids = compact('user_ids');
        }
        $this->followings()->detach($user_ids);
    }

    // 是否关注
    public function isFollowing($user_id)
    {
        // $this->followings 返回的是：粉丝关注的博主列表的集合
        // $this->followings() 返回的是：数据库请求构建器（也就是数据库查询语句）
        // $this->followings == $this->followings()->get() // 等于 true
        // contains 方法是 Collection集合 的一个方法
        return $this->followings->contains($user_id);
    }
    ```
    - 「多对多」关联语法
      ```
      $this-> belongsToMany(关联表model，中间表表名，中间表中本model的关联ID，中间表中关联model的关联ID);
      ```
    - 「多对多」[关联动作](https://learnku.com/courses/laravel-essential-training/6.x/fan-data-table/5501#065818)
      - 「关注」这个动作，就是在「某粉丝id」的「博主列表」中做加法，用 `attach()` 和 `sync()` 方法
        ```
        // attach() 添加 id 时不会去重，比如多次执行时，可能会重复添加id为"2"的记录
        $user->followings()->attach([2, 3])
        
        // sync() 添加 id 时会去重，不会重复添加同一个id
        // sync() 的第二个参数表示添加关注人(博主)id数组时，「是否」删除其他关注人id，false 表示「否」
        $user->followings()->sync([3], false)
        ```
      - 「取关」这个动作，就是在「某粉丝id」的「博主列表」中做减法，用 `detach()` 方法
        ```
        $user->followings()->detach([2,3])
        ```
### 11.3 社交统计信息
  - 1.填充「关注」的假数据
      ```
      php artisan make:seeder FollowersTableSeeder
      ```
      ```
      public function run()
      {
          // 让「第1个用户」和「其他用户」互相关注
          $users = User::all();

          $user = $users->first();
          $followers = $users->slice(1);

          $user_id = $user->id;
          $follower_ids = $followers->pluck('id')->toArray();

          // 1号 关注 其余人
          $user->follow($follower_ids);

          // 其余人 关注 1号
          foreach($followers as $follower) {
              $follower->follow($user_id);
          }
      }
      ```
      调用填充文件 database/seeds/DatabaseSeeder.php

      ```
      public function run()
      {

          $this->call(UsersTableSeeder::class);
          $this->call(StatusesTableSeeder::class);
          $this->call(FollowersTableSeeder::class);

      }
      ```
      重置并填充
      ```
      php artisan migrate:refresh --seed
      ```
  - 2.社交统计局部视图 resources/views/shared/_stats.blade.php
    ```
    <a href="#">
      <strong id="following" class="stat">
        {{ count($user->followings) }}
      </strong>
      关注
    </a>
    <a href="#">
      <strong id="followers" class="stat">
        {{ count($user->followers) }}
      </strong>
      粉丝
    </a>
    <a href="#">
      <strong id="statuses" class="stat">
        {{ $user->statuses()->count() }}
      </strong>
      微博
    </a>
    ```
    - 知识点：计数时，尽量在「查询构建器」上`count()` （不过，最好的方式单独一个字段计数，这样就不用count()了）
      ```
      $user->statuses->count()   // 数据 70W 条，数据太大，内存溢出（这种是先取出集合数据，再统计，数据太大全部取出时，很耗时且占内存）
      $user->statuses()->count() // 数据 70W 条，我用这种方式是 0 秒（这种不用取出数据，直接 sql 计数）
      ```
  - 3.加载子视图
    - 主页加载子视图 resources/views/static_pages/home.blade.php
      ```
      @if (Auth::check())
        <div class="row">
          <div class="col-md-8">
            <section class="status_form">
              @include('shared._status_form')
            </section>
            <h4>微博列表</h4>
            <hr>
            @include('shared._feed')
          </div>
          <aside class="col-md-4">
            <section class="user_info">
              @include('shared._user_info', ['user' => Auth::user()])
            </section>
            <section class="stats mt-2">
              @include('shared._stats', ['user' => Auth::user()])
            </section>
          </aside>
        </div>
      @else
      ```
    - 个人页面也加载子视图 resources/views/users/show.blade.php
      ```
      <div class="row">
        <div class="offset-md-2 col-md-8">
          <section class="user_info">
            @include('shared._user_info', ['user' => $user])
          </section>
          <section class="stats mt-2">
            @include('shared._stats')
          </section>
          <hr>
          <section class="statuses">
            @if($statuses->count() > 0)
              <ul class="list-unstyled">
                @foreach($statuses as $status)
                  @include('statuses._status')
                @endforeach
              </ul>
              <div class="mt-5">
                {!! $statuses->render() !!}
              </div>
            @else
              <p>没有数据！</p>
            @endif
          </section>
        </div>
      </div>
      ```
  - 4.css样式 resources/sass/app.scss
    ```
    .stats {
      overflow: auto;
      margin-top: 0;
      padding: 0;
      a {
        float: left;
        padding: 0 10px;
        text-align: center;
        width: 33%;
        border-left: 1px solid #eee;
        color: #33383c;
        &:first-child {
          padding-left: 0;
          border: 0;
        }
        &:hover {
          text-decoration: none;
          color: #337ab7;
        }
      }
      strong {
        display: block;
        font-size: 1.2em;
        color: black;
      }
    }
    ```
### 11.4 粉丝页面
  - 1.路由 routes/web.php
    ```
    Route::get('users/{user}/followings', 'UsersController@followings')->name('users.followings'); // 博主列表
    Route::get('users/{user}/followers', 'UsersController@followers')->name('users.followers'); // 粉丝列表
    ```
  - 2.入口链接 resources/views/shared/_stats.blade.php
    ```
    <a href="{{ route('users.followings', $user->id) }}">
      <strong id="following" class="stat">
        {{ count($user->followings) }}
      </strong>
      关注
    </a>
    <a href="{{ route('users.followers', $user->id) }}">
      <strong id="followers" class="stat">
        {{ $user->followers->count() }}
      </strong>
      粉丝
    </a>
    <a href="{{ route('users.show', $user->id) }}">
      <strong id="statuses" class="stat">
        {{ $user->statuses()->count() }}
      </strong>
      微博
    </a>
    ```
  - 3.控制器 app/Http/Controllers/UsersController.php
    ```
    public function followings(User $user)
    {
        $users = $user->followings()->paginate(10);
        $title = $user->name . '关注的人';
        return view('users.show_follow', compact('users', 'title'));
    }

    public function followers(User $user)
    {
        $users = $user->followers()->paginate(10);
        $title = $user->name . '的粉丝';
        return view('users.show_follow', compact('users', 'title'));
    }
    ```
  - 4.视图（博主列表和粉丝列表共用一个视图）
    ```
    @extends('layouts.default')
    @section('title', $title)

    @section('content')
    <div class="offset-md-2 col-md-8">
      <h2 class="mb-4 text-center">{{ $title }}</h2>

      <div class="list-group list-group-flush">
        @foreach ($users as $user)
          <div class="list-group-item">
            <img class="mr-3" src="{{ $user->gravatar() }}" alt="{{ $user->name }}" width=32>
            <a href="{{ route('users.show', $user) }}">
              {{ $user->name }}
            </a>
          </div>

        @endforeach
      </div>

      <div class="mt-3">
        {!! $users->render() !!}
      </div>
    </div>
    @stop
    ```
### 关注按钮
  - 1.路由
    ```
    Route::post('/users/followers/{user}', 'FollowersController@store')->name('followers.store');
    Route::delete('/users/followers/{user}', 'FollowersController@destroy')->name('followers.destroy');
    ```
  - 2.授权策略 app/Policies/UserPolicy.php
    ```
    public function follow(User $currentUser, User $user)
    {
        // 自己不能关注自己
        return $currentUser->id !== $user->id;
    }
    ```
  - 3.关注表单的局部视图 resources/views/users/_follow_form.blade.php
    ```
    @can('follow', $user)
      <div class="text-center mt-2 mb-4">
        @if (Auth::user()->isFollowing($user->id))
          <form action="{{ route('followers.destroy', $user->id) }}" method="post">
            {{ csrf_field() }}
            {{ method_field('DELETE') }}
            <button type="submit" class="btn btn-sm btn-outline-primary">取消关注</button>
          </form>
        @else
          <form action="{{ route('followers.store', $user->id) }}" method="post">
            {{ csrf_field() }}
            <button type="submit" class="btn btn-sm btn-primary">关注</button>
          </form>
        @endif
      </div>
    @endcan
    ```
  - 4.个人页面 添加「关注表单」子视图 resources/views/users/show.blade.php
    ```
    <section class="user_info">
      @include('shared._user_info', ['user' => $user])
    </section>

    @if (Auth::check())
      @include('users._follow_form')
    @endif

    <section class="stats mt-2">
      @include('shared._stats', ['user' => $user])
    </section>
    <hr>
    ```
  - 5.控制器 app/Http/Controllers/FollowersController.php
    ```
    php artisan make:controller FollowersController
    ```
    ```
      public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(User $user)
    {
        $this->authorize('follow', $user);

        if ( ! Auth::user()->isFollowing($user->id)) {
            Auth::user()->follow($user->id);
        }

        // return redirect()->route('users.show', $user->id);
        return back();
    }

    public function destroy(User $user)
    {
        $this->authorize('follow', $user);

        if ( Auth::user()->isFollowing($user->id)) {
            Auth::user()->unfollow($user->id);
        }

        return redirect()->route('users.show', $user->id);
    }
    ```
### 动态流（显示所有关注用户的微博动态）
  - app/Models/User.php
    ```
    // 动态流
    public function feed()
    {
        // 自己的动态流
        // return $this->statuses()
        //                 ->orderBy('created_at', 'desc');

        // 自己和关注用户的动态流
        $user_ids = $this->followings->pluck('id')->toArray();
        array_push($user_ids, $this->id);
        return Status::whereIn('user_id', $user_ids)
                                ->with('user')
                                ->orderBy('created_at', 'desc');
    }
    ```
    - [查询构建器](https://learnku.com/docs/laravel/6.x/queries/5171) whereIn 方法取出所有用户的微博动态并进行倒序排序；
    - [预加载](https://learnku.com/docs/laravel/6.x/eloquent-relationships/5177#eager-loading) `with('user')` 预加载方法，提前取出所有 $statuses 里面的 $user , 避免了 N+1 查找的问题。