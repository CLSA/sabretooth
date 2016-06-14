<?php
/**
 * call_history.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\business\report;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Mailout required report data.
 * 
 * @abstract
 */
class call_history extends \cenozo\business\report\base_report
{
  /**
   * Build the report
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    $participant_class_name = lib::get_class_name( 'database\participant' );

    $select = lib::create( 'database\select' );
    $select->from( 'participant' );
    $select->add_table_column( 'cohort', 'name', 'Cohort' );
    $select->add_table_column( 'language', 'name', 'Language' );
    $select->add_column( 'uid', 'UID' );
    $select->add_column( 'IFNULL( state.name, "None" )', 'Condition', false );
    $select->add_column( 'honorific', 'Honorific' );
    $select->add_column( 'first_name', 'First Name' );
    $select->add_column( 'last_name', 'Last Name' );
    $select->add_table_column( 'address', 'address1', 'Address1' );
    $select->add_table_column( 'address', 'address2', 'Address2' );
    $select->add_table_column( 'address', 'city', 'City' );
    $select->add_table_column( 'region', 'name', 'Province/State' );
    $select->add_table_column( 'address', 'postcode', 'Postcode' );
    $select->add_table_column( 'region', 'country', 'Country' );
    $select->add_column( 'IFNULL( email, "" )', 'Email', false );
    $select->add_column(
      'IF( '.
        'participant_last_consent.consent_id IS NULL, '.
        '"None", '.
        'CONCAT( IF( written, "Written ", "Verbal " ), IF( accept, "Accept", "Deny" ) ) '.
      ')', 'Consent', false );

    $modifier = lib::create( 'database\modifier' );
    $modifier->join( 'language', 'participant.language_id', 'language.id' );
    $modifier->join( 'cohort', 'participant.cohort_id', 'cohort.id' );
    $modifier->left_join( 'state', 'participant.state_id', 'state.id' );
    $modifier->join( 'participant_last_consent', 'participant.id', 'participant_last_consent.participant_id' );
    $modifier->join( 'consent_type', 'participant_last_consent.consent_type_id', 'consent_type.id' );
    $modifier->left_join( 'consent', 'participant_last_consent.consent_id', 'consent.id' );
    $modifier->join( 'participant_first_address', 'participant.id', 'participant_first_address.participant_id' );
    $modifier->left_join( 'address', 'participant_first_address.address_id', 'address.id' );
    $modifier->left_join( 'region', 'address.region_id', 'region.id' );
    $modifier->where( 'consent_type.name', '=', 'participation' );

    // set up requirements
    $this->apply_restrictions( $modifier );

    $header = array();
    $content = array();
    $sql = sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() );

    // set up the content
    foreach( $participant_class_name::select( $select, $modifier ) as $row ) $content[] = array_values( $row );

    // set up the header
    foreach( $row as $column => $value ) $header[] = ucwords( str_replace( '_', ' ', $column ) );

    $this->add_table( NULL, $header, $content, NULL );
  }
}
