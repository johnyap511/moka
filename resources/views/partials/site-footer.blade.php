{{--
    The one site footer, used by both page templates (auth.newTheme and v2).
    Design is the original MOKA footer: deep green, Source Sans semibold caps,
    address and contact on the right, divider, logo and copyright with the
    social icons. Self-contained: its own font, colours and icons, so it looks
    identical whichever template includes it.
--}}
@php
    $solutions = config('solutions.pages', []);
@endphp
<style>
@font-face{font-family:"MokaFooterSemi";src:url(/new-theme23/fonts/SourceSansPro-SemiBold.woff2) format("woff2"),url(/new-theme23/fonts/SourceSansPro-SemiBold.ttf) format("truetype");font-display:swap}
@font-face{font-family:"MokaFooterReg";src:url(/new-theme23/fonts/SourceSansPro-Regular.woff2) format("woff2"),url(/new-theme23/fonts/SourceSansPro-Regular.ttf) format("truetype");font-display:swap}
.mk-footer{background:#004a49;color:#fff;font-family:"MokaFooterSemi","Source Sans Pro",-apple-system,"Segoe UI",sans-serif;padding:56px 24px 40px}
.mk-footer *{box-sizing:border-box}
.mk-footer a{color:#fff;text-decoration:none}
.mk-footer a:hover{color:#ff6b35}
.mk-footer .mk-in{max-width:1240px;margin:0 auto}
.mk-footer .mk-top{display:grid;grid-template-columns:1.1fr 1fr 1.2fr 1.4fr;gap:32px;padding:32px 0 44px;border-bottom:1px solid #fff}
.mk-footer ul{list-style:none;margin:0;padding:0}
.mk-footer li{margin:0 0 8px}
.mk-footer .mk-link{font-size:23px;line-height:1.2;text-transform:uppercase;letter-spacing:.01em;display:inline-block}
.mk-footer .mk-sub{font-size:19px;line-height:1.25;text-transform:none;display:inline-block}
.mk-footer .mk-col-title{font-size:23px;text-transform:uppercase;margin:0 0 8px}
.mk-footer .mk-contact{text-align:right;font-size:23px;line-height:1.35}
.mk-footer .mk-contact p{margin:0}
.mk-footer .mk-contact .mk-gap{height:22px}
.mk-footer .mk-bottom{display:flex;align-items:center;justify-content:space-between;gap:24px;padding-top:40px}
.mk-footer .mk-brand{display:flex;align-items:center;gap:18px}
.mk-footer .mk-brand img{height:56px;width:auto;display:block}
.mk-footer .mk-brand p{margin:0;font-family:"MokaFooterReg","Source Sans Pro",sans-serif;font-size:15px;line-height:1.5}
.mk-footer .mk-news label{display:block;font-size:13px;letter-spacing:.12em;margin:0 0 8px;color:rgba(255,255,255,.75)}
.mk-footer .mk-news-row{display:flex;max-width:340px}
.mk-footer .mk-news-row input{flex:1;min-width:0;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.25);color:#fff;padding:11px 14px;border-radius:8px 0 0 8px;font-family:"MokaFooterReg",sans-serif;font-size:15px}
.mk-footer .mk-news-row input::placeholder{color:rgba(255,255,255,.55)}
.mk-footer .mk-news-row button{background:#ff6b35;color:#fff;border:0;padding:0 18px;border-radius:0 8px 8px 0;font-size:20px;cursor:pointer}
.mk-footer .mk-social{display:flex;gap:25px}
.mk-footer .mk-social a{display:inline-flex;width:32px;height:32px;align-items:center;justify-content:center}
.mk-footer .mk-social svg{width:30px;height:30px;fill:#fff}
.mk-footer .mk-social a:hover svg{fill:#ff6b35}
@media (max-width:991px){.mk-footer .mk-top{grid-template-columns:1fr 1fr}.mk-footer .mk-contact{grid-column:1 / -1;text-align:left}}
@media (max-width:640px){.mk-footer{padding:40px 20px 110px}.mk-footer .mk-news-row{max-width:100%}.mk-footer .mk-top{grid-template-columns:1fr;gap:26px;padding:16px 0 32px}.mk-footer .mk-link,.mk-footer .mk-col-title,.mk-footer .mk-contact{font-size:20px}.mk-footer .mk-sub{font-size:17px}.mk-footer .mk-bottom{flex-direction:column;align-items:flex-start;padding-top:28px}}
</style>
<footer class="mk-footer">
    <div class="mk-in">
        <div class="mk-top">
            <div>
                <ul>
                    <li><a class="mk-link" href="/homepage">Why MOKA?</a></li>
                    <li><a class="mk-link" href="/service">Our Services</a></li>
                    <li><a class="mk-link" href="/designs">Our Designs</a></li>
                    <li><a class="mk-link" href="/about">About Us</a></li>
                    <li><a class="mk-link" href="/blog">Blog</a></li>
                </ul>
            </div>
            <div>
                <ul>
                    <li><a class="mk-link" href="/login" onclick="var m=document.getElementById('loninModal'); if (m && window.bootstrap) { event.preventDefault(); bootstrap.Modal.getOrCreateInstance(m).show(); }">Hosts Log In</a></li>
                    <li><a class="mk-link" href="/get/estimate">Get Estimate</a></li>
                    <li><a class="mk-link" href="/contact">Contact</a></li>
                    <li><a class="mk-link" href="https://wa.me/message/GJMYMABOT7CSG1" target="_blank" rel="noopener">WhatsApp</a></li>
                    <li><a class="mk-sub" href="/policy">Privacy Policy</a></li>
                    <li><a class="mk-sub" href="/terms">Terms of Service</a></li>
                </ul>
            </div>
            <div>
                <p class="mk-col-title">Solutions</p>
                <ul>
                    @foreach($solutions as $sp)
                        <li><a class="mk-sub" href="/solutions/{{ $sp['slug'] }}">{{ $sp['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="mk-contact">
                <p>Menara Lien Hoe, Tropicana, 47410</p>
                <p>Petaling Jaya, Selangor</p>
                <div class="mk-gap"></div>
                <p>CONTACT US</p>
                <p><a href="tel:60367892288">T: + 603 6789 2288</a></p>
                <p><a href="mailto:hello@homemoka.com">E: hello@homemoka.com</a></p>
                <p><a href="https://share.google/pLkG6DaCGgnjUA4mj" target="_blank" rel="noopener">Find us on Google</a></p>
            </div>
        </div>
        <div class="mk-bottom">
            <div class="mk-brand">
                <a href="/homepage" aria-label="MOKA home"><img src="{{ asset('new-theme23/images/logo.png') }}" alt="MOKA" loading="lazy"></a>
                <p>Malaysia's #1 short-stay property management company.<br>{{ date('Y') }} © Owned and operated by Moka. All rights reserved.</p>
            </div>
            <form class="mk-news" method="POST" action="/subscribe" aria-label="Newsletter">
                @csrf
                <label for="mk-news-email">STAY IN THE LOOP</label>
                <div class="mk-news-row"><input id="mk-news-email" type="email" name="email" placeholder="Your email address" required><button type="submit" aria-label="Subscribe">→</button></div>
            </form>
            <div class="mk-social">
                <a href="https://facebook.com/mokahomemy" target="_blank" rel="noopener" aria-label="Facebook"><svg viewBox="0 0 24 24"><path d="M13.5 22v-8h2.7l.4-3.2h-3.1V8.8c0-.9.3-1.6 1.6-1.6h1.7V4.4c-.3 0-1.3-.1-2.5-.1-2.5 0-4.1 1.5-4.1 4.2v2.3H7.4V14h2.8v8h3.3z"/></svg></a>
                <a href="https://instagram.com/mokahomemy" target="_blank" rel="noopener" aria-label="Instagram"><svg viewBox="0 0 24 24"><path d="M12 7.3a4.7 4.7 0 100 9.4 4.7 4.7 0 000-9.4zm0 7.7a3 3 0 110-6 3 3 0 010 6zm6-7.9a1.1 1.1 0 11-2.2 0 1.1 1.1 0 012.2 0zM21 8.1c-.1-1.5-.4-2.8-1.5-3.9S17 2.8 15.6 2.7C14.1 2.6 9.9 2.6 8.4 2.7 6.9 2.8 5.6 3.1 4.5 4.2S3.1 6.6 3 8.1c-.1 1.5-.1 5.7 0 7.2.1 1.5.4 2.8 1.5 3.9s2.4 1.4 3.9 1.5c1.5.1 5.7.1 7.2 0 1.5-.1 2.8-.4 3.9-1.5s1.4-2.4 1.5-3.9c.1-1.5.1-5.7 0-7.2zm-2 8.7a3 3 0 01-1.7 1.7c-1.2.5-4 .4-5.3.4s-4.1.1-5.3-.4a3 3 0 01-1.7-1.7c-.5-1.2-.4-4-.4-5.3s-.1-4.1.4-5.3A3 3 0 016.7 5.1c1.2-.5 4-.4 5.3-.4s4.1-.1 5.3.4a3 3 0 011.7 1.7c.5 1.2.4 4 .4 5.3s.1 4.1-.4 5.3z"/></svg></a>
                <a href="https://www.linkedin.com/company/mokahomemy" target="_blank" rel="noopener" aria-label="LinkedIn"><svg viewBox="0 0 24 24"><path d="M20.4 2H3.6A1.6 1.6 0 002 3.6v16.8A1.6 1.6 0 003.6 22h16.8a1.6 1.6 0 001.6-1.6V3.6A1.6 1.6 0 0020.4 2zM8 19H5V9.5h3V19zM6.5 8.2a1.7 1.7 0 110-3.4 1.7 1.7 0 010 3.4zM19 19h-3v-4.6c0-1.1 0-2.5-1.5-2.5S12.7 13 12.7 14.3V19h-3V9.5h2.9v1.3a3.2 3.2 0 012.8-1.5c3 0 3.6 2 3.6 4.6V19z"/></svg></a>
                <a href="https://www.youtube.com/channel/UCO6k7qYO2JRv9ML48bKx_Rw" target="_blank" rel="noopener" aria-label="YouTube"><svg viewBox="0 0 24 24"><path d="M22.5 7.2a2.7 2.7 0 00-1.9-1.9C18.9 4.8 12 4.8 12 4.8s-6.9 0-8.6.5A2.7 2.7 0 001.5 7.2 28 28 0 001 12a28 28 0 00.5 4.8 2.7 2.7 0 001.9 1.9c1.7.5 8.6.5 8.6.5s6.9 0 8.6-.5a2.7 2.7 0 001.9-1.9A28 28 0 0023 12a28 28 0 00-.5-4.8zM9.8 15V9l5.7 3-5.7 3z"/></svg></a>
            </div>
        </div>
    </div>
</footer>
