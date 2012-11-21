INSERT IGNORE INTO setting( category, name, type, value, description )
VALUES( "queue state", "scheduled call ready", "boolean", "true",
       "Whether to assign participants from the \"scheduled call ready\" queue." );

INSERT IGNORE INTO setting( category, name, type, value, description )
VALUES( "callback", "call pre-window", "integer", "5",
"Number of minutes before a callback when it is considered assignable." );
