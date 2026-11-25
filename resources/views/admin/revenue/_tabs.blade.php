{{-- Revenue desk tabs. A Business Development user sees only Buildings. --}}
@php $full = admin_can('reports.push'); @endphp
<style>
.rd-tabs{display:flex;gap:2px;border-bottom:1px solid var(--border,#e5e7eb);margin:0 0 16px;overflow-x:auto;scrollbar-width:none}.rd-tabs::-webkit-scrollbar{display:none}
.rd-tabs a{flex:0 0 auto;padding:10px 14px;font-size:13.5px;font-weight:500;color:var(--text-secondary,#6e6e73);text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-1px;white-space:nowrap}
.rd-tabs a:hover{color:var(--text,#1d1d1f)}.rd-tabs a.on{color:#0f766e;border-bottom-color:#0f766e;font-weight:600}
.rd-tabs a small{display:block;font-size:11px;font-weight:400;color:var(--text-secondary,#6e6e73);margin-top:1px}
@media (max-width:600px){.rd-tabs{justify-content:space-between}.rd-tabs a{padding:10px 7px;font-size:13px}.rd-tabs a small{display:none}}
@media (max-width:380px){.rd-tabs a{padding:10px 3px;font-size:12.5px}}
</style>
<nav class="rd-tabs" aria-label="Revenue desk">
    @if($full)
    <a href="/admin/units-to-push" class="{{ $tab === 'push' ? 'on' : '' }}">Act now<small>units to push</small></a>
    <a href="/admin/revenue-desk/demand" class="{{ $tab === 'demand' ? 'on' : '' }}">Demand ahead<small>spikes, rates, holidays</small></a>
    <a href="/admin/revenue-desk/ahead" class="{{ $tab === 'ahead' ? 'on' : '' }}">Booked ahead<small>who is booked early</small></a>
    @endif
    <a href="/admin/revenue-desk/buildings" class="{{ $tab === 'buildings' ? 'on' : '' }}">Buildings<small>where demand is</small></a>
</nav>
