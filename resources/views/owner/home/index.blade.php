@extends('owner.layout')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
{{-- Production's own dashboard stylesheet, so the layout and proportions match
     rather than approximate it. --}}
<link rel="stylesheet" href="{{ asset('new-theme23/fontawesome-free-6.3.0-web/css/fontawesome.css') }}">
<link rel="stylesheet" href="{{ asset('new-theme23/fontawesome-free-6.3.0-web/css/solid.css') }}">
<link rel="stylesheet" href="{{ asset('new-theme23/css/ownerDashboard23.css') }}">
<style>
/* ownerDashboard23.css expects these from all23.css and bootstrap. Only the
   pieces the dashboard needs are defined, and they are scoped to .owner-db so
   the stylesheet cannot restyle the sidebar or the other owner pages. */
.owner-db {
    --orange: #ff6e00;
    --green: #004a49;
    --light_green: #5fc8ba;
}
.owner-db .progress {
    display: flex;
    overflow: hidden;
    background-color: #e9ecef;
    border-radius: 30px;
    height: 13px;
}
.owner-db .progress-bar { display: block; height: 100%; }

/* The three coloured tiles put the icon at the top and the figure beneath. */
.owner-db .calender,
.owner-db .revenue,
.owner-db .occupancy { justify-content: space-between; }
.owner-db .calender i,
.owner-db .revenue i,
.owner-db .occupancy i { color: #fff; font-size: 34px; }
.owner-db .calender h3,
.owner-db .revenue h3,
.owner-db .occupancy h3 { font-size: 30px; font-weight: 700; margin: 0; }
.owner-db .calender p,
.owner-db .revenue p,
.owner-db .occupancy p { font-size: 15px; margin: 0; }

.owner-db .price-digit h3 { font-size: 30px; font-weight: 700; margin: 0; }
.owner-db .price-digit .per-price { font-size: 14px; font-weight: 600; }
.owner-db .card-title {
    font-size: 13px; font-weight: 600; color: #333; margin: 0;
}
.owner-db .legend-dot {
    width: 9px; height: 9px; border-radius: 50%; display: inline-block; flex-shrink: 0;
}
.owner-db .other-listing-performance { column-gap: 8px; margin-bottom: 10px; }
.owner-db .other-listing-performance .olp-name { font-size: 12px; font-weight: 600; color: #333; }
.owner-db .other-listing-performance .olp-pct  { font-size: 12px; color: #666; }
.owner-db .filter-bar { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 18px; }
.owner-db .filter-bar select,
.owner-db .filter-bar input {
    padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; background: #fff;
}
.owner-db .filter-bar button {
    background: var(--green); color: #fff; border: 0; border-radius: 8px;
    padding: 11px 30px; font-weight: 600; font-size: 14px; cursor: pointer;
}
</style>
@endpush

@section('content')
<div class="owner-db">

<h2 class="owner-pannel-heding" style="margin-bottom:18px">Hello {{ Auth::user()->name }} !</h2>

<form method="GET" action="/owner/dashboard" class="filter-bar">
    <select name="listing_id" style="flex:1;min-width:220px;max-width:760px">
        @foreach($allListings as $l)
            <option value="{{ $l->id }}" {{ ($id == $l->id) ? 'selected' : '' }}>{{ $l->name }}</option>
        @endforeach
    </select>
    <input type="month" name="date" value="{{ $selDate->format('Y-m') }}" style="min-width:280px;flex:1;max-width:420px">
    <button type="submit">Update</button>
</form>

@php
    // Production's palette, so the charts and bars agree with the tiles.
    $palette = ['#ff6e00', '#5fc8ba', '#004a49', '#3b82f6', '#8b5cf6', '#f59e0b', '#64748b'];
@endphp

{{-- The grid is production's: three tiles and two donuts, then three figures
     beside the comparison panel, then the two trend charts. --}}
<div class="db-main">

    {{-- 1 --}}
    <div>
        <div class="calender">
            <i class="fa-solid fa-calendar-days"></i>
            <div>
                <h3>{{ $bookingCount }}</h3>
                <p>Booking</p>
            </div>
        </div>
    </div>

    {{-- 2 --}}
    <div>
        <div class="revenue">
            <i class="fa-solid fa-hand-holding-dollar"></i>
            <div>
                <h3>RM {{ number_format($monthRevenue, 2) }}</h3>
                <p>Revenue</p>
            </div>
        </div>
    </div>

    {{-- 3 --}}
    <div>
        <div class="occupancy">
            <i class="fa-solid fa-user-group"></i>
            <div>
                <h3>{{ $occupancy }}%</h3>
                <p>Occupancy</p>
            </div>
        </div>
    </div>

    {{-- 4 --}}
    <div>
        <div>
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px">
                <h6 class="card-title">Booking Sources</h6>
                <div style="display:flex;flex-direction:column;gap:3px;align-items:flex-end">
                    @php $si = 0; @endphp
                    @foreach($sourceBreakdown as $src => $data)
                        @if($data['count'] > 0)
                            <span style="display:flex;align-items:center;gap:5px;font-size:11px;color:#555">
                                <span class="legend-dot" style="background:{{ $palette[$si % count($palette)] }}"></span>{{ $src }}
                            </span>
                        @endif
                        @php $si++; @endphp
                    @endforeach
                </div>
            </div>
            <div style="height:calc(100% - 26px);display:flex;align-items:center;justify-content:center">
                <canvas id="sourceChart"></canvas>
            </div>
        </div>
    </div>

    {{-- 5 --}}
    <div>
        <div>
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px">
                <h6 class="card-title">Booking by Category</h6>
                <div style="display:flex;flex-direction:column;gap:3px;align-items:flex-end">
                    @php $ci = 0; @endphp
                    @foreach($categoryBreakdown as $cat => $data)
                        @if($data['count'] > 0)
                            <span style="display:flex;align-items:center;gap:5px;font-size:11px;color:#555">
                                <span class="legend-dot" style="background:{{ $palette[$ci % count($palette)] }}"></span>{{ $cat }}
                            </span>
                        @endif
                        @php $ci++; @endphp
                    @endforeach
                </div>
            </div>
            <div style="height:calc(100% - 26px);display:flex;align-items:center;justify-content:center">
                <canvas id="catChart"></canvas>
            </div>
        </div>
    </div>

    {{-- 6 --}}
    <div>
        <div class="price-digit">
            <h3>RM {{ number_format($accumulatedSales, 2) }}</h3>
            <p>Accumulated Sales<br>Based on {{ $selDate->year }}</p>
        </div>
    </div>

    {{-- 7 --}}
    <div>
        <div class="price-digit">
            <h3>RM {{ number_format($avgDailyRate, 2) }}</h3>
            <span class="per-price">Per day</span>
            <p>Average Daly Rate<br>(For The Month)</p>
        </div>
    </div>

    {{-- 8 --}}
    <div>
        <div class="price-digit">
            <h3>{{ $avgLengthOfStay }} Days</h3>
            <p>Average Length of Stay<br>Per Booking</p>
        </div>
    </div>

    {{-- 9 --}}
    <div>
        <div>
            <h6 class="card-title" style="margin-bottom:14px">Other Listing Performance and Comparison Analysis</h6>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 28px">
                <div>
                    @php $si2 = 0; @endphp
                    @foreach($sourceBreakdown as $src => $data)
                        <div class="other-listing-performance">
                            <div class="olp-name">{{ $src }}</div>
                            <div class="olp-pct">{{ $data['pct'] > 0 ? $data['pct'] . '%' : '%' }}</div>
                            <div class="progress progress-1">
                                <div class="progress-bar" style="width:{{ $data['pct'] }}%;background-color:{{ $palette[$si2 % count($palette)] }}"></div>
                            </div>
                        </div>
                        @php $si2++; @endphp
                    @endforeach
                </div>
                <div>
                    @php $ci2 = 0; @endphp
                    @foreach($categoryBreakdown as $cat => $data)
                        <div class="other-listing-performance">
                            <div class="olp-name">{{ $cat }}</div>
                            <div class="olp-pct">{{ $data['pct'] > 0 ? $data['pct'] . '%' : '%' }}</div>
                            <div class="progress progress-1">
                                <div class="progress-bar" style="width:{{ $data['pct'] }}%;background-color:{{ $palette[$ci2 % count($palette)] }}"></div>
                            </div>
                        </div>
                        @php $ci2++; @endphp
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- 10 --}}
    <div>
        <div>
            <h6 class="card-title">Average Monthly Rate</h6>
            <div style="height:calc(100% - 24px)"><canvas id="rateChart"></canvas></div>
        </div>
    </div>

    {{-- 11 --}}
    <div>
        <div>
            <h6 class="card-title">Occupancy Rate</h6>
            <div style="height:calc(100% - 24px)"><canvas id="occChart"></canvas></div>
        </div>
    </div>

</div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
    var graphLabels = {!! json_encode(array_column($graphArray, 0)) !!};
    var graphOcc    = {!! json_encode(array_column($graphArray, 1)) !!};
    var graphRate   = {!! json_encode(array_column($graphavg,  1)) !!};

    var palette = ['#ff6e00','#5fc8ba','#004a49','#3b82f6','#8b5cf6','#f59e0b','#64748b'];

    var srcLabels = {!! json_encode(array_keys($sourceBreakdown)) !!};
    var srcCounts = {!! json_encode(array_column(array_values($sourceBreakdown), 'count')) !!};
    var catLabels = {!! json_encode(array_keys($categoryBreakdown)) !!};
    var catCounts = {!! json_encode(array_column(array_values($categoryBreakdown), 'count')) !!};

    function donut(el, labels, counts) {
        var node = document.getElementById(el);
        if (!node) return;
        new Chart(node, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{ data: counts, backgroundColor: palette, borderWidth: 0 }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                cutout: '58%'
            }
        });
    }

    donut('sourceChart', srcLabels, srcCounts);
    donut('catChart', catLabels, catCounts);

    function line(el, data, colour, opts) {
        var node = document.getElementById(el);
        if (!node) return;
        new Chart(node, {
            type: 'line',
            data: {
                labels: graphLabels,
                datasets: [{
                    data: data,
                    borderColor: colour,
                    pointBackgroundColor: colour,
                    fill: false, tension: 0.3, pointRadius: 3, pointHoverRadius: 5, borderWidth: 2
                }]
            },
            options: Object.assign({
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }, opts)
        });
    }

    line('rateChart', graphRate, '#004a49', {
        scales: {
            y: { beginAtZero: false, grid: { color: '#f0f0f0' }, ticks: { font: { size: 11 } } },
            x: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    });

    line('occChart', graphOcc, '#ff6e00', {
        scales: {
            y: {
                beginAtZero: true, max: 100, grid: { color: '#f0f0f0' },
                ticks: { font: { size: 11 } }
            },
            x: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    });
})();
</script>
@endpush
