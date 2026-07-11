function initAll () {!function(e){var n={};function t(r){if(n[r])return n[r].exports;var o=n[r]={i:r,l:!1,exports:{}};return e[r].call(o.exports,o,o.exports,t),o.l=!0,o.exports}t.m=e,t.c=n,t.d=function(e,n,r){t.o(e,n)||Object.defineProperty(e,n,{enumerable:!0,get:r})},t.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},t.t=function(e,n){if(1&n&&(e=t(e)),8&n)return e;if(4&n&&"object"==typeof e&&e&&e.__esModule)return e;var r=Object.create(null);if(t.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:e}),2&n&&"string"!=typeof e)for(var o in e)t.d(r,o,function(n){return e[n]}.bind(null,o));return r},t.n=function(e){var n=e&&e.__esModule?function(){return e.default}:function(){return e};return t.d(n,"a",n),n},t.o=function(e,n){return Object.prototype.hasOwnProperty.call(e,n)},t.p="/",t(t.s=42)}({42:function(e,n,t){e.exports=t(43)},43:function(e,n){$((function(){$(".table-responsive").responsiveTable({addDisplayAllBtn:"btn btn-secondary"}),$('.dropdown-btn-group').prepend('<div class="app-search d-none d-lg-inline-block" style="margin-right: 10px;padding:0px;"><div class="position-relative"><form name="search-form"><input type="text" class="form-control" id="search" name="search" placeholder="Search..."><span class="fa fa-search" style="cursor:pointer;" onclick="document.forms['+"'search-form'"+'].submit();"></span></div></div>')}))}});
$(function () {
    if(window.location.href.indexOf('/settings/users')>-1 || window.location.href.indexOf('/settings/medics')>-1 || window.location.href.indexOf('/settings/drivers')>-1 || window.location.href.indexOf('/settings/logists')>-1) {
      $('.focus-btn-group').append('<a href="/settings/users/add"><button class="btn btn-primary" style="margin-left:15px;">Add New User</button></a>');
    }
    if(window.location.href.indexOf('/pharmacys')>-1) {
        $('.focus-btn-group').append('<a href="/pharmacys/add"><button class="btn btn-primary" style="margin-left:15px;">Add New Pharmacy</button></a>');
    }
    if(window.location.href.indexOf('/offices')>-1) {
        $('.focus-btn-group').append('<a href="/offices/add"><button class="btn btn-primary" style="margin-left:15px;">Add New Office</button></a>');
    }
    if(window.location.href.indexOf('/pharmacy')>-1 && window.location.href.indexOf('/users')>-1) {
        $('.focus-btn-group').append('<a href="'+window.location.pathname+'/add"><button class="btn btn-primary" style="margin-left:15px;">Add New Pharmacists</button></a>');
    }
    if(window.location.href.indexOf('/patients')>-1 && window.location.href.indexOf('/removed')==-1) {
        $('.focus-btn-group').append('<button class="btn btn-primary" type="button" style="margin-left:15px;" data-bs-toggle="offcanvas" data-bs-target="#addPatientModal" aria-controls="addPatientModal">Add New Patient</button>');
        $('.focus-btn-group').append('<a href="'+window.location.pathname+'/removed"><button class="btn btn-primary" style="margin-left:15px;">Removed Patient List</button></a>');
        $('.focus-btn-group').append('<a href="/news_patient"><button class="btn btn-warning" style="margin-left:15px;">Patient News</button></a>');
    }
    if(window.location.href.indexOf('/facilitys')>-1 && window.location.href.indexOf('/removed')==-1) {
        $('.focus-btn-group').append('<button class="btn btn-primary" type="button" style="margin-left:15px;" data-bs-toggle="offcanvas" data-bs-target="#addPatientModal" aria-controls="addPatientModal">Add New Facility</button>');
    }
    if(window.location.href.indexOf('/drivers')>-1) {
        $('.focus-btn-group').append('<a href="'+window.location.pathname+'/add"><button class="btn btn-primary" style="margin-left:15px;">Add New Driver</button></a>');
    }
    if(window.location.href.indexOf('/orders')>-1) {
        $('.focus-btn-group').append('<div class="btn-group dropend"><button type="button" class="btn btn-info waves-effect waves-light dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="mdi mdi-pencil-plus"></i>Add New Order<i class="mdi mdi-chevron-right"></i></button><div class="dropdown-menu"><a href="'+window.location.pathname+'/add" class="addorder dropdown-item">Single order</a><a class="dropdown-item" href="'+window.location.pathname+'/facilitys_add">Facility order</a></div></div><button class="btn btn-secondary waves-effect" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight" style="margin-left: 10px;"><i class="mdi mdi-cloud-upload"></i> Upload Delivery Slip </button><div style="display:flex"><input style="margin-left:10px;width:100px;font-size:10px;min-height:35px;padding:0 5px" class="form-control" type="date" id="date_print"><button class="btn btn-warning" onclick="PrintDay()" style="margin-left:10px">Print Day</button></div>');
    }
    if(window.location.href.indexOf('/medicines')>-1) {
        $('.focus-btn-group').append('<a href="'+window.location.pathname+'/add"><button class="btn btn-primary" style="margin-left:15px;">Add New Medicine</button></a>');
    }
  });
}