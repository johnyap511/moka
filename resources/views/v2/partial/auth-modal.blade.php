{{-- MOKA v2 — Auth Modal (Login & Sign Up) --}}

{{-- ══════════════════════════════════════════
     MAIN AUTH MODAL
══════════════════════════════════════════ --}}
<div class="modal-overlay"
     data-modal="auth"
     id="authModal"
     role="dialog"
     aria-modal="true"
     aria-labelledby="authModalTitle">
    <div class="modal-box auth-modal-box">

        {{-- Close Button --}}
        <button class="modal-close" aria-label="Close">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <path d="M18 6 6 18M6 6l12 12"/>
            </svg>
        </button>

        {{-- Modal Brand Mark --}}
        <div class="auth-brand">
            <img src="{{ asset('/new-theme23/images/logo.png') }}" alt="MOKA" width="56" height="34" loading="lazy">
        </div>

        {{-- Animated Title --}}
        <h2 class="auth-modal-title" id="authModalTitle">Welcome to MOKA</h2>
        <p class="auth-modal-subtitle">Malaysia's #1 short-stay property management</p>

        {{-- Tab Switcher --}}
        <div class="auth-tabs" role="tablist" aria-label="Authentication options">
            <button class="auth-tab active"
                    data-tab="login"
                    role="tab"
                    aria-selected="true"
                    aria-controls="auth-panel-login"
                    id="tab-login">
                Log In
            </button>
            <button class="auth-tab"
                    data-tab="signup"
                    role="tab"
                    aria-selected="false"
                    aria-controls="auth-panel-signup"
                    id="tab-signup">
                Sign Up
            </button>
            {{-- Sliding indicator --}}
            <span class="auth-tab-indicator" aria-hidden="true"></span>
        </div>

        {{-- Panels Wrapper --}}
        <div class="auth-panels-wrapper">

            {{-- ── LOGIN PANEL ─────────────────────────── --}}
            <div class="auth-panel active"
                 data-panel="login"
                 id="auth-panel-login"
                 role="tabpanel"
                 aria-labelledby="tab-login">

                {{-- Social Login --}}
                <div class="social-login-row">
                    <a href="{{ asset('/login/social/google') }}" class="social-btn social-btn--google">
                        <svg width="20" height="20" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                        Continue with Google
                    </a>
                    <a href="{{ asset('/login/social/facebook') }}" class="social-btn social-btn--facebook">
                        <svg width="20" height="20" fill="#1877F2" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        Continue with Facebook
                    </a>
                </div>

                <div class="auth-divider"><span>or sign in with email</span></div>

                <form action="{{ url('/login') }}" method="POST" class="auth-form" novalidate>
                    @csrf

                    <div class="auth-field">
                        <label class="form-label" for="loginEmail">Email address</label>
                        <input id="loginEmail"
                               class="form-input"
                               type="email"
                               name="email"
                               placeholder="you@example.com"
                               autocomplete="email"
                               required>
                    </div>

                    <div class="auth-field">
                        <div class="auth-field-header">
                            <label class="form-label" for="loginPassword">Password</label>
                            <a href="{{ url('/forget/password') }}" class="auth-forgot-link">Forgot password?</a>
                        </div>
                        <div class="password-field-wrap">
                            <input id="loginPassword"
                                   class="form-input"
                                   type="password"
                                   name="password"
                                   placeholder="Enter your password"
                                   autocomplete="current-password"
                                   required>
                            <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                                <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary auth-submit-btn">
                        Log In to MOKA
                    </button>
                </form>

                <p class="auth-switch-text">
                    Don't have an account?
                    <a data-switch-tab="signup" role="button" tabindex="0">Sign up free</a>
                </p>
            </div>

            {{-- ── SIGN UP PANEL ───────────────────────── --}}
            <div class="auth-panel"
                 data-panel="signup"
                 id="auth-panel-signup"
                 role="tabpanel"
                 aria-labelledby="tab-signup">

                <form action="{{ url('/register') }}" method="POST" class="auth-form" novalidate>
                    @csrf

                    <div class="auth-fields-row">
                        <div class="auth-field">
                            <label class="form-label" for="signupName">Full name</label>
                            <input id="signupName"
                                   class="form-input"
                                   type="text"
                                   name="name"
                                   placeholder="Your full name"
                                   autocomplete="name"
                                   required>
                        </div>

                        <div class="auth-field">
                            <label class="form-label" for="signupEmail">Email address</label>
                            <input id="signupEmail"
                                   class="form-input"
                                   type="email"
                                   name="email"
                                   placeholder="you@example.com"
                                   autocomplete="email"
                                   required>
                        </div>
                    </div>

                    <div class="auth-field">
                        <label class="form-label">Phone number</label>
                        <div class="phone-input-group">
                            <select class="phone-country-select" name="country_code" aria-label="Country code">
                                @include('auth.newTheme.partial.countryCodes')
                            </select>
                            <input type="tel"
                                   name="phone"
                                   class="form-input phone-number-input"
                                   placeholder="12 345 6789"
                                   autocomplete="tel">
                        </div>
                    </div>

                    <div class="auth-fields-row">
                        <div class="auth-field">
                            <label class="form-label" for="signupPassword">Password</label>
                            <div class="password-field-wrap">
                                <input id="signupPassword"
                                       class="form-input"
                                       type="password"
                                       name="password"
                                       placeholder="Create a strong password"
                                       autocomplete="new-password"
                                       required>
                                <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                                    <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="auth-field">
                            <label class="form-label" for="signupPasswordConfirm">Confirm password</label>
                            <input id="signupPasswordConfirm"
                                   class="form-input"
                                   type="password"
                                   name="password_confirmation"
                                   placeholder="Repeat your password"
                                   autocomplete="new-password"
                                   required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary auth-submit-btn">
                        Create Account
                    </button>
                </form>

                <p class="auth-terms-text">
                    By signing up, you agree to MOKA's
                    <a href="{{ url('/terms') }}">Terms of Service</a> and
                    <a href="{{ url('/policy') }}">Privacy Policy</a>.
                </p>

                <p class="auth-switch-text">
                    Already have an account?
                    <a data-switch-tab="login" role="button" tabindex="0">Log in</a>
                </p>
            </div>

        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════
     FORGOT PASSWORD MODAL
══════════════════════════════════════════ --}}
<div class="modal-overlay"
     data-modal="forgetPassword"
     id="forgetPasswordModal"
     role="dialog"
     aria-modal="true"
     aria-labelledby="forgetPasswordTitle">
    <div class="modal-box auth-modal-box">

        <button class="modal-close" aria-label="Close">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <path d="M18 6 6 18M6 6l12 12"/>
            </svg>
        </button>

        <div class="auth-brand">
            <div class="auth-reset-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
        </div>

        <h2 class="auth-modal-title" id="forgetPasswordTitle">Reset Password</h2>
        <p class="auth-modal-subtitle">Enter your email and we'll send you a reset link.</p>

        <div class="auth-panels-wrapper" style="padding-top:var(--space-6);">
            <form action="{{ url('/forget/password') }}" method="POST" class="auth-form" novalidate>
                @csrf
                <div class="auth-field">
                    <label class="form-label" for="forgetEmail">Email address</label>
                    <input id="forgetEmail"
                           class="form-input"
                           type="email"
                           name="email"
                           placeholder="you@example.com"
                           required>
                </div>
                <button type="submit" class="btn btn-primary auth-submit-btn">
                    Send Reset Link
                </button>
            </form>

            <p class="auth-switch-text" style="margin-top:var(--space-5);">
                Remembered your password?
                <a onclick="document.getElementById('forgetPasswordModal').classList.remove('open'); document.getElementById('authModal').classList.add('open');"
                   role="button"
                   tabindex="0"
                   style="cursor:pointer;">
                    Back to Log In
                </a>
            </p>
        </div>
    </div>
</div>
