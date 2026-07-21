@extends('owner.layout')
@section('title', 'Dashboard')

@push('styles')
<style>
.stat-colored{border-radius:14px;padding:22px 24px;color:#fff;position:relative;overflow:hidden}
.stat-colored .s-icon{opacity:.5;margin-bottom:10px}
.stat-colored .s-val{font-size:32px;font-weight:700;line-height:1.1}
.stat-colored .s-lbl{font-size:14px;margin-top:6px;opacity:.9}
.metric-card{background:#fff;border-radius:14px;padding:22px;box-shadow:0 1px 4px rgba(0,0,0,.07);text-align:center}
.metric-card .m-val{font-size:22px;font-weight:700;color:#F36523;line-height:1.1}
.metric-card .m-sub{font-size:11px;color:#F36523;font-weight:600;margin-top:2px}
.metric-card .m-lbl{font-size:12px;color:#888;margin-top:6px;line-height:1.5}
.chart-card{background:#fff;border-radius:14px;padding:18px;box-shadow:0 1px 4px rgba(0,0,0,.07)}
.chart-card h4{font-size:13px;font-weight:600;margin-bottom:14px;color:#333}
.ota-row{display:flex;align-items:center;gap:8px;margin-bottom:10px;font-size:12px}
.ota-label{width:90px;color:#333;font-weight:500;font-size:11.5px}
.ota-bar-wrap{flex:1;height:8px;background:#f0f0f0;border-radius:4px;overflow:hidden}
.ota-bar{height:100%;border-radius:4px;transition:width .4s}
.ota-pct{width:38px;text-align:right;font-size:11.5px;font-weight:600;color:#555}
.filter-bar{background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.07);padding:14px 20px;display:flex;align-items:center;gap:12px;margin-bottom:22px;flex-wrap:wrap}
</style>
@endpush

@section('content')

{{-- Greeting --}}
<h2 style="font-size:22px;font-weight:700;color:#F36523;margin-bottom:18px">
    Hello {{ Auth::user()->name }}!
</h2>

{{-- Filter bar --}}
<form method="GET" action="/owner/dashboard" class="filter-bar">
    <select name="listing_id" class="form-select" style="flex:1;min-width:200px;max-width:420px">
        @foreach($allListings as $l)
            <option value="{{ $l->id }}" {{ ($id == $l->id) ? 'selected' : '' }}>{{ $l->name }}</option>
        @endforeach
    </select>
    <input type="month" name="date" class="form-input" value="{{ $selDate->format('Y-m') }}" style="width:160px">
    <button type="submit" class="btn" style="background:#0a5c4a;color:#fff;padding:9px 28px;font-weight:600;font-size:14px">Update</button>
</form>

{{-- Row 1: 3 colored stat cards --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:18px">
    {{-- Bookings (orange) --}}
    <div class="stat-colored" style="background:#F36523">
        <div class="s-icon">
            <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <div class="s-val">{{ $bookingCount }}</div>
        <div class="s-lbl">Booking{{ $bookingCount != 1 ? 's' : '' }}</div>
    </div>
    {{-- Revenue (teal) --}}
    <div class="stat-colored" style="background:#0d9d7c">
        <div class="s-icon">
            <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="s-val" style="font-size:26px">RM {{ number_format($monthRevenue, 2) }}</div>
        <div class="s-lbl">Revenue</div>
    </div>
    {{-- Occupancy (dark teal) --}}
    <div class="stat-colored" style="background:#0a5c4a">
        <div class="s-icon">
            <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        <div class="s-val">{{ $occupancy }}%</div>
        <div class="s-lbl">Occupancy</div>
    </div>
</div>

{{-- Row 2: 3 metrics + 2 donut charts --}}
<div style="display:grid;grid-template-columns:3fr 2fr;gap:16px;margin-bottom:18px">

    {{-- Left: 3 metric cards --}}
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
        <div class="metric-card">
            <div class="m-val">RM {{ number_format($accumulatedSales, 0) }}</div>
            <div class="m-lbl">Accumulated Sales<br>Based on {{ $selDate->year }}</div>
        </div>
        <div class="metric-card">
            <div class="m-val">RM {{ number_format($avgDailyRate, 1) }}</div>
            <div class="m-sub">Per day</div>
            <div class="m-lbl">Average Daily Rate<br>(For The Month)</div>
        </div>
        <div class="metric-card">
            <div class="m-val">{{ $avgLengthOfStay }} Days</div>
            <div class="m-lbl">Average Length of Stay<br>Per Booking</div>
        </div>
    </div>

    {{-- Right: 2 donut charts --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="chart-card" style="display:flex;flex-direction:column;align-items:center">
            <h4 style="align-self:flex-start">Booking Sources</h4>
            <canvas id="sourceChart" width="120" height="120"></canvas>
            <div style="margin-top:8px;width:100%">
                @php $srcColors = ['#F36523','#0d9d7c','#0a5c4a','#3b82f6','#8b5cf6','#f59e0b','#64748b']; $si=0; @endphp
                @foreach($sourceBreakdown as $src => $data)
                    @if($data['count'] > 0)
                    <div style="display:flex;align-items:center;gap:5px;font-size:11px;margin-bottom:2px">
                        <span style="width:8px;height:8px;border-radius:50%;background:{{ $srcColors[$si] ?? '#ccc' }};flex-shrink:0;display:inline-block"></span>
                        <span style="color:#555">{{ $src }}</span>
                    </div>
                    @php $si++; @endphp
                    @endif
                @endforeach
            </div>
        </div>
        <div class="chart-card" style="display:flex;flex-direction:column;align-items:center">
            <h4 style="align-self:flex-start">Booking by Category</h4>
            <canvas id="catChart" width="120" height="120"></canvas>
            <div style="margin-top:8px;width:100%">
                @php $catColors = ['#0d9d7c','#0a5c4a','#F36523','#3b82f6','#8b5cf6','#f59e0b']; $ci=0; @endphp
                @foreach($categoryBreakdown as $cat => $data)
                    @if($data['count'] > 0)
                    <div style="display:flex;align-items:center;gap:5px;font-size:11px;margin-bottom:2px">
                        <span style="width:8px;height:8px;border-radius:50%;background:{{ $catColors[$ci] ?? '#ccc' }};flex-shrink:0;display:inline-block"></span>
                        <span style="color:#555">{{ $cat }}</span>
                    </div>
                    @php $ci++; @endphp
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Row 3: OTA comparison --}}
<div class="chart-card" style="margin-bottom:18px">
    <h4 style="font-size:14px;margin-bottom:16px">Other Listing Performance and Comparison Analysis</h4>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
        {{-- Sources --}}
        <div>
            @php $srcColors2 = ['#F36523','#0d9d7c','#0a5c4a','#3b82f6','#8b5cf6','#f59e0b','#64748b']; $si2=0; @endphp
            @foreach($sourceBreakdown as $src => $data)
            <div class="ota-row">
                <div class="ota-label">{{ $src }}</div>
                <div class="ota-bar-wrap"><div class="ota-bar" style="width:{{ $data['pct'] }}%;background:{{ $srcColors2[$si2] ?? '#ccc' }}"></div></div>
                <div class="ota-pct">{{ $data['pct'] }}%</div>
            </div>
            @php $si2++; @endphp
            @endforeach
        </div>
        {{-- Categories --}}
        <div>
            @php $catColors2 = ['#0d9d7c','#0a5c4a','#F36523','#3b82f6','#8b5cf6','#f59e0b']; $ci2=0; @endphp
            @foreach($categoryBreakdown as $cat => $data)
            <div class="ota-row">
                <div class="ota-label">{{ $cat }}</div>
                <div class="ota-bar-wrap"><div class="ota-bar" style="width:{{ $data['pct'] }}%;background:{{ $catColors2[$ci2] ?? '#ccc' }}"></div></div>
                <div class="ota-pct">{{ $data['pct'] }}%</div>
            </div>
            @php $ci2++; @endphp
            @endforeach
        </div>
    </div>
</div>

{{-- Row 4: 2 line charts --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
    <div class="chart-card">
        <h4>Average Monthly Rate</h4>
        <canvas id="rateChart" height="80"></canvas>
    </div>
    <div class="chart-card">
        <h4>Occupancy Rate</h4>
        <canvas id="occChart" height="80"></canvas>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
    var graphLabels   = {!! json_encode(array_column($graphArray, 0)) !!};
    var graphOcc      = {!! json_encode(array_column($graphArray, 1)) !!};
    var graphRate     = {!! json_encode(array_column($graphavg,  1)) !!};

    var srcLabels = {!! json_encode(array_keys($sourceBreakdown)) !!};
    var srcCounts = {!! json_encode(array_column(array_values($sourceBreakdown), 'count')) !!};
    var srcColors = ['#F36523','#0d9d7c','#0a5c4a','#3b82f6','#8b5cf6','#f59e0b','#64748b'];

    var catLabels = {!! json_encode(array_keys($categoryBreakdown)) !!};
    var catCounts = {!! json_encode(array_column(array_values($categoryBreakdown), 'count')) !!};
    var catColors = ['#0d9d7c','#0a5c4a','#F36523','#3b82f6','#8b5cf6','#f59e0b'];

    // Source donut
    new Chart(document.getElementById('sourceChart'), {
        type: 'doughnut',
        data: { labels: srcLabels, datasets: [{ data: srcCounts, backgroundColor: srcColors, borderWidth: 2 }] },
        options: { plugins: { legend: { display: false } }, cutout: '65%' }
    });

    // Category donut
    new Chart(document.getElementById('catChart'), {
        type: 'doughnut',
        data: { labels: catLabels, datasets: [{ data: catCounts, backgroundColor: catColors, borderWidth: 2 }] },
        options: { plugins: { legend: { display: false } }, cutout: '65%' }
    });

    var lineDefaults = {
        fill: false,
        tension: 0.3,
        pointRadius: 3,
        pointHoverRadius: 5,
        borderWidth: 2
    };

    // Avg Monthly Rate chart
    new Chart(document.getElementById('rateChart'), {
        type: 'line',
        data: {
            labels: graphLabels,
            datasets: [Object.assign({}, lineDefaults, {
                label: 'Avg Rate (RM)',
                data: graphRate,
                borderColor: '#0a5c4a',
                pointBackgroundColor: '#0a5c4a'
            })]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: false, grid: { color: '#f0f0f0' }, ticks: { font: { size: 11 } } },
                x: { grid: { display: false }, ticks: { font: { size: 11 } } }
            }
        }
    });

    // Occupancy Rate chart
    new Chart(document.getElementById('occChart'), {
        type: 'line',
        data: {
            labels: graphLabels,
            datasets: [Object.assign({}, lineDefaults, {
                label: 'Occupancy %',
                data: graphOcc,
                borderColor: '#F36523',
                pointBackgroundColor: '#F36523'
            })]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, max: 100, grid: { color: '#f0f0f0' }, ticks: { font: { size: 11 }, callback: function(v){ return v+'%'; } } },
                x: { grid: { display: false }, ticks: { font: { size: 11 } } }
            }
        }
    });
})();
</script>
@endpush
