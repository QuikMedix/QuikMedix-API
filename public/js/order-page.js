// The order page: its maps, the driver's trail and the print windows.
// Page data comes from window.orderPage, set by resources/views/orders/show.blade.php.
function encodeImageFileAsURL(element) {
    if(element.files[0].size > 10097152){
        alert("File is too big!");element.value = "";
    }
}
var markersArray = [];
var markersArray2 = [];
var locationUser = orderPage.userLocation.split(',');
var locationPharmasy = orderPage.pharmacyLocation.split(',');
if (orderPage.hasDriver) {
    var locationDriver = orderPage.driverLocation.split(',');
    var locationDrivers = orderPage.trail;
    var locationDriversID = orderPage.trailTimes;
    var deliverTime = orderPage.finishDigits;
    var Driver = orderPage.driverUserId;
}
function addMarker(latlng, text, map) {
    if(parseInt(text.replace(/\D+/g,""))==parseInt(deliverTime) || parseInt(text.replace(/\D+/g,""))+1==parseInt(deliverTime)  || parseInt(text.replace(/\D+/g,""))+2==parseInt(deliverTime) || parseInt(text.replace(/\D+/g,""))-1==parseInt(deliverTime) || parseInt(text.replace(/\D+/g,""))-2==parseInt(deliverTime)) {
        marker2 = L.marker([latlng[0],latlng[1]],{
            icon: customIconDriverLog(text),
        }).addTo(map);
    } else {
        marker2 = L.marker([latlng[0],latlng[1]],{
            icon: customIconDriverLog(text,0.35),
        }).addTo(map);
    }
}
const here = {
    apiKey: orderPage.hereApiKey
}
const style = 'normal.day';
const hereTileUrl = `https://maps.hereapi.com/v3/base/mc/{z}/{x}/{y}/png?apiKey=${here.apiKey}&ppi=400`;
const UserLatlng = [Number(locationUser[0]), Number(locationUser[1])];
const PharmasyLatlng = [Number(locationPharmasy[0]), Number(locationPharmasy[1])];
if (orderPage.hasDriver) {
    var DriverLatlng = [Number(locationDriver[0]), Number(locationDriver[1])];
    $('#show_live_tracker').on('click',function(){
        var map2 = L.map(document.getElementById("map-driver")).setView(UserLatlng, 20);
        L.tileLayer(hereTileUrl).addTo(map2);
        var marker2 = L.marker(UserLatlng,{
            icon: customIconUser(orderPage.id + '\n(' + orderPage.finishTime + ')'),
        }).addTo(map2);
        for(n=0;n<locationDrivers.length;n++) {
            addMarker(locationDrivers[n].split(','),locationDriversID[n].toString(),map2);
        }
        setTimeout(function () {
            window.dispatchEvent(new Event('resize'));
        }, 300);
    });
}
$(document).ready(function() {
    var map = L.map(document.getElementById("map")).setView(UserLatlng, 13);
    L.tileLayer(hereTileUrl).addTo(map);
    var marker = L.marker(UserLatlng,{
        icon: customIconUser(orderPage.id),
    }).addTo(map);
    marker = L.marker(PharmasyLatlng,{
        icon: customPharmasyIcon(),
    }).addTo(map);
    if (orderPage.hasDriverPosition) {
    marker = L.marker(DriverLatlng,{
        icon: customIconDriver(),
    }).addTo(map);
    }
});
function customIconUser(text='') {
    return L.divIcon({
        iconSize: "auto",
        html:'<div style="width:80px;height:80px;position:relative;margin-left: -50%;margin-top: -50%;"><img alt="" src="/images/i033.svg" draggable="false" style="width:80px;height:80px;"><div style="position: absolute;top: calc(100% - 19px);"><div style="display: block;width: 80px;text-align: center;"><div class="" aria-hidden="true" style="color: rgb(0, 0, 0);line-height: 20px; font-size: 16px; font-family: Arial;">'+text+'</div></div></div></div>'
    });
}
function customPharmasyIcon() {
    return L.divIcon({
        iconSize: "auto",
        html:'<div style="width:50px;height:50px;position:relative;margin-left: -50%;margin-top: -50%;"><img alt="" src="/images/i011.svg" draggable="false" style="width:50px;height:50px;"></div>'
    });
}
function customIconDriver() {
    return L.divIcon({
        iconSize: "auto",
        html:'<div style="width:50px;height:50px;position:relative;margin-left: -50%;margin-top: -50%;"><img alt="" src="/images/i022.svg" draggable="false" style="width:50px;height:50px;"></div>'
    });
}
function customIconDriverLog(text='',opacity=1) {
    return L.divIcon({
        iconSize: "auto",
        html:'<div style="width:90px;height:90px;position:relative;margin-left: -50%;margin-top: -50%;opacity:'+opacity+';"><img alt="" src="/images/i055.svg" draggable="false" style="width:90px;height:90px;"><div style="position: absolute;bottom: 19px;"><div style="display: block;width: 90px;text-align: center;"><div class="" aria-hidden="true" style="color: rgb(0, 0, 0); font-size: 16px; font-family: Arial;">'+text+'</div></div></div></div>'
    });
}
$('#toggle_map').on('click',function () {
    if($('body').find('#google_map').length<1) {
        var s = document.createElement("script");
        s.type = "text/javascript";
        s.id = "google_map";
        s.src = "https://maps.googleapis.com/maps/api/js?key=" + encodeURIComponent(orderPage.mapsKey || '') + "&region=US&language=en&v=weekly&callback=initializeMap2";
        $('#footerScript').append(s);
    }
    if($('#map').css('display')=='none'){
        $('#toggle_map').text('Street View');
        $('#map').show(300);
        $('#pano').hide(300);
    } else {
        $('#toggle_map').text('Map View');
        $('#map').hide(300);
        $('#pano').show(300);
    }
});
var panorama;
function initializeMap2() {
    const fenway = { lat: UserLatlng[0], lng: UserLatlng[1] };
    var sv = new google.maps.StreetViewService();
    panorama = new google.maps.StreetViewPanorama(
        document.getElementById("pano")
    );
    sv.getPanorama({
        location: fenway,
        radius: 50,
        source: google.maps.StreetViewSource.OUTDOOR
    }, processSVData);
}
function processSVData(data, status) {
    console.log(data,status);
    if (status === google.maps.StreetViewStatus.OK) {
        panorama.setPano(data.location.pano);
        panorama.setPov({
            heading: 0,
            pitch: 0
        });
        panorama.setVisible(true);
    }
}
function PrintElem(elem){
    Popup($(elem).html());
}
function PrintElem2(elem){
    Popup2($(elem).html());
}  
function PrintElem3(elem){
    Popup3($(elem).html());
}  
function PrintElem7(elem){
    Popup7($(elem).html());
}  

function Popup(data){
    if (data && data.indexOf('data-document-unavailable') !== -1) {
        Swal.fire('Document unavailable', 'Please contact QuikMedix support for this form.', 'info');
        return false;
    }
    var mywindow = window.open('', 'my div', 'height=400,width=600');
    mywindow.document.write('<html><head><title>Delivery_Slip_' + orderPage.patientLastName + '_' + orderPage.patientName + '</title>');
    mywindow.document.write('<link href="' + orderPage.cssUrl + '/bootstrap.min.css?v=quikmedix-1" id="bootstrap-style" rel="stylesheet" type="text/css">');
    mywindow.document.write('</head><body >');
    mywindow.document.write(data);
    mywindow.document.write('</body></html>');
    mywindow.document.close(); // necessary for IE >= 10
    mywindow.focus(); // necessary for IE >= 10
    setTimeout(() => {mywindow.print(); }, 1500);
    mywindow.onafterprint = function(){ mywindow.close();}
    return true;
}
function Popup2(data){
    if (data && data.indexOf('data-document-unavailable') !== -1) {
        Swal.fire('Document unavailable', 'Please contact QuikMedix support for this form.', 'info');
        return false;
    }
    var mywindow = window.open('', 'my div', 'height=400,width=600');
    mywindow.document.write('<html><head><title>HIPAA_' + orderPage.patientLastName + '_' + orderPage.patientName + '</title>');
    mywindow.document.write('<link href="' + orderPage.cssUrl + '/bootstrap.min.css?v=quikmedix-1" id="bootstrap-style" rel="stylesheet" type="text/css">');
    mywindow.document.write('</head><body >');
    mywindow.document.write(data);
    mywindow.document.write('</body></html>');
    mywindow.document.close(); // necessary for IE >= 10
    mywindow.focus(); // necessary for IE >= 10
    setTimeout(() => {mywindow.print(); }, 1500);
    mywindow.onafterprint = function(){ mywindow.close();}
    return true;
} 
function Popup3(data){
    if (data && data.indexOf('data-document-unavailable') !== -1) {
        Swal.fire('Document unavailable', 'Please contact QuikMedix support for this form.', 'info');
        return false;
    }
    var mywindow = window.open('', 'my div', 'height=400,width=600');
    mywindow.document.write('<html><head><title>AOB_' + orderPage.patientLastName + '_' + orderPage.patientName + '</title>');
    mywindow.document.write('<link href="' + orderPage.cssUrl + '/bootstrap.min.css?v=quikmedix-1" id="bootstrap-style" rel="stylesheet" type="text/css">');
    mywindow.document.write('</head><body >');
    mywindow.document.write(data);
    mywindow.document.write('</body></html>');
    mywindow.document.close(); // necessary for IE >= 10
    mywindow.focus(); // necessary for IE >= 10
    setTimeout(() => {mywindow.print(); }, 1500);
    mywindow.onafterprint = function(){ mywindow.close();}
    return true;
}   
function Popup7(data){
    if (data && data.indexOf('data-document-unavailable') !== -1) {
        Swal.fire('Document unavailable', 'Please contact QuikMedix support for this form.', 'info');
        return false;
    }
    var mywindow = window.open('', 'my div', 'height=400,width=600');
    mywindow.document.write('<html><head><title>Print_' + orderPage.patientName + '</title>');
    mywindow.document.write('<link href="' + orderPage.cssUrl + '/bootstrap.min.css?v=quikmedix-1" id="bootstrap-style" rel="stylesheet" type="text/css">');
    mywindow.document.write('</head><body >');
    mywindow.document.write(data);
    mywindow.document.write('</body></html>');
    mywindow.document.close(); // necessary for IE >= 10
    mywindow.focus(); // necessary for IE >= 10
    setTimeout(() => {mywindow.print(); }, 1500);
    mywindow.onafterprint = function(){ mywindow.close();}
    return true;
}
