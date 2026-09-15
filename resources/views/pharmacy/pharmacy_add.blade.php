@extends('layouts.master')

@section('title') Add Pharmacy @endsection

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
    padding: 2px 6px !important;
}
[type="date"]::-webkit-calendar-picker-indicator {
  color: transparent;
  opacity: 1;
  background: url(https://cdn1.iconfinder.com/data/icons/cc_mono_icon_set/blacks/16x16/calendar_2.png) no-repeat center;
  background-size: contain;
  filter: opacity(0.5) drop-shadow(0 0 0 blue);
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
                                    <form method="post" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="save" value="1">
                                        @if($alert!='') 
                                            <div class="alert alert-danger" role="alert">{{ $alert }}</div>
                                        @endif
                                        @if($errors->any())
                                            <div class="alert alert-danger" role="alert">
                                                <ul class="mb-0">
                                                    @foreach($errors->all() as $error)
                                                        <li>{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                        @if(in_array(Auth::user()->role, ['superadmin', 'admin', 'dispadmin']) && ($plans->isEmpty() || $areas->isEmpty()))
                                            <div class="alert alert-info" role="status">
                                                @if($plans->isEmpty())
                                                    A tariff plan is required. <a href="{{ url('/settings/plans/add') }}" target="_blank" rel="noopener">Create a tariff plan</a> with your delivery prices.
                                                @endif
                                                @if($areas->isEmpty())
                                                    For pricing by coverage area, <a href="{{ url('/settings/area/add') }}" target="_blank" rel="noopener">create a tariff area</a>. Area selection is optional on this form.
                                                @endif
                                                Setup opens in a new tab. After saving, reload this form to see the new options.
                                            </div>
                                        @endif
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Logo</label>
                                            <div class="col-sm-10" style="margin-bottom: 5px;">
                                                <div style="max-width: 200px;height: 200px;overflow: hidden;border-radius: 50%;">
                                                    <img style="max-width: inherit;width: 100%;position: relative;top: 50%;transform: translate(0, -50%)" id="user_img" src="{{ $input['image'] }}">
                                                </div> 
                                                <input class="form-control" type="file" name="image" onchange='encodeImageFileAsURL(this);' accept="image/x-png,image/jpeg,image/jpg">
                                                <small>Image cannot be larger than 2 mb</small>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Image Front</label>
                                            <div class="col-sm-10" style="margin-bottom: 5px;">
                                                <div style="max-width: 200px;height: 200px;">
                                                    <img style="max-width: inherit;width: 100%;position: relative;top: 50%;transform: translate(0, -50%)" id="image_front" src="{{ $input['image_front'] }}">
                                                </div> 
                                                <input class="form-control" type="file" name="image_front" onchange='encodeImageFileAsURL(this);' accept="image/x-png,image/jpeg,image/jpg">
                                                <small>Image cannot be larger than 2 mb</small>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Name</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="text" name="name" value="{{ old('name', $input['name']) }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Email</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="email" name="email" value="{{ old('email', $input['email']) }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Phone</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="text" name="phone" id="phone" value="{{ old('phone', $input['phone']) }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Address</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required id="searchTextField" type="text" name="address" value="{{ old('address', $input['address']) }}">
                                                @unless(config('app.googlemaps_apikey'))
                                                    <small class="form-text text-danger">Address lookup is unavailable. An administrator needs to configure Google Maps before this pharmacy can be saved.</small>
                                                @endunless
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Website</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="text" name="site" value="{{ old('site', $input['site']) }}">
                                            </div>
                                        </div>
                                        @if(in_array(Auth::user()->role, ['superadmin', 'admin', 'dispadmin']))
                                        <div class="form-group row">
                                            <label for="zone_id" class="col-sm-2 col-form-label">Admin Zone</label>
                                            <div class="col-sm-10">
                                                <select name="zone_id" id="zone_id" class="form-control" required>
                                                    <option value="">Not Selected</option>
                                                    @foreach($admin_areas as $admin_area)
                                                    <option value="{{$admin_area->id}}" @if($admin_area->id==old('zone_id', $input['zone_id'])){{'selected'}}@endif>{{$admin_area->name}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="plan_id" class="col-sm-2 col-form-label">Tariff Plan</label>
                                            <div class="col-sm-10">
                                                <select class="form-control" required name="plan_id" id="plan_id">
                                                    <option value="">Select a tariff plan</option>
                                                    @foreach($plans as $plan)
                                                    <option value="{{$plan->id}}" @if($plan->id == old('plan_id', $input['plan_id'] ?? '')) selected @endif>{{$plan->name}}, Monthly Order Rate: {{$plan->order_rate}}, Default Tariff: {{$plan->tariff}} $</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="areas" class="col-sm-2 col-form-label">Tariff Areas</label>
                                            <div class="col-sm-10">
                                                @php
                                                    $selectedTariffAreas = old('tariff_areas', $input['tariff_areas'] ?? []);
                                                    $selectedTariffAreas = is_array($selectedTariffAreas) ? $selectedTariffAreas : [];
                                                @endphp
                                                <select class="form-control" name="tariff_areas[]" multiple id="areas" @if($areas->isEmpty()) disabled @endif aria-describedby="tariff-areas-help">
                                                    @foreach($areas as $area)
                                                    <option value="{{$area->id}}" @if(in_array($area->id, $selectedTariffAreas)) selected @endif>{{$area->state}}, {{$area->name}}</option>
                                                    @endforeach
                                                </select>
                                                <small id="tariff-areas-help" class="form-text text-muted">
                                                    @if($areas->isEmpty())
                                                        No tariff areas exist yet. Create one using the setup link above.
                                                    @else
                                                        Select one or more areas covered by the default tariff. Other coverage tiers can be set after saving.
                                                    @endif
                                                </small>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Default Tariff</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="number" step="0.01" min="0.5" name="tariff" value="{{ old('tariff', $input['tariff']) }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Markup QuikMedix Driver - Next day delivery</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="number" step="0.01" min="0" name="tariff_next_day" value="{{ old('tariff_next_day', $input['tariff_next_day']) }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Markup QuikMedix Driver - Same day delivery</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="number" step="0.01" min="0" name="tariff_same_day" value="{{ old('tariff_same_day', $input['tariff_same_day']) }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Markup QuikMedix Driver - ASAP Delivery</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="number" step="0.01" min="0" name="tariff_asap" value="{{ old('tariff_asap', $input['tariff_asap']) }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Markup QuikMedix Driver - After Hours Delivery</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="number" step="0.01" min="0" name="tariff_after_hours" value="{{ old('tariff_after_hours', $input['tariff_after_hours']) }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Markup QuikMedix Driver - Delivery With Fridge</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="number" step="0.01" min="0" name="tariff_fridge" value="{{ old('tariff_fridge', $input['tariff_fridge']) }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Function</label>
                                            <div class="col-sm-10">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="1" name="massiveBagsTransfer" id="massiveBagsTransfer">
                                                    <label class="form-check-label" for="massiveBagsTransfer">On/Off Function massive bags transfer</label>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                        <button type="submit" style="margin-top:10px;" class="btn btn-primary">Save</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->
                    <script type='text/javascript'>
                    function encodeImageFileAsURL(element) {
                        if(element.files[0].size > 2097152){
                            alert("File is too big!");element.value = "";
                        }
                    }
                    </script>
                    @if(config('app.googlemaps_apikey'))
                    <script src="https://maps.googleapis.com/maps/api/js?key={{config('app.googlemaps_apikey')}}&region=US&language=en&libraries=places"></script>
                    <script>
                        var input = document.getElementById('searchTextField');
                        var autocomplete = new google.maps.places.Autocomplete(input);

                        input.addEventListener('input', function () {
                        this.dataset.originalVal = this.value;
                        });
                        input.addEventListener('focus', function () {
                        this.value = input.dataset.originalVal ? input.dataset.originalVal : this.value;
                        });
                    </script>
                    @endif
                    
@endsection

@section('footerScript')
<script src="{{ URL::asset('/js/select2.min.js')}}"></script>
<script>
    $('#areas').select2({ placeholder: 'Select tariff areas', width: '100%' });
</script>
@endsection
