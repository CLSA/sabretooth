-- add in the new quota queue
DROP PROCEDURE IF EXISTS patch_queue;
DELIMITER //
CREATE PROCEDURE patch_queue()
  BEGIN
    DECLARE test INT;
    SET @test = (
      SELECT COUNT(*)
      FROM queue );
    IF @test = 107 THEN

      -- drop assignment table's foreign key to queue and recreate the table
      ALTER TABLE assignment DROP FOREIGN KEY fk_assignment_queue_id;
      DROP TABLE IF EXISTS queue ;
      CREATE TABLE IF NOT EXISTS queue (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT ,
        update_timestamp TIMESTAMP NOT NULL ,
        create_timestamp TIMESTAMP NOT NULL ,
        name VARCHAR(45) NOT NULL ,
        title VARCHAR(255) NOT NULL ,
        rank INT UNSIGNED NULL DEFAULT NULL ,
        qnaire_specific TINYINT(1) NOT NULL ,
        parent_queue_id INT UNSIGNED NULL DEFAULT NULL ,
        description TEXT NULL ,
        PRIMARY KEY (id) ,
        UNIQUE INDEX uq_rank (rank ASC) ,
        INDEX fk_parent_queue_id (parent_queue_id ASC) ,
        UNIQUE INDEX uq_name (name ASC) ,
        CONSTRAINT fk_queue_parent_queue_id
          FOREIGN KEY (parent_queue_id )
          REFERENCES queue (id )
          ON DELETE NO ACTION
          ON UPDATE NO ACTION)
      ENGINE = InnoDB;

      INSERT INTO queue SET
      name = "all",
      title = "All Participants",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = NULL,
      description = "All participants in the database.";

      INSERT INTO queue SET
      name = "finished",
      title = "Finished all questionnaires",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "all" ) AS tmp ),
      description = "Participants who have completed all questionnaires.";

      INSERT INTO queue SET
      name = "ineligible",
      title = "Not eligible to answer questionnaires",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "all" ) AS tmp ),
      description = "Participants who are not eligible to answer questionnaires due to a permanent
      condition, because they are inactive or because they do not have a phone number.";

      INSERT INTO queue SET
      name = "inactive",
      title = "Inactive participants",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they have
      been marked as inactive.";

      INSERT INTO queue SET
      name = "sourcing required",
      title = "Participants without a phone number",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they have
      no active phone numbers.";

      INSERT INTO queue SET
      name = "refused consent",
      title = "Participants who refused consent",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they have
      refused consent.";

      INSERT INTO queue SET
      name = "deceased",
      title = "Deceased participants",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they are
      deceased.";

      INSERT INTO queue SET
      name = "deaf",
      title = "Deaf participants",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they are hard
      of hearing.";

      INSERT INTO queue SET
      name = "mentally unfit",
      title = "Mentally unfit participants",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they are
      mentally unfit.";

      INSERT INTO queue SET
      name = "language barrier",
      title = "Participants with a language barrier",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because of a language
      barrier.";

      INSERT INTO queue SET
      name = "age range",
      title = "Participants whose age is outside of the valid range",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because their age is
      not within the valid range.";

      INSERT INTO queue SET
      name = "not canadian",
      title = "Participants who are not Canadian",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they are not
      a Canadian citizen.";

      INSERT INTO queue SET
      name = "federal reserve",
      title = "Participants who live on a federal reserve",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they reside
      on a federal reserve.";

      INSERT INTO queue SET
      name = "armed forces",
      title = "Participants who are in the armed forces",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they are full
      time members of the armed forces.";

      INSERT INTO queue SET
      name = "institutionalized",
      title = "Participants who are intitutionalized",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they are
      institutionalized.";

      INSERT INTO queue SET
      name = "noncompliant",
      title = "Participants who are not complying with the rules of the study.",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they are
      not complying with the rules of the study.  This list may include participants who are being abusive
      to CLSA staff.";

      INSERT INTO queue SET
      name = "other",
      title = "Participants with an undefined condition",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they have
      been identified to have an undefined condition (other).";

      INSERT INTO queue SET
      name = "eligible",
      title = "Eligible to answer questionnaires",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "all" ) AS tmp ),
      description = "Participants who are eligible to answer questionnaires.";

      INSERT INTO queue SET
      name = "qnaire",
      title = "Questionnaire",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "eligible" ) AS tmp ),
      description = "Eligible participants who are currently assigned to the questionnaire.";

      INSERT INTO queue SET
      name = "restricted",
      title = "Restricted from calling",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "qnaire" ) AS tmp ),
      description = "Eligible participants whose city, province or postcode have been restricted.";

      INSERT INTO queue SET
      name = "qnaire waiting",
      title = "Waiting to begin",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "qnaire" ) AS tmp ),
      description = "Eligible participants who are waiting the scheduled cool-down period before
      beginning the questionnaire.";

      INSERT INTO queue SET
      name = "assigned",
      title = "Currently assigned",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "qnaire" ) AS tmp ),
      description = "Eligible participants who are currently assigned to an operator.";

      INSERT INTO queue SET
      name = "not assigned",
      title = "Not assigned",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "qnaire" ) AS tmp ),
      description = "Eligible participants who are not assigned to an operator.";

      INSERT INTO queue SET
      name = "appointment",
      title = "Participants with appointments",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "not assigned" ) AS tmp ),
      description = "Participants who have an (unassigned) appointment.";

      INSERT INTO queue SET
      name = "upcoming appointment",
      title = "Appointment upcoming",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "appointment" ) AS tmp ),
      description = "Participants who have an appointment in the future.";

      INSERT INTO queue SET
      name = "assignable appointment",
      title = "Appointment assignnable",
      rank = 1,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "appointment" ) AS tmp ),
      description = "Participants who have an immediate appointment which is ready to be assigned.";

      INSERT INTO queue SET
      name = "missed appointment",
      title = "Appointment missed",
      rank = 2,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "appointment" ) AS tmp ),
      description = "Participants who have an appointment which was missed.";

      INSERT INTO queue SET
      name = "no appointment",
      title = "Participants without appointments",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "not assigned" ) AS tmp ),
      description = "Participants who do not have an appointment.";

      INSERT INTO queue SET
      name = "quota disabled",
      title = "Participant's quota is disabled",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "no appointment" ) AS tmp ),
      description = "Participants who belong to a quota which has been disabled";

      INSERT INTO queue SET
      name = "new participant",
      title = "Never assigned participants",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "no appointment" ) AS tmp ),
      description = "Participants who have never been assigned to an operator.";

      INSERT INTO queue SET
      name = "new participant outside calling time",
      title = "New participants, outside calling time",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "new participant" ) AS tmp ),
      description = "New participants whose local time is outside of the valid calling hours.";

      INSERT INTO queue SET
      name = "new participant within calling time",
      title = "New participants, within calling time",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "new participant" ) AS tmp ),
      description = "New participants whose local time is within the valid calling hours.";

      INSERT INTO queue SET
      name = "new participant available",
      title = "New participants, available",
      rank = 30,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "new participant within calling time" ) AS tmp ),
      description = "New participants who are available.";

      INSERT INTO queue SET
      name = "new participant always available",
      title = "New participants always available",
      rank = 31,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "new participant within calling time" ) AS tmp ),
      description = "New participants who are always available.";

      INSERT INTO queue SET
      name = "new participant not available",
      title = "New participants, not available",
      rank = 32,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "new participant within calling time" ) AS tmp ),
      description = "New participants who are not available.";

      INSERT INTO queue SET
      name = "old participant",
      title = "Previously assigned participants",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "no appointment" ) AS tmp ),
      description = "Participants who have been previously assigned.";

      INSERT INTO queue SET
      name = "contacted",
      title = "Last call: contacted",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "old participant" ) AS tmp ),
      description = "Participants whose last call result was 'contacted'.";

      INSERT INTO queue SET
      name = "contacted waiting",
      title = "Last call: contacted (waiting)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "contacted" ) AS tmp ),
      description = "Participants whose last call result was 'contacted' and the scheduled call back
      time has not yet been reached.";

      INSERT INTO queue SET
      name = "contacted ready",
      title = "Last call: contacted (ready)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "contacted" ) AS tmp ),
      description = "Participants whose last call result was 'contacted' and the scheduled call
      back time has been reached.";

      INSERT INTO queue SET
      name = "contacted outside calling time",
      title = "Last call: contacted (outside calling time)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "contacted ready" ) AS tmp ),
      description = "Participants whose last call result was 'contacted', the scheduled call
      back time has been reached and the participant's local time is outside of valid calling times.";

      INSERT INTO queue SET
      name = "contacted within calling time",
      title = "Last call: contacted (within calling time)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "contacted ready" ) AS tmp ),
      description = "Participants whose last call result was 'contacted', the scheduled call
      back time has been reached and the participant is within valid calling times.";

      INSERT INTO queue SET
      name = "contacted available",
      title = "Last call: contacted (available)",
      rank = 3,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "contacted within calling time" ) AS tmp ),
      description = "Available participants whose last call result was 'contacted', the scheduled call
      back time has been reached and the participant is within valid calling times.";

      INSERT INTO queue SET
      name = "contacted always available",
      title = "Last call: contacted (always available)",
      rank = 4,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "contacted within calling time" ) AS tmp ),
      description = "Always available participants whose last call result was 'contacted', the scheduled
      call back time has been reached and the participant is within valid calling times.";

      INSERT INTO queue SET
      name = "contacted not available",
      title = "Last call: contacted (not available)",
      rank = 5,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "contacted within calling time" ) AS tmp ),
      description = "Unavailable participants whose last call result was 'contacted', the scheduled call
      back time has been reached and the participant is within valid calling times.";

      INSERT INTO queue SET
      name = "busy",
      title = "Last call: busy line",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "old participant" ) AS tmp ),
      description = "Participants whose last call result was 'busy'.";

      INSERT INTO queue SET
      name = "busy waiting",
      title = "Last call: busy line (waiting)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "busy" ) AS tmp ),
      description = "Participants whose last call result was 'busy' and the scheduled call back
      time has not yet been reached.";

      INSERT INTO queue SET
      name = "busy ready",
      title = "Last call: busy line (ready)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "busy" ) AS tmp ),
      description = "Participants whose last call result was 'busy' and the scheduled call back
      time has been reached.";

      INSERT INTO queue SET
      name = "busy outside calling time",
      title = "Last call: busy (outside calling time)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "busy ready" ) AS tmp ),
      description = "Participants whose last call result was 'busy', the scheduled call
      back time has been reached and the participant's local time is outside of valid calling times.";

      INSERT INTO queue SET
      name = "busy within calling time",
      title = "Last call: busy (within calling time)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "busy ready" ) AS tmp ),
      description = "Participants whose last call result was 'busy', the scheduled call
      back time has been reached and the participant is within valid calling times.";

      INSERT INTO queue SET
      name = "busy available",
      title = "Last call: busy (available)",
      rank = 6,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "busy within calling time" ) AS tmp ),
      description = "Available participants whose last call result was 'busy', the scheduled call
      back time has been reached and the participant is within valid calling times.";

      INSERT INTO queue SET
      name = "busy always available",
      title = "Last call: busy (always available)",
      rank = 7,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "busy within calling time" ) AS tmp ),
      description = "Always available participants whose last call result was 'busy', the scheduled call
      back time has been reached and the participant is within valid calling times.";

      INSERT INTO queue SET
      name = "busy not available",
      title = "Last call: busy (not available)",
      rank = 8,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "busy within calling time" ) AS tmp ),
      description = "Unavailable participants whose last call result was 'busy', the scheduled call
      back time has been reached and the participant is within valid calling times.";

      INSERT INTO queue SET
      name = "fax",
      title = "Last call: fax line",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "old participant" ) AS tmp ),
      description = "Participants whose last call result was 'fax'.";

      INSERT INTO queue SET
      name = "fax waiting",
      title = "Last call: fax line (waiting)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "fax" ) AS tmp ),
      description = "Participants whose last call result was 'fax' and the scheduled call back
      time has not yet been reached.";

      INSERT INTO queue SET
      name = "fax ready",
      title = "Last call: fax line (ready)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "fax" ) AS tmp ),
      description = "Participants whose last call result was 'fax' and the scheduled call back
      time has been reached.";

      INSERT INTO queue SET
      name = "fax outside calling time",
      title = "Last call: contacted (outside calling time)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "fax ready" ) AS tmp ),
      description = "Participants whose last call result was 'fax', the scheduled call
      back time has been reached and the participant's local time is outside of valid calling times.";

      INSERT INTO queue SET
      name = "fax within calling time",
      title = "Last call: fax (within calling time)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "fax ready" ) AS tmp ),
      description = "Participants whose last call result was 'fax', the scheduled call
      back time has been reached and the participant is within valid calling times.";

      INSERT INTO queue SET
      name = "fax available",
      title = "Last call: fax (available)",
      rank = 9,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "fax within calling time" ) AS tmp ),
      description = "Available participants whose last call result was 'fax', the scheduled call 
      back time has been reached and the participant is within valid calling times.";

      INSERT INTO queue SET
      name = "fax always available",
      title = "Last call: fax (always available)",
      rank = 10,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "fax within calling time" ) AS tmp ),
      description = "Always available participants whose last call result was 'fax', the scheduled call
      back time has been reached and the participant is within valid calling times.";

      INSERT INTO queue SET
      name = "fax not available",
      title = "Last call: fax (not available)",
      rank = 11,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "fax within calling time" ) AS tmp ),
      description = "Unavailable participants whose last call result was 'fax', the scheduled call 
      back time has been reached and the participant is within valid calling times.";

      INSERT INTO queue SET
      name = "not reached",
      title = "Last call: not reached",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "old participant" ) AS tmp ),
      description = "Participants whose last call result was 'not reached'.";

      INSERT INTO queue SET
      name = "not reached waiting",
      title = "Last call: not reached (waiting)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "not reached" ) AS tmp ),
      description = "Participants whose last call result was 'not reached' and the scheduled call back
      time has not yet been reached.";

      INSERT INTO queue SET
      name = "not reached ready",
      title = "Last call: not reached (ready)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "not reached" ) AS tmp ),
      description = "Participants whose last call result was 'not reached' and the scheduled call back
      time has been reached.";

      INSERT INTO queue SET
      name = "not reached outside calling time",
      title = "Last call: not reached (outside calling time)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "not reached ready" ) AS tmp ),
      description = "Participants whose last call result was 'not reached', the scheduled call back time
      has been reached and the participant's local time is outside of valid calling times.";

      INSERT INTO queue SET
      name = "not reached within calling time",
      title = "Last call: not reached (within calling time)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "not reached ready" ) AS tmp ),
      description = "Participants whose last call result was 'not reached', the scheduled call back time
      has been reached and the participant is within valid calling times.";

      INSERT INTO queue SET
      name = "not reached available",
      title = "Last call: not reached (available)",
      rank = 12,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "not reached within calling time" ) AS tmp ),
      description = "Available participants whose last call result was 'not reached', the scheduled call
      back time has been reached and the participant is within valid calling times.";

      INSERT INTO queue SET
      name = "not reached always available",
      title = "Last call: not reached (always available)",
      rank = 13,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "not reached within calling time" ) AS tmp ),
      description = "Always available participants whose last call result was 'not reached', the
      scheduled call back time has been reached and the participant is within valid calling times.";

      INSERT INTO queue SET
      name = "not reached not available",
      title = "Last call: not reached (not available)",
      rank = 14,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "not reached within calling time" ) AS tmp ),
      description = "Unavailable participants whose last call result was 'not reached', the scheduled
      call back time has been reached and the participant is within valid calling times.";

      INSERT INTO queue SET
      name = "no answer",
      title = "Last call: no answer",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "old participant" ) AS tmp ),
      description = "Participants whose last call result was 'no answer'.";

      INSERT INTO queue SET
      name = "no answer waiting",
      title = "Last call: no answer (waiting)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "no answer" ) AS tmp ),
      description = "Participants whose last call result was 'no answer' and the scheduled call back
      time has not yet been reached.";

      INSERT INTO queue SET
      name = "no answer ready",
      title = "Last call: no answer (ready)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "no answer" ) AS tmp ),
      description = "Participants whose last call result was 'no answer' and the scheduled call back
      time has been reached.";

      INSERT INTO queue SET
      name = "no answer outside calling time",
      title = "Last call: no answer (outside calling time)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "no answer ready" ) AS tmp ),
      description = "Participants whose last call result was 'no answer', the scheduled call
      time has been reached and the participant's local time is outside of valid calling times.";

      INSERT INTO queue SET
      name = "no answer within calling time",
      title = "Last call: no answer (within calling time)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "no answer ready" ) AS tmp ),
      description = "Participants whose last call result was 'no answer', the scheduled call
      back time has been reached and the participant's local time is within valid calling times.";

      INSERT INTO queue SET
      name = "no answer available",
      title = "Last call: no answer (available)",
      rank = 15,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "no answer within calling time" ) AS tmp ),
      description = "Available participants whose last call result was 'no answer', the scheduled call
      back time has been reached and the participant's local time is within valid calling times.";

      INSERT INTO queue SET
      name = "no answer always available",
      title = "Last call: no answer (always available)",
      rank = 16,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "no answer within calling time" ) AS tmp ),
      description = "Always available participants whose last call result was 'no answer', the scheduled call
      back time has been reached and the participant's local time is within valid calling times.";

      INSERT INTO queue SET
      name = "no answer not available",
      title = "Last call: no answer (not available)",
      rank = 17,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "no answer within calling time" ) AS tmp ),
      description = "Unavailable participants whose last call result was 'no answer', the scheduled call
      back time has been reached and the participant's local time is within valid calling times.";

      INSERT INTO queue SET
      name = "machine message",
      title = "Last call: answering machine, message left",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "old participant" ) AS tmp ),
      description = "Participants whose last call result was 'machine message'.";

      INSERT INTO queue SET
      name = "machine message waiting",
      title = "Last call: answering machine, message left (waiting)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "machine message" ) AS tmp ),
      description = "Participants whose last call result was 'machine message' and the scheduled call back
      time has not yet been reached.";

      INSERT INTO queue SET
      name = "machine message ready",
      title = "Last call: answering machine, message left (ready)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "machine message" ) AS tmp ),
      description = "Participants whose last call result was 'machine message' and the scheduled call back
      time has been reached.";

      INSERT INTO queue SET
      name = "machine message outside calling time",
      title = "Last call: machine message (outside calling time)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "machine message ready" ) AS tmp ),
      description = "Participants whose last call result was 'machine message',
      the scheduled call back time has been reached and the participant's local time is outside of
      valid calling times.";

      INSERT INTO queue SET
      name = "machine message within calling time",
      title = "Last call: machine message (within calling time)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "machine message ready" ) AS tmp ),
      description = "Participants whose last call result was 'machine message',
      the scheduled call back time has been reached and the participant's local time is within valid
      calling times.";

      INSERT INTO queue SET
      name = "machine message available",
      title = "Last call: answering machine, message left (available)",
      rank = 18,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "machine message within calling time" ) AS tmp ),
      description = "Available participants whose last call result was 'machine message',
      the scheduled call back time has been reached and the participant's local time is within valid
      calling times.";

      INSERT INTO queue SET
      name = "machine message always available",
      title = "Last call: answering machine, message left (always available)",
      rank = 19,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "machine message within calling time" ) AS tmp ),
      description = "Always available participants whose last call result was 'machine message',
      the scheduled call back time has been reached and the participant's local time is within valid
      calling times.";

      INSERT INTO queue SET
      name = "machine message not available",
      title = "Last call: answering machine, message left (not available)",
      rank = 20,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "machine message within calling time" ) AS tmp ),
      description = "Unavailable participants whose last call result was 'machine message',
      the scheduled call back time has been reached and the participant's local time is within valid
      calling times.";

      INSERT INTO queue SET
      name = "machine no message",
      title = "Last call: answering machine, message not left",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "old participant" ) AS tmp ),
      description = "Participants whose last call result was 'machine no message'.";

      INSERT INTO queue SET
      name = "machine no message waiting",
      title = "Last call: answering machine, message not left (waiting)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "machine no message" ) AS tmp ),
      description = "Participants whose last call result was 'machine no message'
      and the scheduled call back time has not yet been reached.";

      INSERT INTO queue SET
      name = "machine no message ready",
      title = "Last call: answering machine, message not left (ready)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "machine no message" ) AS tmp ),
      description = "Participants whose last call result was 'machine no message'
      and the scheduled call back time has been reached.";

      INSERT INTO queue SET
      name = "machine no message outside calling time",
      title = "Last call: machine no message (outside calling time)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "machine no message ready" ) AS tmp ),
      description = "Participants whose last call result was 'machine no message',
      the scheduled call back time has been reached and the participant's local time is outside of
      valid calling times.";

      INSERT INTO queue SET
      name = "machine no message within calling time",
      title = "Last call: machine no message (within calling time)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "machine no message ready" ) AS tmp ),
      description = "Participants whose last call result was 'machine no message',
      the scheduled call back time has been reached and the participant's local time is within valid
      calling times.";

      INSERT INTO queue SET
      name = "machine no message available",
      title = "Last call: answering machine, message not left (available)",
      rank = 21,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id 
          FROM queue
          WHERE name = "machine no message within calling time" ) AS tmp ),
      description = "Available participants whose last call result was 'machine no message',
      the scheduled call back time has been reached and the participant's local time is within valid
      calling times.";

      INSERT INTO queue SET
      name = "machine no message always available",
      title = "Last call: answering machine, message not left (always available)",
      rank = 22,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "machine no message within calling time" ) AS tmp ),
      description = "Always available participants whose last call result was 'machine no message',
      the scheduled call back time has been reached and the participant's local time is within valid
      calling times.";

      INSERT INTO queue SET
      name = "machine no message not available",
      title = "Last call: answering machine, message not left (not available)",
      rank = 23,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "machine no message within calling time" ) AS tmp ),
      description = "Unavailable participants whose last call result was 'machine no message',
      the scheduled call back time has been reached and the participant's local time is within valid
      calling times.";

      INSERT INTO queue SET
      name = "hang up",
      title = "Last call: hang up",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "old participant" ) AS tmp ),
      description = "Participants whose last call result was 'hang up'.";

      INSERT INTO queue SET
      name = "hang up waiting",
      title = "Last call: hang up (waiting)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "hang up" ) AS tmp ),
      description = "Participants whose last call result was 'hang up' and the scheduled call back
      time has not yet been reached.";

      INSERT INTO queue SET
      name = "hang up ready",
      title = "Last call: hang up (ready)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "hang up" ) AS tmp ),
      description = "Participants whose last call result was 'hang up' and the scheduled call back
      time has been reached.";

      INSERT INTO queue SET
      name = "hang up outside calling time",
      title = "Last call: hang up (outside calling time)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "hang up ready" ) AS tmp ),
      description = "Participants whose last call result was 'hang up', the scheduled call
      back time has been reached and the participant's local time is outside of valid calling times.";

      INSERT INTO queue SET
      name = "hang up within calling time",
      title = "Last call: hang up (within calling time)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "hang up ready" ) AS tmp ),
      description = "Participants whose last call result was 'hang up', the scheduled call
      back time has been reached and the participant's local time is within valid calling times.";

      INSERT INTO queue SET
      name = "hang up available",
      title = "Last call: hang up (available)",
      rank = 24,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "hang up within calling time" ) AS tmp ),
      description = "Available participants whose last call result was 'hang up', the scheduled call
      back time has been reached and the participant's local time is within valid calling times.";

      INSERT INTO queue SET
      name = "hang up always available",
      title = "Last call: hang up (always available)",
      rank = 25,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "hang up within calling time" ) AS tmp ),
      description = "Always available participants whose last call result was 'hang up', the scheduled call
      back time has been reached and the participant's local time is within valid calling times.";

      INSERT INTO queue SET
      name = "hang up not available",
      title = "Last call: hang up (not available)",
      rank = 26,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "hang up within calling time" ) AS tmp ),
      description = "Unavailable participants whose last call result was 'hang up', the scheduled call
      back time has been reached and the participant's local time is within valid calling times.";

      INSERT INTO queue SET
      name = "soft refusal",
      title = "Last call: soft refusal",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "old participant" ) AS tmp ),
      description = "Participants whose last call result was 'soft refusal'.";

      INSERT INTO queue SET
      name = "soft refusal waiting",
      title = "Last call: soft refusal (waiting)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "soft refusal" ) AS tmp ),
      description = "Participants whose last call result was 'soft refusal' and the scheduled call back
      time has not yet been reached.";

      INSERT INTO queue SET
      name = "soft refusal ready",
      title = "Last call: soft refusal (ready)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "soft refusal" ) AS tmp ),
      description = "Participants whose last call result was 'soft refusal' and the scheduled call back
      time has been reached.";

      INSERT INTO queue SET
      name = "soft refusal outside calling time",
      title = "Last call: soft refusal (outside calling time)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "soft refusal ready" ) AS tmp ),
      description = "Participants whose last call result was 'soft refusal', the scheduled call
      back time has been reached and the participant's local time is outside of valid calling times.";

      INSERT INTO queue SET
      name = "soft refusal within calling time",
      title = "Last call: soft refusal (within calling time)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "soft refusal ready" ) AS tmp ),
      description = "Participants whose last call result was 'soft refusal', the scheduled call
      back time has been reached and the participant's local time within of valid calling times.";

      INSERT INTO queue SET
      name = "soft refusal available",
      title = "Last call: soft refusal (available)",
      rank = 27,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "soft refusal within calling time" ) AS tmp ),
      description = "Available participants whose last call result was 'soft refusal', the scheduled call
      back time has been reached and the participant's local time within of valid calling times.";

      INSERT INTO queue SET
      name = "soft refusal always available",
      title = "Last call: soft refusal (always available)",
      rank = 28,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "soft refusal within calling time" ) AS tmp ),
      description = "Always available participants whose last call result was 'soft refusal', the
      scheduled call back time has been reached and the participant's local time within of valid calling
      times.";

      INSERT INTO queue SET
      name = "soft refusal not available",
      title = "Last call: soft refusal (not available)",
      rank = 29,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "soft refusal within calling time" ) AS tmp ),
      description = "Unavailable participants whose last call result was 'soft refusal', the scheduled
      call back time has been reached and the participant's local time within of valid calling times.";

      -- update the assignments with the new queue IDs
      UPDATE assignment SET queue_id = 108 WHERE queue_id = 107;
      UPDATE assignment SET queue_id = 107 WHERE queue_id = 106;
      UPDATE assignment SET queue_id = 106 WHERE queue_id = 105;
      UPDATE assignment SET queue_id = 105 WHERE queue_id = 104;
      UPDATE assignment SET queue_id = 104 WHERE queue_id = 103;
      UPDATE assignment SET queue_id = 103 WHERE queue_id = 102;
      UPDATE assignment SET queue_id = 102 WHERE queue_id = 101;
      UPDATE assignment SET queue_id = 101 WHERE queue_id = 100;
      UPDATE assignment SET queue_id = 100 WHERE queue_id = 99;
      UPDATE assignment SET queue_id = 99 WHERE queue_id = 98;
      UPDATE assignment SET queue_id = 98 WHERE queue_id = 97;
      UPDATE assignment SET queue_id = 97 WHERE queue_id = 96;
      UPDATE assignment SET queue_id = 96 WHERE queue_id = 95;
      UPDATE assignment SET queue_id = 95 WHERE queue_id = 94;
      UPDATE assignment SET queue_id = 94 WHERE queue_id = 93;
      UPDATE assignment SET queue_id = 93 WHERE queue_id = 92;
      UPDATE assignment SET queue_id = 92 WHERE queue_id = 91;
      UPDATE assignment SET queue_id = 91 WHERE queue_id = 90;
      UPDATE assignment SET queue_id = 90 WHERE queue_id = 89;
      UPDATE assignment SET queue_id = 89 WHERE queue_id = 88;
      UPDATE assignment SET queue_id = 88 WHERE queue_id = 87;
      UPDATE assignment SET queue_id = 87 WHERE queue_id = 86;
      UPDATE assignment SET queue_id = 86 WHERE queue_id = 85;
      UPDATE assignment SET queue_id = 85 WHERE queue_id = 84;
      UPDATE assignment SET queue_id = 84 WHERE queue_id = 83;
      UPDATE assignment SET queue_id = 83 WHERE queue_id = 82;
      UPDATE assignment SET queue_id = 82 WHERE queue_id = 81;
      UPDATE assignment SET queue_id = 81 WHERE queue_id = 80;
      UPDATE assignment SET queue_id = 80 WHERE queue_id = 79;
      UPDATE assignment SET queue_id = 79 WHERE queue_id = 78;
      UPDATE assignment SET queue_id = 78 WHERE queue_id = 77;
      UPDATE assignment SET queue_id = 77 WHERE queue_id = 76;
      UPDATE assignment SET queue_id = 76 WHERE queue_id = 75;
      UPDATE assignment SET queue_id = 75 WHERE queue_id = 74;
      UPDATE assignment SET queue_id = 74 WHERE queue_id = 73;
      UPDATE assignment SET queue_id = 73 WHERE queue_id = 72;
      UPDATE assignment SET queue_id = 72 WHERE queue_id = 71;
      UPDATE assignment SET queue_id = 71 WHERE queue_id = 70;
      UPDATE assignment SET queue_id = 70 WHERE queue_id = 69;
      UPDATE assignment SET queue_id = 69 WHERE queue_id = 68;
      UPDATE assignment SET queue_id = 68 WHERE queue_id = 67;
      UPDATE assignment SET queue_id = 67 WHERE queue_id = 66;
      UPDATE assignment SET queue_id = 66 WHERE queue_id = 65;
      UPDATE assignment SET queue_id = 65 WHERE queue_id = 64;
      UPDATE assignment SET queue_id = 64 WHERE queue_id = 63;
      UPDATE assignment SET queue_id = 63 WHERE queue_id = 62;
      UPDATE assignment SET queue_id = 62 WHERE queue_id = 61;
      UPDATE assignment SET queue_id = 61 WHERE queue_id = 60;
      UPDATE assignment SET queue_id = 60 WHERE queue_id = 59;
      UPDATE assignment SET queue_id = 59 WHERE queue_id = 58;
      UPDATE assignment SET queue_id = 58 WHERE queue_id = 57;
      UPDATE assignment SET queue_id = 57 WHERE queue_id = 56;
      UPDATE assignment SET queue_id = 56 WHERE queue_id = 55;
      UPDATE assignment SET queue_id = 55 WHERE queue_id = 54;
      UPDATE assignment SET queue_id = 54 WHERE queue_id = 53;
      UPDATE assignment SET queue_id = 53 WHERE queue_id = 52;
      UPDATE assignment SET queue_id = 52 WHERE queue_id = 51;
      UPDATE assignment SET queue_id = 51 WHERE queue_id = 50;
      UPDATE assignment SET queue_id = 50 WHERE queue_id = 49;
      UPDATE assignment SET queue_id = 49 WHERE queue_id = 48;
      UPDATE assignment SET queue_id = 48 WHERE queue_id = 47;
      UPDATE assignment SET queue_id = 47 WHERE queue_id = 46;
      UPDATE assignment SET queue_id = 46 WHERE queue_id = 45;
      UPDATE assignment SET queue_id = 45 WHERE queue_id = 44;
      UPDATE assignment SET queue_id = 44 WHERE queue_id = 43;
      UPDATE assignment SET queue_id = 43 WHERE queue_id = 42;
      UPDATE assignment SET queue_id = 42 WHERE queue_id = 41;
      UPDATE assignment SET queue_id = 41 WHERE queue_id = 40;
      UPDATE assignment SET queue_id = 40 WHERE queue_id = 39;
      UPDATE assignment SET queue_id = 39 WHERE queue_id = 38;
      UPDATE assignment SET queue_id = 38 WHERE queue_id = 37;
      UPDATE assignment SET queue_id = 37 WHERE queue_id = 36;
      UPDATE assignment SET queue_id = 36 WHERE queue_id = 35;
      UPDATE assignment SET queue_id = 35 WHERE queue_id = 34;
      UPDATE assignment SET queue_id = 34 WHERE queue_id = 33;
      UPDATE assignment SET queue_id = 33 WHERE queue_id = 32;
      UPDATE assignment SET queue_id = 32 WHERE queue_id = 31;
      UPDATE assignment SET queue_id = 31 WHERE queue_id = 30;
      UPDATE assignment SET queue_id = 30 WHERE queue_id = 29;

      -- add back assignment's foreign key to the queue table
      ALTER TABLE assignment
      ADD CONSTRAINT fk_assignment_queue_id
      FOREIGN KEY (queue_id) REFERENCES queue (id)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION;

    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_queue();
DROP PROCEDURE IF EXISTS patch_queue;
