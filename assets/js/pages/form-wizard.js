
/*
Template Name: Adminox - Responsive Bootstrap 4 Admin Dashboard
Author: CoderThemes
Version: 2.0.0
Website: https://coderthemes.com/
Contact: support@coderthemes.com
File: Form wizard init js
*/

!function($) {
  "use strict";

  var FormWizard = function() {};

  FormWizard.prototype.createBasic = function($form_container) {
      $form_container.children("div").steps({
          headerTag: "h3",
          bodyTag: "section",
          transitionEffect: "slideLeft",
          onFinishing: function (event, currentIndex) { 
              //NOTE: Here you can do form validation and return true or false based on your validation logic
              console.log("Form has been validated!");
              return true; 
          }, 
          onFinished: function (event, currentIndex) {
             //NOTE: Submit the form, if all validation passed.
              console.log("Form can be submitted using submit method. E.g. $('#basic-form').submit()"); 
              $("#basic-form").submit();

          }
      });
      return $form_container;
  },
  //creates form with validation
  FormWizard.prototype.createValidatorForm = function($form_container) {
      $form_container.validate({
          errorPlacement: function errorPlacement(error, element) {
              element.after(error);
          }
      });
      $form_container.children("div").steps({
          headerTag: "h3",
          bodyTag: "section",
          transitionEffect: "slideLeft",
          onStepChanging: function (event, currentIndex, newIndex) {
              $form_container.validate().settings.ignore = ":disabled,:hidden";
              return $form_container.valid();
          },
          onFinishing: function (event, currentIndex) {
              $form_container.validate().settings.ignore = ":disabled";
              return $form_container.valid();
          },
          onFinished: function (event, currentIndex) {
              alert("Submitted!");
          }
      });

      return $form_container;
  },
  //creates vertical form
  FormWizard.prototype.createVertical = function($form_container) {
      $form_container.steps({
          headerTag: "h3",
          bodyTag: "section",
          transitionEffect: "fade",
          stepsOrientation: "vertical"
      });
      return $form_container;
  },
  FormWizard.prototype.init = function() {
      //initialzing various forms

      //basic form
      this.createBasic($("#basic-form"));

      //form with validation
      this.createValidatorForm($("#wizard-validation-form"));

      //vertical form
      this.createVertical($("#wizard-vertical"));
  },
  //init
  $.FormWizard = new FormWizard, $.FormWizard.Constructor = FormWizard
}(window.jQuery),

//initializing 
function($) {
  "use strict";
  $.FormWizard.init()
}(window.jQuery);