{{-- MOKA v2 — Footer --}}
<footer class="moka-footer" role="contentinfo">
    <div class="footer-main">
        <div class="container">
            <div class="footer-grid">

                {{-- Brand Column --}}
                <div class="footer-brand">
                    <a href="{{ url('/homepage') }}" aria-label="MOKA Home">
                        <img src="{{ asset('/new-theme23/images/logo.png') }}"
                             alt="MOKA"
                             width="88"
                             height="52"
                             loading="lazy">
                    </a>
                    <p class="footer-tagline">
                        Malaysia's #1 short-stay property management company. Helping homeowners earn more — effortlessly.
                    </p>
                    <div class="footer-social">
                        <a href="https://facebook.com/mokahomemy"
                           class="social-icon"
                           target="_blank"
                           rel="noopener noreferrer"
                           aria-label="Follow MOKA on Facebook">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                        </a>
                        <a href="https://instagram.com/mokahomemy"
                           class="social-icon"
                           target="_blank"
                           rel="noopener noreferrer"
                           aria-label="Follow MOKA on Instagram">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
                        </a>
                        <a href="https://www.linkedin.com/company/mokahomemy"
                           class="social-icon"
                           target="_blank"
                           rel="noopener noreferrer"
                           aria-label="Follow MOKA on LinkedIn">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/></svg>
                        </a>
                        <a href="https://www.youtube.com/channel/UCO6k7qYO2JRv9ML48bKx_Rw"
                           class="social-icon"
                           target="_blank"
                           rel="noopener noreferrer"
                           aria-label="Watch MOKA on YouTube">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46A2.78 2.78 0 0 0 1.46 6.42 29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58 2.78 2.78 0 0 0 1.95 1.96C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.96-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" fill="white"/></svg>
                        </a>
                    </div>
                </div>

                {{-- Company Links --}}
                <div class="footer-col">
                    <h4 class="footer-col-heading">Company</h4>
                    <nav class="footer-links" aria-label="Company links">
                        <a href="{{ url('/homepage') }}">Why MOKA?</a>
                        <a href="{{ url('/service') }}">Our Services</a>
                        <a href="{{ url('/designs') }}">Our Designs</a>
                        <a href="{{ url('/about') }}">About Us</a>
                        <a href="{{ url('/location/search') }}">Find a Property</a>
                    </nav>
                </div>

                {{-- Hosts Links --}}
                <div class="footer-col">
                    <h4 class="footer-col-heading">Homeowners</h4>
                    <nav class="footer-links" aria-label="Homeowner links">
                        <a href="{{ url('/get/estimate') }}" target="_blank" rel="noopener">Get Free Estimate</a>
                        <button type="button" data-open-modal="auth" class="footer-link-btn">Host Log In</button>
                        <a href="{{ url('/policy') }}">Privacy Policy</a>
                        <a href="{{ url('/terms') }}">Terms of Service</a>
                        <a href="https://wa.me/message/GJMYMABOT7CSG1" target="_blank" rel="noopener">Chat With Us</a>
                    </nav>
                </div>

                {{-- Contact + Newsletter --}}
                <div class="footer-col">
                    <h4 class="footer-col-heading">Get in Touch</h4>
                    <address class="footer-address">
                        <p>Menara Lien Hoe, Tropicana<br>47410 Petaling Jaya, Selangor</p>
                        <a href="tel:60367892288">+603 6789 2288</a>
                        <a href="mailto:hello@homemoka.com">hello@homemoka.com</a>
                    </address>

                    <div class="footer-newsletter">
                        <p class="footer-newsletter-label">Stay in the loop</p>
                        <form class="footer-newsletter-form" action="{{ url('/newsletter/subscribe') }}" method="POST" novalidate>
                            @csrf
                            <input type="email"
                                   name="email"
                                   placeholder="Your email address"
                                   class="footer-newsletter-input"
                                   required
                                   aria-label="Newsletter email address">
                            <button type="submit" class="footer-newsletter-btn" aria-label="Subscribe to newsletter">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Footer Bottom Bar --}}
    <div class="footer-bottom">
        <div class="container">
            <div class="footer-bottom-inner">
                <p class="footer-copyright">
                    &copy; {{ date('Y') }} MOKA. All rights reserved.
                </p>
                <div class="footer-legal-links">
                    <a href="{{ url('/terms') }}">Terms</a>
                    <span aria-hidden="true">·</span>
                    <a href="{{ url('/policy') }}">Privacy</a>
                    <span aria-hidden="true">·</span>
                    <a href="https://wa.me/message/GJMYMABOT7CSG1" target="_blank" rel="noopener">WhatsApp</a>
                </div>
            </div>
        </div>
    </div>
</footer>
