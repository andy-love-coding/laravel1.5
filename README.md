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