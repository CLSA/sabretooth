<?php
/**
 * base_report.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * Base class for all report widgets
 * 
 * @abstract
 * @package sabretooth\ui
 */
abstract class base_report extends \sabretooth\ui\widget
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The subject being viewed.
   * @param string $name The name of the operation.
   * @param array $args An associative array of arguments to be processed by the widget
   * @throws exception\argument
   * @access public
   */
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, 'report', $args );

    // allow pull reports to ask whether a restriction has been added
    // e.g.,  'true' == $this->get_argument( 'has_restrict_dates' )
    foreach( $this->restrictions as $key => $value )
    {
      $restriction_type = 'has_restrict_'.$key;
      $this->add_parameter( $restriction_type, 'hidden' );
    }
  }

  /**
   * Adds a restriction to the report, for example, restrict by site.  To add a new
   * type, edit the class array ivar 'restrictions' and perform an add_parameter as
   * required so that pull classes can act accordingly. Child classes need only call
   * add_restriction in their constructor.  Retrictions can also influence report
   * title generation: see pull/base_report class.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param string $restriction_type The type of restriction requested.
   * @throws exception\argument
   * @access protected
   */
  protected function add_restriction( $restriction_type )
  {
    if( !array_key_exists( $restriction_type, $this->restrictions ) )
      throw new exc\argument( 'restriction_type', $restriction_type, __METHOD__ );

    if( 'site' == $restriction_type )
    {
      $this->restrictions[ 'site' ]  = true;

      if( static::may_restrict_by_site() ) 
      {
        $this->add_parameter( 'restrict_site_id', 'enum', 'Site' );
      }
      else
      {
        $this->add_parameter( 'restrict_site_id', 'hidden' );

        // if restricted, show the site's name in the heading
        $predicate = bus\session::self()->get_site()->name;
        $this->set_heading( $this->get_heading().' for '.$predicate );
      }
    }
    else if( 'qnaire' == $restriction_type )
    {
      $this->restrictions[ 'qnaire' ] = true;

      $this->add_parameter( 'restrict_qnaire_id', 'enum', 'Questionnaire' );
    }
    else if( 'dates' == $restriction_type )
    {
      $this->restrictions[ 'dates' ] = true;

      $this->add_parameter( 'restrict_start_date', 'date', 'Start Date', 
        'Leave blank for an overall report (warning, an overall repost my be a VERY large file).' );
      $this->add_parameter( 'restrict_end_date', 'date', 'End Date', 
        'Leave blank for an overall report (warning, an overall repost my be a VERY large file).' );
    }
    else if( 'consent' == $restriction_type )
    {
      $this->restrictions[ 'consent' ] = true;

      $this->add_parameter( 'restrict_consent_id', 'enum', 'Consent Status');
    }
    else if( 'mailout' == $restriction_type )
    {
      $this->restrictions[ 'mailout' ] = true;

      $this->add_parameter( 'restrict_mailout_id', 'enum', 'Mailout' );
    }
    else if( 'province' == $restriction_type )
    {
      $this->restrictions[ 'province' ] = true;

      $this->add_parameter( 'restrict_province_id', 'enum', 'Province' );
    }
    else if( 'site_or_province' == $restriction_type )
    {
      $this->restrictions[ 'site_or_province' ] = true;

      $this->add_parameter( 'restrict_site_or_province_id', 'enum', 'Site or Province' );
    }
  }

  /**
   * Add a parameter to the report.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $param_id The parameter's id, can be one of the record's column names.
   * @param string $type The parameter's type, one of "boolean", "date", "time", "datetime",
   *               "number", "string", "text", "enum" or "hidden"
   * @param string $heading The parameter's heading as it will appear in the view
   * @param string $note A note to add below the parameter.
   * @access public
   */
  public function add_parameter( $param_id, $type, $heading = NULL, $note = NULL )
  {
    // add timezone info to the note if the parameter is a time or datetime
    if( 'time' == $type || 'datetime' == $type )
    {
      // build time time zone help text
      $date_obj = util::get_datetime_object();
      $time_note = sprintf( 'Time is in %s\'s time zone (%s)',
                            bus\session::self()->get_site()->name,
                            $date_obj->format( 'T' ) );
      $note = is_null( $note ) ? $time_note : $time_note.'<br>'.$note;
    }

    $this->parameters[$param_id] = array( 'type' => $type );
    if( !is_null( $heading ) ) $this->parameters[$param_id]['heading'] = $heading;
    if( !is_null( $note ) ) $this->parameters[$param_id]['note'] = $note;
  }

  /**
   * Sets a parameter's value and additional data.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $param_id The parameter's id, can be one of the record's column names.
   * @param mixed $value The parameter's value.
   * @param mixed $data For enum parameter types, an array of all possible values and for date and
   *              datetime types an associative array of min_date and/or max_date
   * @throws exception\argument
   * @access public
   */
  public function set_parameter( $param_id, $value, $required = false, $data = NULL )
  {
    // make sure the parameter exists
    if( !array_key_exists( $param_id, $this->parameters ) )
      throw new exc\argument( 'param_id', $param_id, __METHOD__ );

    // process the value so that it displays correctly
    if( 'boolean' == $this->parameters[$param_id]['type'] )
    {
      if( is_null( $value ) ) $value = '';
      else $value = $value ? 'Yes' : 'No';
    }
    else if( 'date' == $this->parameters[$param_id]['type'] )
    {
      if( strlen( $value ) )
      {
        $date_obj = util::get_datetime_object( $value );
        $value = $date_obj->format( 'Y-m-d' );
      }
      else $value = '';
    }
    else if( 'time' == $this->parameters[$param_id]['type'] )
    {
      if( strlen( $value ) )
      {
        $date_obj = util::get_datetime_object( $value );
        $value = $date_obj->format( 'H:i' );
      }
      else $value = '12:00';
    }
    else if( 'hidden' == $this->parameters[$param_id]['type'] )
    {
      if( is_bool( $value ) ) $value = $value ? 'true' : 'false';
    }
    else if( 'constant' == $this->parameters[$param_id]['type'] &&
             ( ( is_int( $value ) && 0 == $value ) ||
               ( is_string( $value ) && '0' == $value ) ) )
    {
      $value = ' 0';
    }
    else if( 'number' == $this->parameters[$param_id]['type'] )
    {
      $value = floatval( $value );
    }

    $this->parameters[$param_id]['value'] = $value;
    if( 'enum' == $this->parameters[$param_id]['type'] )
    {
      $enum = $data;
      if( is_null( $enum ) )
        throw new exc\runtime(
          'Trying to set enum parameter without enum values.', __METHOD__ );

      // add a null entry (to the front of the array) if the parameter is not required
      if( !$required )
      {
        $enum = array_reverse( $enum, true );
        $enum['NULL'] = '';
        $enum = array_reverse( $enum, true );
      }
      $this->parameters[$param_id]['enum'] = $enum;
    }
    else if( 'date' == $this->parameters[$param_id]['type'] ||
             'datetime' == $this->parameters[$param_id]['type'] )
    {
      if( is_array( $data ) )
      {
        $date_limits = $data;
        if( array_key_exists( 'min_date', $date_limits ) )
          $this->parameters[$param_id]['min_date'] = $date_limits['min_date'];
        if( array_key_exists( 'max_date', $date_limits ) )
          $this->parameters[$param_id]['max_date'] = $date_limits['max_date'];
      }
    }

    $this->parameters[$param_id]['required'] = $required;
  }

  /**
   * Must be called after all parameters have been set.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish_setting_parameters()
  {
    $this->set_variable( 'parameters', $this->parameters );
  }

  /**
   * Child classes should implement and call parent's finish and then call 
   * finish_setting_parameters
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    if( $this->restrictions[ 'site' ] )
    {
      if( static::may_restrict_by_site() )
      {
        // if allowed, give them a list of sites to choose from
        $sites = array( 0 => 'All sites' );
        foreach( db\site::select() as $db_site )
          $sites[$db_site->id] = $db_site->name;
  
        $this->set_parameter( 'restrict_site_id', key( $sites ), true, $sites );
      }
      else
      {
        $this->set_parameter( 'restrict_site_id', bus\session::self()->get_site()->id );
      }
    }
    
    if( $this->restrictions[ 'qnaire' ] )
    {
      $qnaires = array();
      foreach( db\qnaire::select() as $db_qnaire ) 
        $qnaires[ $db_qnaire->id ] = $db_qnaire->name;

      $this->set_parameter( 'restrict_qnaire_id', current( $qnaires ), true, $qnaires );  
    }

    if( $this->restrictions[ 'consent' ] )
    {
      $consent_types = db\consent::get_enum_values( 'event' );
      array_unshift( $consent_types, 'Any' );
      $consent_types = array_combine( $consent_types, $consent_types );

      $this->set_parameter( 'restrict_consent_id', current( $consent_types ), true, $consent_types );
    }

    if( $this->restrictions[ 'province' ] )
    {
      $region_mod = new db\modifier();
      $region_mod->order( 'abbreviation' );
      $region_mod->where( 'country', '=', 'Canada' );
      $region_types = array( 'All provinces' );
      foreach( db\region::select( $region_mod ) as $db_region )
        $region_types[ $db_region->id ] = $db_region->name;

      $this->set_parameter( 'restrict_province_id', current( $region_types ), true, $region_types );
    }

    if( $this->restrictions[ 'site_or_province' ] )
    {
      $site_or_prov = array( 'Site', 'Province' );
      $site_or_prov = array_combine( $site_or_prov, $site_or_prov );

      $this->set_parameter( 'restrict_site_or_province_id', 
        current( $site_or_prov ), true, $site_or_prov );
    }

    if( $this->restrictions[ 'dates' ] )
    {
      $this->set_parameter( 'restrict_start_date', '', false );
      $this->set_parameter( 'restrict_end_date', '', false );
    }

    if( $this->restrictions[ 'mailout' ] )
    {
      $mailout_types = array( 'Participant information package',
                              'Proxy information package' );
      $mailout_types = array_combine( $mailout_types, $mailout_types );

      $this->set_parameter( 'restrict_mailout_id', current( $mailout_types ), true, $mailout_types );
    }

    foreach( $this->restrictions as $key => $value )
    {
      $restriction_type = 'has_restrict_'.$key;
      $this->set_parameter( $restriction_type,  $value );
    }
    // this has to be done AFTER the remove_column() call above
    parent::finish();
  }

  /**
   * Determines whether the current user may choose which site to restrict by.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return boolean
   * @static
   * @access public
   */
  public static function may_restrict_by_site()
  {
    return 3 == bus\session::self()->get_role()->tier;
  }

  /**
   * An associative array where the key is a unique identifier (usually a column name) and the
   * value is an associative array which includes:
   * "heading" => the label to display
   * "type" => the type of variable (see {@link add_parameter} for details)
   * "value" => the value of the column
   * "enum" => all possible values if the parameter type is "enum"
   * "required" => boolean describes whether the value can be left blank
   * @var array
   * @access private
   */
  private $parameters = array();

  private $restrictions = array( 
    'site' => false,
    'qnaire' => false,
    'dates' => false,
    'consent' => false,
    'province' => false,
    'mailout' => false,
    'site_or_province' => false );

}
?>
