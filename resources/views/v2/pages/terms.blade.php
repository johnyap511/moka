@extends('auth.newTheme.layout')
@section('seo_title', 'Terms of Use | MOKA')
@section('seo_description', 'The terms for using the MOKA website and owner portal: what the site is for, accounts, estimates, acceptable use, your legal rights and how to contact us.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('new-theme23/css/blog23.css') }}?v={{ filemtime(public_path('new-theme23/css/blog23.css')) }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/css/legal23.css') }}?v={{ filemtime(public_path('new-theme23/css/legal23.css')) }}">
@endpush

@section('content')
    @include('auth.newTheme.partials.header')

    <div class="blog-hero legal-hero">
        <div class="blog-hero__inner">
            <div class="blog-hero__eyebrow">Terms of use</div>
            <h1>Terms of Use</h1>
            <p class="blog-hero__meta">Applies to homemoka.com and the MOKA owner portal, operated by Moka Venture Sdn Bhd. Questions: <a href="mailto:hello@homemoka.com">hello@homemoka.com</a></p>
        </div>
    </div>

    <div class="legal-body">
        <div class="legal-layout">
            <article class="legal-article" id="legalArticle">
@if(now()->lt(\Carbon\Carbon::parse('2026-12-17')))
<div class="blog-callout"><p><strong>Updated on 16 November 2026.</strong> These Terms were rewritten to describe what this Website does today. Stays are no longer booked on this Website, so the booking and listing terms have been removed.</p></div>
@endif
<p><strong>Last updated: 16 November 2026</strong></p>
<p>These terms (&ldquo;<strong>Terms</strong>&rdquo;) apply to your use of homemoka.com and the MOKA owner portal (together, the &ldquo;<strong>Website</strong>&rdquo;). The Website is operated by Moka Venture Sdn. Bhd. (Registration No. 202001018827 (1375147-V)) (&ldquo;<strong>MOKA</strong>&rdquo;, &ldquo;<strong>we</strong>&rdquo;, &ldquo;<strong>us</strong>&rdquo;).</p>
<p>By using the Website you agree to these Terms and to our <a href="/policy">Personal Data Protection Notice</a>. If you do not agree, please do not use the Website.</p>

<h2>What this Website is for</h2>
<p>The Website gives information about our property management services and lets you:</p>
<ul>
<li>ask for an income estimate for your property, or send us an enquiry;</li>
<li>apply for a job with us;</li>
<li>sign in to the owner portal, if you are a property owner we manage for; and</li>
<li>submit a refund request, if we have sent you a link to do so.</li>
</ul>
<p><strong>Stays are not booked on this Website.</strong> Stays in the homes we manage are booked through staymoka.com, through third-party booking platforms, or directly with our team. Each booking is governed by the booking terms, house rules and cancellation policy shown to you when you book, and not by these Terms.</p>
<p><strong>Property owners.</strong> Our services to an owner, and the fees and payments between us, are governed by the management agreement signed with that owner. If these Terms and a signed agreement differ, the signed agreement applies.</p>

<h2>Who may use the Website</h2>
<p>You must be at least 18 years old and able to enter into a binding contract under Malaysian law. If you use the Website for a company or another person, you confirm that you are authorised to act for them.</p>

<h2>Owner portal accounts</h2>
<ul>
<li>The information you give us to open or maintain an account must be accurate, current and complete.</li>
<li>Keep your password confidential. You are responsible for activity carried out through your account, except where it results from our fault.</li>
<li>Tell us immediately if you suspect that someone else has used your account.</li>
<li>The owner portal shows booking and income information for your property for your convenience. Statements and payments are made under your management agreement.</li>
<li>We may suspend or close an account if the information given is false, if the account is misused, or if the management agreement has ended.</li>
</ul>

<h2>Estimates and information</h2>
<p>An income estimate is our indication of what a property may earn, based on bookings in comparable units. It is not a guarantee, a promise of income or a valuation, and actual results can be higher or lower. Articles and guides on the Website are general information and are not legal, tax or financial advice. Please take your own professional advice before making a decision.</p>

<h2>Enquiries, job applications and refund requests</h2>
<ul>
<li>Information you send us through the Website must be true and must be your own, or sent with the permission of the person it belongs to.</li>
<li>Sending an enquiry, an estimate request or a job application does not create a contract for services or employment.</li>
<li>A refund request is assessed under the cancellation policy and booking terms that applied to your booking. Where a refund is approved, we pay it to the account you give us within 14 working days of approval. Please check the account details carefully, because we cannot recover a payment sent to an account you entered wrongly.</li>
</ul>

<h2>Acceptable use</h2>
<p>You agree not to:</p>
<ul>
<li>use the Website for anything unlawful, fraudulent or harmful;</li>
<li>send content that is false, misleading, defamatory, obscene, threatening or discriminatory, or that infringes another person&rsquo;s rights;</li>
<li>impersonate another person, or misrepresent your connection with a person or company;</li>
<li>try to gain access to accounts, systems or data you are not authorised to use;</li>
<li>introduce viruses or other harmful code, or interfere with the operation or security of the Website; or</li>
<li>copy or collect content or data from the Website by automated means, or send automated or bulk submissions through our forms, without our written permission.</li>
</ul>

<h2>Intellectual property</h2>
<p>MOKA&reg; and the MOKA logo are registered trademarks of Moka Venture Sdn Bhd. They may not be reproduced, imitated or used in any name, listing, advertisement, website, social media account or messaging profile without our written permission. We act against impersonation: MOKA communicates only from @homemoka.com email addresses, our published telephone numbers and our verified social media accounts, and never asks for deposits or payments to personal bank accounts. Report suspected misuse to hello@homemoka.com.</p>
<p>All content on the Website, including text, photographs, graphics, layout and software, belongs to us or to our licensors and is protected by copyright and other intellectual property laws. You may view and print pages for your own personal, non-commercial use. You may not otherwise copy, modify, distribute or use the content without our written permission.</p>

<h2>Links and third-party services</h2>
<p>The Website links to websites and services operated by others, such as booking platforms, payment providers, chat and social media. We do not control them and are not responsible for their content, terms or handling of personal data. Your use of them is governed by their own terms.</p>

<h2>Availability of the Website</h2>
<p>We work to keep the Website available and its information accurate, but we do not promise that it will always be available, uninterrupted or free of errors. We may change, suspend or withdraw any part of the Website. You are responsible for protecting your own device, including by using up-to-date security software.</p>

<h2>Our responsibility to you</h2>
<p><strong>Your legal rights are not affected.</strong> Nothing in these Terms excludes or limits any guarantee, right or remedy that you have under the Consumer Protection Act 1999 or any other law of Malaysia that cannot lawfully be excluded or limited. Nothing in these Terms excludes or limits our liability for fraud, or for death or personal injury caused by our negligence.</p>
<p>Subject to the paragraph above, and to the extent the law permits:</p>
<ul>
<li>the Website and its content are provided for general information and on an &ldquo;as available&rdquo; basis;</li>
<li>we are not liable for loss of profit, loss of business or opportunity, loss of data, or any indirect or consequential loss arising from your use of, or inability to use, the Website;</li>
<li>we are not liable for loss caused by events outside our reasonable control, by a third-party website or service, or by your breach of these Terms; and</li>
<li>we are not liable for loss that was not a reasonably foreseeable result of our breach.</li>
</ul>
<p>Our responsibilities for a stay, or for property management services, are set out in the booking terms or the management agreement that applies, and not in these Terms.</p>

<h2>Your responsibility to us</h2>
<p>If you breach these Terms or use the Website unlawfully, and we suffer loss or face a claim from a third party as a result, you agree to compensate us for the loss and reasonable costs that your breach caused.</p>

<h2>Suspension and termination</h2>
<p>We may suspend or end your access to the Website if you breach these Terms or if we reasonably believe that your use puts the Website, other users or us at risk. Where it is reasonable to do so, we will tell you first.</p>

<h2>Events outside our control</h2>
<p>We are not liable for a delay or failure to perform that results from a cause outside our reasonable control, including natural disasters, floods, fire, epidemics or pandemics, war, terrorism, riots, acts or orders of government, strikes, and failures of power, telecommunications or third-party systems.</p>

<h2>Changes to these Terms</h2>
<p>We may update these Terms. The date at the top shows when they were last changed. For a material change, we will display a notice on this page for 30 days. A change does not affect rights and obligations that arose before it took effect. If you continue to use the Website after a change takes effect, the updated Terms apply to that use.</p>

<h2>General</h2>
<ul>
<li><strong>Notices.</strong> We may give you notice by email to the address you have given us, or by a notice on the Website. You may give us notice by email to hello@homemoka.com.</li>
<li><strong>Transfer.</strong> We may transfer our rights and obligations under these Terms to a successor to our business. You may not transfer yours without our written consent.</li>
<li><strong>No waiver.</strong> If we do not enforce a right at once, we may still enforce it later.</li>
<li><strong>Severability.</strong> If a court finds part of these Terms invalid or unenforceable, the rest continues to apply.</li>
<li><strong>Whole agreement.</strong> These Terms and the Personal Data Protection Notice are the whole agreement between you and us about your use of the Website. They do not replace a booking contract or a management agreement.</li>
</ul>

<h2>Governing law and disputes</h2>
<p>These Terms are governed by the laws of Malaysia, and the courts of Malaysia have jurisdiction over any dispute arising from them. This does not affect any right you have to bring a claim before the Tribunal for Consumer Claims Malaysia or another body that the law makes available to you.</p>
<p>If you use the Website from outside Malaysia, you are responsible for complying with the laws of the place you are in.</p>

<h2>Personal data</h2>
<p>We handle personal data as described in our <a href="/policy">Personal Data Protection Notice</a>.</p>

<h2>Contact us</h2>
<p>Moka Venture Sdn. Bhd. (Registration No. 202001018827 (1375147-V))<br>11.1, 11th Floor Menara Lien Hoe, 8, Persiaran Tropicana, 47410 Petaling Jaya, Selangor, Malaysia<br>Email: hello@homemoka.com<br>Telephone: +603 6789 2288, Monday to Friday, 9am to 6pm</p>
            </article>
            <aside class="legal-aside">
                <div class="legal-toc" id="legalToc" hidden>
                    <div class="legal-toc__title">On this page</div>
                    <ol></ol>
                </div>
                <div class="legal-card">
                    <b>Need to talk to a person?</b>
                    <p>Email <a href="mailto:hello@homemoka.com">hello@homemoka.com</a> or call <a href="tel:60367892288">+603 6789 2288</a>, Monday to Friday, 9am to 6pm.</p>
                </div>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var hs = document.querySelectorAll('#legalArticle h2'), toc = document.getElementById('legalToc'), ol = toc.querySelector('ol');
    if (hs.length < 3) return;
    hs.forEach(function (h, i) { h.id = h.id || 'section-' + (i + 1); var li = document.createElement('li'), a = document.createElement('a'); a.href = '#' + h.id; a.textContent = h.textContent; li.appendChild(a); ol.appendChild(li); });
    toc.hidden = false;
})();
</script>
@endpush
