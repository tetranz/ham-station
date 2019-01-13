const hamstationApp = (function ($) {

  // ----- UI Controller -----
  const uiController = (function () {

    let map = null;
    let mapData;
    let rectangles = [];
    let gridLabels = [];
    let markers = [];
    let txtOverlay;
    let gridKeys = ['center', 'northWest', 'north', 'northEast', 'east', 'southEast', 'south', 'southWest', 'west'];

    function selectQueryType(queryType) {
      const findClass = `query-input-${queryType}`;
      $('.query-input').each((index, element) => {
        let $element = $(element);
        if ($element.hasClass(findClass)) {
          $element.removeClass('hidden');
        }
        else {
          $element.addClass('hidden');
        }
      });
    }

    function showMap() {
      let center = {lat: mapData.mapCenterLat, lng: mapData.mapCenterLng};
      let map_container = document.querySelector('.map-container');

      if (!map) {
        map = new google.maps.Map(map_container, {
          zoom: 14,
          center: center
        });

        $('.map-container').show();
      }
    }

    function drawGridsquares(show) {
      rectangles.forEach((el, index) => {
        rectangles[index].setMap(null);
        rectangles[index] = null;
      });

      rectangles = [];

      if (show) {
        gridKeys.forEach(el => drawGridsquare(mapData[el]));
      }
    }

    function drawGridsquare(subsquare) {
      let rectangle = new google.maps.Rectangle({
        strokeColor: '#000000',
        strokeOpacity: 0.5,
        strokeWeight: 1,
        fillOpacity: 0,
        map: map,
        bounds: {
          north: subsquare.latNorth,
          south: subsquare.latSouth,
          east: subsquare.lngEast,
          west: subsquare.lngWest
        }
      });

      rectangles.push(rectangle);
    }

    function writeGridlabels(show) {
      gridLabels.forEach((el, index) => {
        gridLabels[index].setMap(null);
        gridLabels[index] = null;
      });

      gridLabels = [];

      if (show) {
        gridKeys.forEach(el => writeGridLabel(mapData[el]));
      }
    }

    function writeGridLabel(subsquare) {
      gridLabels.push(new txtOverlay(subsquare.latCenter, subsquare.lngCenter, subsquare.code, "grid-marker", map));
    }

    function drawMarkers(show) {
      markers.forEach((el, index) => {
        markers[index].setMap(null);
        markers[index] = null;
      });

      markers = [];

      if (show) {
        mapData.stations.forEach(el => drawMarker(el));
      }
    }

    function drawMarker(address) {
      marker = new google.maps.Marker({
        position: {lat: address.lat, lng: address.lng},
        map: map,
        label: address.stations[0].callsign + (address.stations.length > 1 ? '+' : '')
      });

      markers.push(marker);
    }

    return {
      init: txtOl => txtOverlay = txtOl,
      setMapData: md => mapData = md,
      selectQueryType: selectQueryType,
      showMap: showMap,
      drawGridsquares: drawGridsquares,
      writeGridLabels: writeGridlabels,
      drawMakers: drawMarkers
    };

  })();

  // ----- Main controller -----
  const controller = (function (uiCtrl) {
    let context;

    let setupEventListeners = () => {
      $('input[type=radio][name=query_type]', context).change(e => {
        uiCtrl.selectQueryType(e.target.value);
      });

      $('#edit-submit', context).click(e => {
        e.preventDefault();
        let queryType =  $('input[type=radio][name=query_type]').val();

        let postData = {
          queryType: queryType
        };

        if (queryType == 'c') {
          postData.value = $('#edit-callsign').val();
        }

        $.post('/ham-map-ajax', postData, (data) => {
          console.log(data);
          uiCtrl.setMapData(data);
          uiCtrl.showMap();
          uiCtrl.drawGridsquares(true);
          uiCtrl.writeGridLabels(true);
          uiCtrl.drawMakers(true);
        });

      });

      $('#edit-show-gridlabels').click(e => {
        uiCtrl.drawGridsquares(e.target.checked);
        uiCtrl.writeGridLabels(e.target.checked);
      });
    };

    return {
      'init': (ctx, txtOl) => {
        context = ctx;
        uiCtrl.init(txtOl);
        setupEventListeners();
      }
    };
  })(uiController);

  return {
    'init': controller.init
  };

})(jQuery);

let txtOverlayLib = function () {

  var TxtOverlay = function(lat, lng, txt, cls, map) {

    // Now initialize all properties.
    this.pos = new google.maps.LatLng(lat, lng);
    this.txt_ = txt;
    this.cls_ = cls;
    this.map_ = map;

    // We define a property to hold the image's
    // div. We'll actually create this div
    // upon receipt of the add() method so we'll
    // leave it null for now.
    this.div_ = null;

    // Explicitly call setMap() on this overlay
    this.setMap(map);
  };

 // TxtOverlay.prototype = new google.maps.OverlayView();

  let onAdd = function() {

    // Note: an overlay's receipt of onAdd() indicates that
    // the map's panes are now available for attaching
    // the overlay to the map via the DOM.

    // Create the DIV and set some basic attributes.
    var div = document.createElement('DIV');
    div.className = this.cls_;

    div.innerHTML = this.txt_;

    // Set the overlay's div_ property to this DIV
    this.div_ = div;
    var overlayProjection = this.getProjection();
    var position = overlayProjection.fromLatLngToDivPixel(this.pos);
    div.style.left = position.x + 'px';
    div.style.top = position.y + 'px';
    div.style.position = 'absolute';
    // We add an overlay to a map via one of the map's panes.

    var panes = this.getPanes();
    panes.floatPane.appendChild(div);
  };

  let draw = function() {

    var overlayProjection = this.getProjection();

    // Retrieve the southwest and northeast coordinates of this overlay
    // in latlngs and convert them to pixels coordinates.
    // We'll use these coordinates to resize the DIV.
    var position = overlayProjection.fromLatLngToDivPixel(this.pos);

    var div = this.div_;
    div.style.left = position.x + 'px';
    div.style.top = position.y + 'px';
  };

//Optional: helper methods for removing and toggling the text overlay.
  let onRemove = function() {
    this.div_.parentNode.removeChild(this.div_);
    this.div_ = null;
  }
  let hide = function() {
    if (this.div_) {
      this.div_.style.visibility = "hidden";
    }
  }

  let show = function() {
    if (this.div_) {
      this.div_.style.visibility = "visible";
    }
  }

  let toggle = function() {
    if (this.div_) {
      if (this.div_.style.visibility == "hidden") {
        this.show();
      } else {
        this.hide();
      }
    }
  }

  let toggleDOM = function() {
    if (this.getMap()) {
      this.setMap(null);
    } else {
      this.setMap(this.map_);
    }
  }

  return {
    init: () => {
      TxtOverlay.prototype = new google.maps.OverlayView();
      TxtOverlay.prototype.onAdd = onAdd;
      TxtOverlay.prototype.draw = draw;
      TxtOverlay.prototype.onRemove = onRemove;
      TxtOverlay.prototype.hide = hide;
      TxtOverlay.prototype.toggle = toggle;
      TxtOverlay.prototype.toggleDOM = toggleDOM;
    },
    txtOverlay: TxtOverlay
  }
}();

(function (Drupal) {
  Drupal.behaviors.hamstation = {
    attach: (context, settings) => {
      txtOverlayLib.init();
      hamstationApp.init(context, txtOverlayLib.txtOverlay);
    }
  };
})(Drupal);

