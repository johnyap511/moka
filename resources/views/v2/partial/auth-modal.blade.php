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
