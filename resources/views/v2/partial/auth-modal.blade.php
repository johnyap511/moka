{{-- MOKA v2 — Auth Modal --}}
<div class="modal-overlay" id="authModal" data-modal="auth" aria-hidden="true" role="dialog" aria-label="Log in or sign up">

    <div class="modal-card">

        <div class="modal-header">
            <img src="{{ asset('/new-theme23/images/logo.png') }}"
                 alt="MOKA"
                 class="modal-logo"
                 loading="lazy">
            <button class="modal-close" aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="auth-tabs" role="tablist">
            <button class="auth-tab active" data-tab="login" role="tab" aria-selected="true">Log In</button>
            <button class="auth-tab" data-tab="register" role="tab" aria-selected="false">Sign Up</button>
        </div>

        <div class="auth-panels">

            {{-- Login Panel --}}
            <div class="auth-panel active" data-panel="login" role="tabpanel">
                <p class="auth-panel-title">Welcome back</p>
                <p class="auth-panel-sub">Sign in to your MOKA owner account.</p>

                <form method="POST" action="{{ url('/login') }}" class="auth-form" novalidate>
                    @csrf

                    <div class="form-group">
                        <label class="form-label" for="loginEmail">Email address</label>
                        <input id="loginEmail"
                               class="form-input"
                               type="email"
                               name="email"
                               placeholder="you@example.com"
                               autocomplete="email"
                               required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="loginPassword">
                            Password
                            <a href="{{ url('/password/reset') }}" class="forgot-link" style="float:right; font-weight:500;">Forgot?</a>
                        </label>
                        <input id="loginPassword"
                               class="form-input"
                               type="password"
                               name="password"
                               placeholder="Your password"
                               autocomplete="current-password"
                               required>
                    </div>

                    <div style="display:flex; align-items:center; gap:var(--space-3); font-size:var(--text-sm); color:var(--gray-500);">
                        <input type="checkbox" id="remember" name="remember" style="width:16px;height:16px;accent-color:var(--teal);">
                        <label for="remember">Keep me signed in</label>
                    </div>

                    <button type="submit" class="btn btn-teal" style="width:100%; justify-content:center;">
                        Sign In
                    </button>
                </form>

                <div class="auth-divider" style="margin-block:var(--space-4);">or continue with</div>

                <div class="social-auth">
                    <button type="button" class="social-btn">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                        Continue with Google
                    </button>
                </div>

                <p class="auth-switch" style="margin-top:var(--space-5);">
                    Don't have an account?
                    <button data-switch-tab="register">Sign up</button>
                </p>
            </div>

            {{-- Register Panel --}}
            <div class="auth-panel" data-panel="register" role="tabpanel">
                <p class="auth-panel-title">Create account</p>
                <p class="auth-panel-sub">Join MOKA and start earning more from your property.</p>

                <form method="POST" action="{{ url('/register') }}" class="auth-form" novalidate>
                    @csrf

                    <div class="form-group">
                        <label class="form-label" for="regName">Full name</label>
                        <input id="regName"
                               class="form-input"
                               type="text"
                               name="name"
                               placeholder="Your full name"
                               autocomplete="name"
                               required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="regEmail">Email address</label>
                        <input id="regEmail"
                               class="form-input"
                               type="email"
                               name="email"
                               placeholder="you@example.com"
                               autocomplete="email"
                               required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="regPassword">Password</label>
                        <input id="regPassword"
                               class="form-input"
                               type="password"
                               name="password"
                               placeholder="At least 8 characters"
                               autocomplete="new-password"
                               required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="regPasswordConfirm">Confirm password</label>
                        <input id="regPasswordConfirm"
                               class="form-input"
                               type="password"
                               name="password_confirmation"
                               placeholder="Repeat your password"
                               autocomplete="new-password"
                               required>
                    </div>

                    <div style="display:flex; align-items:flex-start; gap:var(--space-3); font-size:var(--text-sm); color:var(--gray-500);">
                        <input type="checkbox" id="regTerms" required style="width:16px;height:16px;accent-color:var(--teal);flex-shrink:0;margin-top:2px;">
                        <label for="regTerms">
                            I agree to MOKA's
                            <a href="{{ url('/terms') }}" style="color:var(--teal);">Terms</a>
                            and
                            <a href="{{ url('/policy') }}" style="color:var(--teal);">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-teal" style="width:100%; justify-content:center;">
                        Create Account
                    </button>
                </form>

                <p class="auth-switch" style="margin-top:var(--space-5);">
                    Already have an account?
                    <button data-switch-tab="login">Log in</button>
                </p>
            </div>

        </div>
    </div>

</div>
