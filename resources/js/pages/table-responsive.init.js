/*
Template Name: Lexa - Responsive Bootstrap 4 Admin Dashboard
Author: Themesbrand
Website: https://themesbrand.com/
Contact: themesbrand@gmail.com
File: Table responsive
*/

$(function() {
    var tablePath = window.location.pathname.replace(/\/+$/, '') || '/';
    $('.table-responsive').responsiveTable({
        addDisplayAllBtn: 'btn btn-secondary'
    });
    $('.dropdown-btn-group').prepend('<div class="app-search d-none d-lg-inline-block" style="margin-right: 10px;padding:0px;"><div class="position-relative"><form name="search-form"><input type="text" class="form-control" id="search" name="search" placeholder="Search..."><span class="fa fa-search" style="cursor:pointer;" onclick="document.forms['+"'search-form'"+'].submit();"></span></div></div>');
    if(/^\/settings\/(users|medics|drivers|logists|admins)$/.test(tablePath)) {
        $('.focus-btn-group').append('<a href="/settings/users/add"><button class="btn btn-primary" style="margin-left:15px;">Add New User</button></a>');
    }
    if(tablePath === '/pharmacys') {
        $('.focus-btn-group').append('<a href="/pharmacys/add"><button class="btn btn-primary" style="margin-left:15px;">Add New Pharmacy</button></a>');
    }
    if(tablePath === '/offices') {
        $('.focus-btn-group').append('<a href="/offices/add"><button class="btn btn-primary" style="margin-left:15px;">Add New Office</button></a>');
    }
    if(/^\/pharmacy\/\d+\/users$/.test(tablePath)) {
        $('.focus-btn-group').append('<a href="'+tablePath+'/add"><button class="btn btn-primary" style="margin-left:15px;">Add New Pharmacists</button></a>');
    }
    if(/^\/patients\/\d+$/.test(tablePath)) {
        $('.focus-btn-group').append('<a href="'+tablePath+'/add"><button class="btn btn-primary" style="margin-left:15px;">Add New Patient</button></a>');
        $('.focus-btn-group').append('<a href="'+tablePath+'/removed"><button class="btn btn-primary" style="margin-left:15px;">Removed Patient List</button></a>');
    }
    if(/^\/orders(?:\/\d+)?$/.test(tablePath)) {
        $('.focus-btn-group').append('<a href="'+tablePath+'/add" class="addorder"><button class="btn btn-primary">Add New Order</button></a><div style="display: flex;"><input style="margin-left:10px;width:85px;" class="form-control" type="date" id="date_print"><button class="btn btn-warning" onclick="PrintDay()" style="margin-left:10px;">Print Day</button></div>');
    }
    if(tablePath === '/medicines') {
        $('.focus-btn-group').append('<a href="'+tablePath+'/add"><button class="btn btn-primary" style="margin-left:15px;">Add New Medicine</button></a>');
    }
});