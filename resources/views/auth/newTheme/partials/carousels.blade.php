{{-- Portfolio and client-review carousels (About, Designs). Same options as the original site's inline script. --}}
<script>
    if (window.jQuery && document.getElementById('portfolio_carousel')) {
        $('#portfolio_carousel').owlCarousel({
            loop: true,
            margin: 30,
            nav: true,
            navText: ["<img src='/new-theme23/images/Asset 26.png' alt='prev'>", "<img src='/new-theme23/images/Asset 27.png' alt='next'>"],
            navContainer: '#portfolio_carousel_nav',
            dots: false,
            responsive: { 0: { items: 1 }, 768: { items: 2 }, 992: { items: 3 }, 1200: { items: 4 } }
        });
    }
    if (window.jQuery && document.getElementById('client_review_carousel')) {
        $('#client_review_carousel').owlCarousel({
            center: true,
            items: 3,
            loop: true,
            margin: 5,
            nav: true,
            navText: ["<div class='previous-btn'><i class='fa-solid fa-chevron-left'></i></div>", "<div class='next-btn'><i class='fa-solid fa-chevron-right'></i></div>"],
            navContainer: '#client_review_carousel_nav',
            dots: true,
            dotsContainer: '#client_review_carousel_dot',
            responsive: { 0: { items: 1 }, 992: { items: 3 } }
        });
    }
</script>
