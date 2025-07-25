
/*
Template Name: Adminox - Responsive Bootstrap 4 Admin Dashboard
Author: CoderThemes
Version: 2.0.0
Website: https://coderthemes.com/
Contact: support@coderthemes.com
File: Tour init js
*/

$(document).ready(function () {

    // Define the tour!
    var tour = {
        id: "my-intro",
        steps: [
            {
                target: "logo-tour",
                title: "Logo Here",
                content: "If several languages coalesce.",
                placement: 'bottom',
                yOffset: 15,
                zindex: 999
            },
            {
                target: 'heading-title-tour',
                title: "Heading Text",
                content: "It will be as simple as Occidental.",
                placement: 'top',
                zindex: 999
            },
            {
                target: 'register-tour',
                title: "Register Account",
                content: "Create your Account Here.",
                placement: 'bottom',
                zindex: 999
            },
            {
                target: 'thankyou-tour',
                title: "Thank you !",
                content: "Thank you for a visit.",
                placement: 'top',
                zindex: 999
            }
        ],
        showPrevButton: true
    };

    // Start the tour!
    hopscotch.startTour(tour);
});
