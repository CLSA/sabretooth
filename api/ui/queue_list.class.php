<?php
/**
 * queue_list.class.php
 * TODO: add indivisual site or global selector for admins
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget queue list
 * 
 * @package sabretooth\ui
 */
class queue_list extends base_list_widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the queue list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'queue', $args );
    
    $this->add_column( 'name', 'Name', false );
    $this->add_column( 'enabled', 'Enabled', false );
    $this->add_column( 'participant_count', 'Participants', false );
    $this->add_column( 'description', 'Description', false, 'left' );
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
    
    $session = \sabretooth\session::self();
    $is_supervisor = 'supervisor' == $session->get_role()->name;

    foreach( $this->get_record_list() as $record )
    {
      $modifier = NULL;
      if( $is_supervisor )
      {
        $mod = new \sabretooth\database\modifier();
        $mod->where( 'site_id', '=', $session->get_site()->id );
        $province_ids = array();
        foreach( \sabretooth\database\province::select( $mod ) as $db_province )
          $province_ids[] = $db_province->id;

        $modifier = new \sabretooth\database\modifier();
        $modifier->where( 'province_id', 'IN', $province_ids );
        $modifier->or_where( 'site_id', '=', $session->get_site()->id );
      }

      $db_setting = \sabretooth\database\setting::get_setting( 'queue state', $record->name );
      $this->add_row( $record->id,
        array( 'name' => $record->name,
               'enabled' => 'true' == $db_setting->value ? 'yes' : 'no',
               'participant_count' => $record->get_participant_count(),
               'description' => $record->description ) );
    }

    $this->finish_setting_rows();
  }
}
?>
