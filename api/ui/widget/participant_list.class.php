<?php
/**
 * participant_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget participant list
 */
class participant_list extends site_restricted_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the participant list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant', $args );
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    $this->add_column( 'uid', 'string', 'UID', true );
    $this->add_column( 'first_name', 'string', 'First', true );
    $this->add_column( 'last_name', 'string', 'Last', true );
    $this->add_column( 'source.name', 'string', 'Source', true );
    $this->add_column( 'status', 'string', 'Condition', true );
    $this->add_column( 'primary_site', 'string', 'Site', false );

    $this->extended_site_selection = true;
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    foreach( $this->get_record_list() as $record )
    {
      $db_source = $record->get_source();
      $source_name = is_null( $db_source ) ? '(none)' : $db_source->name;
      $this->add_row( $record->id,
        array( 'uid' => $record->uid ? $record->uid : '(none)',
               'first_name' => $record->first_name,
               'last_name' => $record->last_name,
               'source.name' => $source_name,
               'status' => $record->status ? $record->status : '(none)',
               'primary_site' => $record->get_primary_site()->name,
               // note count isn't a column, it's used for the note button
               'note_count' => $record->get_note_count() ) );
    }

    // include the sync action if the widget isn't parented
    if( is_null( $this->parent ) )
    {
      $operation_class_name = lib::get_class_name( 'database\operation' );
      $db_operation = $operation_class_name::get_operation( 'widget', 'participant', 'sync' );
      if( lib::create( 'business\session' )->is_allowed( $db_operation ) )
        $this->add_action( 'sync', 'Participant Sync', $db_operation,
          'Synchronize participants with Mastodon' );
    }
  }
}
?>
