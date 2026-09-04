@extends('v2.partial.layout')
@section('title', 'Contact Us — MOKA')
@section('meta_description', 'Get in touch with MOKA about hosting your property or a stay.')
@section('content')
<style>
  .contact-page{max-width:640px;margin:0 auto;padding:48px 20px 80px}
  .contact-page h1{font-size:30px;margin:0 0 6px}
  .contact-page .lede{color:#5f6b74;margin-bottom:24px}
  .contact-page label{display:block;font-weight:600;font-size:14px;margin:14px 0 6px}
  .contact-page input,.contact-page textarea{width:100%;padding:11px 12px;border:1px solid #d9dcd4;border-radius:8px;font:inherit;font-size:15px}
  .contact-page textarea{min-height:140px;resize:vertical}
  .contact-page button{margin-top:20px;padding:12px 24px;border:0;border-radius:8px;background:#003d3c;color:#fff;font-weight:600;font-size:15px;cursor:pointer}
  .contact-page .ok{background:#dff0e5;color:#2f7a4f;padding:12px 14px;border-radius:8px;margin-bottom:16px}
  .contact-page .err{color:#a83a3a;font-size:13px;margin-top:4px}
  .contact-page .alt{margin-top:28px;color:#5f6b74;font-size:14px}
</style>
<section class="contact-page">
  <h1>Contact us</h1>
  <p class="lede">Drop us a message if you have any enquiry. We reply within one working day.</p>
  @if(session('success'))<div class="ok">{{ session('success') }}</div>@endif
  <form method="POST" action="/contact">
    @csrf
    <label for="c-name">Name</label>
    <input id="c-name" name="name" value="{{ old('name') }}" required>
    @error('name')<div class="err">{{ $message }}</div>@enderror
    <label for="c-email">Email</label>
    <input id="c-email" type="email" name="email" value="{{ old('email') }}" required>
    @error('email')<div class="err">{{ $message }}</div>@enderror
    <label for="c-phone">Phone</label>
    <input id="c-phone" type="tel" name="phone" value="{{ old('phone') }}" required>
    @error('phone')<div class="err">{{ $message }}</div>@enderror
    <label for="c-message">Message</label>
    <textarea id="c-message" name="message" required>{{ old('message') }}</textarea>
    @error('message')<div class="err">{{ $message }}</div>@enderror
    <button type="submit">Send message</button>
  </form>
  <p class="alt">Or email hello@homemoka.com, or WhatsApp us from the Book Now page.</p>
</section>
@endsection
