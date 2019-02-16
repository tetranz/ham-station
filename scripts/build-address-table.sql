# Once only use to build ham_address table.

INSERT INTO ham_address
(hash, uuid, langcode, address__langcode, address__country_code,
address__administrative_area, address__locality, address__dependent_locality,
address__postal_code, address__sorting_code, address__address_line1,
address__address_line2, address__organization, address__given_name,
address__additional_name, address__family_name,
geocode_provider, geocode_status, geocode_response,
latitude, longitude, osm_geocode_status, osm_geocode_response,
osm_latitude, osm_longitude, user_id, status, created, changed)

SELECT address_hash,
MIN(uuid), MIN(langcode), MIN(address__langcode), MIN(address__country_code),
MIN(address__administrative_area), MIN(address__locality), MIN(address__dependent_locality),
MIN(address__postal_code), MIN(address__sorting_code), MIN(address__address_line1),
MIN(address__address_line2), MIN(address__organization), MIN(address__given_name),
MIN(address__additional_name), MIN(address__family_name),
MIN(geocode_provider), MIN(geocode_status), MIN(geocode_response),
MIN(latitude), MIN(longitude), MIN(osm_geocode_status), MIN(osm_geocode_response),
MIN(osm_latitude), MIN(osm_longitude), MIN(user_id), MIN(status), MIN(created), MIN(changed)
FROM ham_station
GROUP BY address_hash

# Once only use to update ham_address table.

UPDATE ham_address ha
INNER JOIN ham_location hl ON hl.latitude = ha.latitude AND hl.longitude = ha.longitude
SET ha.location_id = hl.id
