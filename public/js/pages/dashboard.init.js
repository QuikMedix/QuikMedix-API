/*
Template Name: Lexa - Responsive Bootstrap 4 Admin Dashboard
Author: Themesbrand
Website: https://themesbrand.com/
Contact: themesbrand@gmail.com
File: Dashboard
*/
function initAll () {
!function($) {
    "use strict";

    var Dashboard = function() {};
    
    //creates area chart
    Dashboard.prototype.createAreaChart = function (element, data, xkey, ykeys, labels, lineColors) {
        Morris.Line({
            element: element,
            data: data,
            xkey: xkey,
            ykeys: ykeys,
            labels: labels,
            lineColors: lineColors,
            parseTime: false,
            yLabelFormat: function (y) { return Math.round(y); }
          });
    },

    //creates Donut chart
    Dashboard.prototype.createDonutChart = function (element, data, colors) {
        Morris.Donut({
            element: element,
            data: data,
            resize: true,
            colors: colors
        });
    },

    //creates Stacked chart
    Dashboard.prototype.createStackedChart  = function(element, data, xkey, ykeys, labels, lineColors) {
        Morris.Bar({
            element: element,
            data: data,
            xkey: xkey,
            ykeys: ykeys,
            stacked: true,
            labels: labels,
            hideHover: 'auto',
            resize: true, //defaulted to true
             gridLineColor: 'rgba(108, 120, 151, 0.1)',
            barColors: lineColors
        });
    },

    //creates Stacked chart
    Dashboard.prototype.createBarChart  = function(element, data, xkey, ykeys, labels, lineColors) {
        Morris.Bar({
            element: element,
            data: data,
            xkey: xkey,
            ykeys: ykeys,
            labels: labels,
            hideHover: 'auto',
            resize: true, //defaulted to true
            gridLineColor: 'rgba(108, 120, 151, 0.1)',
            barSizeRatio: .4,
            barColors: lineColors
        });
    },

    $('#sparkline').sparkline([8, 6, 4, 7, 10, 12, 7, 4, 9, 12, 13, 11, 12], {
        type: 'bar',
        height: '130',
        barWidth: '10',
        barSpacing: '7',
        barColor: '#7A6FBE'
    });
  
    
    Dashboard.prototype.init = function() {
        //creating donut chart
        var $donutData = [
            {label: "Apple Driver", value: app_ios_drivers},
            {label: "Apple Customer", value: app_ios_users},
            {label: "Android Driver", value: app_android_drivers},
            {label: "Android Customer", value: app_android_users}
        ];
        if(document.getElementById('morris-donut-example')!==null) {
            this.createDonutChart('morris-donut-example', $donutData, ['#30898b', '#7a6fbe', '#28bbe3', '#262649']);
        }

        var $stckedData  = dataForChartDelivered;
        if(document.getElementById('morris-bar-stacked')!==null) {
            this.createStackedChart('morris-bar-stacked', $stckedData, 'y', ['a'], ['Delivered'], ['#7c7cb4']);
        }
        var lineDataDelivered = lineDataDelivered7days;
        if(document.getElementById('morris-line-example')!==null) {
            this.createBarChart('morris-line-example', lineDataDelivered,'y',['a','b'],['Created','Delivered'],['#7c7cb4','#30898b']);
        }
    },
    //init
    $.Dashboard = new Dashboard, $.Dashboard.Constructor = Dashboard
}(window.jQuery),

//initializing 
function($) {
    "use strict";
    $.Dashboard.init();
}(window.jQuery);
}