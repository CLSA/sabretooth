-- ----------------------------------------------------------------------------------------------------
-- This file has sample data for help with development.
-- It is highly recommended to not run this script for anything other than development purposes.
-- ----------------------------------------------------------------------------------------------------
SET AUTOCOMMIT=0;

INSERT INTO site( name, timezone ) VALUES
( 'Dalhousie', 'Canada/Atlantic' ),
( 'McMaster', 'Canada/Eastern' ),
( 'Manitoba', 'Canada/Central' ),
( 'Sherbrooke', 'Canada/Eastern' ),
( 'Victoria', 'Canada/Pacific' );

UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "Victoria" )
WHERE abbreviation = "AB";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "Victoria" )
WHERE abbreviation = "BC";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "Manitoba" )
WHERE abbreviation = "MB";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "Dalhousie" )
WHERE abbreviation = "NB";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "Dalhousie" )
WHERE abbreviation = "NL";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "Victoria" )
WHERE abbreviation = "NT";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "Dalhousie" )
WHERE abbreviation = "NS";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "Manitoba" )
WHERE abbreviation = "NU";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "McMaster" )
WHERE abbreviation = "ON";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "Dalhousie" )
WHERE abbreviation = "PE";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "Sherbrooke" )
WHERE abbreviation = "QC";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "Manitoba" )
WHERE abbreviation = "SK";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "Victoria" )
WHERE abbreviation = "YT";

INSERT INTO user( name, first_name, last_name ) VALUES
( 'patrick', 'P.', 'Emond' ),
( 'dean', 'D.', 'Inglis' ),
( 'dipietv', 'V.', 'DiPietro' );

INSERT INTO access
SET user_id = ( SELECT id FROM user WHERE name = 'patrick' ),
    role_id = ( SELECT id FROM role WHERE name = 'administrator' ),
    site_id = ( SELECT id FROM site WHERE name = 'McMaster' );
INSERT INTO access
SET user_id = ( SELECT id FROM user WHERE name = 'patrick' ),
    role_id = ( SELECT id FROM role WHERE name = 'supervisor' ),
    site_id = ( SELECT id FROM site WHERE name = 'McMaster' );
INSERT INTO access
SET user_id = ( SELECT id FROM user WHERE name = 'patrick' ),
    role_id = ( SELECT id FROM role WHERE name = 'supervisor' ),
    site_id = ( SELECT id FROM site WHERE name = 'Manitoba' );
INSERT INTO access
SET user_id = ( SELECT id FROM user WHERE name = 'patrick' ),
    role_id = ( SELECT id FROM role WHERE name = 'operator' ),
    site_id = ( SELECT id FROM site WHERE name = 'McMaster' );
INSERT INTO access
SET user_id = ( SELECT id FROM user WHERE name = 'patrick' ),
    role_id = ( SELECT id FROM role WHERE name = 'operator' ),
    site_id = ( SELECT id FROM site WHERE name = 'Manitoba' );

LOAD DATA LOCAL INFILE "./participants.csv"
INTO TABLE participant
FIELDS TERMINATED BY ',' ENCLOSED BY '"';

LOAD DATA LOCAL INFILE "./addresses.csv"
INTO TABLE address
FIELDS TERMINATED BY ',' ENCLOSED BY '"';

LOAD DATA LOCAL INFILE "./phone_numbers.csv"
INTO TABLE phone
FIELDS TERMINATED BY ',' ENCLOSED BY '"';

INSERT INTO qnaire ( name, rank, prev_qnaire_id, delay ) VALUES
( 'Baseline', 1, NULL, 52 ),
( 'Maintaining Contact', 2, 1, 78 ),
( 'Follow Up', 3, 1, 156 );

COMMIT;
