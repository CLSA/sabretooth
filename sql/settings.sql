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
VALUES( "appointment", "duration", "integer", "45",
"The length of time an appointment is estimated to take, in minutes." );

INSERT INTO setting( category, name, type, value, description )
VALUES( "appointment", "call pre-window", "integer", "5",
"Number of minutes before an appointment when it is considered assignable." );

INSERT INTO setting( category, name, type, value, description )
VALUES( "appointment", "call post-window", "integer", "15",
"Number of minutes after an appointment when it is considered assignable, after which it will be
considered missed." );

-- calling times
INSERT INTO setting( category, name, type, value, description )
VALUES( "calling", "start time", "string", "09:00",
"The time when calls may begin (not including appointments).  The local time at the participant's
\"first address\" is tested." );

INSERT INTO setting( category, name, type, value, description )
VALUES( "calling", "end time", "string", "21:00",
"The time when calls end (not including appointments).  The local time at the participant's
\"first address\" is tested." );

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
VALUES( "callback timing", "no answer", "integer", "1440",
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

INSERT INTO setting( category, name, type, value, description )
VALUES( "callback timing", "fax", "integer", "15",
"Number of minutes to wait before calling back a participant where the previous call was a fax
machine." );

INSERT INTO setting( category, name, type, value, description )
VALUES( "callback timing", "not reached", "integer", "4320",
"Number of minutes to wait before calling back a participant where the previous call reached a
person other than the participant." );

INSERT INTO setting( category, name, type, value, description )
VALUES( "callback timing", "hang up", "integer", "2880",
"Number of minutes to wait before calling back a participant where the previous call was a hang
up." );

INSERT INTO setting( category, name, type, value, description )
VALUES( "callback timing", "soft refusal", "integer", "525600",
"Number of minutes to wait before calling back a participant where the previous call was a fax
machine." );

COMMIT;
