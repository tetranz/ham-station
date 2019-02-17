-- This seemed like a good idea but has not been used.
-- The idea was to make the address hash case insensitive so we don't get multiple addresses on the map which
-- only vary by case. On a closer look, there is not so many of them to make this worth while.

ALTER TABLE ham_address
ADD COLUMN uc_hash VARCHAR(40) NULL AFTER changed

ALTER TABLE ham_address
ADD INDEX uc_hash (uc_hash ASC);

UPDATE ham_address
SET uc_hash = SHA1(UCASE(CONCAT(address__address_line1, address__locality, address__administrative_area, address__postal_code)))
WHERE id > 0

UPDATE ham_station
INNER JOIN ham_address ha1 ON ha1.hash = ham_station.address_hash
SET ham_station.address_hash = ha1.uc_hash
WHERE ham_station.address_hash != ha1.uc_hash

CREATE TABLE ha_delete (
  haid INT NOT NULL,
  PRIMARY KEY (haid))

INSERT INTO ha_delete
(haid)
SELECT ha1.id FROM ham_address ha1
WHERE (SELECT COUNT(*) FROM ham_address ha2 WHERE ha2.uc_hash = ha1.uc_hash) > 1
AND ha1.id != (SELECT MIN(ha3.id) FROM ham_address ha3 WHERE ha3.uc_hash = ha1.uc_hash)

DELETE ham_address
FROM ham_address
INNER JOIN ha_delete ON ha_delete.haid = ham_address.id

UPDATE ham_address
SET hash = uc_hash
WHERE hash != uc_hash
AND id > 0

DROP TABLE ha_delete

ALTER TABLE ham_address
DROP COLUMN uc_hash,
DROP INDEX uc_hash
