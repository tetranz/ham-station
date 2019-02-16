UPDATE ham_address
SET geocode_time = unix_timestamp()
WHERE geocode_status IN (1,2)
