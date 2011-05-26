-- -----------------------------------------------------
-- Settings
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

-- voip
INSERT INTO setting( category, name, type, value, description )
VALUES( "voip", "survey without sip", "boolean", "false",
"Whether to allow operators to fill out surveys without an active SIP connection." );

-- queue state
INSERT INTO setting( category, name, type, value, description )
SELECT "queue state", name, "boolean", "true",
       CONCAT( "Whether to assign participants from the \"", title, "\" queue." )
FROM queue
WHERE rank IS NOT NULL
ORDER BY rank;

-- appointment
INSERT INTO setting( category, name, type, value, description )
VALUES( "appointment", "call pre-window", "integer", "5",
"Number of minutes before an appointment when it is considered assignable." );

INSERT INTO setting( category, name, type, value, description )
VALUES( "appointment", "call post-window", "integer", "15",
"Number of minutes after an appointment when it is considered assignable, after which it will be
considered missed." );

INSERT INTO setting( category, name, type, value, description )
VALUES( "appointment", "start_time", "string", "09:00",
"The start time-of-day that appointments are expected to be booked.  This time is used for filling
in expected operator times in the site calendar and when booking appointments." );

INSERT INTO setting( category, name, type, value, description )
VALUES( "appointment", "end_time", "string", "21:00",
"The end time-of-day that appointments are expected to be booked.  This time is used for filling
in expected operator times in the site calendar and when booking appointments." );

-- callback timing
INSERT INTO setting( category, name, type, value, description )
VALUES( "callback timing", "contacted", "integer", "10080",
"Number of minutes to wait before calling back a participant where the previous call resulted in
direct contact." );

INSERT INTO setting( category, name, type, value, description )
VALUES( "callback timing", "busy", "integer", "15",
"Number of minutes to wait before calling back a participant where the previous call was a busy
signal." );

INSERT INTO setting( category, name, type, value, description )
VALUES( "callback timing", "fax", "integer", "15",
"Number of minutes to wait before calling back a participant where the previous call was a fax
machine." );

INSERT INTO setting( category, name, type, value, description )
VALUES( "callback timing", "language", "integer", "4320",
"Number of minutes to wait before calling back a participant where the previous call was a language
barrier." );

INSERT INTO setting( category, name, type, value, description )
VALUES( "callback timing", "no answer", "integer", "2160",
"Number of minutes to wait before calling back a participant where there was no answer during the
previous call." );

INSERT INTO setting( category, name, type, value, description )
VALUES( "callback timing", "machine message", "integer", "4320",
"Number of minutes to wait before calling back a participant where the previous call was an
answering machine and a message was left." );

INSERT INTO setting( category, name, type, value, description )
VALUES( "callback timing", "machine no message", "integer", "4320",
"Number of minutes to wait before calling back a participant where the previous call was an
answering machine ano no message was left." );

COMMIT;
