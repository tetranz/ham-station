(function ($) {
  Drupal.behaviors.ham_neighbors = {
    $wrapper: null,
    map: null,
    markers: [],
    infowindows: [],
    active_infowindow: null,

    attach: function (context, settings) {
      var self = Drupal.behaviors.ham_neighbors;
      var mysettings = settings.ham_neighbors;
      self.$wrapper = $(".ham-neighbors-wrapper");

      if (mysettings.status == 1) {
        self.showMap(parseFloat(mysettings.lat), parseFloat(mysettings.lng));
      }

      self.$wrapper.find(".submit-button").once("click").click(function (e) {
        e.preventDefault();
        var callsign = self.$wrapper.find(".callsign-input").val().trim();

        if (!callsign) {
          return;
        }

        self.search(callsign);
      });

      self.$wrapper.on("click", "a.from-here", function (e) {
        e.preventDefault();
        var parts = this.href.split('/');
        var callsign = parts[parts.length - 1];
        self.search(callsign);
      });

      self.updateStatesDone();
    },

    search: function (callsign) {
      var self = Drupal.behaviors.ham_neighbors;
      self.$wrapper.find(".ajax-processing").removeClass('hidden').show();
      self.$wrapper.find(".submit-button").prop('disabled', true);
      $.post("/neighbors-ajax/call/" + callsign, this.processResponse);
    },

    processResponse: function (data) {
      var self = Drupal.behaviors.ham_neighbors;
      var delim = data.indexOf("$");
      var info = data.substr(0, delim).split("|");
      var status = info[0];
      var callsign = info[1];

      // This makes it bookmarkable.
      window.history.pushState({}, null, "/neighbors/" + callsign);

      self.$wrapper.find(".view-container").html(data.substr(delim + 1));

      if (status > 1) {
        self.$wrapper.find(".message").text(info[2]).show();
        self.$wrapper.find(".map-container").hide();
      }
      else {
        self.$wrapper.find(".message").text("").hide();
      }

      var $title = self.$wrapper.find(".results-title");
      if (status == 1) {
        self.$wrapper.find(".map-container").show();
        self.showMap(parseFloat(info[3]), parseFloat(info[4]));
        $title.find(".callsign").html(info[1]);
        $title.show();
        self.$wrapper.find(".info-block-2").show();
      }
      else {
        $title.hide();
        self.$wrapper.find(".info-block-2").hide();
      }

      self.$wrapper.find(".ajax-processing").hide();
      self.$wrapper.find(".submit-button").prop('disabled', false);
    },

    showMap: function (lat, lng) {
      var self = Drupal.behaviors.ham_neighbors;

      // Center the map on the closest store.
      var center = {lat: lat, lng: lng};
      var map_container = document.getElementsByClassName("map-container")[0];
      var tmp_id;

      if (!self.map) {
        // Build map for the first time.
        self.map = new google.maps.Map(map_container, {
          zoom: 15,
          center: center
        });
      }
      else {
        // Clear old markers.
        for (tmp_id in self.markers) {
          self.markers[tmp_id].setMap(null);
        }

        // Recenter existing map.
        self.map.setCenter(center);

        self.markers = [];
        self.infowindows = [];
      }

      self.$wrapper.find(".view-container .ham-station").each(function (index) {
        var $ham_station = $(this);
        var id = $ham_station.data('id');
        var loc_id = $ham_station.data('loc-id');
        var marker;
        var infowindow;
        var callsign = $ham_station.find('.callsign').text();

        if (loc_id) {
          marker = self.markers[loc_id];
          if (marker.getLabel().slice(-1) !== '+') {
            marker.setLabel(marker.getLabel() + '+');
          }
          infowindow = self.infowindows[loc_id];
          infowindow.setContent(infowindow.getContent() + $ham_station.html());
          return;
        }

        var lat = parseFloat($ham_station.data('lat'));
        var lng = parseFloat($ham_station.data('lng'));

        marker = new google.maps.Marker({
          position: {lat: lat, lng: lng},
          map: self.map,
          label: callsign
        });

        self.markers[id] = marker;

        infowindow = new google.maps.InfoWindow({
          content: "<div class='infowindow'>" + $ham_station.html()
        });

        self.infowindows[id] = infowindow;

        marker.addListener("click", function () {
          if (self.active_infowindow) {
            self.active_infowindow.close();
          }
          infowindow.open(self.map, marker);
          self.active_infowindow = infowindow;
        });

      });

      var infowindow;
      for (tmp_id in self.infowindows) {
        infowindow = self.infowindows[tmp_id];
        infowindow.setContent(infowindow.getContent() + "</div>");
      }

      self.$wrapper.find('.map-container').show();
    },

    updateStatesDone: function () {
      // Use ajax for this so we can cache the page.
      var self = Drupal.behaviors.ham_neighbors;

      var $done_element = self.$wrapper.find(".states-done");
      var $working_on_element = self.$wrapper.find(".states-working");

      if (!$done_element.length && !$working_on_element.length) {
        return;
      }

      $.ajax({
        url: "/neighbors-ajax/states-done-ajax"
      })
        .done(function (data) {
          if ($done_element.length) {
            $done_element.html(data.done.join(", "));
          }

          if ($working_on_element.length) {
            $working_on_element.html(data.working_on);
          }
      });
    }

  };
}(jQuery));
