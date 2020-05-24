<div class="list-group-item">
  <img src="{{ $user->gravatar() }}" alt="{{ $user->name }}" class="mr-3" width=32>
  <a href="{{ route('users.show', $user) }}">
    {{ $user->name }}
  </a>
</div>