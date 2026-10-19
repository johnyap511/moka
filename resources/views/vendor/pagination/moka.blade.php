{{-- Compact pagination for admin pages: plain words, no framework styles needed (19 Oct 2026). --}}
@if ($paginator->hasPages())
<nav class="mk-pg" role="navigation" aria-label="Pages">
    <style>.mk-pg{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;font-size:12.5px;color:#64748b}.mk-pg ul{display:flex;gap:4px;list-style:none;margin:0;padding:0}.mk-pg a,.mk-pg span.pg{display:inline-block;min-width:30px;padding:5px 8px;border:1px solid #e5e7eb;border-radius:7px;background:#fff;color:#334155;text-decoration:none;text-align:center;line-height:1.2}.mk-pg a:hover{border-color:#0f766e;color:#0f766e}.mk-pg span.pg.on{background:#0f766e;border-color:#0f766e;color:#fff;font-weight:600}.mk-pg span.pg.off{color:#cbd5e1}</style>
    <div>Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }}</div>
    <ul>
        @if ($paginator->onFirstPage())<li><span class="pg off">‹ Previous</span></li>@else<li><a href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Previous</a></li>@endif
        @foreach ($elements as $element)
            @if (is_string($element))<li><span class="pg off">{{ $element }}</span></li>@endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())<li><span class="pg on">{{ $page }}</span></li>@else<li><a href="{{ $url }}">{{ $page }}</a></li>@endif
                @endforeach
            @endif
        @endforeach
        @if ($paginator->hasMorePages())<li><a href="{{ $paginator->nextPageUrl() }}" rel="next">Next ›</a></li>@else<li><span class="pg off">Next ›</span></li>@endif
    </ul>
</nav>
@endif
