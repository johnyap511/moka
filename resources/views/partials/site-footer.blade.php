{{--
    The one site footer, used by both page templates (auth.newTheme and v2).
    Original MOKA footer language: deep green, Source Sans, orange accents,
    white rule, logo and social icons. Every colour is set explicitly so the
    page's own type rules cannot leak in.
--}}
@php
    $solutions = config('solutions.pages', []);
@endphp
<style>
@font-face{font-family:"MokaFooterSemi";src:url(/new-theme23/fonts/SourceSansPro-SemiBold.woff2) format("woff2"),url(/new-theme23/fonts/SourceSansPro-SemiBold.ttf) format("truetype");font-display:swap}
@font-face{font-family:"MokaFooterReg";src:url(/new-theme23/fonts/SourceSansPro-Regular.woff2) format("woff2"),url(/new-theme23/fonts/SourceSansPro-Regular.ttf) format("truetype");font-display:swap}
/* Original MOKA footer: green #004a49, Source Sans semibold, pure white text, brand orange #ff6e00 only on the one button. */
.mk-footer{background:#004a49;color:#fff;font-family:"MokaFooterSemi","Source Sans Pro",-apple-system,"Segoe UI",sans-serif;padding:72px 24px 48px;border:0;margin:0;line-height:1.35}
.mk-footer *{box-sizing:border-box}
.mk-footer p,.mk-footer li,.mk-footer a,.mk-footer label,.mk-footer span,.mk-footer h4{color:#fff;margin:0;padding:0;opacity:1}
.mk-footer a{text-decoration:none}
.mk-footer a:hover{text-decoration:underline;text-underline-offset:4px}
.mk-footer .mk-in{max-width:1240px;margin:0 auto}
.mk-footer .mk-top{display:grid;grid-template-columns:1fr 1fr 1.2fr 1.5fr;gap:32px;padding-bottom:56px;border-bottom:1px solid #fff}
.mk-footer h4{font-size:23px;text-transform:uppercase;font-weight:normal;margin:0 0 8px}
.mk-footer ul{list-style:none;margin:0;padding:0;display:grid;gap:8px}
.mk-footer .mk-primary a,.mk-footer .mk-list a{font-size:23px;text-transform:uppercase;line-height:1.2}
.mk-footer .mk-sub a{font-size:19px;text-transform:none;line-height:1.25}
.mk-footer .mk-contact{text-align:right}
.mk-footer .mk-contact p{font-size:23px;line-height:1.45}
.mk-footer .mk-contact .mk-addr{margin-bottom:28px}
.mk-footer .mk-bottom{display:grid;grid-template-columns:1.4fr 1.2fr auto;gap:32px;align-items:center;padding-top:44px}
.mk-footer .mk-brand{display:flex;align-items:center;gap:18px}
.mk-footer .mk-brand img{height:64px;width:auto;display:block}
.mk-footer .mk-brand p{font-family:"MokaFooterReg","Source Sans Pro",sans-serif;font-size:15px;line-height:1.5}
.mk-footer .mk-news label{display:block;font-size:15px;text-transform:uppercase;margin:0 0 8px}
.mk-footer .mk-news-row{display:flex;height:46px;max-width:360px;border-radius:100px;overflow:hidden;border:1px solid #fff}
.mk-footer .mk-news-row input{flex:1;min-width:0;height:100%;background:transparent;border:0;outline:0;color:#fff;padding:0 18px;font-family:"MokaFooterReg",sans-serif;font-size:15px}
.mk-footer .mk-news-row input::placeholder{color:#fff}
.mk-footer .mk-news-row button{height:100%;background:#ff6e00;color:#fff;border:0;padding:0 22px;font-family:"MokaFooterSemi",sans-serif;font-size:15px;cursor:pointer;white-space:nowrap}
.mk-footer .mk-news-row button:hover{background:#e56300}
.mk-footer .mk-social{display:flex;gap:25px;justify-content:flex-end}
.mk-footer .mk-social a{display:inline-flex;align-items:center;justify-content:center}
.mk-footer .mk-social svg{width:30px;height:30px;fill:#fff}
@media (max-width:1024px){.mk-footer .mk-top{grid-template-columns:1fr 1fr;gap:36px 32px}.mk-footer .mk-contact{grid-column:1 / -1;text-align:left}.mk-footer .mk-bottom{grid-template-columns:1fr 1fr}.mk-footer .mk-social{grid-column:1 / -1;justify-content:flex-start}}
@media (max-width:640px){.mk-footer{padding:44px 20px 110px}.mk-footer .mk-top{grid-template-columns:1fr;gap:30px;padding-bottom:36px}.mk-footer .mk-bottom{grid-template-columns:1fr;gap:26px;padding-top:26px}.mk-footer .mk-news-row{max-width:100%}.mk-footer h4,.mk-footer .mk-primary a,.mk-footer .mk-list a,.mk-footer .mk-contact p{font-size:20px}.mk-footer .mk-sub a{font-size:17px}}
</style>
<footer class="mk-footer">
    <div class="mk-in">
        <div class="mk-top">
            <div>
                <ul class="mk-primary">
                    <li><a href="/homepage">Why MOKA?</a></li>
                    <li><a href="/service">Our Services</a></li>
                    <li><a href="/designs">Our Designs</a></li>
                    <li><a href="/about">About Us</a></li>
                    <li><a href="/blog">Blog</a></li>
                </ul>
            </div>
            <div>
                <ul class="mk-list">
                    <li><a href="/login" onclick="var m=document.getElementById('loninModal'); if (m && window.bootstrap) { event.preventDefault(); bootstrap.Modal.getOrCreateInstance(m).show(); }">Hosts log in</a></li>
                    <li><a href="/get/estimate">Get estimate</a></li>
                    <li><a href="/contact">Contact</a></li>
                    <li><a href="https://wa.me/message/GJMYMABOT7CSG1" target="_blank" rel="noopener">WhatsApp</a></li>
                    <li><a href="/policy">Privacy policy</a></li>
                    <li><a href="/terms">Terms of service</a></li>
                </ul>
            </div>
            <div>
                <h4>Solutions</h4>
                <ul class="mk-sub">
                    @foreach($solutions as $sp)
                        <li><a href="/solutions/{{ $sp['slug'] }}">{{ $sp['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="mk-contact">
                <p class="mk-addr">Menara Lien Hoe, Tropicana, 47410<br>Petaling Jaya, Selangor</p>
                <p>CONTACT US</p>
                <p><a href="tel:60367892288">T: + 603 6789 2288</a></p>
                <p><a href="mailto:hello@homemoka.com">E: hello@homemoka.com</a></p>
                <p><a href="https://share.google/pLkG6DaCGgnjUA4mj" target="_blank" rel="noopener">Find us on Google</a></p>
            </div>
        </div>
        <div class="mk-bottom">
            <div class="mk-brand">
                <a href="/homepage" aria-label="MOKA home"><img src="{{ asset('new-theme23/images/logo.png') }}" alt="MOKA" loading="lazy"></a>
                <p>{{ date('Y') }} © Owned and operated<br>by Moka.<br>All rights reserved.</p>
            </div>
            <form class="mk-news" method="POST" action="/subscribe" aria-label="Newsletter">
                @csrf
                <label for="mk-news-email">Stay in the loop</label>
                <div class="mk-news-row"><input id="mk-news-email" type="email" name="email" placeholder="Your email address" required><button type="submit">Subscribe</button></div>
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
