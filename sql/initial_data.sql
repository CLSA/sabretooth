-- -----------------------------------------------------
-- Load all initial data
-- -----------------------------------------------------

SOURCE operations.sql
SOURCE roles.sql
SOURCE queue.sql
SOURCE settings.sql

LOAD DATA LOCAL INFILE "./regions.csv"
INTO TABLE region
FIELDS TERMINATED BY ',' ENCLOSED BY '"';
