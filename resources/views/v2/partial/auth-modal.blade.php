{{-- MOKA v2 — Auth Modal (Login & Sign Up) --}}

<div class="modal-overlay" data-modal="auth" id="authModal" role="dialog" aria-modal="true" aria-labelledby="authModalTitle">
    <div class="modal-box">

        <div class="modal-header">
            <h2 class="modal-title" id="authModalTitle">Welcome to MOKA</h2>
            <button class="modal-close" aria-label="Close">&times;</button>
        </div>

        <div class="modal-body">

            <!-- Tab Switcher -->
            <div class="auth-tabs" role="tablist">
                <button class="auth-tab active" data-tab="login"  role="tab" aria-selected="true">Log In</button>
                <button class="auth-tab"         data-tab="signup" role="tab" aria-selected="false">Sign Up</button>
            </div>

            <!-- ── LOGIN PANEL ───────────────────────── -->
            <div class="auth-panel active" data-panel="login">
                <form action="{{ url('/login') }}" method="POST" class="modal-form" novalidate>
                    @csrf

                    <div>
                        <label class="form-label" for="loginEmail">Email address</label>
                        <input id="loginEmail"
                               class="form-input"
                               type="email"
                               name="email"
                               placeholder="you@example.com"
                               autocomplete="email"
                               required>
                    </div>

                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--space-2);">
                            <label class="form-label" for="loginPassword" style="margin:0;">Password</label>
                            <a href="{{ url('/forget/password') }}"
                               style="font-size:var(--text-sm); color:var(--orange);">Forgot password?</a>
                        </div>
                        <input id="loginPassword"
                               class="form-input"
                               type="password"
                               name="password"
                               placeholder="Enter your password"
                               autocomplete="current-password"
                               required>
                    </div>

                    <button type="submit" class="btn btn-primary">Log In</button>
                </form>

                <div class="modal-divider" style="margin-block:var(--space-5);">or continue with</div>

                <div class="social-login-row">
                    <a href="{{ asset('/login/social/google') }}" class="social-btn">
                        <img src="{{ asset('/new-theme23/images/icon-google.png') }}" alt="Google" width="20">
                        Google
                    </a>
                    <a href="{{ asset('/login/social/facebook') }}" class="social-btn">
                        <img src="{{ asset('/new-theme23/images/icon-facebook.png') }}" alt="Facebook" width="20">
                        Facebook
                    </a>
                </div>

                <p class="modal-footer-text">
                    Don't have an account?
                    <a data-switch-tab="signup">Sign Up</a>
                </p>
            </div>

            <!-- ── SIGN UP PANEL ─────────────────────── -->
            <div class="auth-panel" data-panel="signup">
                <form action="{{ url('/register') }}" method="POST" class="modal-form" novalidate>
                    @csrf

                    <div>
                        <label class="form-label" for="signupName">Full name</label>
                        <input id="signupName"
                               class="form-input"
                               type="text"
                               name="name"
                               placeholder="Your full name"
                               autocomplete="name"
                               required>
                    </div>

                    <div>
                        <label class="form-label" for="signupEmail">Email address</label>
                        <input id="signupEmail"
                               class="form-input"
                               type="email"
                               name="email"
                               placeholder="you@example.com"
                               autocomplete="email"
                               required>
                    </div>

                    <div>
                        <label class="form-label">Phone number</label>
                        <div class="phone-input-group">
                            <select class="phone-country-select" name="country_code">
                                @include('auth.newTheme.partial.countryCodes')
                            </select>
                            <input type="tel"
                                   name="phone"
                                   placeholder="12 345 6789"
                                   autocomplete="tel">
                        </div>
                    </div>

                    <div>
                        <label class="form-label" for="signupPassword">Password</label>
                        <input id="signupPassword"
                               class="form-input"
                               type="password"
                               name="password"
                               placeholder="Create a strong password"
                               autocomplete="new-password"
                               required>
                    </div>

                    <div>
                        <label class="form-label" for="signupPasswordConfirm">Confirm password</label>
                        <input id="signupPasswordConfirm"
                               class="form-input"
                               type="password"
                               name="password_confirmation"
                               placeholder="Repeat your password"
                               autocomplete="new-password"
                               required>
                    </div>

                    <button type="submit" class="btn btn-primary">Create Account</button>
                </form>

                <p class="modal-footer-text">
                    Already have an account?
                    <a data-switch-tab="login">Log In</a>
                </p>

                <p style="font-size:var(--text-xs); color:var(--gray-400); text-align:center; margin-top:var(--space-4); line-height:1.6;">
                    By signing up, you agree to MOKA's
                    <a href="{{ url('/terms') }}" style="color:var(--orange);">Terms of Service</a>
                    and
                    <a href="{{ url('/policy') }}" style="color:var(--orange);">Privacy Policy</a>.
                </p>
            </div>

        </div>
    </div>
</div>

{{-- Forget Password Modal --}}
<div class="modal-overlay" data-modal="forgetPassword" id="forgetPasswordModal" role="dialog" aria-modal="true">
    <div class="modal-box">
        <div class="modal-header">
            <h2 class="modal-title">Reset Password</h2>
            <button class="modal-close" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <p style="color:var(--gray-500); font-size:var(--text-sm); margin-bottom:var(--space-5); line-height:1.65;">
                Enter your email address and we'll send you a link to reset your password.
            </p>
            <form action="{{ url('/forget/password') }}" method="POST" class="modal-form" novalidate>
                @csrf
                <div>
                    <label class="form-label" for="forgetEmail">Email address</label>
                    <input id="forgetEmail"
                           class="form-input"
                           type="email"
                           name="email"
                           placeholder="you@example.com"
                           required>
                </div>
                <button type="submit" class="btn btn-primary">Send Reset Link</button>
            </form>
            <p class="modal-footer-text" style="margin-top:var(--space-4);">
                Remembered it?
                <a onclick="document.getElementById('forgetPasswordModal').classList.remove('open'); document.getElementById('authModal').classList.add('open');"
                   style="cursor:pointer;">Back to Log In</a>
            </p>
        </div>
    </div>
</div>
