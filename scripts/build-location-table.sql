# Once only use to build ham_location table.

INSERT INTO ham_location
(uuid, langcode, user_id, latitude, longitude, status, created, changed)
SELECT UUID() as uuid, 'en' as langcode, 1 as user_id,
ha.latitude, ha.longitude, 1 as status, UNIX_TIMESTAMP() as created, UNIX_TIMESTAMP() as changed
FROM ham_address ha
WHERE ha.latitude IS NOT NULL AND ha.longitude IS NOT NULL
GROUP BY ha.latitude, ha.longitude
