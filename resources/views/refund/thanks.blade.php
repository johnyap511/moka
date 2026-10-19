@extends('refund._shell')
@section('title', 'Request received')

@section('body')
<div class="card center">
    <div class="ok">✓</div>
    <h1>Thank you, your request is in.</h1>
    <p class="muted">We will return your security deposit within 3 working days.</p>
    @if(session('refs'))
    <div class="refbox"><b>Reference {{ implode(', ', array_map(fn ($i) => '#' . $i, session('refs')['ids'])) }}</b>@if(session('refs')['due']) · refund due by {{ session('refs')['due'] }}@endif
        @if(session('refs')['email'])<div>A receipt has been emailed to {{ session('refs')['email'] }}. No need to submit again.</div>@endif</div>
    @endif
    <div class="next">
        <div><span class="n">1</span><span>Our Finance team checks your stay and the deposit paid.</span></div>
        <div><span class="n">2</span><span>The refund is transferred to the bank account you gave us.</span></div>
        <div><span class="n">3</span><span>If anything needs confirming, we will WhatsApp or call the number you entered.</span></div>
    </div>
</div>
<div class="card center"><p class="muted" style="margin:0">Loved your stay? Follow us for offers on your next trip.</p></div>
@endsection
