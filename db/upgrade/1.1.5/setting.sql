-- add new queue reverse sort time setting
INSERT IGNORE INTO setting( category, name, type, value, description )
VALUES( "queue", "reverse sort time", "string", "17:00",
"The time when the weekday calling queue sorting is reversed (from youngest to oldest)." );
