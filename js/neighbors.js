(function ($) {
  Drupal.behaviors.ham_neighbors = {
    $view: null,
    active_infowindow: null,

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
      var infowindows = [];

      self.$view.find('.ham-station').each(function (index) {
        var $ham_station = $(this);
        var id = $ham_station.data('id');
        var loc_id = $ham_station.data('loc-id');
        var marker;
        var infowindow;
        var callsign = $ham_station.find('.callsign').text();

        if (loc_id) {
          marker = markers[loc_id];
          if (marker.getLabel().slice(-1) !== '+') {
            marker.setLabel(marker.getLabel() + '+');
          }
          infowindow = infowindows[loc_id];
          infowindow.setContent(infowindow.getContent() + $ham_station.html());
          return;
        }

        var lat = parseFloat($ham_station.data('lat'));
        var lng = parseFloat($ham_station.data('lng'));

        marker = new google.maps.Marker({
          position: {lat: lat, lng: lng},
          map: map,
          label: callsign
        });

        markers[id] = marker;

        infowindow = new google.maps.InfoWindow({
          content: "<div class='infowindow'>" + $ham_station.html()
        });

        infowindows[id] = infowindow;

        marker.addListener('click', function () {
          if (self.active_infowindow) {
            self.active_infowindow.close();
          }
          infowindow.open(self.map, marker);
          self.active_infowindow = infowindow;
        });

      });

      var infowindow;
      for (var id in infowindows) {
        infowindow = infowindows[id];
        infowindow.setContent(infowindow.getContent() + "</div>");
      }
    }

  };
}(jQuery));
