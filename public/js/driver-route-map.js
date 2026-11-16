// The driver route page's map: markers for the driver, pharmacies, patients and offices, the
// drawn route and the area selector. The positions come from the arrays the page declares.
    function addMarker(latlng, text, color, background, id, map, weight="bold") {
        var point = window.routeMapTools.point(latlng);
        if (!point) { return; }
        var latLng = new google.maps.LatLng(point.lat, point.lng);
        if(markersArray.length != 0) {
            for (i=0; i < markersArray.length; i++) {
                var existingMarker = markersArray[i];
                var pos = existingMarker.getPosition();
                if (latLng.equals(pos)) {
                    var a = 360.0 / markersArray.length;
                    var newLat = pos.lat() + -.00005 * Math.cos((+a*i) / 180 * Math.PI);  //x
                    var newLng = pos.lng() + -.00005 * Math.sin((+a*i) / 180 * Math.PI);  //Y
                    latLng = new google.maps.LatLng(newLat,newLng);
                }
            }
        }
        const marker = new google.maps.Marker({
            position: latLng,
            icon: window.routeMapTools.icon('#0e46b7'),
            label: {
                text:text,
                fontWeight: weight,
                fontSize: '8px',
                color: '#fff'
            },
            title: id,
            id: id,
            map: map,
        });
        markersArray.push(marker);
        attachSecretMessage(marker);
    }
    function addMarkerDriver(latlng, map) {
        var point = window.routeMapTools.point(latlng);
        if (!point) { return; }
        const marker = new google.maps.Marker({
            position: point,
            icon: window.routeMapTools.icon('#242424'),
            label: { text: 'D', color: '#fff', fontWeight: 'bold' },
            map: map,
        });
        attachSecretMessage(marker);
    }
    function addMarkerOffice(latlng, text, color, background, id, map, weight="bold") {
        var point = window.routeMapTools.point(latlng);
        if (!point) { return; }
        const marker = new google.maps.Marker({
            position: point,
            icon: window.routeMapTools.icon('#eca40d'),
            label: { text: text, color: '#212529', fontWeight: 'bold' },
            title: id,
            id: id,
            map: map,
        });
        attachSecretMessage(marker);
    }
    function addMarkerPharmacy(latlng, text, color, background, id, map, weight="bold") {
        var point = window.routeMapTools.point(latlng);
        if (!point) { return; }
        const marker = new google.maps.Marker({
            position: point,
            icon: window.routeMapTools.icon('#19b313'),
            label: {
                text:text,
                fontWeight: weight,
                fontSize: '8px',
                color: color
            },
            title: id,
            id: id,
            map: map,
        });
        markersArray.push(marker);
        attachSecretMessage(marker);
    }
    function initMap() {
        if (!window.google || !window.google.maps) { return; }
        if (areaSelector) { areaSelector.destroy(); }
        _myPolygon = null;
        markersArray = [];
        map = new google.maps.Map(document.getElementById("map"), {
            zoom: 11,
            center: mapCenter(),
            legend: 'none'
        });

        if (locationDriver) { addMarkerDriver(locationDriver,map); }
        for(n=0;n<locationOffice.length;n++) {
            addMarkerOffice(locationOffice[n],locationOfficeID[n].toString(),"#000","#eca40d","office"+locationOfficeID[n].toString(),map);
        }
        if(showPatient) {
            for(n=0;n<locationPatients.length;n++) {
                if(locationPatientsID[n].toString().search(";")>-1) {
                    var id2 = locationPatientsID[n].toString().split(";")[0];
                    var prior2 = locationPatientsID[n].toString().split(";")[1];
                    addMarker(locationPatients[n],prior2,"#000","#0e46b7","patient"+id2,map,"bold");
                } else {
                    var id2 = locationPatientsID[n].toString().split(";")[0];
                    addMarker(locationPatients[n],id2,"#000","#0e46b7","patient"+id2,map,"bold");
                }
            }
        }
        if(showPharmacy) {
            for(n=0;n<locationPharmacy.length;n++) {
                if(locationPharmacyID[n].toString().search(";")>-1) {
                    var id2 = locationPharmacyID[n].toString().split(";")[0];
                    var prior2 = locationPharmacyID[n].toString().split(";")[1];
                    addMarkerPharmacy(locationPharmacy[n],prior2,"#fff","#19b313","pharmacy"+id2,map,"bold");
                } else {
                    var id2 = locationPharmacyID[n].toString().split(";")[0];
                    addMarkerPharmacy(locationPharmacy[n],id2,"#fff","#19b313","pharmacy"+id2,map,"bold");
                }
            }
        }
        var visibleStops = locationOffice.concat(showPatient ? locationPatients : [], showPharmacy ? locationPharmacy : []);
        var visibleLocations = [locationDriver].concat(visibleStops);
        window.routeMapTools.fitStops(map, visibleLocations);
        $('#route-map-message').text('No mapped stops match this route or its filters. Check the selected stops and their addresses.').prop('hidden', visibleStops.some(function(location) { return window.routeMapTools.point(location); }));
        areaSelector = window.routeMapTools.areaSelector(map, function(polygon) {
            _myPolygon = polygon;
            $('#region-form .order_ids').remove();
            for(n=0;n<locationPatients.length;n++) {
                var patientPoint = window.routeMapTools.point(locationPatients[n]);
                if(patientPoint && google.maps.geometry.poly.containsLocation(new google.maps.LatLng(patientPoint.lat, patientPoint.lng),polygon) && showPatient) {
                    $("#region-form").append('<input class="order_ids" type="hidden" name="patients_id[]" value="'+locationPatientsID[n].split(';')[0]+'">');
                }
            }
            for(n1=0;n1<locationPharmacy.length;n1++) {
                var pharmacyPoint = window.routeMapTools.point(locationPharmacy[n1]);
                if(pharmacyPoint && google.maps.geometry.poly.containsLocation(new google.maps.LatLng(pharmacyPoint.lat, pharmacyPoint.lng),polygon) && showPharmacy) {
                    $("#region-form").append('<input class="order_ids" type="hidden" name="pharmacys_id[]" value="'+locationPharmacyID[n1].split(';')[0]+'">');
                }
            }
            for(n2=0;n2<locationOffice.length;n2++) {
                var officePoint = window.routeMapTools.point(locationOffice[n2]);
                if(officePoint && google.maps.geometry.poly.containsLocation(new google.maps.LatLng(officePoint.lat, officePoint.lng),polygon)) {
                    $("#region-form").append('<input class="order_ids" type="hidden" name="office_id[]" value="'+locationOfficeID[n2]+'">');
                }
            }
            if(n>=locationPatients.length && n1>=locationPharmacy.length && n2>=locationOffice.length) {
                $('.modal1').fadeIn(300);
            }
        });
    }
    function attachSecretMessage(marker) {
        marker.addListener("click", () => {
            var id = marker.get('id');
            if(id) {
                if(setup_route==true) {
                    $('.modal2').find('input[name="id"]').val(id);
                    if($('.wrapper .plus-item').index($('.wrapper').find("."+id))<0) {
                        $('.modal2').find('input[name="priority"]').val(1);
                    } else {
                        $('.modal2').find('input[name="priority"]').val($('.wrapper .plus-item').index($('.wrapper').find("."+id))+1);
                    }
                    setup_marker = marker;
                    $('.modal2').fadeIn(300);
                } else {
                    $(".office_block").removeClass("focus");
                    $(".pharmacie_block").removeClass("focus");
                    $(".patient_block").removeClass("focus");
                    $(".plus-item").removeClass("focus");
                    $("."+id).addClass("focus");
                    if(id.indexOf('patient')>-1) {
                        if($('.wrapper').find("."+id).length) {
                            $('.wrapper').animate({ scrollTop: $('.wrapper').find("."+id).offset().top-580 }, 500);
                        } else {
                            $('#patients_list').animate({ scrollTop: $("."+id).offset().top-320 }, 500);
                        }
                    }
                    if(id.indexOf('pharmacy')>-1) {
                        if($('.wrapper').find("."+id).length) {
                            $('.wrapper').animate({ scrollTop: $('.wrapper').find("."+id).offset().top-580 }, 500);
                        } else {
                            $('#pharmacies_list').animate({ scrollTop: $("."+id).offset().top-320 }, 500);
                        }
                    }
                    $('html, body').animate({ scrollTop: 0 }, 400);
                }
            }
        });
    }
