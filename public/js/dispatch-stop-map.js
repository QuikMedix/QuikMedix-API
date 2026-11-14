// Stop map shared by the dispatch driver page and the pharmacy process page.
// The page supplies locationDriver, locationPatients, locationPharmacy, their *ID lists and mapCenter()
// (resources/views/dispatching/stop-map-data.blade.php) plus setup_route, showPatient and showPharmacy.
// Google Maps calls initMap once its script has loaded.
var map;
var _myPolygon;
var markersArray = [];

function addMarker(latlng, text, color, background, id, map, weight="bold") {
    var latLng = new google.maps.LatLng(parseFloat(latlng.split(",")[0]), parseFloat(latlng.split(",")[1]));
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
        icon: {
            url: '/images/i033.svg', // url
            scaledSize: new google.maps.Size(35, 35), // scaled size
            origin: new google.maps.Point(0,0), // origin
            anchor: new google.maps.Point(30, 30), // anchor
            labelOrigin: new google.maps.Point(18, 31)
        },
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
function addMarkerDriver(latlng, map) {
    const marker = new google.maps.Marker({
        position: { lat: parseFloat(latlng.split(",")[0]), lng: parseFloat(latlng.split(",")[1]) },
        icon: {
            url: '/images/i022.svg', // url
            scaledSize: new google.maps.Size(46, 46), // scaled size
            origin: new google.maps.Point(0,0), // origin
            anchor: new google.maps.Point(23, 23) // anchor
        },
        map: map,
    });
    attachSecretMessage(marker);
}
function addMarkerOffice(latlng, text, color, background, id, map, weight="bold") {
    const marker = new google.maps.Marker({
        position: { lat: parseFloat(latlng.split(",")[0]), lng: parseFloat(latlng.split(",")[1]) },
        icon: {
            url: '/images/i044.svg', // url
            scaledSize: new google.maps.Size(30, 30), // scaled size
            origin: new google.maps.Point(0,0), // origin
            anchor: new google.maps.Point(30, 30) // anchor
        },
        title: id,
        id: id,
        map: map,
    });
    attachSecretMessage(marker);
}
function addMarkerPharmacy(latlng, text, color, background, id, map, weight="bold") {
    const marker = new google.maps.Marker({
        position: { lat: parseFloat(latlng.split(",")[0]), lng: parseFloat(latlng.split(",")[1]) },
        icon: {
            url: '/images/i011.svg', // url
            scaledSize: new google.maps.Size(35, 35), // scaled size
            origin: new google.maps.Point(0,0), // origin
            anchor: new google.maps.Point(20, 20), // anchor
            labelOrigin: new google.maps.Point(18, 31)
        },
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
    map = new google.maps.Map(document.getElementById("map"), {
        zoom: 11,
        center: { lat: parseFloat(mapCenter().split(",")[0]), lng: parseFloat(mapCenter().split(",")[1]) },
        legend: 'none'
    });

    if (locationDriver) addMarkerDriver(locationDriver,map);
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
}
function attachSecretMessage(marker) {
    marker.addListener("click", () => {
        var id = marker.get('id');
        if(id!="") {
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
