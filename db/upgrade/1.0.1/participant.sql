CREATE UNIQUE INDEX uq_uid ON participant (uid);
ALTER TABLE participant CHANGE uid uid VARCHAR(45) NOT NULL COMMENT 'External unique ID'
