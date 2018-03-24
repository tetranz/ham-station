(function ($) {
  Drupal.behaviors.ham_neighbors = {
    $view: null,
    attach: function (context, settings) {
      var self = this;
      var mysettings = settings.ham_neighbors;

      self.$view = $('.view-ham-neighbors');
      if (self.$view.length == 0) {
        return;
      }

      self.initMap(parseFloat(mysettings.lat), parseFloat(mysettings.lng));
    },
    
    initMap: function (lat, lng) {
      var self = this;

      var map = new google.maps.Map(document.getElementById("map"), {
        zoom: 15,
        center: {lat: lat, lng: lng}
      });

      self.$view.find('.ham-station').each(function (index) {
        var $ham_station = $(this);
        var lat = parseFloat($ham_station.data('lat'));
        var lng = parseFloat($ham_station.data('lng'));

        var marker = new google.maps.Marker({
          position: {lat: lat, lng: lng},
          map: map,
          draggable:true,
          title: $ham_station.find('.callsign').text()
        });

      });
    }

  };
}(jQuery));
