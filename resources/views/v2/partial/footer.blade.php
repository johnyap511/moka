{{-- MOKA v2 — Footer --}}
<footer class="moka-footer">
    <div class="container">
        <div class="footer-grid">

            <!-- Brand -->
            <div class="footer-brand">
                <img src="{{ asset('/new-theme23/images/logo.png') }}" alt="MOKA" width="80">
                <p>Malaysia's leading short-stay property management company. Helping homeowners earn more — hassle free.</p>
                <div class="footer-social">
                    <a href="https://facebook.com/mokahomemy" class="social-icon" target="_blank" rel="noopener" aria-label="Facebook">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                    </a>
                    <a href="https://instagram.com/mokahomemy" class="social-icon" target="_blank" rel="noopener" aria-label="Instagram">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
                    </a>
                    <a href="https://www.linkedin.com/company/mokahomemy" class="social-icon" target="_blank" rel="noopener" aria-label="LinkedIn">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/></svg>
                    </a>
                    <a href="https://www.youtube.com/channel/UCO6k7qYO2JRv9ML48bKx_Rw" class="social-icon" target="_blank" rel="noopener" aria-label="YouTube">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46A2.78 2.78 0 0 0 1.46 6.42 29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58 2.78 2.78 0 0 0 1.95 1.96C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.96-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" fill="white"/></svg>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-col">
                <h4>Company</h4>
                <div class="footer-links">
                    <a href="{{ url('/homepage') }}">Why MOKA?</a>
                    <a href="{{ url('/service') }}">Our Services</a>
                    <a href="{{ url('/designs') }}">Our Designs</a>
                    <a href="{{ url('/about') }}">About Us</a>
                </div>
            </div>

            <!-- Hosts -->
            <div class="footer-col">
                <h4>Hosts</h4>
                <div class="footer-links">
                    <a href="{{ url('/get/estimate') }}" target="_blank">Get Free Estimate</a>
                    <button data-open-modal="auth">Host Log In</button>
                    <a href="{{ url('/policy') }}">Privacy Policy</a>
                    <a href="{{ url('/terms') }}">Terms of Service</a>
                </div>
            </div>

            <!-- Contact -->
            <div class="footer-col footer-contact">
                <h4>Contact Us</h4>
                <p>Menara Lien Hoe, Tropicana<br>47410 Petaling Jaya, Selangor</p>
                <p><a href="tel:60367892288">T: +603 6789 2288</a></p>
                <p><a href="mailto:hello@homemoka.com">E: hello@homemoka.com</a></p>
                <p style="margin-top:var(--space-4);">
                    <a href="https://wa.me/message/GJMYMABOT7CSG1"
                       target="_blank"
                       rel="noopener"
                       style="display:inline-flex; align-items:center; gap:8px; background:#25d366; color:#fff; font-weight:700; font-size:var(--text-sm); padding:10px 18px; border-radius:var(--radius-full); text-decoration:none; transition:all 0.2s;">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.119.553 4.107 1.522 5.834L0 24l6.335-1.502A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.891 0-3.667-.493-5.209-1.355l-.374-.222-3.755.89.949-3.658-.244-.389A9.939 9.939 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
                        Chat on WhatsApp
                    </a>
                </p>
            </div>
        </div>

        <div class="footer-bottom">
            <p class="footer-copyright">© {{ date('Y') }} MOKA. Owned and operated by Moka. All rights reserved.</p>
            <div class="footer-legal">
                <a href="{{ url('/terms') }}">Terms</a>
                <a href="{{ url('/policy') }}">Privacy</a>
            </div>
        </div>
    </div>
</footer>
