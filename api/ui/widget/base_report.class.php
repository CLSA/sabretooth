<?php
/**
 * base_report.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Base class for all report widgets
 * 
 * @abstract
 * @package sabretooth\ui
 */
abstract class base_report extends \cenozo\ui\widget\base_report
{
  /**
   * Constructor
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The subject being viewed.
   * @param string $name The name of the operation.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, 'report', $args );
  
    $this->restrictions['qnaire'] = false;
    $this->restrictions['consent'] = false;
    $this->restrictions['mailout'] = false;
  }

  /**
   * Adds more restrictions to reports.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param string $restriction_type The type of restriction requested.
   * @throws exception\argument
   * @access protected
   */
  protected function add_restriction( $restriction_type )
  {
    parent::add_restriction( $restriction_type );

    if( 'qnaire' == $restriction_type )
    {
      $this->restrictions[ 'qnaire' ] = true;
      $this->add_parameter( 'restrict_qnaire_id', 'enum', 'Questionnaire' );
    }
    else if( 'consent' == $restriction_type )
    {
      $this->restrictions[ 'consent' ] = true;
      $this->add_parameter( 'restrict_consent', 'enum', 'Consent Status');
    }
    else if( 'mailout' == $restriction_type )
    {
      $this->restrictions[ 'mailout' ] = true;
      $this->add_parameter( 'restrict_mailout', 'enum', 'Mailout' );
    }
  }

  /**
   * Child classes should implement and call parent's finish and then call 
   * finish_setting_parameters
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    if( $this->restrictions[ 'qnaire' ] )
    {
      $qnaires = array();
      $class_name = lib::get_class_name( 'database\qnaire' );
      foreach( $class_name::select() as $db_qnaire ) 
        $qnaires[ $db_qnaire->id ] = $db_qnaire->name;

      $this->set_parameter( 'restrict_qnaire_id', current( $qnaires ), true, $qnaires );  
    }

    if( $this->restrictions[ 'consent' ] )
    {
      $class_name = lib::get_class_name( 'database\consent' );
      $consent_types = $class_name::get_enum_values( 'event' );
      array_unshift( $consent_types, 'Any' );
      $consent_types = array_combine( $consent_types, $consent_types );

      $this->set_parameter(
        'restrict_consent', current( $consent_types ), true, $consent_types );
    }

    if( $this->restrictions[ 'mailout' ] )
    {
      $mailout_types = array( 'Participant information package',
                              'Proxy information package' );
      $mailout_types = array_combine( $mailout_types, $mailout_types );

      $this->set_parameter(
        'restrict_mailout', current( $mailout_types ), true, $mailout_types );
    }

    parent::finish();
  }
}
?>
