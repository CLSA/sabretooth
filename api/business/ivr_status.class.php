<?php
/**
 * ivr_status.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\business;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * This is an enum class which defines all types of participant status from the IVR service.
 */
abstract class ivr_status
{
  const NO_APPOINTMENT = 0;
  const FUTURE_APPOINTMENT_SCHEDULED = 1;
  const CALLING_IN_PROGRESS = 2;
  const CALLING_COMPLETE_INTERVIEW_COMPLETE = 3;
  const CALLING_COMPLETE_INTERVIEW_NOT_COMPLETE = 4;
  const ERROR = -1;
}
