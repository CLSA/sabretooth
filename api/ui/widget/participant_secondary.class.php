<?php
/**
 * participant_secondary.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget participant secondary
 */
class participant_secondary extends \cenozo\ui\widget\base_record
{
  /**
   * Constructor
   * 
   * Defines all variables required by the participant secondary widget.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant', 'secondary', $args );
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    $this->set_heading( 'Secondary Contact List' );
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

    // get a list of this participant's alternates from Mastodon
    $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
    $alternate_info = $mastodon_manager->pull( 'participant','list_alternate',
      array( 'uid' => $this->get_record()->uid ) );

    $alternate_list = array();
    foreach( $alternate_info->data as $alternate )
    {
      if( $alternate->alternate && isset( $alternate->phone_list ) )
      { // only add alternates (not proxies or informants)
        $phone_list = array();
        foreach( $alternate->phone_list as $phone )
        {
          if( $phone->active )
            $phone_list[$phone->rank] = array(
              'type' => $phone->type,
              'number' => $phone->number,
              'clean_number' => preg_replace( '/[^0-9]/', '', $phone->number ),
              'note' => $phone->note ? $phone->note : '' );
        }
        ksort( $phone_list );

        $alternate_list[] = array(
          'id' => $alternate->id,
          'first_name' => $alternate->first_name,
          'last_name' => $alternate->last_name,
          'association' => $alternate->association ? $alternate->association : 'unknown',
          'phone_list' => $phone_list );
      }
    }

    $this->set_variable( 'alternate_list', $alternate_list );
    $this->set_variable( 'participant_name',
      sprintf( $this->get_record()->first_name.' '.$this->get_record()->last_name ) );
    $this->set_variable( 'secondary_id',
      array_key_exists( 'secondary_id', $_COOKIE ) ?  $_COOKIE['secondary_id'] : 0 );
  }
}
?>
