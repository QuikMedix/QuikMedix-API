@extends('layouts.master')

@section('title') Settings Admin Zones Add @endsection

@section('headerCss')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.select2-container--default .select2-selection--multiple .select2-selection__choice__display {
    padding-left: 15px;
    padding-right: 0px;
}
.select2-container .select2-selection--multiple .select2-search__field {
    color: rgb(51, 51, 51);
    font-family: inherit;
    font-size: inherit;
    margin-top: 9px;
    margin-left: 0px;
}
.select2-container .select2-selection--multiple .select2-selection__rendered {
    padding: 0 !important;
}
[type="date"]::-webkit-calendar-picker-indicator {
  color: transparent;
  opacity: 1;
  background: url(https://cdn1.iconfinder.com/data/icons/cc_mono_icon_set/blacks/16x16/calendar_2.png) no-repeat center;
  background-size: contain;
  filter: opacity(0.5) drop-shadow(0 0 0 blue);
}
.select2-container .select2-selection--multiple .select2-selection__choice {
    padding: 0 20px !important;
}
</style>
@endsection

@section('content')
 <!-- start page title -->
                    <div class="row">
                    
                    </div>
                    <!-- end page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    @if($alert!='') 
                                        <div class="alert alert-danger" role="alert">{{ $alert }}</div>
                                    @endif
                                    <form method="POST">
                                        @csrf
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Zone Name</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="text" name="name" value="{{ $admin_area->name }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="areas" class="col-sm-2 col-form-label">Tariff Areas</label>
                                            <div class="col-sm-10">
                                                <select class="form-control" name="tariff_areas[]" multiple id="areas">
                                                    @foreach($areas as $area)
                                                    @if(in_array($area->id,$admin_area_zones))
                                                    <option selected value="{{$area->id}}">{{$area->state}}, {{$area->name}}</option>
                                                    @else
                                                    <option value="{{$area->id}}">{{$area->state}}, {{$area->name}}</option>
                                                    @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-lg waves-effect waves-light">Save</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->

@endsection

@section('footerScript')
<script src="{{ URL::asset('/js/select2.min.js')}}"></script>
<script>
    $('#areas').select2({templateResult: hideSelect2Option});
    function hideSelect2Option(data, container) {
        if(data.element) {
            $(container).addClass($(data.element).attr("class"));
            $(container).attr('hidden',$(data.element).attr("hidden"));
        }
        return data.text;
    }
</script>
@endsection