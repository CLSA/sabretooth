-- appointment
INSERT IGNORE INTO setting( category, name, type, value, description )
VALUES( "appointment", "half appointment duration", "integer", "30",
"The length of time a half appointment is estimated to take, in minutes." );
