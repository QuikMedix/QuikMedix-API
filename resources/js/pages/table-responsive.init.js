/*
Template Name: Lexa - Responsive Bootstrap 4 Admin Dashboard
Author: Themesbrand
Website: https://themesbrand.com/
Contact: themesbrand@gmail.com
File: Table responsive
*/

$(function() {
    $('.table-responsive').responsiveTable({
        addDisplayAllBtn: 'btn btn-secondary'
    });
    $('.dropdown-btn-group').prepend('<div class="app-search d-none d-lg-inline-block" style="margin-right: 10px;padding:0px;"><div class="position-relative"><form name="search-form"><input type="text" class="form-control" id="search" name="search" placeholder="Search..."><span class="fa fa-search" style="cursor:pointer;" onclick="document.forms['+"'search-form'"+'].submit();"></span></div></div>');
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
        $('.focus-btn-group').append('<a href="'+window.location.pathname+'/add"><button class="btn btn-primary" style="margin-left:15px;">Add New Patient</button></a>');
        $('.focus-btn-group').append('<a href="'+window.location.pathname+'/removed"><button class="btn btn-primary" style="margin-left:15px;">Removed Patient List</button></a>');
    }
    if(window.location.href.indexOf('/orders')>-1) {
        $('.focus-btn-group').append('<a href="'+window.location.pathname+'/add" class="addorder"><button class="btn btn-primary">Add New Order</button></a><div style="display: flex;"><input style="margin-left:10px;width:85px;" class="form-control" type="date" id="date_print"><button class="btn btn-warning" onclick="PrintDay()" style="margin-left:10px;">Print Day</button></div>');
    }
    if(window.location.href.indexOf('/medicines')>-1) {
        $('.focus-btn-group').append('<a href="'+window.location.pathname+'/add"><button class="btn btn-primary" style="margin-left:15px;">Add New Medicine</button></a>');
    }
});