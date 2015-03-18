DELETE FROM setting
WHERE category = "callback timing"
AND name IN ( "machine message", "machine no message" );

UPDATE setting SET description = 
"Number of minutes to wait before calling back a participant where the previous call reached
a person other than the participant, was an answering machine or was a disconnected or wrong
number."
WHERE category = "callback timing"
AND name = "not reached";
