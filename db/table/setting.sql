CREATE TABLE setting (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  site_id int(10) unsigned NOT NULL,
  mail_name varchar(255) DEFAULT NULL,
  mail_address varchar(127) DEFAULT NULL,
  call_without_webphone tinyint(1) NOT NULL DEFAULT 0,
  calling_start_time time NOT NULL DEFAULT '09:00:00',
  calling_end_time time NOT NULL DEFAULT '21:00:00',
  appointment_duration int(10) unsigned NOT NULL DEFAULT 60,
  pre_call_window int(10) unsigned NOT NULL DEFAULT 5,
  post_call_window int(10) unsigned NOT NULL DEFAULT 15,
  contacted_wait int(10) unsigned NOT NULL DEFAULT 10080,
  busy_wait int(10) unsigned NOT NULL DEFAULT 15,
  fax_wait int(10) unsigned NOT NULL DEFAULT 15,
  no_answer_wait int(10) unsigned NOT NULL DEFAULT 1440,
  not_reached_wait int(10) unsigned NOT NULL DEFAULT 4320,
  hang_up_wait int(10) unsigned NOT NULL DEFAULT 2880,
  soft_refusal_wait int(10) unsigned NOT NULL DEFAULT 525600,
  PRIMARY KEY (id),
  UNIQUE KEY uq_site_id (site_id),
  KEY fk_site_id (site_id),
  CONSTRAINT fk_setting_site_id
    FOREIGN KEY (site_id)
    REFERENCES cenozo.site (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;