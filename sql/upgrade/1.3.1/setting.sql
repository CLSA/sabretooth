SELECT "Adding new 'calling' 'end call warning' setting" AS "";

INSERT IGNORE INTO setting( category, name, type, value, description )
VALUES( "calling", "end call warning", "boolean", "false",
"Whether to show a warning when an operator ends a call with a participant who's current
questionnaire is non-repeating and incomplete." );
