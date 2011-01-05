CREATE LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION synchrotron_canAccessApi(INET) RETURNS BOOLEAN AS '
DECLARE
    p_addr ALIAS FOR $1;
    rv     BOOLEAN;
BEGIN
    INSERT INTO accesses (address) VALUES (p_addr);
    SELECT INTO rv COUNT(address) < 60 FROM accesses WHERE address = p_addr AND ts > CURRENT_TIMESTAMP - ''15 minutes''::interval;
    RETURN rv;
END;
' LANGUAGE 'PLPGSQL';

CREATE OR REPLACE FUNCTION synchrotron_clearAccesses() RETURNS VOID AS '
DECLARE
BEGIN
    DELETE FROM accesses WHERE ts < CURRENT_TIMESTAMP - ''15 minutes''::interval;
END;
' LANGUAGE 'PLPGSQL';

