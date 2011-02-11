<?php
/**
 * site_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget site list
 * 
 * @package sabretooth\ui
 */
class site_list extends base_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the site list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'site', $args );
    
    $session = \sabretooth\session::self();

    // define all template variables for this list
    $this->set_heading( 'Site list' );

    $this->columns = array(
      array( 'id' => 'name',
             'heading' => 'Name',
             'sortable' => true ),
      array( 'id' => 'users',
             'heading' => 'Users',
             'sortable' => false ),
      array( 'id' => 'last',
             'heading' => 'Last activity',
             'sortable' => false ) ); 
  }

  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function set_rows()
  {
    // reset the array
    $this->rows = array();
    
    // get all sites
    foreach( $this->get_record_list() as $record )
    {
      // determine the last activity
      $db_activity = $record->get_last_activity();
      $last = is_null( $db_activity )
            ? 'never'
            : \sabretooth\util::get_fuzzy_time_ago( $db_activity->date );
      
      // determine the last activity
      $db_activity = $record->get_last_activity();
      $last = \sabretooth\util::get_fuzzy_time_ago(
                is_null( $db_activity ) ? null : $db_activity->date );

      array_push(
        $this->rows,
        array( 'id' => $record->id,
               'columns' =>
                 array( 'name' => $record->name,
                        'users' => $record->get_user_count(),
                        'last' => $last ) ) );
    }
  }
}
?>
