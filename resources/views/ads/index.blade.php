@extends('layouts.master')

@section('title') ADS @endsection

@section('headerCss')
<style>
.rep-menu a {
    font-size: 14px;
    line-height: 30px;
    color: black;
    display: block;
    margin: 10px 0;
    padding: 5px 0 5px 20px;

}
.rep-menu a:hover {
    background: #c90016;
    color: #ffffff !important;
}
.rep-menu i {
    font-size: 16px;
    margin: 0px 7px;
}

</style>
        

@endsection

@section('content')
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">ADS</h4>
                                    {{-- Banners are not built yet: there is nothing to submit to and nothing counts views or clicks. --}}
                                    <img src="{{ URL::asset('/images/c-soon.jpg')}}" alt="Coming soon" class="mr-0 pl-0 float-left" width="100%">
                                </div>
                            </div>
                        </div>
                    </div>
@endsection

@section('footerScript')

@endsection
