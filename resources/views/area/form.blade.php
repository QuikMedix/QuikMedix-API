@extends('layouts.master')

@section('title') {{$title}} @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="save" value="1">
                    @if($alert != '')
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
                    <div class="form-group row">
                        <label for="area-name" class="col-sm-2 col-form-label">Area name</label>
                        <div class="col-sm-10">
                            <input id="area-name" type="text" class="form-control" required name="name" value="{{ old('name', $area->name) }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="state" class="col-sm-2 col-form-label">State</label>
                        <div class="col-sm-10">
                            @php
                                $stateNames = $states_list->pluck('name');
                                if ($stateNames->isEmpty()) {
                                    $stateNames = collect(config('us_states'));
                                }
                            @endphp
                            <select class="form-control" required id="state" name="state">
                                <option value="">Select state</option>
                                @foreach($stateNames as $stateName)
                                    <option value="{{ $stateName }}" @if(old('state', $area->state) == $stateName) selected @endif>{{ $stateName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div data-tariff-area-editor>
                        <input type="hidden" name="polygon" id="polygon" value="{{ old('polygon', $polygon) }}">
                        <p>Click the map to add at least three corners, then choose <strong>Finish area</strong>. Drag the corners to adjust the boundary.</p>
                        <div class="mb-2" role="group" aria-label="Area drawing controls">
                            <button type="button" id="area-add-corners" class="btn btn-outline-primary" disabled>Add corners</button>
                            <button type="button" id="area-finish" class="btn btn-primary" disabled>Finish area</button>
                            <button type="button" id="area-undo" class="btn btn-outline-secondary" disabled>Undo corner</button>
                            <button type="button" id="area-clear" class="btn btn-outline-secondary" disabled>Clear area</button>
                        </div>
                        <p id="area-map-status" role="status" aria-live="polite">
                            @if(config('app.googlemaps_apikey'))
                                Loading map… If it does not appear, check the Google Maps configuration.
                            @else
                                The map is unavailable. Configure the Google Maps API key to draw a tariff area.
                            @endif
                        </p>
                        <div id="map" style="height: 600px; width: 100%;" aria-label="Tariff area map"></div>
                        @php
                            $mapAreas = collect($polygons)->map(function ($item) {
                                return ['name' => $item->name, 'points' => json_decode($item->polygon ?: '[]', true)];
                            })->values();
                        @endphp
                        <script type="application/json" id="tariff-area-data">@json($mapAreas)</script>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footerScript')
<script src="{{ URL::asset('/js/pages/tariff-area.init.js?v=1') }}"></script>
@if(config('app.googlemaps_apikey'))
<script async src="https://maps.googleapis.com/maps/api/js?key={{ config('app.googlemaps_apikey') }}&amp;region=US&amp;language=en&amp;v=weekly&amp;loading=async&amp;callback=initTariffAreaMap"></script>
@endif
@endsection
