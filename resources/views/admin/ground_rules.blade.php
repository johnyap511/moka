@extends('admin.layout')
@section('title', 'Ground rules')
@section('content')
<div class="card" style="max-width:960px">
    <div class="card-body rules-doc">
        {!! \Illuminate\Support\Str::markdown(file_get_contents(base_path('docs/GROUND-RULES.md'))) !!}
    </div>
</div>
<style>.rules-doc h1{font-size:1.4rem}.rules-doc h2{font-size:1.1rem;margin-top:1.4rem}.rules-doc ol{padding-left:1.2rem}.rules-doc li{margin:.35rem 0;line-height:1.5}</style>
@endsection
