@extends('layouts.default')
@section('title', '更新个人资料')

@section('content')
<div class="offset-md-2 col-md-8">
  <div class="card">
    <div class="card-header">
      <h5>更新个人资料</h5>
    </div>
    <div class="card-body">
      @include('shared._errors')

      <div class="gravatar_edit">
        <a href="{{ route('users.show', $user) }}" target="_blank">
          <img src="{{ $user->gravatar('200') }}" alt="{{ $user->name }}" class="gravatar" />
        </a>
      </div>

      <form action="{{ route('users.update', $user->id) }}" method="post">
        {{ csrf_field() }}
        {{ method_field('PATCH') }}

        <div class="form-group">
          <label for="name">姓名：</label>
          <input type="text" name="name" class="form-control" value="{{ $user->name }}">
        </div>

        <div class="form-group">
          <label for="email">邮箱：</label>
          <input type="email" name="email" class="form-control" value="{{ $user->email }}" disabled>
        </div>

        <div class="form-group">
          <label for="password">密码：</label>
          <input type="password" name="password" class="form-control" value="{{ old('password') }}">
        </div>

        <div class="form-group">
          <label for="password_confirmatino">确认密码：</label>
          <input type="password" name="password_confirmation" class="form-control" value="{{ old('passsword_confirmation') }}">
        </div>

        <button type="submit" class="btn btn-primary">更新</button>
      </form>
    </div>
  </div>
</div>
@stop