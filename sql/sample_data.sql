-- ----------------------------------------------------------------------------------------------------
-- This file has sample data for help with development.
-- It is highly recommended to not run this script for anything other than development purposes.
-- ----------------------------------------------------------------------------------------------------
SET AUTOCOMMIT=0;

INSERT INTO site( name, timezone ) VALUES( 'Dalhousie', 'Canada/Atlantic' );
INSERT INTO site( name, timezone ) VALUES( 'McMaster', 'Canada/Eastern' );
INSERT INTO site( name, timezone ) VALUES( 'Manitoba', 'Canada/Central' );
INSERT INTO site( name, timezone ) VALUES( 'Sherbrooke', 'Canada/Eastern' );
INSERT INTO site( name, timezone ) VALUES( 'Victoria', 'Canada/Pacific' );

UPDATE province SET site_id = ( SELECT id FROM site WHERE name = "Victoria" )
WHERE abbreviation = "AB";
UPDATE province SET site_id = ( SELECT id FROM site WHERE name = "Victoria" )
WHERE abbreviation = "BC";
UPDATE province SET site_id = ( SELECT id FROM site WHERE name = "Manitoba" )
WHERE abbreviation = "MB";
UPDATE province SET site_id = ( SELECT id FROM site WHERE name = "Dalhousie" )
WHERE abbreviation = "NB";
UPDATE province SET site_id = ( SELECT id FROM site WHERE name = "Dalhousie" )
WHERE abbreviation = "NL";
UPDATE province SET site_id = ( SELECT id FROM site WHERE name = "Victoria" )
WHERE abbreviation = "NT";
UPDATE province SET site_id = ( SELECT id FROM site WHERE name = "Dalhousie" )
WHERE abbreviation = "NS";
UPDATE province SET site_id = ( SELECT id FROM site WHERE name = "Manitoba" )
WHERE abbreviation = "NU";
UPDATE province SET site_id = ( SELECT id FROM site WHERE name = "McMaster" )
WHERE abbreviation = "ON";
UPDATE province SET site_id = ( SELECT id FROM site WHERE name = "Dalhousie" )
WHERE abbreviation = "PE";
UPDATE province SET site_id = ( SELECT id FROM site WHERE name = "Sherbrooke" )
WHERE abbreviation = "QC";
UPDATE province SET site_id = ( SELECT id FROM site WHERE name = "Manitoba" )
WHERE abbreviation = "SK";
UPDATE province SET site_id = ( SELECT id FROM site WHERE name = "Victoria" )
WHERE abbreviation = "YT";

INSERT INTO user( name, first_name, last_name ) VALUES( 'patrick', 'Patrick', 'Emond' );
INSERT INTO user( name ) VALUES( 'dipietv' );
INSERT INTO user( name ) VALUES( 'kamzic' );

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
    role_id = ( SELECT id FROM role WHERE name = 'operator' ),
    site_id = ( SELECT id FROM site WHERE name = 'McMaster' );
INSERT INTO access
SET user_id = ( SELECT id FROM user WHERE name = 'dipietv' ),
    role_id = ( SELECT id FROM role WHERE name = 'administrator' ),
    site_id = ( SELECT id FROM site WHERE name = 'McMaster' );
INSERT INTO access
SET user_id = ( SELECT id FROM user WHERE name = 'kamzic' ),
    role_id = ( SELECT id FROM role WHERE name = 'administrator' ),
    site_id = ( SELECT id FROM site WHERE name = 'McMaster' );

INSERT INTO participant( first_name, last_name ) VALUES( 'Adam', 'Ant' );
INSERT INTO participant( first_name, last_name ) VALUES( 'Bob', 'Badger' );
INSERT INTO participant( first_name, last_name ) VALUES( 'Carl', 'Cat' );
INSERT INTO participant( first_name, last_name ) VALUES( 'Dan', 'Drake' );
INSERT INTO participant( first_name, last_name ) VALUES( 'Ed', 'Eft' );
INSERT INTO participant( first_name, last_name ) VALUES( 'Frank', 'Fawn' );
INSERT INTO participant( first_name, last_name ) VALUES( 'George', 'Gibbon' );

INSERT INTO contact
SET participant_id = ( SELECT id FROM participant WHERE last_name = 'Ant' ),
    active = true,
    rank = 1,
    phone = '905-525-9140',
    province_id = ( SELECT id FROM province WHERE abbreviation = 'AB' );
INSERT INTO contact
SET participant_id = ( SELECT id FROM participant WHERE last_name = 'Badger' ),
    active = true,
    rank = 1,
    phone = '905-525-9140',
    province_id = ( SELECT id FROM province WHERE abbreviation = 'ON' );
INSERT INTO contact
SET participant_id = ( SELECT id FROM participant WHERE last_name = 'Cat' ),
    active = true,
    rank = 1,
    phone = '905-525-9140',
    province_id = ( SELECT id FROM province WHERE abbreviation = 'MB' );
INSERT INTO contact
SET participant_id = ( SELECT id FROM participant WHERE last_name = 'Drake' ),
    active = true,
    rank = 1,
    phone = '905-525-9140',
    province_id = ( SELECT id FROM province WHERE abbreviation = 'NB' );
INSERT INTO contact
SET participant_id = ( SELECT id FROM participant WHERE last_name = 'Eft' ),
    active = true,
    rank = 1,
    phone = '905-525-9140',
    province_id = ( SELECT id FROM province WHERE abbreviation = 'NT' );
INSERT INTO contact
SET participant_id = ( SELECT id FROM participant WHERE last_name = 'Fawn' ),
    active = true,
    rank = 1,
    phone = '905-525-9140',
    province_id = ( SELECT id FROM province WHERE abbreviation = 'ON' );
INSERT INTO contact
SET participant_id = ( SELECT id FROM participant WHERE last_name = 'Fawn' ),
    active = true,
    rank = 2,
    phone = '905-525-9150',
    province_id = ( SELECT id FROM province WHERE abbreviation = 'QC' );
INSERT INTO contact
SET participant_id = ( SELECT id FROM participant WHERE last_name = 'Gibbon' ),
    active = true,
    rank = 1,
    phone = '905-525-9140',
    province_id = ( SELECT id FROM province WHERE abbreviation = 'ON' );

INSERT INTO availability
SET participant_id = ( SELECT id FROM participant WHERE last_name = 'Cat' ),
    saturday = true,
    sunday = true,
    start_time = '09:00:00',
    end_time = '17:00:00';
INSERT INTO availability
SET participant_id = ( SELECT id FROM participant WHERE last_name = 'Drake' ),
    monday = true,
    tuesday = true,
    wednesday = true,
    thursday = true,
    friday = true,
    start_time = '09:00:00',
    end_time = '17:00:00';
INSERT INTO availability
SET participant_id = ( SELECT id FROM participant WHERE last_name = 'Drake' ),
    wednesday = true,
    start_time = '19:00:00',
    end_time = '22:00:00';

INSERT INTO sample( name, description ) VALUES ( 'First Sample', 'This is a test sample.' );
INSERT INTO sample( name, description ) VALUES ( 'Second Sample', 'This is another test sample.' );

INSERT INTO sample_has_participant
SET sample_id = ( SELECT id FROM sample WHERE name = 'First Sample' ),
    participant_id = ( SELECT id FROM participant WHERE first_name = 'Bob' );
INSERT INTO sample_has_participant
SET sample_id = ( SELECT id FROM sample WHERE name = 'First Sample' ),
    participant_id = ( SELECT id FROM participant WHERE first_name = 'Dan' );
INSERT INTO sample_has_participant
SET sample_id = ( SELECT id FROM sample WHERE name = 'First Sample' ),
    participant_id = ( SELECT id FROM participant WHERE first_name = 'Frank' );

INSERT INTO sample_has_participant
SET sample_id = ( SELECT id FROM sample WHERE name = 'Second Sample' ),
    participant_id = ( SELECT id FROM participant WHERE first_name = 'Adam' );
INSERT INTO sample_has_participant
SET sample_id = ( SELECT id FROM sample WHERE name = 'Second Sample' ),
    participant_id = ( SELECT id FROM participant WHERE first_name = 'Carl' );
INSERT INTO sample_has_participant
SET sample_id = ( SELECT id FROM sample WHERE name = 'Second Sample' ),
    participant_id = ( SELECT id FROM participant WHERE first_name = 'Ed' );
INSERT INTO sample_has_participant
SET sample_id = ( SELECT id FROM sample WHERE name = 'Second Sample' ),
    participant_id = ( SELECT id FROM participant WHERE first_name = 'George' );

COMMIT;
