<?php
/**
 * interview_queue.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\business
 */

namespace sabretooth\business;
require_once API_PATH.'/base_object.class.php';

/**
 * interview_queue: manages the creation of new interviews
 *
 * The interview_queue class manages a list of queues containing participants from which to create
 * interviews from.
 * @package sabretooth\business
 */
class interview_queue extends \sabretooth\base_object {}
?>
