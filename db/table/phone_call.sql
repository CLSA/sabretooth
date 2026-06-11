CREATE TABLE phone_call (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  assignment_id int(10) unsigned NOT NULL,
  phone_id int(10) unsigned NOT NULL,
  start_datetime datetime NOT NULL COMMENT 'The time the call started.',
  end_datetime datetime DEFAULT NULL COMMENT 'The time the call endede.',
  status enum('contacted','busy','no answer','machine message','machine no message','fax','disconnected','wrong number','not reached','hang up','soft refusal') DEFAULT NULL,
  PRIMARY KEY (id),
  KEY fk_assignment_id (assignment_id),
  KEY dk_status (status),
  KEY fk_phone_id (phone_id),
  KEY dk_start_datetime (start_datetime),
  KEY dk_end_datetime (end_datetime),
  CONSTRAINT fk_phone_call_assignment_id
    FOREIGN KEY (assignment_id)
    REFERENCES assignment (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_phone_call_phone_id
    FOREIGN KEY (phone_id)
    REFERENCES cenozo.phone (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;