// The driver route page: the right-click menu on stops and the pay-type fields.
$(document).ready(function() {
    var menu = new BootstrapMenu('.plus-item.active-yellow,.plus-item.active-blue,.plus-item.active-green', {
        fetchElementData: function($rowElem) {
            return $rowElem;
        },
        actions: [{
            name: ' Delete',
            iconClass: 'mdi mdi-trash-can',
            onClick: function(el) {
                if(el.find("input[name='type[]']").val()=='patient') {
                    var arr = [];
                    if($(el).find('select').is('select')) {
                        $.each($(el).find('select').val(), function( index, value ) {
                            $(el).find('select').find('option[value="'+value+'"]').remove();
                            arr.push(value.toString());
                            $(".patient_block").find('.order_id[data-id="'+value+'"][data-type="patient"]').next().next('.type_id[data-id="'+el.find("input[name='type_id[]']").val()+'"]').parent('.left').parent('.patient_block').show();
                        });
                        if($(el).find('select option').length==0) {
                            $(el).remove();
                        }
                        $(el).find('.multiple_select').multiselect('rebuild');
                        $(el).find('.multiple_select').multiselect('selectAll', false);
                        $(el).find('.multiple_select').change();
                    } else {
                        $(".patient_block").find('.order_id[data-id="'+el.find("input[name='order_id[]']").val()+'"][data-type="patient"]').next().next('.type_id[data-id="'+el.find("input[name='type_id[]']").val()+'"]').parent('.left').parent('.patient_block').show();
                        $(el).remove();
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
                    if($(el).find('select').is('select')) {
                        $.each($(el).find('select').val(), function( index, value ) {
                            $(el).find('select').find('option[value="'+value+'"]').remove();
                            arr.push(value.toString());
                            $(".pharmacie_block").find('.order_id[data-id="'+value+'"][data-type="pharmacy"]').next().next('.type_id[data-id="'+el.find("input[name='type_id[]']").val()+'"]').parent('.left').parent('.pharmacie_block').show();
                        });
                        if($(el).find('select option').length==0) {
                            $(el).remove();
                        }
                        $(el).find('.multiple_select').multiselect('rebuild');
                        $(el).find('.multiple_select').multiselect('selectAll', false);
                        $(el).find('.multiple_select').change();
                    } else {
                        $(".pharmacie_block").find('.order_id[data-id="'+el.find("input[name='order_id[]']").val()+'"][data-type="pharmacy"]').next().next('.type_id[data-id="'+el.find("input[name='type_id[]']").val()+'"]').parent('.left').parent('.pharmacie_block').show();
                        $(el).remove();
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
                    $(el).remove();
                }
                reload_priority();
            }
        }]
    });
    var menu2 = new BootstrapMenu('.pharmacie_block,.patient_block,.office_block', {
        fetchElementData: function($rowElem) {
            return $rowElem;
        },
        actions: [{
            name: ' Transfer to Route',
            iconClass: 'mdi mdi-transfer-left',
            onClick: function(el2) {
                var type = $(el2).find('.order_id').data('type');
                $(".wrapper").addClass("activeted");
                var el = $(".wrapper").find(".plus-item.no-active").first();
                if(type=='patient') {
                    if($('.plus-item').is('.patient'+$(el2).find('.type_id').data('id'))) {
                        if($('.plus-item.patient'+$(el2).find('.type_id').data('id')).find('select').is('select')) {
                            $('.plus-item.patient'+$(el2).find('.type_id').data('id')).find('select').append('<option selected value="'+$(el2).find('.order_id').data('id')+'">'+$(el2).find('.order_id').data('id')+' ('+$(el2).find('.text-center.date span').first().clone().children().remove().end().text().replace(/ /g,"")+')</option>');
                            $('.plus-item.patient'+$(el2).find('.type_id').data('id')).find('.multiple_select').multiselect('rebuild');
                            $('.plus-item.patient'+$(el2).find('.type_id').data('id')).find('.multiple_select').multiselect('selectAll', false);
                            $('.plus-item.patient'+$(el2).find('.type_id').data('id')).find('.multiple_select').change();
                        } else {
                            $('.plus-item.patient'+$(el2).find('.type_id').data('id')).find('.order').html('Order: <select class="multiple_select" multiple="multiple"><option selected value="'+$('.plus-item.patient'+$(el2).find('.type_id').data('id')).find("input[name='order_id[]']").val()+'">'+$('.plus-item.patient'+$(el2).find('.type_id').data('id')).find("input[name='order_id[]']").val()+' ('+$('.plus-item.patient'+$(el2).find('.type_id').data('id')).find("span.badge").first().clone().children().remove().end().text().replace(/ /g,"")+')</option><option selected value="'+$(el2).find('.order_id').data('id')+'">'+$(el2).find('.order_id').data('id')+' ('+$(el2).find('.text-center.date span').first().clone().children().remove().end().text().replace(/ /g,"")+')</option></select>');
                            $('.plus-item.patient'+$(el2).find('.type_id').data('id')).find('.multiple_select').multiselect(window.routeOrderSelect.options);
                            $('.plus-item.patient'+$(el2).find('.type_id').data('id')).find('.multiple_select').multiselect('selectAll', false);
                            $('.plus-item.patient'+$(el2).find('.type_id').data('id')).find('.multiple_select').change();
                        }
                    } else {
                        el.find("p.number").remove();
                        el.removeClass("no-active");
                        el.find('.eta_block').show();
                        el.find("input[name='order_id[]']").val($(el2).find('.order_id').data('id'));
                        el.find("input[name='type[]']").val(type);
                        el.find("input[name='type_id[]']").val($(el2).find('.type_id').data('id'));
                        el.addClass("active-blue");
                        el.addClass("patient"+$(el2).find('.type_id').data('id'));
                        el.append("<p class='order'>Order: "+$(el2).find('.order_id').data('id')+"</p>");
                        el.append("<p>Customer: "+$(el2).find('.type_id').data('id')+"</p>");
                        $(el2).find('.text-center.date span').first().clone().removeAttr('style').appendTo(el);
                        el.attr('title',$(el2).find('.text-center.date span').first().text());
                    }
                    if(orderIds.indexOf($(el2).find('.order_id').data('id').toString())==-1) {
                        orderIds.push($(el2).find('.order_id').data('id').toString());
                    }
                    $(el2).hide();
                    updateOfficeId(orderIds);
                }
                if(type=='pharmacy') {
                    if($('.plus-item').is('.pharmacy'+$(el2).find('.type_id').data('id'))) {
                        if($('.plus-item.pharmacy'+$(el2).find('.type_id').data('id')).find('select').is('select')) {
                            $('.plus-item.pharmacy'+$(el2).find('.type_id').data('id')).find('select').append('<option selected value="'+$(el2).find('.order_id').data('id')+'">'+$(el2).find('.order_id').data('id')+' ('+$(el2).find('.text-center.date span').first().clone().children().remove().end().text().replace(/ /g,"")+')</option>');
                            $('.plus-item.pharmacy'+$(el2).find('.type_id').data('id')).find('.multiple_select').multiselect('rebuild');
                            $('.plus-item.pharmacy'+$(el2).find('.type_id').data('id')).find('.multiple_select').multiselect('selectAll', false);
                            $('.plus-item.pharmacy'+$(el2).find('.type_id').data('id')).find('.multiple_select').change();
                        } else {
                            $('.plus-item.pharmacy'+$(el2).find('.type_id').data('id')).find('.order').html('Order: <select class="multiple_select" multiple="multiple"><option selected value="'+$('.plus-item.pharmacy'+$(el2).find('.type_id').data('id')).find("input[name='order_id[]']").val()+'">'+$('.plus-item.pharmacy'+$(el2).find('.type_id').data('id')).find("input[name='order_id[]']").val()+' ('+$('.plus-item.patient'+$(el2).find('.type_id').data('id')).find("span.badge").first().clone().children().remove().end().text().replace(/ /g,"")+')</option><option selected value="'+$(el2).find('.order_id').data('id')+'">'+$(el2).find('.order_id').data('id')+' ('+$(el2).find('.text-center.date span').first().clone().children().remove().end().text().replace(/ /g,"")+')</option></select>');
                            $('.plus-item.pharmacy'+$(el2).find('.type_id').data('id')).find('.multiple_select').multiselect(window.routeOrderSelect.options);
                            $('.plus-item.pharmacy'+$(el2).find('.type_id').data('id')).find('.multiple_select').multiselect('selectAll', false);
                            $('.plus-item.pharmacy'+$(el2).find('.type_id').data('id')).find('.multiple_select').change();
                        }
                    } else {
                        el.find("p.number").remove();
                        el.removeClass("no-active");
                        el.find('.eta_block').show();
                        el.find("input[name='order_id[]']").val($(el2).find('.order_id').data('id'));
                        el.find("input[name='type[]']").val(type);
                        el.find("input[name='type_id[]']").val($(el2).find('.type_id').data('id'));
                        el.addClass("active-green");
                        el.addClass("pharmacy"+$(el2).find('.type_id').data('id'));
                        el.append("<p class='order'>Order: "+$(el2).find('.order_id').data('id')+"</p>");
                        el.append("<p>"+$(el2).find('.left .type_id').text()+"</p>");
                    }
                    if(orderIds.indexOf($(el2).find('.order_id').data('id').toString())==-1) {
                        orderIds.push($(el2).find('.order_id').data('id').toString());
                    }
                    $(el2).hide();
                    updateOfficeId(orderIds);
                }
                if(type=='office') {
                    el.find("p.number").remove();
                    el.removeClass("no-active");
                    el.find('.eta_block').show();
                    el.find("input[name='order_id[]']").val($(el2).find('.order_id').data('id'));
                    el.find("input[name='type[]']").val(type);
                    el.find("input[name='type_id[]']").val($(el2).find('.type_id').data('id'));
                    el.addClass("active-yellow");
                    el.addClass("office"+$(el2).find('.type_id').data('id'));
                    el.find("input[name='order_id[]']").val('');
                    el.append("<p class='order'>Orders: <button type='button' class='btn btn-light office-order-picker' data-office-order-picker>Choose orders</button></p>");
                    el.append("<p>Office #"+$(el2).find('.order_id').data('id')+"</p>");
                    updateOfficeId(orderIds);
                }
            }
        }]
    });
    $('.multiple_select').multiselect(window.routeOrderSelect.options);
    $(".select2").selectize()
    $(document).on('change','.tariff [name="type_pay"]',function(){
        if($(this).val()=="1"){
            $(".tariff .input-group").show(200);
        } else {
            $(".tariff .input-group").hide(200);
        }
    });
    $('.modal2 input[name="priority"]').TouchSpin({
        min: 1,
        max: $(".wrapper").find(".active-green,.active-yellow,.active-blue").length,
        step: 1,
    });
    $('body').on('click','.patient_block.special',function(){
        $(this).toggleClass('open');
    });
    $('body').on('click','.plus-item.active-blue.special',function(){
        $(this).toggleClass('open');
    });
});
