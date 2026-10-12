{{-- Analysis sections of the admin Unit report. Shown only when the controller passes $extras (never on the owner side). --}}
@php
    $rm = fn ($v) => 'RM ' . number_format((float) $v, 2);
    $x = $extras; $sp = $x['split']; $ng = $x['nights'];
    $chg = function ($c) {
        if ($c['pct'] === null) return '<span class="ur-chg ur-chg--na">no data</span>';
        $cls = $c['pct'] > 0 ? 'ur-chg--up' : ($c['pct'] < 0 ? 'ur-chg--down' : '');
        return '<span class="ur-chg ' . $cls . '">' . ($c['pct'] > 0 ? '+' : '') . number_format($c['pct'], 1) . '%</span>';
    };
    $fmt = fn ($v, $kind) => $kind === 'rm' ? 'RM ' . number_format($v, 2) : ($kind === 'pct' ? number_format($v, 1) . '%' : number_format($v));
@endphp
<style>
.ur-h{display:flex;align-items:baseline;justify-content:space-between;margin:22px 0 10px}.ur-h h2{font-size:16px;margin:0}.ur-h span{font-size:12.5px;color:var(--text-secondary)}
.ur-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}@media (max-width:1000px){.ur-grid{grid-template-columns:1fr}}
.ur-card{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);padding:14px 16px}.ur-card h3{font-size:13.5px;margin:0 0 10px;display:flex;justify-content:space-between}.ur-card h3 small{font-weight:400;color:var(--text-secondary)}
.ur-rows{display:grid;gap:6px;font-size:13px}.ur-rows div{display:flex;justify-content:space-between;gap:10px}.ur-rows .k{color:var(--text-secondary)}.ur-rows .tot{border-top:1px solid var(--border,#e5e7eb);padding-top:6px;font-weight:700}.ur-rows .net{background:#f0fdfa;border-radius:8px;padding:8px 10px;font-weight:700;color:#0f766e}
.ur-note{font-size:11.5px;color:var(--text-secondary);margin-top:8px;line-height:1.4}
.ur-big{font-size:26px;font-weight:700;letter-spacing:-.4px}.ur-big small{font-size:12.5px;font-weight:500;color:var(--text-secondary);margin-left:6px}
.ur-bar{height:8px;background:#f1f5f9;border-radius:999px;overflow:hidden;margin:8px 0 4px}.ur-bar i{display:block;height:100%;background:#0f766e}
.ur-table{width:100%;border-collapse:collapse;font-size:12.5px}.ur-table th,.ur-table td{padding:7px 8px;border-bottom:1px solid var(--border,#e5e7eb);text-align:left;vertical-align:middle;white-space:nowrap}.ur-table th{font-size:11px;text-transform:uppercase;letter-spacing:.03em;color:var(--text-secondary)}.ur-table .num{text-align:right}.ur-table tbody tr:hover{background:#fafcfb}
.ur-chg{display:inline-block;font-size:12px;font-weight:700;padding:1px 8px;border-radius:999px;background:#f1f5f9;color:#475569}.ur-chg--up{background:#dcfce7;color:#166534}.ur-chg--down{background:#fee2e2;color:#991b1b}.ur-chg--na{font-weight:500}
.ur-dot{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:6px;vertical-align:middle}
.ur-sharebar{display:inline-block;width:90px;height:6px;background:#f1f5f9;border-radius:999px;overflow:hidden;vertical-align:middle;margin-left:8px}.ur-sharebar i{display:block;height:100%}
.ur-wrap{overflow:auto}
</style>

<div class="ur-h"><h2>Analysis · {{ $x['month'] }}</h2><span>Admin and managers only · {{ $x['units'] > 1 ? $x['units'] . ' pooled units' : $listing->name }}</span></div>
<div class="ur-grid">
    <div class="ur-card">
        <h3>Revenue split <small>rows checking in this month</small></h3>
        <div class="ur-rows">
            <div><span class="k">Room charges</span><b>{{ $rm($sp['room']) }}</b></div>
            <div><span class="k">Cleaning fees</span><span>{{ $rm($sp['cleaning']) }}</span></div>
            <div><span class="k">SST collected</span><span>{{ $rm($sp['sst']) }}</span></div>
            <div class="tot"><span>Total charged to guests</span><span>{{ $rm($sp['gross']) }}</span></div>
            <div><span class="k">Channel / OTA fees</span><span>− {{ $rm($sp['fee']) }}</span></div>
            <div><span class="k">MOKA share ({{ rtrim(rtrim(number_format($sp['share_pct'], 2), '0'), '.') }}% of room)</span><span>− {{ $rm($sp['moka']) }}</span></div>
            <div class="net"><span>Owner's room revenue after fees</span><span>{{ $rm($sp['net']) }}</span></div>
        </div>
        <div class="ur-note">Indicative, before utilities and adjustments. The monthly statement from Owner reports is the final figure.</div>
    </div>
    <div class="ur-card">
        <h3>Nights &amp; RevPAR <small>{{ $x['month'] }}</small></h3>
        <div class="ur-big">{{ number_format($ng['sold']) }}<small>of {{ number_format($ng['available']) }} nights sold</small></div>
        <div class="ur-bar"><i style="width:{{ $ng['available'] > 0 ? min(100, $ng['sold'] / $ng['available'] * 100) : 0 }}%"></i></div>
        <div class="ur-rows" style="margin-top:10px">
            <div><span class="k">Nights available</span><span>{{ $selDate->daysInMonth }} days × {{ $x['units'] }} unit{{ $x['units'] > 1 ? 's' : '' }}</span></div>
            <div><span class="k">Nights unsold</span><span>{{ number_format(max(0, $ng['available'] - $ng['sold'])) }}</span></div>
            <div><span class="k">RevPAR</span><b>{{ $rm($ng['revpar']) }}</b></div>
            <div><span class="k">ADR</span><span>{{ $rm($avgDailyRate) }}</span></div>
        </div>
        <div class="ur-note">RevPAR = room revenue ÷ every night available, sold or not. It is what the unit earns per night in money, so a high ADR with low occupancy and a low ADR with high occupancy can be compared.</div>
    </div>
    <div class="ur-card">
        <h3>Same month last year <small>{{ $x['lastYear'] }}</small></h3>
        <table class="ur-table">
            <thead><tr><th></th><th class="num">{{ $x['lastYear'] }}</th><th class="num">{{ $selDate->format('M Y') }}</th><th class="num">Change</th></tr></thead>
            <tbody>
            @foreach($x['compare'] as $c)
            <tr><td>{{ $c['label'] }}</td><td class="num">{{ $fmt($c['then'], $c['kind']) }}</td><td class="num"><b>{{ $fmt($c['now'], $c['kind']) }}</b></td><td class="num">{!! $chg($c) !!}</td></tr>
            @endforeach
            </tbody>
        </table>
        <div class="ur-note">Same unit, same calendar month, measured the same way as the tiles above.</div>
    </div>
</div>

<div class="ur-h"><h2>Revenue by channel</h2><span>room charges, rows checking in {{ $x['month'] }}</span></div>
<div class="ur-card ur-wrap">
    <table class="ur-table">
        <thead><tr><th>Channel</th><th class="num">Bookings</th><th class="num">Nights</th><th class="num">ADR</th><th class="num">Room revenue</th><th class="num">Channel fee</th><th>Share of revenue</th></tr></thead>
        <tbody>
        @forelse($x['byChannel'] as $c)
        @php $col = $x['colours'][$c['channel']] ?? '#94a3b8'; @endphp
        <tr>
            <td><span class="ur-dot" style="background:{{ $col }}"></span>{{ $c['channel'] ?: 'Unknown' }}</td>
            <td class="num">{{ $c['bookings'] }}</td><td class="num">{{ $c['nights'] }}</td><td class="num">{{ $rm($c['adr']) }}</td>
            <td class="num"><b>{{ $rm($c['room']) }}</b></td><td class="num">{{ $c['fee'] > 0 ? $rm($c['fee']) : '—' }}</td>
            <td>{{ number_format($c['share'], 1) }}%<span class="ur-sharebar"><i style="width:{{ $c['share'] }}%;background:{{ $col }}"></i></span></td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;color:var(--text-secondary);padding:18px">No bookings checking in this month.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="ur-grid" style="grid-template-columns:1.4fr 1fr">
    <div class="ur-card"><h3>Room revenue, last 6 months <small>rows checking in each month</small></h3><div style="position:relative;height:220px"><canvas id="ur-rev"></canvas></div></div>
    <div class="ur-card"><h3>Channel share of revenue <small>{{ $x['month'] }}</small></h3><div style="position:relative;height:220px"><canvas id="ur-ch"></canvas></div></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    if (typeof Chart === 'undefined') return;
    var money = function(v){ return 'RM ' + Number(v).toLocaleString('en-MY', {maximumFractionDigits: 0}); };
    var t = @json($x['trend']);
    new Chart(document.getElementById('ur-rev'), { type: 'bar', data: { labels: t.map(function(r){ return r.label; }), datasets: [
        { label: 'Room', data: t.map(function(r){ return r.room; }), backgroundColor: '#0f766e', borderRadius: 6, maxBarThickness: 42 },
        { label: 'Cleaning', data: t.map(function(r){ return r.cleaning; }), backgroundColor: '#f59e0b', borderRadius: 6, maxBarThickness: 42 } ] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } }, tooltip: { callbacks: { label: function(c){ return c.dataset.label + ': ' + money(c.raw); } } } }, scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, ticks: { callback: money }, grid: { color: '#f1f5f9' } } } } });
    var ch = @json($x['byChannel']), colours = @json($x['colours']);
    new Chart(document.getElementById('ur-ch'), { type: 'doughnut', data: { labels: ch.map(function(c){ return c.channel || 'Unknown'; }), datasets: [{ data: ch.length ? ch.map(function(c){ return c.room; }) : [1], backgroundColor: ch.length ? ch.map(function(c){ return colours[c.channel] || '#94a3b8'; }) : ['#eceef1'], borderWidth: 2, borderColor: '#fff' }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'right', labels: { boxWidth: 10, font: { size: 11 } } }, tooltip: { callbacks: { label: function(c){ return c.label + ': ' + money(c.raw); } } } } } });
});
</script>
