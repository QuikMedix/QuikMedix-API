@extends('layouts.master')

@section('title') Faq @endsection

@section('headerCss')

@endsection

@section('content')
 <!-- start page title -->
                    <div class="row">

                   
                    </div>
                    <!-- end page title -->

                    <div class="row">
                        <div class="col-8">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Popular guides and tutorials</h4>
                                    @if(Auth::user()->role=="admin")
                                    <a href="/faq/add" class="addorder"><button class="btn btn-primary">Add FAQ</button></a>
                                    @endif
                                    <p class="card-title-desc">&nbsp;</p>
                                    <div id="accordion">
                                        @foreach ($faqs as $faq)
                                            <div class="card mb-1 shadow-none">
                                                <div class="card-header p-3" id="heading{{ $faq->id }}">
                                                    <h6 class="m-0 font-size-14">
                                                        <a href="#collapse{{ $faq->id }}" class="text-dark collapsed" data-toggle="collapse"
                                                                aria-expanded="false"
                                                                aria-controls="collapse{{ $faq->id }}">
                                                                {{ $faq->title }}
                                                        </a>
                                                    </h6>
                                                    @if(Auth::user()->role=="admin")
                                                    <form method="post" style="position:absolute;top:12px;right:12px;">
                                                        @csrf
                                                        <input type="hidden" name="faq_id" value="{{$faq->id}}">
                                                        <input type="hidden" name="remove" value="1">
                                                        <button class="btn btn-sm btn-danger" type="button" onclick="if(confirm('Are you sure?')){$(this).parent('form').submit();}">Remove</button>
                                                    </form>
                                                    @endif
                                                </div>
                                                <div id="collapse{{ $faq->id }}" class="collapse" aria-labelledby="heading{{ $faq->id }}" data-parent="#accordion">
                                                    <div class="card-body">
                                                        {!! $faq->text !!}
                                                    </div>
                                                </div>
                                            </div>   
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card">
                                <div class="card-body">
                                <img class="img-fluid" src="https://cp.a2brx.com/images/faq.jpg" alt="FAQ">
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->




                   
@endsection

@section('footerScript')

@endsection
