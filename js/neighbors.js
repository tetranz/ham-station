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

      var markers = [];

      self.$view.find('.ham-station').each(function (index) {
        var $ham_station = $(this);
        var id = $ham_station.data('id');
        var loc_id = $ham_station.data('loc-id');
        var marker = null;
        var callsign = $ham_station.find('.callsign').text();

        if (loc_id) {
          marker = markers[loc_id];
          marker.setTitle(marker.getTitle() + "\n" + callsign);
          if (marker.getLabel().slice(-1) !== '+') {
            marker.setLabel(marker.getLabel() + '+');
          }
          return;
        }

        var lat = parseFloat($ham_station.data('lat'));
        var lng = parseFloat($ham_station.data('lng'));

        marker = new google.maps.Marker({
          position: {lat: lat, lng: lng},
          map: map,
          title: callsign,
          label: callsign
        });

        markers[id] = marker;
      });
    }

  };
}(jQuery));
