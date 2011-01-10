REPLACE INTO user SET id = 1000001, name = 'patrick';
REPLACE INTO user SET id = 1000002, name = 'ron';
REPLACE INTO user SET id = 1000003, name = 'val';

REPLACE INTO participant SET id = 1000001, first_name = 'Jane', last_name = 'Doe', language = 'en';
REPLACE INTO participant SET id = 1000002, first_name = 'John', last_name = 'Doe', language = 'en';
REPLACE INTO participant SET id = 1000003, first_name = 'Guy', last_name = 'Lafleur', language = 'fr';

REPLACE INTO contact SET id = 1000001, participant_id = 1000001, active = 1, rank = 1, phone = '123-456-7890', type = 'home', province = 'ON';
REPLACE INTO contact SET id = 1000002, participant_id = 1000001, active = 1, rank = 2, phone = '223-456-7890', type = 'home2', province = 'ON';
REPLACE INTO contact SET id = 1000003, participant_id = 1000002, active = 0, rank = 1, phone = '323-456-7890', type = 'work';
REPLACE INTO contact SET id = 1000004, participant_id = 1000002, active = 1, rank = 2, phone = '423-456-7890', type = 'home', province = 'BC';
REPLACE INTO contact SET id = 1000005, participant_id = 1000003, active = 1, rank = 1, phone = '523-456-7890', type = 'cell', province = 'QC';

-- The following won't work because of schema changes

-- REPLACE INTO assignment SET user_id = 1000001, participant_id = 1000001, qnaire_id = 1000001, start_time = now();
-- REPLACE INTO assignment SET user_id = 1000001, participant_id = 1000001, qnaire_id = 1000001, start_time = now();

-- REPLACE INTO phone_call SET user_id = 1000001, contact_id = 1000001, status = 'contacted', start_time = '2011-01-01';
-- REPLACE INTO phone_call SET user_id = 1000001, contact_id = 1000001, status = 'busy', start_time = '2011-01-02';
-- REPLACE INTO phone_call SET user_id = 1000001, contact_id = 1000002, status = 'fax', start_time = '2011-01-03';
-- REPLACE INTO phone_call SET user_id = 1000001, contact_id = 1000002, status = 'no answer', start_time = '2011-01-04';
-- REPLACE INTO phone_call SET user_id = 1000001, contact_id = 1000004, status = 'contacted', start_time = '2011-01-05';
