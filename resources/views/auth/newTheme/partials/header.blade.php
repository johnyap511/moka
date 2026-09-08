        <header class="fix-header">
        <nav class="navbar navbar-expand-lg p-0" id="main_header">
            <div class="container-fluid main-header py-2 py-lg-3 px-3 px-lg-4">
                <div class="d-flex align-items-center justify-content-between flex-grow-1">
                    <div class="d-flex align-items-center">
                        <a href="/" class=" border-lg-none"><img src="{{ asset('new-theme23/images/logo.png') }}" alt="logo" class="logo-main"></a>
                        <button class="navbar-toggler border-start border-white ps-3 ms-3 ps-sm-4 ms-sm-4 py-2" style="border-radius:0 !important;" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation" onclick="menuExpand(this)">
                                <span class="font-white font-semi-bold fs_18 me-3">Menu</span>
                                <i class="fa-solid fa-chevron-up"></i>
                        </button>
                    </div>
                    <a href="/get/estimate" target="_blank" class="d-inline d-lg-none">
                        <button class="get-est-btn-sm">Get a quick estimate</button>
                    </a>
                </div>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav header-menu fs-5" id="navbarNav">
                        <li class="nav-item"><a href="https://staymoka.com/">Book now</a></li>
                        <li class="nav-item"><a href="/homepage">Why MOKA?</a></li>
                        <li class="nav-item"><a href="/service">Our Services</a></li>
                        <li class="nav-item"><a href="/solutions">Our Solutions</a></li>
                        <li class="nav-item"><a href="/designs">Our Designs</a></li>
                        <li class="nav-item"><a href="/about">About Us</a></li>
                        <li class="nav-item"><a href="/blog">Blog</a></li>
                        <!-- <li class="nav-item" data-bs-toggle="modal" data-bs-target="#signupModal">Sign Up</li> -->
                        <li class="nav-item nav-login">
                            <a href="/login"><i class="fa-solid fa-user d-lg-none"></i><span class="d-lg-none">Owner log in</span><span class="d-none d-lg-inline">Log In</span></a>
                        </li>
                        <li class="nav-item d-none d-lg-block">
                            <a href="/get/estimate" target="_blank" class="get-est-btn">Get a quick estimate (Free)</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
</header>
<script>
// Header behaviour for pages that use this header. Once scrolled, the header
// is painted brand green. The original pages (homepage, services, about,
// designs, estimate) carry their own scroll script that adds .bg-orange
// (!important), which wins over this, so those pages stay orange as before
// and the green pages (blog, solutions, contact) stay green.
(function () {
    var header = document.getElementById('main_header');
    if (!header || header.dataset.scrollBound) return;
    header.dataset.scrollBound = '1';
    function paint() {
        var on = window.scrollY > 5;
        header.style.backgroundColor = on ? '#004a49' : '';
        if (!header.classList.contains('bg-orange')) header.style.boxShadow = on ? '0px 0px 20px -3px rgba(0,0,0,0.1)' : 'none';
    }
    window.addEventListener('scroll', paint, { passive: true });
    paint();
})();
</script>
