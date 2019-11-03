@extends('site.app')
@section('title', 'Order Completed')
@section('content')
    <section class="section-pagetop bg-dark">
        <div class="container clearfix">
            <h2 class="title-page">Request cancelled</h2>
        </div>
    </section>
    <section class="section-content bg padding-y border-top">
        <div class="container">
            <div class="row">
                <main class="col-sm-12">
                    <p class="alert alert-success">This could be caused by :<br>
                        cancelled request <br>
                    insufficient funds<br>
                        system initiated request
                    </p></main>
            </div>
        </div>
    </section>
@stop
