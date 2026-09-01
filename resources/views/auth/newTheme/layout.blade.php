<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    @include('partials.seo')

    <link rel="shortcut icon" href="{{ asset('images/layout/fav.png') }}">
    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('images/layout/logo3.svg') }}">
    <link rel="icon" type="image/png" href="{{ asset('images/layout/logo3.svg') }}">

    <!-- <link rel="stylesheet" href="{{ asset('css/star-rating-svg.css') }}" type="text/css"> -->

    <link rel="stylesheet" href="{{ asset('new-theme23/owlcarousel/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/owlcarousel/owl.theme.default.min.css') }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/aos/aos.css') }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/fontawesome-free-6.3.0-web/css/fontawesome.css') }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/fontawesome-free-6.3.0-web/css/brands.css') }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/fontawesome-free-6.3.0-web/css/solid.css') }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/toastr/toastr.css') }}">

    <link rel="stylesheet" href="{{ asset('new-theme23/css/all23.css') }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/css/home23.css') }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/css/header23.css') }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/css/footer23.css') }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/css/registerLoginModal23.css') }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/css/service23.css') }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/css/about23.css') }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/css/ownerDashboard23.css') }}">



    {{-- Page-specific styles --}}
    @stack('styles')
</head>
<body>
<div class="site-wrap">

    @yield('content')

    @sectionMissing('hide-footer')
        @include('auth.newTheme.partials.footer')
    @endif
<script src="{{ asset('new-theme23/bootstrap/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('new-theme23/aos/aos.js') }}"></script>
<script>
    AOS.init({
        delay: 0, // values from 0 to 3000, with step 50ms
        duration: 1000, // values from 0 to 3000, with step 50ms
        easing: 'ease', // default easing for AOS animations
        once: true, // whether animation should happen only once - while scrolling down
    });
</script>
<script src="{{ asset('new-theme23/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('new-theme23/owlcarousel/owl.carousel.min.js') }}"></script>
<script src="{{ asset('plugins/toastr/toastr.min.js') }}"></script>
<script>
   $(function() {
        toastr.remove();
        toastr.options = {
            "timeOut": "10000",
            "closeButton": true,
            "onCloseClick": function() {
                //
            }
        };
            });
</script>
<script>
    
    // Bedrooms count
    let bedPlus = document.getElementById("bed_plus");
    let bedMinus = document.getElementById("bed_minus");
    let bed_count = document.getElementById("bed_count");
    bed_count_number = 1;
    bedPlus.addEventListener("click", ()=>{
        bed_count_number++
        bed_count.innerHTML = `${bed_count_number} Bedrooms`;
    });
    bedMinus.addEventListener("click", ()=>{
        if(bed_count_number > 1){
            bed_count_number--
            bed_count.innerHTML = `${bed_count_number} Bedrooms`;
        }
    });

    $(document).ready(function () { 
        $('.inc243n1').on('click', function () {
            // console.log(`${bed_count_number} Bedrooms`);
            $('#bedroomInput23').empty();
            $('#bedroomInput23').val(`${bed_count_number} Bedrooms`);
         });
      });

    // Smarter Management image
    let imageContainer = document.querySelector(".img-tab").children;
    function changeImage(index) {
        for (const i of imageContainer) {
            i.classList.add("d-none");            
        }
        imageContainer[index].classList.remove("d-none");
    }
    changeImage(0);
</script>

@stack('scripts')
</body>
</html>
