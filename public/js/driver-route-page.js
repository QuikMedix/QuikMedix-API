// The driver route page: arranging stops, the trash and office pickers, and loading the map.
// Page data comes from window.driverRoutePage, set by resources/views/routes/driver.blade.php.
var setup_route = false;
var setup_marker;
var showPharmacy = true;
var showPatient = true;
$("#pharmacies").on("change",function(){
    if(this.checked) {
        showPharmacy = true;
    } else {
        showPharmacy = false;
    }
    initMap();
});
$("#patients").on("change",function(){
    if(this.checked) {
        showPatient = true;
    } else {
        showPatient = false;
    }
    initMap();
});
$("#setup_route").on("click",function(){
    if($(this).hasClass("active")==true) {
        $(this).removeClass("active");
        $(this).next("small").hide(200);
        setup_route=false;
    } else {
        $(this).addClass("active");
        $(this).next("small").show(200);
        setup_route=true;
    }
});
$(".setup_route_yes").on("click",function(){
    var id0 = $('.modal2 input[name="id"]').val();
    var prior = $('.modal2 input[name="priority"]').val();
    if(id0!="" && prior!="") {
        if((prior-2)<0) {
            $(".wrapper .drag-text").after($(".wrapper").find("."+id0));
        } else {
            if($('.wrapper .plus-item').index($('.wrapper').find("."+id0))<prior) {
                $(".wrapper").find(".plus-item").eq((prior-1)).after($(".wrapper").find("."+id0));
            } else {
                $(".wrapper").find(".plus-item").eq((prior-2)).after($(".wrapper").find("."+id0));
            }
        }
        for (let marker0 of markersArray) {
            var label = marker0.getLabel();
            label.text = String($('.wrapper .plus-item').index($('.wrapper').find("."+marker0.id))+1);
            marker0.setLabel(label);
        };
    }
    $('.modal2').fadeOut(300);
});
function reload_priority() {
    var nmax = 300-$('.plus-item').length;
    for(n=0;n<nmax;n++) {
        $('.wrapper').append(element.replaceAll("$num",$('.plus-item').length+1));
    }
}
function draggablePharmacy() {
    $(".pharmacie_block").draggable({ cursor: "move",helper: "clone", cursorAt: { top: 20, left: 120 },revert:"invalid", revertDuration: 100,
        start: function() {
            $(".wrapper").addClass("active");
        }, 
        stop: function() {
            $(".wrapper").removeClass("active");
            $(this).removeClass("focus");
        } 
    });
}
function draggablePatient() {
    $(".patient_block").draggable({ cursor: "move",helper: "clone", cursorAt: { top: 20, left: 120 },revert:"invalid", revertDuration: 100,
        start: function() {
            $(".wrapper").addClass("active");
        }, 
        stop: function() {
            $(".wrapper").removeClass("active");
            $(this).removeClass("focus");
        } 
    });
}
var element = '<div class="plus-item no-active"><input type="hidden" name="order_id[]"><input type="hidden" name="type[]"><input type="hidden" name="type_id[]"><div class="eta_block" style="display:none;"><i class="mdi mdi-timer"></i> <span class="eta"></span><br><i class="mdi mdi-clock-outline"></i> <span class="time"></span><br><span class="num">$num</span></div><p class="number">$num</p></div>';
function updateOfficeId(ids) {
    window.officeOrderPicker.updateOfficeId(ids);
}
var orderIds = window.driverRoutePage.orderIds;
$(document).ready(function() {
    $(".plus-item.active-yellow").eq(0).find('.multiple_select').find('option').each(function(index) {
        //orderIds.push($(this).val().toString());
    });
    $('body').on('click','.close',function() {
        $('.modal2').fadeOut(300);
        $('.modal1').fadeOut(300);
        $('.order_ids').remove();
        if (_myPolygon) { _myPolygon.setMap(null); }
    });
    $('body').on('click','.close0',function() {
        $('.modal1').fadeOut(300);
        $('.modal2').fadeOut(300);
        $('.order_ids').remove();
        if (_myPolygon) { _myPolygon.setMap(null); }
    });
    draggablePharmacy();
    draggablePatient();
    $(".office_block").draggable({ cursor: "move",helper: "clone", cursorAt: { top: 20, left: 120 },revert:"invalid", revertDuration: 100,
        start: function() {
            $(".wrapper").addClass("active");
        }, 
        stop: function() {
            $(".wrapper").removeClass("active");
            $(this).removeClass("focus");
        }
    });
    $(".patient_block.disabled").draggable("disable");
    $(".patient_block.disabled").on('click',function(){
        alert("You cannot deliver this order to the patient, since it has been returned to the office more than 3 times, you can return this order to the pharmacy");
    });
    if (window.driverRoutePage.hasStops) {
        $(".wrapper").sortable({cancel: "input,select,textarea,button,label,a,[data-office-order-picker]", cursor: "move", cursorAt: { top: 30, left: 55 },revert:"invalid", revertDuration: 100,
        start: function() {
            $(".trash_block").addClass("active");
        }, 
        stop: function() {
            $(".trash_block").removeClass("active");
            $(".trash_block").removeClass("focuse");
            var last = 0;
            $('.plus-item').each(function( index ) {
                if($(this).find("input[name='type[]']").val()=='') {
                    $(this).remove();
                }
            });
            reload_priority();
        }});
    }
    $(".trash_block").droppable({
        over:function(){
            $(".trash_block").addClass("focuse");
        },
        drop:function(event, ui){
            $(".trash_block").removeClass("focuse");
            var el = $(ui.draggable);
            if(el.find("input[name='type[]']").val()=='patient') {
                var arr = [];
                if($(ui.draggable).find('select').is('select')) {
                    $.each($(ui.draggable).find('select').val(), function( index, value ) {
                        $(ui.draggable).find('select').find('option[value="'+value+'"]').remove();
                        arr.push(value.toString());
                        $(".patient_block").find('.order_id[data-id="'+value+'"][data-type="patient"]').next().next('.type_id[data-id="'+el.find("input[name='type_id[]']").val()+'"]').parent('.left').parent('.patient_block').show();
                    });
                    if($(ui.draggable).find('select option').length==0) {
                        $(ui.draggable).remove();
                    }
                    $(ui.draggable).find('.multiple_select').multiselect('rebuild');
                    $(ui.draggable).find('.multiple_select').multiselect('selectAll', false);
                    $(ui.draggable).find('.multiple_select').change();
                } else {
                    $(".patient_block").find('.order_id[data-id="'+el.find("input[name='order_id[]']").val()+'"][data-type="patient"]').next().next('.type_id[data-id="'+el.find("input[name='type_id[]']").val()+'"]').parent('.left').parent('.patient_block').show();
                    $(ui.draggable).remove();
                    arr.push(el.find("input[name='order_id[]']").val().toString());
                }
                draggablePatient();
                $.each(arr, function( index, value ) {
                    if(orderIds.indexOf(value.toString())>-1 && !($('body').find('.active-green').find("input[name='order_id[]'][value='"+value+"']").is('input') || $('body').find('.active-green').find('.multiple_select').find('option[value="'+value+'"]').is('option') || $('body').find('.active-blue').find("input[name='order_id[]'][value='"+value+"']").is('input') || $('body').find('.active-blue').find('.multiple_select').find('option[value="'+value+'"]').is('option'))) {
                        orderIds.splice(orderIds.indexOf(value.toString()), 1);
                    }
                });
                updateOfficeId(orderIds);
            }
            if(el.find("input[name='type[]']").val()=='pharmacy') {
                var arr = [];
                if($(ui.draggable).find('select').is('select')) {
                    $.each($(ui.draggable).find('select').val(), function( index, value ) {
                        $(ui.draggable).find('select').find('option[value="'+value+'"]').remove();
                        arr.push(value.toString());
                        $(".pharmacie_block").find('.order_id[data-id="'+value+'"][data-type="pharmacy"]').next().next('.type_id[data-id="'+el.find("input[name='type_id[]']").val()+'"]').parent('.left').parent('.pharmacie_block').show();
                    });
                    if($(ui.draggable).find('select option').length==0) {
                        $(ui.draggable).remove();
                    }
                    $(ui.draggable).find('.multiple_select').multiselect('rebuild');
                    $(ui.draggable).find('.multiple_select').multiselect('selectAll', false);
                    $(ui.draggable).find('.multiple_select').change();
                } else {
                    $(".pharmacie_block").find('.order_id[data-id="'+el.find("input[name='order_id[]']").val()+'"][data-type="pharmacy"]').next().next('.type_id[data-id="'+el.find("input[name='type_id[]']").val()+'"]').parent('.left').parent('.pharmacie_block').show();
                    $(ui.draggable).remove();
                    arr.push(el.find("input[name='order_id[]']").val().toString());
                }
                draggablePharmacy();
                $.each(arr, function( index, value ) {
                    if(orderIds.indexOf(value.toString())>-1 && !($('body').find('.active-green').find("input[name='order_id[]'][value='"+value+"']").is('input') || $('body').find('.active-green').find('.multiple_select').find('option[value="'+value+'"]').is('option') || $('body').find('.active-blue').find("input[name='order_id[]'][value='"+value+"']").is('input') || $('body').find('.active-blue').find('.multiple_select').find('option[value="'+value+'"]').is('option'))) {
                        orderIds.splice(orderIds.indexOf(value.toString()), 1);
                    }
                });
                updateOfficeId(orderIds);
            }
            if(el.find("input[name='type[]']").val()=='office') {
                $(ui.draggable).remove();
            }
            reload_priority();
        }
    });
    $(".wrapper").droppable({
        drop:function(event, ui){
            $(".wrapper").sortable({cancel: "input,select,textarea,button,label,a,[data-office-order-picker]", cursor: "move", cursorAt: { top: 30, left: 55 },revert:"valid", revertDuration: 100,
            start: function() {
                $(".trash_block").addClass("active");
            }, 
            stop: function() {
                $(".trash_block").removeClass("active");
                $(".trash_block").removeClass("focuse");
                var last = 0;
                $('.plus-item').each(function( index ) {
                    if($(this).find("input[name='type[]']").val()=='') {
                        $(this).remove();
                    }
                });
                reload_priority();
            }});
            var type = $(ui.draggable).find('.order_id').data('type');
            $(".wrapper").addClass("activeted");
            var el = $(this).find(".plus-item.no-active").first();
            if(type=='patient') {
                if($('.plus-item').is('.patient'+$(ui.draggable).find('.type_id').data('id'))) {
                    if($('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find('select').is('select')) {
                        $('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find('select').append('<option selected value="'+$(ui.draggable).find('.order_id').data('id')+'">'+$(ui.draggable).find('.order_id').data('id')+' ('+$(ui.draggable).find('.text-center.date span').first().clone().children().remove().end().text().replace(/ /g,"")+')</option>');
                        $('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').multiselect('rebuild');
                        $('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').multiselect('selectAll', false);
                        $('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').change();
                    } else {
                        $('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find('.order').html('Order: <select class="multiple_select" multiple="multiple"><option selected value="'+$('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find("input[name='order_id[]']").val()+'">'+$('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find("input[name='order_id[]']").val()+' ('+$('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find("span.badge").first().clone().children().remove().end().text().replace(/ /g,"")+')</option><option selected value="'+$(ui.draggable).find('.order_id').data('id')+'">'+$(ui.draggable).find('.order_id').data('id')+' ('+$(ui.draggable).find('.text-center.date span').first().clone().children().remove().end().text().replace(/ /g,"")+')</option></select>');
                        $('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').multiselect(window.routeOrderSelect.options);
                        $('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').multiselect('selectAll', false);
                        $('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').change();
                    }
                } else {
                    el.find("p").remove();
                    el.removeClass("no-active");
                    el.find('.eta_block').show();
                    el.find("input[name='order_id[]']").val($(ui.draggable).find('.order_id').data('id'));
                    el.find("input[name='type[]']").val(type);
                    el.find("input[name='type_id[]']").val($(ui.draggable).find('.type_id').data('id'));
                    el.addClass("active-blue");
                    el.addClass("patient"+$(ui.draggable).find('.type_id').data('id'));
                    el.append("<p class='order'>Order: "+$(ui.draggable).find('.order_id').data('id')+"</p>");
                    el.append("<p>Customer: "+$(ui.draggable).find('.type_id').data('id')+"</p>");
                    $(ui.draggable).find('.text-center.date span').first().clone().removeAttr('style').appendTo(el);
                    el.attr('title',$(ui.draggable).find('.text-center.date span').first().text());
                }
                if(orderIds.indexOf($(ui.draggable).find('.order_id').data('id').toString())==-1) {
                    orderIds.push($(ui.draggable).find('.order_id').data('id').toString());
                }
                $(ui.draggable).hide();
                updateOfficeId(orderIds);
            }
            if(type=='pharmacy') {
                if($('.plus-item').is('.pharmacy'+$(ui.draggable).find('.type_id').data('id'))) {
                    if($('.plus-item.pharmacy'+$(ui.draggable).find('.type_id').data('id')).find('select').is('select')) {
                        $('.plus-item.pharmacy'+$(ui.draggable).find('.type_id').data('id')).find('select').append('<option selected value="'+$(ui.draggable).find('.order_id').data('id')+'">'+$(ui.draggable).find('.order_id').data('id')+' ('+$(ui.draggable).find('.text-center.date span').first().clone().children().remove().end().text().replace(/ /g,"")+')</option>');
                        $('.plus-item.pharmacy'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').multiselect('rebuild');
                        $('.plus-item.pharmacy'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').multiselect('selectAll', false);
                        $('.plus-item.pharmacy'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').change();
                    } else {
                        $('.plus-item.pharmacy'+$(ui.draggable).find('.type_id').data('id')).find('.order').html('Order: <select class="multiple_select" multiple="multiple"><option selected value="'+$('.plus-item.pharmacy'+$(ui.draggable).find('.type_id').data('id')).find("input[name='order_id[]']").val()+'">'+$('.plus-item.pharmacy'+$(ui.draggable).find('.type_id').data('id')).find("input[name='order_id[]']").val()+' ('+$('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find("span.badge").first().clone().children().remove().end().text().replace(/ /g,"")+')</option><option selected value="'+$(ui.draggable).find('.order_id').data('id')+'">'+$(ui.draggable).find('.order_id').data('id')+' ('+$(ui.draggable).find('.text-center.date span').first().clone().children().remove().end().text().replace(/ /g,"")+')</option></select>');
                        $('.plus-item.pharmacy'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').multiselect(window.routeOrderSelect.options);
                        $('.plus-item.pharmacy'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').multiselect('selectAll', false);
                        $('.plus-item.pharmacy'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').change();
                    }
                } else {
                    el.find("p").remove();
                    el.removeClass("no-active");
                    el.find('.eta_block').show();
                    el.find("input[name='order_id[]']").val($(ui.draggable).find('.order_id').data('id'));
                    el.find("input[name='type[]']").val(type);
                    el.find("input[name='type_id[]']").val($(ui.draggable).find('.type_id').data('id'));
                    el.addClass("active-green");
                    el.addClass("pharmacy"+$(ui.draggable).find('.type_id').data('id'));
                    el.append("<p class='order'>Order: "+$(ui.draggable).find('.order_id').data('id')+"</p>");
                    el.append("<p>"+$(ui.draggable).find('.left .type_id').text()+"</p>");
                }
                if(orderIds.indexOf($(ui.draggable).find('.order_id').data('id').toString())==-1) {
                    orderIds.push($(ui.draggable).find('.order_id').data('id').toString());
                }
                $(ui.draggable).hide();
                updateOfficeId(orderIds);
            }
            if(type=='office') {
                el.find("p").remove();
                el.removeClass("no-active");
                el.find('.eta_block').show();
                el.find("input[name='order_id[]']").val($(ui.draggable).find('.order_id').data('id'));
                el.find("input[name='type[]']").val(type);
                el.find("input[name='type_id[]']").val($(ui.draggable).find('.type_id').data('id'));
                el.addClass("active-yellow");
                el.addClass("office"+$(ui.draggable).find('.type_id').data('id'));
                el.find("input[name='order_id[]']").val('');
                el.append("<p class='order'>Orders: <button type='button' class='btn btn-light office-order-picker' data-office-order-picker>Choose orders</button></p>");
                el.append("<p>Office #"+$(ui.draggable).find('.order_id').data('id')+"</p>");
                updateOfficeId(orderIds);
            }
        }
    });
    reload_priority();
});
$(window).resize(function() {
    reload_priority();
});
$('#load_map').on('click',function(){
    $(this).hide();
    $('.block_map').show();
    if (window.google && window.google.maps) {
        if (google.maps.geometry) {
            initMap();
        } else {
            google.maps.importLibrary('geometry').then(initMap).catch(function () {
                $('#route-map-message').text('The map could not load. Try Show Map again.').prop('hidden', false);
                $('#load_map').show();
            });
        }
        return;
    }
    var s = document.createElement("script");
    s.type = "text/javascript";
    s.src = "https://maps.googleapis.com/maps/api/js?key=" + encodeURIComponent(window.driverRoutePage.mapsKey || '') + "&region=US&language=en&libraries=geometry&v=weekly&callback=initMap";
    s.onerror = function () {
        $('#route-map-message').text('The map could not load. Try Show Map again.').prop('hidden', false);
        $('#load_map').show();
    };
    $('#footerScript').append(s);
});
