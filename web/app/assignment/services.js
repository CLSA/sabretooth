define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnAssignmentListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( params ) {
        var base = CnBaseListFactory.instance( params );
        for( var p in base ) if( base.hasOwnProperty( p ) ) this[p] = base[p];

        ////////////////////////////////////
        // factory customizations start here
        this.columnList = {
          user: {
            column: 'user.name',
            title: 'Operator'
          },
          site: {
            column: 'site.name',
            title: 'Site'
          },
          uid: {
            column: 'interview.participant.uid',
            title: 'UID'
          },
          start_datetime: {
            column: 'assignment.start_datetime',
            title: 'Start Time',
            filter: 'date:"MMM d, y HH:mm"',
            isDate: true
          },
          status: {
            title: 'Status'
          },
          complete: {
            column: 'interview.completed',
            title: 'Complete',
            filter: 'cnCheckmark'
          }
        };
        this.order = { column: 'start_datetime', reverse: true };
        // factory customizations end here
        //////////////////////////////////

        cnCopyParams( this, params );
      };

      object.prototype = CnBaseListFactory.prototype;
      return { instance: function( params ) { return new object( undefined === params ? {} : params ); } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnAssignmentViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      return { instance: function( params ) { return CnBaseViewFactory.instance( params ); } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnAssignmentSingleton', [
    'CnBaseSingletonFactory', 'CnAssignmentListFactory', 'CnAssignmentViewFactory',
    function( CnBaseSingletonFactory, CnAssignmentListFactory, CnAssignmentViewFactory ) {
      var object = function() {
        var base = CnBaseSingletonFactory.instance( {
          subject: 'assignment',
          name: {
            singular: 'assignment',
            plural: 'assignments',
            possessive: 'assignment\'s',
            pluralPossessive: 'assignments\''
          },
          cnList: CnAssignmentListFactory.instance( { subject: 'assignment' } ),
          cnView: CnAssignmentViewFactory.instance( { subject: 'assignment' } )
        } );
        for( var p in base ) if( base.hasOwnProperty( p ) ) this[p] = base[p];
      };

      object.prototype = CnBaseSingletonFactory.prototype;
      // don't return a method to create instances, create and return the singleton
      return new object();
    }
  ] );

  return true;
} );
