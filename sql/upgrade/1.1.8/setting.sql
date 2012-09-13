INSERT IGNORE INTO setting( category, name, type, value, description )
VALUES( "queue state", "scheduled call ready", "boolean", "true",
       "Whether to assign participants from the \"scheduled call ready\" queue." );
