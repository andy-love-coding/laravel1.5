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
      - <input type="text" name="name" class="form-control" value="{{ old('name') }}">
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

      