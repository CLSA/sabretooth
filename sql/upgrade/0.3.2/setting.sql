-- calling times
INSERT INTO setting( category, name, type, value, description )
VALUES( "calling", "start time", "string", "09:00",
"The time when calls may begin (not including appointments).  The local time at the participant's
\"first address\" is tested." );

INSERT INTO setting( category, name, type, value, description )
VALUES( "calling", "end time", "string", "21:00",
"The time when calls end (not including appointments).  The local time at the participant's
\"first address\" is tested." );
