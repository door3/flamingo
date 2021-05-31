import 'popper.js';
import 'bootstrap';

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.helloWorld = {
    attach: function (context) {
      console.log('Hello World');
    }
  };

  Drupal.behaviors.matchHeight = {
    attach: function (context) {
      var selectors = [
        '.compare-reviews .card-body .form-item-dedicated-client-success-score',
        '.compare-reviews .card-body .form-item-dedicated-client-success-comment',
        '.compare-reviews .card-body .form-item-succeeding-together-comment',
        '.compare-reviews .card-body .form-item-get-it-done-comment',
        '.compare-reviews .card-body .form-item-drive-to-the-why-comment',
        '.compare-reviews .card-body .form-item-excellence-and-expertise-comment',
        '.compare-reviews .card-body .form-item-integrity-comment',
        '.compare-reviews .card-body .form-item-practice-leave-comment',
        '.compare-reviews .card-body .form-item-what-do-you-do-well',
        '.compare-reviews .card-body .form-item-what-are-the-areas-you-need-to-improve-comment',
        '.compare-reviews .card-body .form-item-the-objectives-for-skill-and-professional-development-comment',
        '.compare-reviews .card-body .form-item-additional-information-please-add-any-additional-info-comment',
        '#2020_performance_review_review--other_questions',
        '#2020_annual_performance_manager--pimary_manager_status_review',
      ];

      selectors.forEach(function(selector){
        $(selector).matchHeight(false);
      });
    }
  };

  Drupal.behaviors.compareWidth = {
    attach: function (context) {
      if($('.compare-reviews').length) {
        var review_count = $('.compare-reviews .views-row').length,
          width = 100 / review_count;

        $('.compare-reviews .views-row').width(width + '%');

        $('#2020_annual_performance_manager--name_of_reviewee').hide();
        $('#2020_annual_performance_manager--pimary_manager_status_review').hide();
      }
    }
  };

  Drupal.behaviors.textareaStretch = {
    attach: function (context) {
      var $textarea = $('.form-textarea');

      if ($textarea.length) {
        $textarea.each(function () {
          this.style.height = (this.scrollHeight)+"px";
        })
      }

      $('textarea').on('input', function (){
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + "px";
      });
    }
  };

})(jQuery, Drupal);
