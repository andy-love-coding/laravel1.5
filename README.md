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