// Runs on every dashboard page once it has loaded. Moved as it was from the end of
// resources/views/layouts/master.blade.php, where it sat inline.
$(document).ready(function () {
    $('#select-state').selectize({
        sortField: 'text'
    });
    $('#select-state1').selectize({
        create: true,
        sortField: 'text'
    });
    $('.select-state').selectize({
        sortField: 'text'
    });
    $('.select-state1').selectize({
        create: true,
        sortField: 'text'
    });
    $("#search-inp").keyup(function() {
        var search = $(this).val();
        if(search!='' && search.length>2){
            $.get('/search/json?search='+search)
            .done(function(response) {
                var res = JSON.parse(response);
                $('.result-search2').html('');
                if(res.results.orders.length>0 || res.results.rxs.length>0 || res.results.users.length>0) {
                    for (const element of res.results.orders) {
                        var row = '<div class="col-2"></div><div class="col-8 mt-3 sblock orderm"><span class="badge">Order</span><h3>'+element.id+'</h3><hr><table class="table mb-3"><tbody><tr><td>Created: '+element.created+'</td><td>Status: <span style="position: unset;border-radius: 33px;font-size: 10px;" class="badge-pill badge-'+element.status_color+'">'+element.status_name+'</span></td></tr><tr><td>Delivered: '+element.finish+'</td><td>Pharmacy: '+element.pharmacy_name+' #'+element.pharmacy_id+'</td></tr><tr><td>Co-pay: $'+element.copay+' </td><td>Patient: '+element.user_name+' #'+element.user_id+'</td></tr></tbody></table><hr><a href="'+element.link+'" class="btnm">Details</a></div><div class="col-2"></div>';
                        $('.result-search2').append(row);
                    }
                    for (const element of res.results.users) {
                        var row = '<div class="col-2"></div><div class="col-8 mt-3  sblock patientm"><span class="badge">Patient</span><h3>'+element.name+' '+element.last_name+' #'+element.id+'</h3><hr><table class="table mb-3"><tbody><tr><td>Phone: '+element.phone+'</td><td>Orders: '+element.count_orders+'</td></tr><tr><td>'+element.address+' '+element.zip+'</td><td>Pharmacy: '+element.pharmacy_name+'</td></tr></tbody></table><hr><a href="'+element.link+'" class="btnm">Details</a></div><div class="col-2"></div>';
                        $('.result-search2').append(row);
                    }
                    for (const element of res.results.rxs) {
                        var row = '<div class="col-2"></div><div class="col-8 mt-3  sblock rxm"><span class="badge">RX</span><h3>'+element.rx_id+'</h3><hr><table class="table mb-3"><tbody><tr><td>Created: '+element.created+'</td><td>Order: #'+element.order_id+'</td></tr><tr><td>Delivered: '+element.finish+'</td><td>Co-pay: $'+element.copay+'</td></tr></tbody></table><hr><a href="'+element.link+'" class="btnm">Details</a></div><div class="col-2"></div>';
                        $('.result-search2').append(row);
                    }
                } else {
                    var row = '<div class="result-search-row"><b>Nothing found</b></div>';
                    $('.result-search2').append(row);
                }
                $('.result-search2').show();
            });
        } else {
            $('.result-search2').hide();
        }
    });
    $("#search-inp1").keyup(function() {
        if($(this).val()!=''){
            $('.result-search01').show();

        } else {
            $('.result-search01').hide();
        }
    });
});
$(window).on('load', function(){
    setTimeout(function(){
        if(typeof(initAll) != "undefined") {
            initAll();
        }
    }, 150);
});
