@extends('layouts.master')

@section('title') {{ $title }} @endsection

@section('content')
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card">
                                <div class="card-body">
                                    <x-flash-messages />
                                    @if ($errors->any())
                                        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
                                    @endif
                                    <h4 class="card-title">Dispatch zones</h4>
                                    <p class="text-muted">An order belongs to the zone its patient's address is in. Route offers are made one zone at a time; the prefix starts the name of each route, and the target stops and pay per stop are what a new offer starts from. A zone that is not in use takes no orders. Prices are not affected: those come from Area Tariff.</p>
                                    <form method="POST" action="/settings/dispatch-zones/import" enctype="multipart/form-data" class="form-inline mb-4">
                                        @csrf
                                        <input type="file" name="file" accept=".geojson,.json,application/geo+json,application/json" class="form-control-file mr-2" style="max-width: 320px;">
                                        <button type="submit" class="btn btn-primary btn-sm waves-effect">Load zones</button>
                                        <span class="text-muted small ml-2">Choose a GeoJSON file of zone outlines, or press Load zones without a file for the New York and New Jersey counties. Loading again updates the outlines and keeps the settings below.</span>
                                    </form>
                                    <div class="table-responsive">
                                        <table class="table table-striped mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Zone</th>
                                                    <th>State</th>
                                                    <th>Orders waiting</th>
                                                    <th>Route prefix</th>
                                                    <th>Target stops</th>
                                                    <th>Pay per stop</th>
                                                    <th>In use</th>
                                                    <th class="text-right">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($zones as $zone)
                                                <tr>
                                                    <td>{{ $zone->name }}@if($zone->county && $zone->county !== $zone->name) <span class="text-muted small">({{ $zone->county }})</span>@endif</td>
                                                    <td>{{ $zone->state }}</td>
                                                    <td>
                                                        @if(($waiting[$zone->id] ?? 0) > 0)
                                                            <a href="/route-offers?zone={{ $zone->id }}">{{ $waiting[$zone->id] }}</a>
                                                        @else
                                                            &mdash;
                                                        @endif
                                                    </td>
                                                    <td><input form="zone-{{ $zone->id }}" type="text" name="code" value="{{ $zone->code }}" maxlength="10" required class="form-control form-control-sm" style="width: 90px;"></td>
                                                    <td><input form="zone-{{ $zone->id }}" type="number" name="target_stops" value="{{ $zone->target_stops }}" min="1" max="500" required class="form-control form-control-sm" style="width: 90px;"></td>
                                                    <td><input form="zone-{{ $zone->id }}" type="number" name="pay_per_stop" value="{{ number_format((float) $zone->pay_per_stop, 2, '.', '') }}" min="0" step="0.01" required class="form-control form-control-sm" style="width: 100px;"></td>
                                                    <td><input form="zone-{{ $zone->id }}" type="checkbox" name="active" value="1" {{ $zone->active ? 'checked' : '' }}></td>
                                                    <td class="text-right">
                                                        <form method="POST" action="/settings/dispatch-zones/{{ $zone->id }}" id="zone-{{ $zone->id }}" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-success btn-sm waves-effect">Save</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="8" class="text-center text-muted">No zones yet. Press Load zones to add the New York and New Jersey counties.</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
@endsection
