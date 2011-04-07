-- -----------------------------------------------------
-- Settings
-- TODO: need to implement ability for sites to override individual parameters
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

-- settings
DELETE FROM setting;

-- queue state
INSERT INTO setting( category, name, value, description )
SELECT "queue state", name, "true",
       CONCAT( "Whether to assign participants from the \"", title, "\" queue." )
FROM queue
WHERE rank IS NOT NULL
ORDER BY rank;

-- appointment
INSERT INTO setting( category, name, value, description )
VALUES( "appointment", "call pre-window", "5",
"Number of minutes before an appointment when it is considered assignable." );

INSERT INTO setting( category, name, value, description )
VALUES( "appointment", "call post-window", "15",
"Number of minutes after an appointment when it is considered assignable, after which it will be
considered missed." );

INSERT INTO setting( category, name, value, description )
VALUES( "appointment", "allow overflow", "true",
"Whether or not to allow more appointments than are allowed by the expected number of appointments
and scheduled shifts" );

-- callback timing
INSERT INTO setting( category, name, value, description )
VALUES( "callback timing", "contacted", "10080",
"Number of minutes to wait before calling back a participant where the previous call resulted in
direct contact." );

INSERT INTO setting( category, name, value, description )
VALUES( "callback timing", "busy", "15",
"Number of minutes to wait before calling back a participant where the previous call was a busy
signal." );

INSERT INTO setting( category, name, value, description )
VALUES( "callback timing", "fax", "15",
"Number of minutes to wait before calling back a participant where the previous call was a fax
machine." );

INSERT INTO setting( category, name, value, description )
VALUES( "callback timing", "no answer", "2160",
"Number of minutes to wait before calling back a participant where there was no answer during the
previous call." );

INSERT INTO setting( category, name, value, description )
VALUES( "callback timing", "machine message", "4320",
"Number of minutes to wait before calling back a participant where the previous call was an
answering machine and a message was left." );

INSERT INTO setting( category, name, value, description )
VALUES( "callback timing", "machine no message", "4320",
"Number of minutes to wait before calling back a participant where the previous call was an
answering machine ano no message was left." );

-- callback limit
-- TODO: implement callback limits in queues
INSERT INTO setting( category, name, value, description )
VALUES( "callback limit", "busy", "10",
"Number of consecutive busy phone call results before the participant is taken out of the callback
queue." );

INSERT INTO setting( category, name, value, description )
VALUES( "callback limit", "fax", "10",
"Number of consecutive fax phone call results before the participant is taken out of the callback
queue." );

INSERT INTO setting( category, name, value, description )
VALUES( "callback limit", "no answer", "10",
"Number of consecutive no answer phone call results before the participant is taken out of the
callback queue." );

INSERT INTO setting( category, name, value, description )
VALUES( "callback limit", "machine message", "10",
"Number of consecutive answering machine phone call results before the participant is taken out of
the callback queue." );

INSERT INTO setting( category, name, value, description )
VALUES( "callback limit", "aggregate", "20",
"Number of consecutive failed contact attempts (includeing calls resulting in busy, fax, no answer
and answering machines) before the participant is taken out of all callback queues." );

COMMIT;
