-- ----------------------------------------------------------------------------------------------------
-- This file has sample data for help with development.
-- Though primary IDs are set as numbers which are unlikely to overrite data in an active instance,
-- it is highly recommended to not run this script for anything other than development purposes.
-- ----------------------------------------------------------------------------------------------------
SET AUTOCOMMIT=0;

DELETE FROM site;
INSERT INTO site( id, name ) VALUES( NULL, 'Dalhousie' );
INSERT INTO site( id, name ) VALUES( NULL, 'McMaster' );
INSERT INTO site( id, name ) VALUES( NULL, 'Manitoba' );
INSERT INTO site( id, name ) VALUES( NULL, 'Sherbrooke' );
INSERT INTO site( id, name ) VALUES( NULL, 'Usher' );
INSERT INTO site( id, name ) VALUES( NULL, 'Victoria' );

DELETE FROM user;
INSERT INTO user( id, name ) VALUES( NULL, 'patrick' );
INSERT INTO user( id, name ) VALUES( NULL, 'ron' );
INSERT INTO user( id, name ) VALUES( NULL, 'val' );

DELETE FROM user_access;
INSERT INTO user_access
SET user_id = ( SELECT id FROM user WHERE name = 'patrick' ),
    role_id = ( SELECT id FROM role WHERE name = 'administrator' ),
    site_id = ( SELECT id FROM site WHERE name = 'McMaster' );
INSERT INTO user_access
SET user_id = ( SELECT id FROM user WHERE name = 'patrick' ),
    role_id = ( SELECT id FROM role WHERE name = 'operator' ),
    site_id = ( SELECT id FROM site WHERE name = 'Manitoba' );

-- DELETE FROM participant;
-- INSERT INTO participant SET id = 1, first_name = 'Jane', last_name = 'Doe', language = 'en';
-- INSERT INTO participant SET id = 2, first_name = 'John', last_name = 'Doe', language = 'en';
-- INSERT INTO participant SET id = 3, first_name = 'Guy', last_name = 'Lafleur', language = 'fr';

-- INSERT INTO contact SET id = 1, participant_id = 1, active = 1, rank = 1, phone = '123-456-7890', type = 'home', province = 'ON';
-- INSERT INTO contact SET id = 2, participant_id = 1, active = 1, rank = 2, phone = '223-456-7890', type = 'home2', province = 'ON';
-- INSERT INTO contact SET id = 3, participant_id = 2, active = 0, rank = 1, phone = '323-456-7890', type = 'work';
-- INSERT INTO contact SET id = 4, participant_id = 2, active = 1, rank = 2, phone = '423-456-7890', type = 'home', province = 'BC';
-- INSERT INTO contact SET id = 5, participant_id = 3, active = 1, rank = 1, phone = '523-456-7890', type = 'cell', province = 'QC';

-- The following won't work because of schema changes

-- INSERT INTO assignment SET user_id = 1000001, participant_id = 1000001, qnaire_id = 1000001, start_time = now();
-- INSERT INTO assignment SET user_id = 1000001, participant_id = 1000001, qnaire_id = 1000001, start_time = now();

-- INSERT INTO phone_call SET user_id = 1000001, contact_id = 1000001, status = 'contacted', start_time = '2011-01-01';
-- INSERT INTO phone_call SET user_id = 1000001, contact_id = 1000001, status = 'busy', start_time = '2011-01-02';
-- INSERT INTO phone_call SET user_id = 1000001, contact_id = 1000002, status = 'fax', start_time = '2011-01-03';
-- INSERT INTO phone_call SET user_id = 1000001, contact_id = 1000002, status = 'no answer', start_time = '2011-01-04';
-- INSERT INTO phone_call SET user_id = 1000001, contact_id = 1000004, status = 'contacted', start_time = '2011-01-05';

COMMIT;
