(function (Drupal, $) {
  Drupal.behaviors.ham_report = {
    attach: (context, settings) => {
      if (context !== document) {
        return;
      }

      let map_element = document.querySelector('.ham-map-report');

      if (!map_element) {
        return;
      }

      $.get('/ham-map/geocode-report-ajax', data => {
        map_element.innerHTML = data;
      });
    }
  };
})(Drupal, jQuery);
