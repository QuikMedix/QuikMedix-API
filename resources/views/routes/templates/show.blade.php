@extends('layouts.master')

@section('title') {{ $title }} @endsection
@section('headerCss')
<link rel="stylesheet" href="{{ URL::asset('/css/bootstrap-multiselect.min.css')}}" type="text/css"/>
<link rel="stylesheet" href="{{ URL::asset('/css/bootstrap-lib.min.css')}}">
<link href="{{ URL::asset('/css/bootstrap-datepicker.min.css')}}" rel="stylesheet">
<link href="{{ URL::asset('/css/select2.min.css')}}" rel="stylesheet" type="text/css">
<link href="{{ URL::asset('/css/jquery.bootstrap-touchspin.min.css')}}" rel="stylesheet">
<style>
/* ... Styles from driver.blade.php ... */
.wrapper {
    display: flex;
    flex-wrap: wrap;
    max-height: 400px; /* Increased height */
    overflow-y: auto;
    overflow-x: hidden;
    width: 100%;
    min-height: 100px; /* Reduced min-height to fit content better initially */
    border: 1px dashed #ccc;
    padding: 10px;
    align-content: flex-start; /* Align items to start to avoid spacing issues */
}
.wrapper.active {
    border: 2px dashed #28c8e2;
}

/* Blocks for Template Items */
.template_block {
    cursor: move;
    width: 200px;
    margin: 5px;
    padding: 10px;
    border-radius: 5px;
    color: white !important; /* Force white text */
    position: relative;
    display: inline-block;
    vertical-align: top;
    font-weight: bold;
    height: auto; /* Allow auto height */
    min-height: 50px;
}
.pharmacy_bg {
    background-color: #19b313 !important; /* Green */
    border: 1px solid #0f7a0b;
}
.office_bg {
    background-color: #f3b567 !important; /* Orange */
    border: 1px solid #d99a4b;
}
.patient_bg {
    background-color: #464086 !important; /* Blue */
    border: 1px solid #302c5e;
}

.btn-close-white {
    filter: invert(1) grayscale(100%) brightness(200%); /* Ensure close button is visible on colored background */
    z-index: 1001; /* Ensure clicks are intercepted */
}

.trash_block {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 30px;
    border: 4px dashed red;
    border-radius: 15px;
    opacity: 0.5;
    width: 150px;
    height: 64px;
    text-align: center;
    padding: 7px;
    z-index: 1000;
}
.trash_block.active {
    display: block;
}

/* Draggable Source List styles */
.source-list {
    max-height: 600px;
    overflow-y: auto;
}
.draggable-source {
    cursor: move;
    margin-bottom: 5px;
    padding: 5px;
    border: 1px solid #ccc;
    border-radius: 3px;
    background-color: #f8f9fa;
}

/* Select2 Font Size Adjustment */
.select2-container .select2-selection--single .select2-selection__rendered {
    font-size: 0.85rem !important;
}
.select2-results__option {
    font-size: 0.85rem !important;
}
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        @if(\Session::has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {!! \Session::get('success') !!}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(\Session::has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {!! \Session::get('error') !!}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">{{ $title }}</h4>
            <div class="page-title-right">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignDriverModal">Assign to Driver</button>
                <button type="button" class="btn btn-success" id="save-template">Save Template</button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column: Source Items -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Available Points</h5>
                <input type="text" id="search-points" class="form-control mb-2" placeholder="Search...">
                
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pharmacies-tab" data-bs-toggle="tab" data-bs-target="#pharmacies" type="button" role="tab">Pharmacies</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="offices-tab" data-bs-toggle="tab" data-bs-target="#offices" type="button" role="tab">Offices</button>
                    </li>
                </ul>
                <div class="tab-content source-list" id="myTabContent">
                    <div class="tab-pane fade show active" id="pharmacies" role="tabpanel">
                        @foreach($pharmacys as $pharmacy)
                            <div class="draggable-source pharmacy_bg text-white" data-type="pharmacy" data-id="{{ $pharmacy->id }}" data-name="{{ $pharmacy->name }}">
                                <strong>{{ $pharmacy->name }}</strong><br>
                                <small>{{ $pharmacy->address }}</small>
                            </div>
                        @endforeach
                    </div>
                    <div class="tab-pane fade" id="offices" role="tabpanel">
                        @foreach($offices as $office)
                            <div class="draggable-source office_bg text-white" data-type="office" data-id="{{ $office->id }}" data-name="{{ $office->name }}">
                                <strong>{{ $office->name }}</strong><br>
                                <small>{{ $office->location }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Middle Column: Route Template (Sortable) -->
    <div class="col-md-9">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Route Sequence (Drag here)</h5>
                <div class="wrapper" id="route-container">
                    @foreach($items as $item)
                        @php
                            $name = "Unknown";
                            $bg = "";
                            if($item->type == 'pharmacy') {
                                $p = $pharmacys->where('id', $item->type_id)->first();
                                $name = $p ? $p->name : 'Unknown Pharmacy';
                                $bg = "pharmacy_bg";
                            } elseif($item->type == 'office') {
                                $o = $offices->where('id', $item->type_id)->first();
                                $name = $o ? $o->name : 'Unknown Office';
                                $bg = "office_bg";
                            }
                        @endphp
                        <div class="template_block {{ $bg }}" data-type="{{ $item->type }}" data-id="{{ $item->type_id }}">
                            {{ $name }}
                            <button type="button" class="btn-close btn-close-white float-end remove-item" aria-label="Close"></button>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        
        <!-- Map -->
        <div class="card">
            <div class="card-body">
                <div id="map" style="height: 500px; width: 100%;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Assign Modal -->
<div class="modal fade" id="assignDriverModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ url('route-templates/'.$template->id.'/assign') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Assign Template to Driver</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>This will search for active orders for the points in this template and assign them to the selected driver.</p>
                    <div class="mb-3">
                        <label>Select Driver</label>
                        <select name="driver_id" class="form-control select2" required style="font-size: 0.85rem !important;">
                            <option value="">-- Select Driver --</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}">{{ $driver->name }} {{ $driver->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('footerScript')
<script src="{{ URL::asset('/js/jquery-ui.min.js')}}"></script>
<script src="{{ URL::asset('/js/select2.min.js')}}"></script>
<script src="https://maps.googleapis.com/maps/api/js?key={{config('app.googlemaps_apikey')}}&libraries=places&callback=initMap" async defer></script>

<script>
    var map;
    var markers = [];
    var pharmacyLocations = @json($pharmacy_locations);
    
    // Init Map
    function initMap() {
        // Default center
        var center = { lat: 40.7128, lng: -74.0060 }; // NY default
        if(pharmacyLocations.length > 0) {
            var parts = pharmacyLocations[0].location.split(',');
            center = { lat: parseFloat(parts[0]), lng: parseFloat(parts[1]) };
        }

        map = new google.maps.Map(document.getElementById("map"), {
            zoom: 11,
            center: center,
        });

        // Add markers for pharmacies
        pharmacyLocations.forEach(function(loc) {
            var parts = loc.location.split(',');
            var latLng = new google.maps.LatLng(parseFloat(parts[0]), parseFloat(parts[1]));
            var marker = new google.maps.Marker({
                position: latLng,
                map: map,
                title: "Pharmacy ID: " + loc.id,
                icon: 'http://maps.google.com/mapfiles/ms/icons/green-dot.png' // Different color for distinction
            });
            
            // InfoWindow logic
            var contentString = 
                '<div style="color: black;">' + // Force black text
                '<h5>' + loc.name + ' (' + loc.type + ')</h5>' + // Use generic name/type
                '<p>' + (loc.address ? loc.address : 'No address') + '</p>' +
                '<p><button class="btn btn-sm btn-primary add-to-route-btn" data-id="'+loc.id+'" data-type="'+loc.type+'">Add to Route</button></p>' +
                '</div>';

            var infowindow = new google.maps.InfoWindow({
                content: contentString
            });
            
            // Store infowindow ref to close others if needed
            marker.infowindow = infowindow;

            marker.addListener("click", function() {
                // Close previously opened infowindows? Optional.
                if (window.lastOpenedInfoWindow) {
                   window.lastOpenedInfoWindow.close();
                }
                infowindow.open(map, marker);
                window.lastOpenedInfoWindow = infowindow;
            });
        });

        // Add to route via button
        $('body').off('click', '.add-to-route-btn').on('click', '.add-to-route-btn', function() {
             var id = $(this).data('id');
             var type = $(this).data('type');
             addPointToRoute(type, id);
        });
    }

    function addPointToRoute(type, id) {
        // Find name
        var name = "Unknown";
        var bg = "";
        
        if(type === 'pharmacy') {
            // Try list first
            var p_el = $('.draggable-source[data-type="pharmacy"][data-id="'+id+'"]');
            if(p_el.length) {
                name = p_el.data('name');
            } else {
                // Try array
                var p_obj = pharmacyLocations.find(x => x.id == id && x.type == 'pharmacy');
                if(p_obj) name = p_obj.name;
            }
            bg = "pharmacy_bg";
        } else if (type === 'office') {
             // Logic for office if markers added later
             var o_el = $('.draggable-source[data-type="office"][data-id="'+id+'"]');
            if(o_el.length) {
                name = o_el.data('name');
            } else {
                 var o_obj = pharmacyLocations.find(x => x.id == id && x.type == 'office');
                 if(o_obj) name = o_obj.name;
            }
            bg = "office_bg";
        }
        
        var html = '<div class="template_block '+bg+'" data-type="'+type+'" data-id="'+id+'">' + 
                   name + 
                   '<button type="button" class="btn-close btn-close-white float-end remove-item"></button></div>';
                   
        $('#route-container').append(html);
    }


    $(document).ready(function() {
        // Init Select2
        $(".select2").select2({
            width: '100%',
            dropdownParent: $('#assignDriverModal'), // Fix for Select2 in Bootstrap Modal
            allowClear: true
        });

        //initMap(); // Called by callback

        // Make source items draggable
        $(".draggable-source").draggable({
            helper: "clone",
            connectToSortable: "#route-container",
            cursor: "move",
            revert: "invalid"
        });

        // Make route container sortable
        $("#route-container").sortable({
            revert: true,
            cursor: "move",
            placeholder: "ui-state-highlight", // Optional: Add a class for placeholder styling
            forcePlaceholderSize: true, // Helps with layout
            stop: function(event, ui) {
                 // The received item is now part of the list, but it might still have the source classes
                 var item = ui.item;
                 
                 // Check if the item originated from the draggable source list
                 if(item.hasClass('draggable-source')) {
                    var type = item.data('type');
                    var id = item.data('id');
                    var name = item.data('name');
                    
                    var bg = (type == 'pharmacy') ? 'pharmacy_bg' : 'office_bg';
                    
                    // Transform the dropped element into a template_block
                    // We remove the old classes and add the new ones
                    item.removeClass('draggable-source ui-draggable ui-draggable-handle')
                        .addClass('template_block ' + bg)
                        // Ensure background color is applied correctly
                        .css({
                            'background-color': '', // Remove inline background color capable of being set by draggable
                            'width': '',
                            'height': '',
                            'left': '',
                            'top': '',
                            'position': '',
                            'z-index': ''
                        });
                    
                    // Set the content
                    item.html(name + '<button type="button" class="btn-close btn-close-white float-end remove-item"></button>');
                 }
            }
        });
        
        // Remove item
        $(document).on('click', '.remove-item', function() {
            $(this).parent().remove();
        });

        // Save Template
        $('#save-template').click(function() {
            var items = [];
            $('#route-container .template_block').each(function() {
                items.push({
                    type: $(this).data('type'),
                    type_id: $(this).data('id')
                });
            });
            
            $.ajax({
                url: "{{ url('route-templates/'.$template->id.'/items') }}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    items: items
                },
                success: function(response) {
                    if(response.success) {
                        alert('Template saved successfully!');
                    } else {
                        alert('Error: ' + response.message);
                    }
                }
            });
        });
        
        // Search filter
        $("#search-points").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $(".draggable-source").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    });
</script>
@endsection
