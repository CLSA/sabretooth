-- -----------------------------------------------------
-- Settings
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

-- voip
INSERT INTO setting( category, name, type, value, description )
VALUES( "voip", "survey without sip", "boolean", "false",
"Whether to allow operators to fill out surveys without an active SIP connection." );

-- queue
INSERT INTO setting( category, name, type, value, description )
VALUES( "queue", "reverse sort time", "string", "17:00",
"The time when the weekday calling queue sorting is reversed (from youngest to oldest)." );

-- queue state
INSERT INTO setting( category, name, type, value, description )
SELECT "queue state", name, "boolean", "true",
       CONCAT( "Whether to assign participants from the \"", title, "\" queue." )
FROM queue
WHERE rank IS NOT NULL
ORDER BY rank;

-- appointment
INSERT INTO setting( category, name, type, value, description )
VALUES( "appointment", "full duration", "integer", "60",
"The length of time a full appointment is estimated to take, in minutes." );

INSERT INTO setting( category, name, type, value, description )
VALUES( "appointment", "half duration", "integer", "30",
"The length of time a half appointment is estimated to take, in minutes." );

INSERT INTO setting( category, name, type, value, description )
VALUES( "appointment", "call pre-window", "integer", "5",
"Number of minutes before an appointment when it is considered assignable." );

INSERT INTO setting( category, name, type, value, description )
VALUES( "appointment", "call post-window", "integer", "15",
"Number of minutes after an appointment when it is considered assignable, after which it will be
considered missed." );

-- callback
INSERT INTO setting( category, name, type, value, description )
VALUES( "callback", "call pre-window", "integer", "5",
"Number of minutes before a callback when it is considered assignable." );

-- calling times
INSERT INTO setting( category, name, type, value, description )
VALUES( "calling", "start time", "string", "09:00",
"The time when calls may begin (not including appointments).  The local time at the participant's
\"first address\" is tested." );

INSERT INTO setting( category, name, type, value, description )
VALUES( "calling", "end time", "string", "21:00",
"The time when calls end (not including appointments).  The local time at the participant's
\"first address\" is tested." );

INSERT INTO setting( category, name, type, value, description )
VALUES( "calling", "max failed calls", "integer", "10",
"Number of consecutive failed calls before the participant is considered unreachable and sourcing
is required." );

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
VALUES( "callback timing", "fax", "integer", "15",
"Number of minutes to wait before calling back a participant where the previous call was a fax
machine." );

INSERT INTO setting( category, name, type, value, description )
VALUES( "callback timing", "not reached", "integer", "4320",
"Number of minutes to wait before calling back a participant where the previous call reached
a person other than the participant, was an answering machine or was a disconnected or wrong
number."

INSERT INTO setting( category, name, type, value, description )
VALUES( "callback timing", "hang up", "integer", "2880",
"Number of minutes to wait before calling back a participant where the previous call was a hang
up." );

INSERT INTO setting( category, name, type, value, description )
VALUES( "callback timing", "soft refusal", "integer", "525600",
"Number of minutes to wait before calling back a participant where the previous call was a fax
machine." );

COMMIT;
