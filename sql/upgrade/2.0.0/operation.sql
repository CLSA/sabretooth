SELECT "Adding new operations" AS "";

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "interview", "add_appointment", true,
"A form to create a new appointment to add to an interview." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "interview", "add_ivr_appointment", true,
"A form to create a new IVR appointment to add to an interview." );
