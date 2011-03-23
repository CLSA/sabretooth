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
class site_list extends base_list_widget
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
    
    $this->add_column( 'name', 'Name', true );
    $this->add_column( 'users', 'Users', false );
    $this->add_column( 'last', 'Last activity', false );
  }

  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    // get all sites
    foreach( $this->get_record_list() as $record )
    {
      // determine the last activity
      $db_activity = $record->get_last_activity();
      $last = \sabretooth\util::get_fuzzy_period_ago(
                is_null( $db_activity ) ? null : $db_activity->date );

      $this->add_row( $record->id,
        array( 'name' => $record->name,
               'users' => $record->get_user_count(),
               'last' => $last ) );
    }

    $this->finish_setting_rows();
  }
}
?>
