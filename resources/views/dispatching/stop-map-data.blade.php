{{-- Stop locations for the dispatch and pharmacy process maps; the map itself is public/js/dispatch-stop-map.js. --}}
<script>
                        var locationDriver = "{{ $locations->location ?? '' }}"; // empty until the driver shares a location
    function mapCenter() { return locationDriver || locationPharmacy[0] || locationPatients[0] || '40.7128,-74.0060'; }
                        var locationPatients = [@foreach($patients_locations as $patients_location)
                        @if(!empty($patients_location['location']))
                            @if((!empty($show_ids) && in_array("patient".$patients_location['id'],$show_ids)))
                            "{{ $patients_location['location'] }}",
                            @endif
                        @endif
                        @endforeach];
                        var locationPharmacy = [@foreach($pharmacy_locations as $pharmacy_location)
                        @if(!empty($pharmacy_location['location']))
                            @if((!empty($show_ids) && in_array("pharmacy".$pharmacy_location['id'],$show_ids)))
                            "{{ $pharmacy_location['location'] }}",
                            @endif
                        @endif
                        @endforeach];
                        var locationPatientsID = [@foreach($patients_locations as $patients_location)
                        @if(!empty($patients_location['id']))
                            @if(!empty($show_ids) && in_array("patient".$patients_location['id'],$show_ids))
                                @if(isset($show_priority['patient'.$patients_location['id']]))
                                "{{ $patients_location['id'] }};{{ $show_priority['patient'.$patients_location['id']]+1 }}",
                                @else
                                "0",
                                @endif
                            @endif
                        @endif
                        @endforeach];
                        var locationPharmacyID = [@foreach($pharmacy_locations as $pharmacy_location)
                        @if(!empty($pharmacy_location['id']))
                            @if(!empty($show_ids) && in_array("pharmacy".$pharmacy_location['id'],$show_ids))
                                @if(isset($show_priority['pharmacy'.$pharmacy_location['id']]))
                                "{{ $pharmacy_location['id'] }};{{ $show_priority['pharmacy'.$pharmacy_location['id']]+1 }}",
                                @else
                                "0",
                                @endif
                            @endif
                        @endif
                        @endforeach];
</script>
