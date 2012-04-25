-- altering settings for half and full appointment durations
INSERT IGNORE INTO setting( category, name, type, value, description )
VALUES( "appointment", "half duration", "integer", "30",
"The length of time a half appointment is estimated to take, in minutes." );
UPDATE setting
SET name = "full duration", description = "The length of time a full appointment is estimated to take, in minutes."
WHERE category = "appointment"
AND name = "duration"
AND type = "integer";
