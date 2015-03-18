INSERT IGNORE INTO setting( category, name, type, value, description )
VALUES( "calling", "max failed calls", "integer", "10",
"Number of consecutive failed calls before the participant is considered unreachable and sourcing
is required." );
