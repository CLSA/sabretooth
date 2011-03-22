<?php
/**
 * queue.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;

/**
 * queue: record
 *
 * @package sabretooth\database
 */
class queue extends record
{
  /**
   * Returns the number of participants currently in the queue.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param modifier $modifier Modifications to the queue.
   * @return int
   * @access public
   */
  public function get_participant_count( $modifier = NULL )
  {
    $session = \sabretooth\session::self();
    if( is_null( $modifier ) ) $modifier = new modifier();

    if( !is_null( $this->db_site ) )
    { // restrict to the site
      $mod = new modifier();
      $mod->where( 'site_id', '=', $session->get_site()->id );
      $province_ids = array();
      foreach( province::select( $mod ) as $db_province ) $province_ids[] = $db_province->id;
      $modifier->where( 'site_id', '=', $session->get_site()->id );
      $modifier->where( 'province_id', 'IN', $province_ids );
    }

    // get the name of the queue-view
    return static::db()->get_one(
      sprintf( "SELECT COUNT(*) FROM %s %s",
               $this->view,
               $modifier->get_sql() ) );
  }

  /**
   * Returns a list of participants currently in the queue.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param modifier $modifier Modifications to the queue.
   * @return array( participant )
   * @access public
   */
  public function get_participant_list( $modifier = NULL )
  {
    $session = \sabretooth\session::self();
    if( is_null( $modifier ) ) $modifier = new modifier();

    if( !is_null( $this->db_site ) )
    { // restrict to the site
      $mod = new modifier();
      $mod->where( 'site_id', '=', $session->get_site()->id );
      $province_ids = array();
      foreach( province::select( $mod ) as $db_province ) $province_ids[] = $db_province->id;
      $modifier->where( 'site_id', '=', $session->get_site()->id );
      $modifier->where( 'province_id', 'IN', $province_ids );
    }

    // get the name of the queue-view
    $participant_ids = static::db()->get_col(
      sprintf( "SELECT id FROM %s %s",
               $this->view,
               $modifier->get_sql() ) );

    $participants = array();
    foreach( $participant_ids as $id ) $participants[] = new participant( $id );
    return $participants;
  }

  /**
   * The site to restrict the queue to.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param site $db_site
   * @access public
   */
  public function set_site( $db_site = NULL )
  {
    $this->db_site = $db_site;
  }

  /**
   * The site to restrict the queue to.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @var site $db_site
   */
  protected $db_site = NULL;
}
?>
