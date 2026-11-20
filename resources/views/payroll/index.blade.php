@extends('layouts.master')

@section('title') Payroll @endsection

@section('content')
                    <div class="row">
                        <div class="col-xl-6 col-sm-6">
                                <div class="card mini-stat bg-primary">
                                    <div class="card-body mini-stat-img">
                                        <div class="mini-stat-icon">
                                            <i class="mdi mdi-car-multiple float-right"></i>
                                        </div>
                                        <div class="text-light">
                                            <h6 class="text-uppercase mb-3 font-size-16">Working drivers</h6>
                                            <h4 class="mb-2">{{ $drivers->count() }}</h4>
                                            <span>In this week</span>
                                        </div>
                                    </div>
                                </div>
                        </div>
                        <div class="col-xl-6 col-sm-6">
                                <div class="card mini-stat bg-primary" style="background-color: #242424 !important;">
                                    <div class="card-body mini-stat-img">
                                        <div class="mini-stat-icon">
                                            <i class="mdi mdi-map-marker-multiple float-right"></i>
                                        </div>
                                        <div class="text-light">
                                            <h6 class="text-uppercase mb-3 font-size-16">Completed stops</h6>
                                            <h4 class="mb-2">{{ $drivers->sum('stops') }}</h4>
                                            <span>In this week</span>
                                        </div>
                                    </div>
                                </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card">
                                <div class="card-body">
                                    <x-flash-messages />
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <a href="/payroll?week={{ $week->subWeek()->toDateString() }}" class="btn btn-primary btn-sm waves-effect"><i class="mdi mdi-chevron-left"></i> Previous week</a>
                                        <h4 class="card-title mb-0">{{ $week->format('m.d.Y') }} - {{ $week->addDays(6)->format('m.d.Y') }}</h4>
                                        <a href="/payroll?week={{ $week->addWeek()->toDateString() }}" class="btn btn-primary btn-sm waves-effect">Next week <i class="mdi mdi-chevron-right"></i></a>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-striped mb-0">
                                            <thead>
                                                <tr>
                                                    <th class="text-center">#</th>
                                                    <th>Driver</th>
                                                    <th>Stops</th>
                                                    <th>Total</th>
                                                    <th>Waiting for a rate</th>
                                                    <th>Status</th>
                                                    <th class="text-right">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($drivers as $driver)
                                                <tr>
                                                    <th scope="row" class="text-center">{{ $loop->iteration }}</th>
                                                    <td>{{ trim($driver->name.' '.$driver->last_name) ?: 'Driver #'.$driver->driver_id }}</td>
                                                    <td>{{ $driver->stops }}</td>
                                                    <td>${{ number_format((float) $driver->total, 2) }}</td>
                                                    <td>
                                                        @if($driver->awaiting_rate > 0)
                                                            {{ $driver->awaiting_rate }} {{ $driver->awaiting_rate == 1 ? 'stop' : 'stops' }}
                                                        @else
                                                            &mdash;
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($driver->unpaid_stops > 0)
                                                            <i class="mdi mdi-checkbox-blank-circle text-warning"></i> Not paid
                                                        @elseif($driver->awaiting_rate == $driver->stops)
                                                            <i class="mdi mdi-checkbox-blank-circle text-secondary"></i> No rate yet
                                                        @else
                                                            <i class="mdi mdi-checkbox-blank-circle text-success"></i> Paid
                                                        @endif
                                                    </td>
                                                    <td class="text-right">
                                                        @if($driver->awaiting_rate > 0)
                                                            {{-- Closing the route on the driver's page sets what it pays and shares it over these stops. --}}
                                                            <a href="/routes-list/driver/{{ $driver->driver_id }}" class="btn btn-warning btn-sm waves-effect">Set the rate <i class="mdi mdi-briefcase-edit"></i></a>
                                                        @endif
                                                        @if($driver->unpaid_stops > 0 || $driver->awaiting_rate < $driver->stops)
                                                        <form method="POST" action="/payroll" class="d-inline">
                                                            @csrf
                                                            <input type="hidden" name="driver_id" value="{{ $driver->driver_id }}">
                                                            <input type="hidden" name="week" value="{{ $week->toDateString() }}">
                                                            @if($driver->unpaid_stops > 0)
                                                                <input type="hidden" name="paid" value="1">
                                                                <button type="submit" class="btn btn-success btn-sm waves-effect">Mark paid <i class="mdi mdi-check"></i></button>
                                                            @else
                                                                <input type="hidden" name="paid" value="0">
                                                                <button type="submit" class="btn btn-outline-secondary btn-sm waves-effect">Mark not paid</button>
                                                            @endif
                                                        </form>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted">No driver completed a stop in this week.</td>
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
