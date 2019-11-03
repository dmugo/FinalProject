@extends('site.app')
@section('title', 'Homepage')
@section('styles')

    <!-- jQuery -->
    <script src="{{asset('frontend/js/jquery-2.0.0.min.js')}}" type="text/javascript"></script>





    <!-- plugin: fancybox  -->
    <script src="{{asset('frontend/plugins/fancybox/fancybox.min.js')}}" type="text/javascript"></script>
    <link href="{{asset('frontend/plugins/fancybox/fancybox.min.css')}}" type="text/css" rel="stylesheet">

    <!-- plugin: owl carousel  -->
    <link href="{{asset('frontend/plugins/owlcarousel/assets/owl.carousel.min.css')}}" rel="stylesheet">
    <link href="{{asset('frontend/plugins/owlcarousel/assets/owl.theme.default.css')}}" rel="stylesheet">
    <script src="{{asset('frontend/plugins/owlcarousel/owl.carousel.min.js')}}"></script>
@endsection
@section('content')
    <!-- ========================= SECTION MAIN ========================= -->
    <section class="section-main bg padding-top-sm">
        <div class="container">

            <div class="row-sm">
                <div class="col-md-8">


                    <!-- ================= main slide ================= -->
                    <div class="owl-init slider-main owl-carousel" data-items="1" data-dots="false" data-nav="true">
                        <div class="item-slide">
                            <img src="{{asset('frontend/images/banners/lake.jpg')}}">
                        </div>
                        <div class="item-slide rounded">
                            <img src="{{asset('frontend/images/banners/floor-tiles.jpg')}}">
                        </div>
                        <div class="item-slide rounded">
                            <img src="{{asset('frontend/images/banners/kilimanjaro.jpg')}}">
                        </div>
                    </div>

                    <!-- ============== main slidesow .end // ============= -->

                </div> <!-- col.// -->
                <aside class="col-md-4">

                    <div class="card mb-3">
                        <figure class="itemside">
                            <div class="aside"><div class="img-wrap p-2 border-right"><img class="img-sm" src="{{asset('frontend/images/items/menu_kitchen_image.jpg')}}"></div></div>
                            <figcaption class="text-wrap align-self-center">
                                <h6 class="title">Group of products is here </h6>
                                <a href="#">More items</a>
                            </figcaption>
                        </figure>
                    </div> <!-- card.// -->

                    <div class="card mb-3">
                        <figure class="itemside">
                            <div class="aside"><div class="img-wrap p-2 border-right"><img class="img-sm" src="{{asset('frontend/images/items/installtaion-banner.jpeg')}}"></div></div>
                            <figcaption class="text-wrap align-self-center">
                                <h6 class="title">Group of products  is here </h6>
                                <a href="#">More items</a>
                            </figcaption>
                        </figure>
                    </div> <!-- card.// -->

                    <div class="card">
                        <figure class="itemside">
                            <div class="aside"><div class="img-wrap p-2 border-right"><img class="img-sm" src="{{asset('frontend/images/items/wall-tile.jpg')}}"></div></div>
                            <figcaption class="text-wrap align-self-center">
                                <h6 class="title">Group of products is here </h6>
                                <a href="#">More items</a>
                            </figcaption>
                        </figure>
                    </div> <!-- card.// -->

                </aside>
            </div>
        </div> <!-- container .//  -->
    </section>
    <!-- ========================= SECTION MAIN END// ========================= -->
    <!-- ========================= SECTION FEATURES ========================= -->
    <section id="features" class="section-features bg2 padding-y-lg">
        <div class="container">

            <header class="section-heading text-center">
                <h2 class="title-section">How it works </h2>
                <p class="lead"> Just a glimpse of how the system work </p>
            </header><!-- sect-heading -->

            <div class="row">
                <aside class="col-sm-4">
                    <figure class="itembox text-center">
                        <span class="icon-wrap icon-lg bg-secondary white"><i class="fa fa-shopping-bag"></i></span>
                        <figcaption class="text-wrap">
                            <h4 class="title">Add an interior Product to cart</h4>
                            <p>Select a product from one of our categories , view the details and add it to cart.</p>
                        </figcaption>
                    </figure> <!-- iconbox // -->
                </aside> <!-- col.// -->
                <aside class="col-sm-4">
                    <figure class="itembox text-center">
                        <span class="icon-wrap icon-lg bg-secondary  white"><i class="fa fa-dollar-sign"></i></span>
                        <figcaption class="text-wrap">
                            <h4 class="title">Checkout via M-Pesa</h4>
                            <p>Place an order by paying via M-Pesa from your mobile phone  </p>
                        </figcaption>
                    </figure> <!-- iconbox // -->
                </aside> <!-- col.// -->
                <aside class="col-sm-4">
                    <figure class="itembox text-center">
                        <span class="icon-wrap icon-lg bg-secondary  white"><i class="fa fa-car"></i>	</span>
                        <figcaption class="text-wrap">
                            <h4 class="title">Product delivered to doorstep</h4>
                            <p>Get the product delivered to doorsteps in good condition </p>
                        </figcaption>
                    </figure> <!-- iconbox // -->
                </aside> <!-- col.// -->
            </div> <!-- row.// -->



        </div><!-- container // -->
    </section>
@stop
