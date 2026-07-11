<div class="favorite-list-item" title="{{$user->name.' '.$user->last_name}}">
    @if($user)
        @if($user->active_status)
            <span class="activeStatus"></span>
        @endif
        <div data-id="{{ $user->id }}" data-action="0" class="avatar av-m"style="background-image: url('{{ $user->image ?? '/images/users/default-user-image.png' }}');">
        </div>
        <p>{{ strlen($user->name.' '.$user->last_name) > 8 ? substr($user->name.' '.$user->last_name,0,8).'..' : $user->name.' '.$user->last_name }}</p>
    @endif
</div>
