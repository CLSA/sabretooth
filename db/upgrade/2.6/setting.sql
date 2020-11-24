SELECT "Adding missing default values to setting table" AS "";

ALTER TABLE setting
MODIFY COLUMN calling_start_time TIME NOT NULL DEFAULT '09:00:00',
MODIFY COLUMN calling_end_time TIME NOT NULL DEFAULT '21:00:00';
