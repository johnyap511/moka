<tr>
    <td><b>{{ $sp->name }}</b><div class="sc-muted">@if(!$sp->user_id)<span class="sc-warn">no login</span>@elseif(empty($sp->admin_role))super admin, sees everyone@else{{ config('admin_permissions.roles.' . $sp->admin_role . '.label', ucfirst($sp->admin_role)) }}@endif</div></td>
    @if(admin_can('sales.manage'))
    <form method="post" action="{{ route('admin.sales.person') }}" id="sp{{ $sp->id }}">@csrf<input type="hidden" name="id" value="{{ $sp->id }}"></form>
    <td><select name="user_id" form="sp{{ $sp->id }}"><option value="">— not tied —</option>@foreach($staff as $u)<option value="{{ $u->id }}" @selected((int) $sp->user_id === (int) $u->id)>{{ $u->name }} · {{ $u->email }}</option>@endforeach</select></td>
    <td><input type="date" name="confirmed_on" form="sp{{ $sp->id }}" value="{{ $sp->confirmed_on }}" data-raw-dates></td>
    <td><input type="date" name="left_on" form="sp{{ $sp->id }}" value="{{ $sp->left_on }}" data-raw-dates></td>
    <td class="num"><button type="submit" form="sp{{ $sp->id }}" class="btn btn-secondary btn-sm">Save</button></td>
    @else
    <td>{{ $sp->email ?: '—' }}</td><td>{{ $fmtY($sp->confirmed_on) }}</td><td>{{ $fmtY($sp->left_on) }}</td><td></td>
    @endif
</tr>
