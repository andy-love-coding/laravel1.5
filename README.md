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
    1. 模型工厂
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
    2. 创建填充文件
      ```
      php artisan make:seeder UsersTableSeeder
      ```
    3. 编写填充文件（在seeder文件中用`factory()`调用模型工厂）
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
    4. 在 database/seeds/DatabaseSeeder.php 中调用填充文件
      ```
      public function run()
      {
          $this->call(UsersTableSeeder::class);
      }
      ```
    5. 执行填充命令
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
    1. 生成迁移文件，用来添加字段
      ```
      php artisan make:migration add_is_admin_to_users_table --table=users
      ```
    2. 编写迁移文件
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
    3. 执行迁移文件 (记得要执行完了新迁移文件后，才能全部回滚 refresh)
      ```
      php artisan migrate
      ```
    4. 将第一个用户设置为管理员 database/seeds/UsersTableSeeder.php
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
    5. 重置数据库 并填充
      ```
      php artisan migrate:refresh --seed
      ```
  - destroy 删除动作
    1. 定义「删除」授权策略 app/Policies/UserPolicy.php
      ```
      public function destroy(User $currentUser, User $user)
      {
          // 管理员才能删除 且 自己不能删除自己
          return $currentUser->is_admin && $currentUser->id !== $user->id;
      }
      ```
    2. 模板中用 `@can 和 @endcan`调用「删除策略」：resources/views/users/_user.blade.php
      ```
      @can('destroy', $user)
        <form action="{{ route('users.destroy', $user) }}" method="post" class="float-right" onsubmit="return confirm('确定要删除该用户吗？')">
          {{ csrf_field() }}
          {{ method_field('DELETE') }}
          <button type="submit" class="btn btn-sm btn-danger delete-btn">删除</button>
        </form>
      @endcan
      ```
    3. 控制器中用 `authorize()` 调用「删除策略」，并执行删除动作：app/Http/Controllers/UsersController.php
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
    1. 生成迁移文件 用来添加2个激活字段
      ```
      php artisan make:migration add_activation_to_users_table --table=users
      ```
    2. 编写迁移文件
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
    3. 执行迁移
      ```
      php artisan migrate
      ```
  - 9.2.2 模型监听 生成激活令牌
    1. 监听 Model 的 creating 事件，在用户「注册」之前生成用户的激活令牌
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
    2. 在模型工厂中将假用户设为激活 database/factories/UserFactory.php
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
    3. 重置数据库 并填充
      ```
      php artisan migrate:refresh --seed
      ```
  - 9.2.3 发送邮件
    1. 在 `.env` 中设置邮件驱动为 log
      ```
      MAIL_DRIVER=log
      ```
    2. 激活路由 (激活链接) routes/web.php
      ```
      Route::get('signup/confirm/{token}', 'UsersController@confirmEmail')->name('confirm_email');
      ```
    3. 激活邮件视图 resources/views/emails/confirm.blade.php
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
    4. 登录时检查是否已激活 app/Http/Controllers/SessionsController.php
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
              return redirect('home');
          }
      } else {
          // 登录失败
          session()->flash('danger', '很抱歉，您的邮箱和密码不匹配');
          // 使用 withInput() 后模板里 old('email') 将能获取到上一次用户提交的内容
          return redirect()->back()->withInput();
      }
      ```
    5. 发送邮件 app/Http/Controllers/UsersController.php
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
