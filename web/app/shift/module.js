define( function() {
  'use strict';

  try { var url = cenozoApp.module( 'shift', true ).url; } catch( err ) { console.warn( err ); return; }
  angular.extend( cenozoApp.module( 'shift' ), {
    identifier: {},
    name: {
      singular: 'shift',
      plural: 'shifts',
      possessive: 'shift\'s',
      pluralPossessive: 'shifts\''
    },
    columnList: {
      username: {
        column: 'user.name',
        title: 'User'
      },
      firstname: {
        column: 'user.first_name',
        title: 'First Name'
      },
      lastname: {
        column: 'user.last_name',
        title: 'Last Name'
      },
      start_datetime: {
        type: 'datetime',
        title: 'Start Date & Time'
      },
      end_datetime: {
        type: 'datetime',
        title: 'End Date & Time'
      }
    },
    defaultOrder: {
      column: 'end_datetime',
      reverse: true
    }
  } );

  cenozoApp.module( 'shift' ).addInputGroup( null, {
    user_id: {
      title: 'User',
      type: 'lookup-typeahead',
      typeahead: {
        table: 'user',
        select: 'CONCAT( first_name, " ", last_name, " (", name, ")" )',
        where: [ 'first_name', 'last_name', 'name' ]
      }
    },
    start_datetime: {
      title: 'Start Date & Time',
      type: 'datetime'
    },
    end_datetime: {
      title: 'End Date & Time',
      type: 'datetime'
    },
  } );

  // converts shifts into events
  function getEventFromShift( shift, timezone ) {
    if( angular.isDefined( shift.start ) && angular.isDefined( shift.end ) ) {
      return shift;
    } else {
      return {
        getIdentifier: function() { return shift.getIdentifier() },
        title: shift.username,
        start: moment( shift.start_datetime ).tz( timezone ),
        end: moment( shift.end_datetime ).tz( timezone )
      };
    }
  }

  cenozoApp.module( 'shift' ).addExtraOperation(
    'list',
    'Shift Calendar',
    function( listModel, $state ) { $state.go( 'shift.calendar' ); }
  );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnShiftAdd', [
    'CnShiftModelFactory', 'CnSession',
    function( CnShiftModelFactory, CnSession ) {
      return {
        templateUrl: url + 'add.tpl.html',
        restrict: 'E',
        controller: function( $scope ) {
          $scope.model = CnShiftModelFactory.root;
          $scope.record = {};
          $scope.model.addModel.onNew( $scope.record ).then( function() {
            if( null != $scope.model.addModel.calendarStartDate ) {
              var addDirective = $scope.$$childHead;
              // set the start date in the record and formatted record
              $scope.record.start_datetime = moment( $scope.model.addModel.calendarStartDate ).format();
              addDirective.formattedRecord.start_datetime = CnSession.formatValue(
                $scope.model.addModel.calendarStartDate, 'datetime', true );
              $scope.model.addModel.calendarStartDate = null;
            }
            $scope.model.setupBreadcrumbTrail( 'add' );
          } );
        },
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnShiftCalendar', [
    'CnShiftModelFactory',
    function( CnShiftModelFactory ) {
      return {
        templateUrl: url + 'calendar.tpl.html',
        restrict: 'E',
        controller: function( $scope ) {
          $scope.model = CnShiftModelFactory.root;
          $scope.model.setupBreadcrumbTrail( 'calendar' );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnShiftList', [
    'CnShiftModelFactory',
    function( CnShiftModelFactory ) {
      return {
        templateUrl: url + 'list.tpl.html',
        restrict: 'E',
        controller: function( $scope ) {
          $scope.model = CnShiftModelFactory.root;
          $scope.model.listModel.onList( true ).then( function() {
            $scope.model.setupBreadcrumbTrail( 'list' );
          } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnShiftView', [
    'CnShiftModelFactory',
    function( CnShiftModelFactory ) {
      return {
        templateUrl: url + 'view.tpl.html',
        restrict: 'E',
        controller: function( $scope ) {
          $scope.model = CnShiftModelFactory.root;
          $scope.model.viewModel.onView().then( function() {
            $scope.model.setupBreadcrumbTrail( 'view' );
          } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnShiftAddFactory', [
    'CnBaseAddFactory', 'CnSession',
    function( CnBaseAddFactory, CnSession ) {
      var object = function( parentModel ) {
        var self = this;
        CnBaseAddFactory.construct( this, parentModel );

        // used to communicate that a new shift is being added from the calendar
        this.calendarStartDate = null;

        // add the new shift's events to the calendar cache
        this.onAdd = function( record ) {
          return this.$$onAdd( record ).then( function() {
            record.getIdentifier = function() { return parentModel.getIdentifierFromRecord( record ); };
            var minDate = parentModel.calendarModel.cacheMinDate;
            var maxDate = parentModel.calendarModel.cacheMaxDate;
            parentModel.calendarModel.cache.push( getEventFromShift( record, CnSession.user.timezone ) );
          } );
        };
      };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnShiftCalendarFactory', [
    'CnBaseCalendarFactory', 'CnSession',
    function( CnBaseCalendarFactory, CnSession ) {
      var object = function( parentModel ) {
        var self = this;
        CnBaseCalendarFactory.construct( this, parentModel );

        // extend onList to transform templates into events
        this.onList = function( replace, minDate, maxDate ) {
          // we must get the load dates before calling $$onList
          var loadMinDate = self.getLoadMinDate( replace, minDate );
          var loadMaxDate = self.getLoadMaxDate( replace, maxDate );
          return self.$$onList( replace, minDate, maxDate ).then( function() {
            self.cache.forEach( function( item, index, array ) {
              array[index] = getEventFromShift( item, CnSession.user.timezone );
            } );
          } );
        };
      };

      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnShiftListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) {
        CnBaseListFactory.construct( this, parentModel );

        // remove the deleted shift from the calendar cache
        this.onDelete = function( record ) {
          return this.$$onDelete( record ).then( function() {
            parentModel.calendarModel.cache = parentModel.calendarModel.cache.filter( function( e ) {
              return e.getIdentifier() != record.getIdentifier();
            } );
          } );
        };
      };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnShiftViewFactory', [
    'CnBaseViewFactory', 'CnSession',
    function( CnBaseViewFactory, CnSession ) {
      var args = arguments;
      var CnBaseViewFactory = args[0];
      var object = function( parentModel, root ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, root );

        // remove the deleted shift's events from the calendar cache
        this.onDelete = function() {
          return this.$$onDelete().then( function() {
            parentModel.calendarModel.cache = parentModel.calendarModel.cache.filter( function( e ) {
              return e.getIdentifier() != self.record.getIdentifier();
            } );
          } );
        };

        // remove and re-add the shift's events from the calendar cache
        this.onPatch = function( data ) {
          return this.$$onPatch( data ).then( function() {
            var minDate = parentModel.calendarModel.cacheMinDate;
            var maxDate = parentModel.calendarModel.cacheMaxDate;
            parentModel.calendarModel.cache = parentModel.calendarModel.cache.filter( function( e ) {
              return e.getIdentifier() != self.record.getIdentifier();
            } );
            parentModel.calendarModel.cache.push( getEventFromShift( self.record, CnSession.user.timezone ) );
          } );
        };
      }
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnShiftModelFactory', [
    'CnBaseModelFactory',
    'CnShiftAddFactory', 'CnShiftCalendarFactory',
    'CnShiftListFactory', 'CnShiftViewFactory',
    '$state',
    function( CnBaseModelFactory,
              CnShiftAddFactory, CnShiftCalendarFactory,
              CnShiftListFactory, CnShiftViewFactory,
              $state ) {
      var object = function( root ) {
        var self = this;
        CnBaseModelFactory.construct( this, cenozoApp.module( 'shift' ) );
        this.addModel = CnShiftAddFactory.instance( this );
        this.calendarModel = CnShiftCalendarFactory.instance( this );
        this.listModel = CnShiftListFactory.instance( this );
        this.viewModel = CnShiftViewFactory.instance( this, root );
      };

      return {
        root: new object( true ),
        instance: function() { return new object(); }
      };
    }
  ] );

} );
