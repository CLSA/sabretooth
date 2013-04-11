INSERT IGNORE INTO setting( category, name, type, value, description )
VALUES( "queue state", "assignable callback", "boolean", "true",
       "Whether to assign participants from the \"assignable callback\" queue." );

INSERT IGNORE INTO setting( category, name, type, value, description )
VALUES( "callback", "call pre-window", "integer", "5",
"Number of minutes before a callback when it is considered assignable." );
