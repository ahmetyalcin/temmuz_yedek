
/*
Template Name: Adminox - Responsive Bootstrap 4 Admin Dashboard
Author: CoderThemes
Version: 2.0.0
Website: https://coderthemes.com/
Contact: support@coderthemes.com
File: Peity init js
*/

!function($) {
    "use strict";

    var PeityChart = function() {};

    //create line
    PeityChart.prototype.createLine = function ($element, $strokeColor, $fillColor, $width ,$height) {
        $($element).peity("line", {
            fill: $strokeColor,
            stroke: $fillColor,
            width: $width,
            height: $height
        });
        return $($element);
    },

    //init
    PeityChart.prototype.init = function() {
        //live graph
        var updatingChart = this.createLine(".updating-chart",'#9261c6','#9261c6', 120,40);

        setInterval(function() {
            var random = Math.round(Math.random() * 10)
            var values = updatingChart.text().split(",")
            values.shift()
            values.push(random)

            updatingChart
                .text(values.join(","))
                .change()
        }, 1000);
    },
    //init
    $.PeityChart = new PeityChart, $.PeityChart.Constructor = PeityChart
}(window.jQuery),

//initializing 
function($) {
    "use strict";
    $.PeityChart.init()
}(window.jQuery);
