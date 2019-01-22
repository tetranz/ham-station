const hamstationApp = (function ($) {

  // ----- UI Controller -----
  const uiController = (function () {

    let map = null;
    let mapData;
    let rectangles = [];
    let gridLabels = [];
    let markers = new Map();
    let locationMap = new Map();
    let activeInfoWindow = null;
    let txtOverlay;
    let mapCenterChangedListener = null;

    let gridKeys = ['center', 'northWest', 'north', 'northEast', 'east', 'southEast', 'south', 'southWest', 'west'];

    function selectQueryType(queryType) {
      let labels = {
        c:['Callsign', 'Enter a callsign.'],
        g:['Gridsquare', 'Enter a six character grid subsquare.'],
        z:['Zip code', 'Enter a five digit zip code.']
      };

      if ('cgz'.indexOf(queryType) > -1) {
        document.querySelector('.ham-map-form .query-other label').innerHTML = labels[queryType][0];
        document.querySelector('.ham-map-form .query-other .description').innerHTML = labels[queryType][1];
        document.querySelector('.ham-map-form .query-other').classList.remove('hidden');
        document.querySelector('.ham-map-form .query-address').classList.add('hidden');
      }
      else {
        document.querySelector('.ham-map-form .query-other').classList.add('hidden');
        document.querySelector('.ham-map-form .query-address').classList.remove('hidden');
      }
    }

    function createMap(mapCenterChangedListener) {
      if (map) {
        return;
      }

      let map_container = document.querySelector('.map-container');

      map = new google.maps.Map(map_container, {
        zoom: 13
      });

      map.addListener('center_changed', function () {
        mapCenterChangedListener(map.getCenter());
      });
    }

    function setMapCenter() {
      map.setCenter({lat: mapData.mapCenterLat, lng: mapData.mapCenterLng});
    }

    function clearInfoWindow() {
      if (activeInfoWindow) {
        activeInfoWindow.close();
        activeInfoWindow = null;
      }
    }

    function clearRectangles() {
      rectangles.forEach((el, index) => {
        rectangles[index].setMap(null);
        rectangles[index] = null;
      });

      rectangles = [];
    }

    function clearGridLabels() {
      gridLabels.forEach((el, index) => {
        gridLabels[index].setMap(null);
        gridLabels[index] = null;
      });

      gridLabels = [];
    }

    function drawGridsquares(show) {
      clearRectangles();

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
      clearGridLabels();

      if (show) {
        gridKeys.forEach(el => writeGridLabel(mapData[el]));
      }
    }

    function writeGridLabel(subsquare) {
      gridLabels.push(new txtOverlay(subsquare.latCenter, subsquare.lngCenter, subsquare.code, "grid-marker", map));
    }

    function drawMarkers() {
      for (var [id, marker] of markers) {
        if (!locationMap.has(id)) {
          marker.setMap(null);
          markers.delete(id);
        }
      }

      mapData.locations.forEach(location => {
        if (!markers.has(location.id)) {
          drawMarker(location);
        }
      });
    }

    function drawMarker(location) {
      let stationCount = 0;
      location.addresses.forEach(address => {
        address.stations.forEach(station => stationCount++);
      });

      let marker = new google.maps.Marker({
        position: {lat: location.lat, lng: location.lng},
        map: map,
        label: location.addresses[0].stations[0].callsign + (stationCount > 1 ? '+' : '')
      });

      markers.set(location.id, marker);

      marker.addListener('click', e => {
        clearInfoWindow();

        let addresses = [];
        let lastIndex = location.addresses.length - 1;
        let multi = location.addresses.length > 1;
        location.addresses.forEach((address, index) => {
          let classes = ['address'];
          if (multi) {
            if (index === 0) {
              classes.push('first');
            }
            else if(index === lastIndex)
            {
              classes.push('last');
            }
          }
          addresses.push(`<div class="${classes.join(' ')}">${writeAddress(address)}</div>`);
        });

        let classes = ['infowindow'];
        if (multi) {
          classes.push('multi');
        }
        let infowindow = new google.maps.InfoWindow({
          content: `<div class="${classes.join(' ')}">${addresses.join('')}</div>`
        });

        infowindow.open(map, marker);
        activeInfoWindow = infowindow;

        infowindow.addListener('closeclick', () => {
          activeInfoWindow = null;
        });

      });
    }

    function writeAddress(address) {
      let stations = [];
      let lastIndex = address.stations.length - 1;
      let multi = address.stations.length > 1;
      address.stations.forEach((station, index) => {
        let classes = ['station'];
        if (multi) {
          if (index === 0) {
            classes.push('first');
          }
          else if(index === lastIndex)
          {
            classes.push('last');
          }
        }
        stations.push(`<div class="${classes.join(' ')}">${writeStation(station)}</div>`)
      });

      let address2 = address.address2 ? address.address2 + '<br>' : '';

      return `<div class="stations">${stations.join('')}</div><div>
        ${address.address1}<br>
        ${address2}
        ${address.city}, ${address.state} ${address.zip}</div>`;
    }

    function writeStation(station) {
      let opclass = station.operatorClass ? (' ' + station.operatorClass) : '';

      return `
      <span>${station.callsign}</span> <a href="https://www.qrz.com/db/${station.callsign}" target="_blank">qrz.com</a>${opclass}<br>
      ${station.name}`;
    }

    function showError(error) {
      let element = document.querySelector('.error-message');
      element.innerHTML = error;
      if (error) {
        element.classList.remove('hidden');
      }
      else {
        element.classList.add('hidden');
      }
    }

    function setMapData(data) {
      mapData = data;
      locationMap.clear();
      mapData.locations.forEach(location => {
        locationMap.set(location.id, true);
      });
    }

    return {
      init: txtOl => txtOverlay = txtOl,
      setMapData: setMapData,
      selectQueryType: selectQueryType,
      createMap: createMap,
      setMapCenter: setMapCenter,
      drawGridsquares: drawGridsquares,
      writeGridLabels: writeGridlabels,
      drawMarkers: drawMarkers,
      showError: showError
    };

  })();

  // ----- Main controller -----
  const controller = (function (uiCtrl) {
    let context;
    let center_moved_timer_id = null;
    let setCenterEnabled = false;
    let autocompleteLocation = null;

    function mapDataRequest(query, setCenter) {
      let showGrid = document.getElementById('edit-show-gridlabels').checked;

      $.post('/ham-map-ajax',
        query,
        (data) => {
          if (data.hasOwnProperty('error')) {
            uiCtrl.showError(data.error);
            return;
          }

          uiCtrl.showError('');
          uiCtrl.setMapData(data);
          uiCtrl.drawGridsquares(showGrid);
          uiCtrl.writeGridLabels(showGrid);
          uiCtrl.drawMarkers();

          if (setCenter) {
            uiCtrl.setMapCenter();
          }

          $('.map-container').show();
        }
      );
    }

    function getAndFormatQuery() {
      let queryType = document.querySelector('input[type=radio][name=query_type]:checked').value;

      if ('cgz'.indexOf(queryType) > 1) {
        let valueElement = document.getElementById('edit-query');

        if (queryType == 'c') {
          return getCallsignQuery(valueElement);
        }

        if (queryType == 'g') {
          return getGridsquareQuery(valueElement);
        }

        if (queryType == 'z') {
          return getZipcodeQuery(valueElement);
        }
      }
      else if (queryType == 'a') {
        return getAddressQuery();
      }
    }

    function getCallsignQuery(valueElement) {
      let value = valueElement.value.trim();

      if (!value) {
        uiCtrl.showError('Please enter a callsign.');
        return null;
      }

      valueElement.value = value.toUpperCase();
      return {queryType:'c', value:valueElement.value};
    }

    function getGridsquareQuery(valueElement) {
      let value = valueElement.value.trim();

      if (!value.match(/^[A-R]{2}\d\d[a-x]{2}$/i)) {
        uiCtrl.showError('Please enter a six character gridsquare.');
        return null;
      }

      valueElement.value = value.substring(0, 2).toUpperCase() + value.substring(2, 4) + value.substring(4).toLowerCase();
      return {queryType:'g', value:valueElement.value};
    }

    function getZipcodeQuery(valueElement) {
      let value = valueElement.value.trim();

      if (!value.match(/^\d{5}$/)) {
        uiCtrl.showError('Please enter a five digit zip code.');
        return null;
      }

      return {queryType:'z', value:value};
    }

    function getAddressQuery() {
      return {queryType:'latlng', value:`${autocompleteLocation.lat()},${autocompleteLocation.lng()}}`}
    }

      let setupEventListeners = () => {
      $('input[type=radio][name=query_type]', context).change(e => {
        uiCtrl.selectQueryType(e.target.value);
      });

      $('#edit-submit', context).click(e => {
        e.preventDefault();

        let query = getAndFormatQuery();
        if (!query) {
          return;
        }

        setCenterEnabled = false;
        mapDataRequest(query, true);
      });

      function mapCenterChanged(location) {
        if (center_moved_timer_id) {
          clearTimeout(center_moved_timer_id);
        }

        center_moved_timer_id = setTimeout(location => {
          if (setCenterEnabled) {
            mapDataRequest({queryType:'latlng', value:`${location.lat()},${location.lng()}}`}, false);
          }
          else {
            setCenterEnabled = true;
          }
        }, 2000, location);
      }

      $('#edit-show-gridlabels').click(e => {
        uiCtrl.drawGridsquares(e.target.checked);
        uiCtrl.writeGridLabels(e.target.checked);
      });

      uiCtrl.createMap(mapCenterChanged);
    };

    function setupAutocomplete() {
      let autocomplete = new google.maps.places.Autocomplete(
        document.getElementById('edit-address')
      );

      autocomplete.setFields(['geometry.location']);

      autocomplete.addListener('place_changed', () => {
        let place = autocomplete.getPlace();
        autocompleteLocation = place.geometry.location;
      });
    }

    return {
      'init': (ctx, txtOl) => {
        context = ctx;
        uiCtrl.init(txtOl);
        setupEventListeners();
        setupAutocomplete();
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

