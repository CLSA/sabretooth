define( [].concat(
          cenozoApp.module( 'appointment' ).getRequiredFiles(),
          cenozoApp.module( 'shift_template' ).getRequiredFiles(),
          cenozoApp.module( 'site_shift' ).getRequiredFiles()
        ), function() {
  'use strict';

  try { var module = cenozoApp.module( 'shift', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( module, {
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

  module.addInputGroup( null, {
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
      type: 'datetime',
      max: 'end_datetime'
    },
    end_datetime: {
      title: 'End Date & Time',
      type: 'datetime',
      min: 'start_datetime'
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

  module.addExtraOperation(
    'calendar',
    'Appointment',
    function( calendarModel, $state ) { $state.go( 'appointment.calendar' ); }
  );

  module.addExtraOperation(
    'calendar',
    'Shift',
    function( calendarModel, $state ) { $state.go( 'shift.calendar' ); },
    true // disabled
  );

  module.addExtraOperation(
    'calendar',
    'Shift Template',
    function( calendarModel, $state ) { $state.go( 'shift_template.calendar' ); }
  );

  module.addExtraOperation(
    'calendar',
    'Availability',
    function( calendarModel, $state ) { $state.go( 'site_shift.calendar' ); }
  );

  module.addExtraOperation(
    'list',
    'Shift Calendar',
    function( listModel, $state ) { $state.go( 'shift.calendar' ); }
  );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnShiftAdd', [
    'CnShiftModelFactory', 'CnSession',
    function( CnShiftModelFactory, CnSession ) {
      return {
        templateUrl: module.url + 'add.tpl.html',
        restrict: 'E',
        controller: function( $scope ) {
          $scope.model = CnShiftModelFactory.root;
          $scope.record = {};
          $scope.model.addModel.onNew( $scope.record ).then( function() {
            if( angular.isDefined( $scope.model.addModel.calendarDate ) ) {
              var addDirective = $scope.$$childHead;
              // set the start date in the record and formatted record
              $scope.record.start_datetime = moment( $scope.model.addModel.calendarDate ).format();
              addDirective.formattedRecord.start_datetime = CnSession.formatValue(
                $scope.model.addModel.calendarDate, 'datetime', true );
              delete $scope.model.addModel.calendarDate;
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
    'CnAppointmentModelFactory', 'CnShiftTemplateModelFactory', 'CnSiteShiftModelFactory',
    function( CnShiftModelFactory,
              CnAppointmentModelFactory, CnShiftTemplateModelFactory, CnSiteShiftModelFactory ) {
      return {
        templateUrl: module.url + 'calendar.tpl.html',
        restrict: 'E',
        controller: function( $scope ) {
          $scope.model = CnShiftModelFactory.root;
          $scope.model.setupBreadcrumbTrail( 'calendar' );
        },
        link: function( scope ) {
          // synchronize appointment, shift, shift_template and site_shift calendars
          scope.$watch( 'model.calendarModel.currentDate', function( date ) {
            var appointmentCalendarModel = CnAppointmentModelFactory.root.calendarModel;
            if( !appointmentCalendarModel.currentDate.isSame( date, 'day' ) )
              appointmentCalendarModel.currentDate = date;
            var shiftTemplateCalendarModel = CnShiftTemplateModelFactory.root.calendarModel;
            if( !shiftTemplateCalendarModel.currentDate.isSame( date, 'day' ) )
              shiftTemplateCalendarModel.currentDate = date;
            var siteShiftCalendarModel = CnSiteShiftModelFactory.root.calendarModel;
            if( !siteShiftCalendarModel.currentDate.isSame( date, 'day' ) )
              siteShiftCalendarModel.currentDate = date;
          } );
          scope.$watch( 'model.calendarModel.currentView', function( view ) {
            var appointmentCalendarModel = CnAppointmentModelFactory.root.calendarModel;
            if( appointmentCalendarModel.currentView != view )
              appointmentCalendarModel.currentView = view;
            var shiftTemplateCalendarModel = CnShiftTemplateModelFactory.root.calendarModel;
            if( shiftTemplateCalendarModel.currentView != view )
              shiftTemplateCalendarModel.currentView = view;
            var siteShiftCalendarModel = CnSiteShiftModelFactory.root.calendarModel;
            if( siteShiftCalendarModel.currentView != view )
              siteShiftCalendarModel.currentView = view;
          } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnShiftList', [
    'CnShiftModelFactory',
    function( CnShiftModelFactory ) {
      return {
        templateUrl: module.url + 'list.tpl.html',
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
        templateUrl: module.url + 'view.tpl.html',
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
    function( CnBaseModelFactory,
              CnShiftAddFactory, CnShiftCalendarFactory,
              CnShiftListFactory, CnShiftViewFactory ) {
      var object = function( root ) {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.addModel = CnShiftAddFactory.instance( this );
        this.calendarModel = CnShiftCalendarFactory.instance( this );
        this.listModel = CnShiftListFactory.instance( this );
        this.viewModel = CnShiftViewFactory.instance( this, root );

        // We must override the getServiceCollectionPath function to ignore parent identifiers so that it
        // can be used by the site_shift module
        this.getServiceCollectionPath = function() {
          var path = this.$$getServiceCollectionPath();
          if( 'site_shift' == path.substring( 0, 10 ) ) path = 'shift';
          return path;
        };
      };

      return {
        root: new object( true ),
        instance: function() { return new object(); }
      };
    }
  ] );

} );
