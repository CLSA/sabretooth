-- add the status types to the status column
ALTER TABLE participant MODIFY status ENUM('deceased','deaf','mentally unfit','language barrier','age range','not canadian','federal reserve','armed forces','institutionalized','noncompliant','other') NULL DEFAULT NULL;
