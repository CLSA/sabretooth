define( [ 'appointment', 'availability', 'shift', 'shift_template' ].reduce( function( list, name ) {
  return list.concat( cenozoApp.module( name ).getRequiredFiles() );
}, [] ), function() {
  'use strict';

  try { var module = cenozoApp.module( 'capacity', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( module, {
    identifier: {},
    name: {
      singular: 'capacity',
      plural: 'capacities',
      possessive: 'capacity\'s',
      pluralPossessive: 'capacities\''
    }
  } );

  function getSlotsFromEvents( appointmentEvents, shiftEvents, shiftTemplateEvents, offset ) {
    var slots = [];

    // create an object grouping all events for each day
    var events = {};
    appointmentEvents.forEach( function( item ) {
      var date = item.start.format( 'YYYY-MM-DD' );
      if( angular.isUndefined( events[date] ) )
        events[date] = { appointments: [], shifts: [], templates: [] };
      events[date].appointments.push( item );
    } );
    shiftEvents.forEach( function( item ) {
      var date = item.start.format( 'YYYY-MM-DD' );
      if( angular.isUndefined( events[date] ) )
        events[date] = { appointments: [], shifts: [], templates: [] };
      events[date].shifts.push( item );
    } );
    shiftTemplateEvents.forEach( function( item ) {
      var date = item.start.format( 'YYYY-MM-DD' );
      if( angular.isUndefined( events[date] ) )
        events[date] = { appointments: [], shifts: [], templates: [] };
      events[date].templates.push( item );
    } );

    // now go through each day and determine the open slots
    for( var date in events ) {
      // determine where the number of slots changes
      var diffs = {};
      if( 0 < events[date].shifts.length ) {
        // process shifts
        events[date].shifts.forEach( function( shift ) {
          var time = shift.start.format( 'HH:mm' );
          if( angular.isUndefined( diffs[time] ) ) diffs[time] = 0;
          diffs[time]++;
          var time = shift.end.format( 'HH:mm' );
          if( angular.isUndefined( diffs[time] ) ) diffs[time] = 0;
          diffs[time]--;
        } );
      } else {
        // processshift templates if there are no shifts
        events[date].templates.forEach( function( shiftTemplate ) {
          var time = moment( shiftTemplate.start ).add( offset, 'minute' ).format( 'HH:mm' );
          if( angular.isUndefined( diffs[time] ) ) diffs[time] = 0;
          diffs[time] += parseInt( shiftTemplate.title );
          var time = moment( shiftTemplate.end ).add( offset, 'minute' ).format( 'HH:mm' );
          if( angular.isUndefined( diffs[time] ) ) diffs[time] = 0;
          diffs[time] -= parseInt( shiftTemplate.title );
        } );
      }

      // remove slots taken up by appointments
      events[date].appointments.forEach( function( appointment ) {
        var time = appointment.start.format( 'HH:mm' );
        if( angular.isUndefined( diffs[time] ) ) diffs[time] = 0;
        diffs[time]--;
        var time = appointment.end.format( 'HH:mm' );
        if( angular.isUndefined( diffs[time] ) ) diffs[time] = 0;
        diffs[time]++;
      } );

      // get an ordered list of all keys in the diffs array
      var times = [];
      for( var time in diffs ) if( diffs.hasOwnProperty( time ) ) times.push( time );
      times.sort();

      // now go through all diffs to determine the slots
      var lastTime = null;
      var lastNumber = 0;
      var number = 0;
      for( var i = 0; i < times.length; i++ ) {
        var time = times[i];
        number += diffs[time];
        if( 0 > number ) {
          number = 0; // appointments may be overloaded
        } else if( 0 < lastNumber ) {
          var colon = time.indexOf( ':' );
          var lastColon = lastTime.indexOf( ':' );
          var tempDate = moment( date );
          slots.push( {
            title: lastNumber + ' slot' + ( 1 < lastNumber ? 's' : '' ),
            start: moment().year( tempDate.year() )
                           .month( tempDate.month() )
                           .date( tempDate.date() )
                           .hour( lastTime.substring( 0, lastColon ) )
                           .minute( lastTime.substring( lastColon + 1 ) )
                           .second( 0 ),
            end: moment().year( tempDate.year() )
                         .month( tempDate.month() )
                         .date( tempDate.date() )
                         .hour( time.substring( 0, colon ) )
                         .minute( time.substring( colon + 1 ) )
                         .second( 0 )
          } );
        }
        lastTime = time;
        lastNumber = number;
      }
    }

    return slots;
  }

  // add an extra operation for each of the appointment-based calendars the user has access to
  [ 'appointment', 'availability', 'capacity', 'shift', 'shift_template' ].forEach( function( name ) {
    var calendarModule = cenozoApp.module( name );
    if( -1 < calendarModule.actions.indexOf( 'calendar' ) ) {
      module.addExtraOperation(
        'calendar',
        calendarModule.subject.snake.replace( "_", " " ).ucWords(),
        function( $state ) { $state.go( name + '.calendar' ); },
        'capacity' == name ? 'btn-warning' : undefined // highlight current model
      );
    }
  } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnCapacityCalendar', [
    'CnCapacityModelFactory',
    'CnAppointmentModelFactory', 'CnAvailabilityModelFactory',
    'CnShiftModelFactory', 'CnShiftTemplateModelFactory',
    function( CnCapacityModelFactory,
              CnAppointmentModelFactory, CnAvailabilityModelFactory,
              CnShiftModelFactory, CnShiftTemplateModelFactory ) {
      return {
        templateUrl: module.url + 'calendar.tpl.html',
        restrict: 'E',
        controller: function( $scope ) {
          $scope.model = CnCapacityModelFactory.root;
          $scope.model.setupBreadcrumbTrail( 'calendar' );
        },
        link: function( scope ) {
          // factory name -> object map used below
          var factoryList = {
            appointment: CnAppointmentModelFactory,
            availability: CnAvailabilityModelFactory,
            capacity: CnCapacityModelFactory,
            shift: CnShiftModelFactory,
            shift_template: CnShiftTemplateModelFactory
          };

          // synchronize appointment/shift-based calendars
          scope.$watch( 'model.calendarModel.currentDate', function( date ) {
            Object.keys( factoryList ).filter( function( name ) {
              return -1 < cenozoApp.moduleList[name].actions.indexOf( 'calendar' );
            } ).forEach( function( name ) {
               var calendarModel = factoryList[name].root.calendarModel;
               if( !calendarModel.currentDate.isSame( date, 'day' ) ) calendarModel.currentDate = date;
            } );
          } );
          scope.$watch( 'model.calendarModel.currentView', function( view ) {
            Object.keys( factoryList ).filter( function( name ) {
              return -1 < cenozoApp.moduleList[name].actions.indexOf( 'calendar' );
            } ).forEach( function( name ) {
               var calendarModel = factoryList[name].root.calendarModel;
               if( calendarModel.currentView != view ) calendarModel.currentView = view;
            } );
          } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnCapacityCalendarFactory', [
    'CnBaseCalendarFactory',
    'CnAppointmentModelFactory', 'CnShiftModelFactory', 'CnShiftTemplateModelFactory',
    'CnSession', '$q',
    function( CnBaseCalendarFactory,
              CnAppointmentModelFactory, CnShiftModelFactory, CnShiftTemplateModelFactory,
              CnSession, $q ) {
      var object = function( parentModel ) {
        var self = this;
        CnBaseCalendarFactory.construct( this, parentModel );

        // remove day and event click callbacks
        delete this.settings.dayClick;
        delete this.settings.eventClick;

        // extend onList to transform templates into events
        this.onList = function( replace, minDate, maxDate, ignoreParent ) {
          // unlike other calendars we don't cache events
          var appointmentCalendarModel = CnAppointmentModelFactory.root.calendarModel;
          var shiftCalendarModel = CnShiftModelFactory.root.calendarModel;
          var shiftTemplateCalendarModel = CnShiftTemplateModelFactory.root.calendarModel;
          if( true ) {
            // instead of calling $$onList we determine events from the events in other calendars
            return $q.all( [
              appointmentCalendarModel.onList( replace, minDate, maxDate, true ),
              shiftCalendarModel.onList( replace, minDate, maxDate, true ),
              shiftTemplateCalendarModel.onList( replace, minDate, maxDate, true )
            ] ).then( function() {
              self.cache = getSlotsFromEvents(
                // get all appointments inside the load date span
                appointmentCalendarModel.cache.filter( function( item ) {
                  return !item.start.isBefore( minDate, 'day' ) && !item.end.isAfter( maxDate, 'day' );
                } ),
                // get all shift events inside the load date span
                shiftCalendarModel.cache.filter( function( item ) {
                  return !item.start.isBefore( minDate, 'day' ) && !item.end.isAfter( maxDate, 'day' );
                } ),
                // get all shift template events inside the load date span
                shiftTemplateCalendarModel.cache.filter( function( item ) {
                  return !item.start.isBefore( minDate, 'day' ) && !item.end.isAfter( maxDate, 'day' );
                } ),
                // get the offset between the user's current timezone and the site's timezone
                moment().tz( CnSession.user.timezone ).utcOffset() -
                moment().tz( CnSession.site.timezone ).utcOffset()
              );
            } );
          }
        };
      };

      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnCapacityModelFactory', [
    'CnBaseModelFactory', 'CnCapacityCalendarFactory', 'CnSession',
    function( CnBaseModelFactory, CnCapacityCalendarFactory, CnSession ) {
      var object = function( root ) {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.calendarModel = CnCapacityCalendarFactory.instance( this );
      };

      return {
        root: new object( true ),
        instance: function() { return new object(); }
      };
    }
  ] );

} );
