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
            join: true,
            title: 'Operator'
          },
          site: {
            column: 'site.name',
            join: true,
            title: 'Site'
          },
          uid: {
            column: 'interview.participant.uid',
            join: true,
            title: 'UID'
          },
          start: {
            column: 'assignment.start_datetime',
            title: 'Start Time',
            filter: 'date:"MMM d, y HH:mm"',
            isDate: true
          },
          end_datetime: {
            title: 'End Time',
            filter: 'date:"MMM d, y HH:mm"',
            isDate: true
          },
          last_status: {
            title: 'Status'
          },
          complete: {
            column: 'interview.completed',
            join: true,
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
