define( [ 'appointment', 'shift', 'shift_template', 'site' ].reduce( function( list, name ) {
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

  function getSlotsFromEvents( appointmentEvents, shiftEvents, shiftTemplateEvents ) {
    var slots = [];

    // function that sorts events by their start time
    var sortByStart = function( a, b ) {
      return a.start.isBefore( b.start ) ? -1
           : a.start.isAfter( b.start ) ? 1
           : 0;
    };

    // create an object grouping all events for each day
    var events = {};
    appointmentEvents.sort( sortByStart ).forEach( function( item ) {
      var date = item.start.format( 'YYYY-MM-DD' );
      if( angular.isUndefined( events[date] ) ) events[date] = { appointments: [], shifts: [], templates: [] };
      events[date].appointments.push( item );
    } );
    shiftEvents.sort( sortByStart ).forEach( function( item ) {
      var date = item.start.format( 'YYYY-MM-DD' );
      if( angular.isUndefined( events[date] ) ) events[date] = { appointments: [], shifts: [], templates: [] };
      events[date].shifts.push( item );
    } );
    shiftTemplateEvents.sort( sortByStart ).forEach( function( item ) {
      var date = item.start.format( 'YYYY-MM-DD' );
      if( angular.isUndefined( events[date] ) ) events[date] = { appointments: [], shifts: [], templates: [] };
      events[date].templates.push( item );
    } );

    // now go through each day and determine the open slots
    for( var date in events ) {
      var tempDate = moment( date );
      var eventList = [];

      // get all shifts for today (or shift templates if there are no shifts
      var lastEvent = null;
      if( 0 < events[date].shifts.length ) {
        events[date].shifts.forEach( function( shift ) {
          var event = {
            start: shift.start.format( 'HH:mm' ),
            end: shift.end.format( 'HH:mm' ),
            slots: 1
          };
          if( null != lastEvent && lastEvent.start == event.start && lastEvent.end == event.end ) {
            lastEvent.slots++;
          } else {
            eventList.push( event );
            lastEvent = event;
          }
        } );
      } else {
        // process shift templates if there are no shifts
        events[date].templates.forEach( function( shiftTemplate ) {
          var event = {
            start: shiftTemplate.start.format( 'HH:mm' ),
            end: shiftTemplate.end.format( 'HH:mm' ),
            slots: parseInt( shiftTemplate.title )
          };
          if( null != lastEvent && lastEvent.start == event.start && lastEvent.end == event.end ) {
            lastEvent.slots++;
          } else {
            eventList.push( event );
            lastEvent = event;
          }
        } );
      }

      // convert start/end times to moment objects
      eventList.forEach( function( event ) {
        var startColon = event.start.indexOf( ':' );
        var endColon = event.end.indexOf( ':' );
        event.start = moment()
          .year( tempDate.year() )
          .month( tempDate.month() )
          .date( tempDate.date() )
          .hour( event.start.substring( 0, startColon ) )
          .minute( event.start.substring( startColon + 1 ) )
          .second( 0 );
        event.end = moment()
          .year( tempDate.year() )
          .month( tempDate.month() )
          .date( tempDate.date() )
          .hour( event.end.substring( 0, endColon ) )
          .minute( event.end.substring( endColon + 1 ) )
          .second( 0 );
      } );

      // remove slots taken up by non-overridden appointments
      events[date].appointments.filter( function( appointment ) {
        return !appointment.override;
      } ).forEach( function( appointment ) {
        // find the shortest slot that fits the appointment
        var workingIndex = null;
        var workingEvent = null;
        var workingLength = 0;
        eventList.forEach( function( event, index ) {
          if( event.start.isSameOrBefore( appointment.start, 'minute' ) &&
              event.end.isSameOrAfter( appointment.end, 'minute' ) ) {
            var length = event.end.diff( event.start, 'minutes' );
            if( length > workingLength ) {
              workingIndex = index;
              workingEvent = event;
              workingLength = workingLength;
            }
          }
        } );

        if( null != workingEvent ) {
          // found an event to remove the appointment from
          if( workingEvent.slots > 1 ) {
            // remove one of the slots and make that the working event
            workingEvent.slots--;
            workingEvent = angular.copy( workingEvent );
            workingEvent.slots = 1;
          } else {
            // remove the whole event
            eventList.splice( workingIndex, 1 );
          }

          // splice the working event based on the appointment's time span
          if( workingEvent.start.isBefore( appointment.start, 'minute' ) ) {
            // create a new event that comes before the appointment
            var beforeEvent = {
              start: angular.copy( workingEvent.start ),
              end: angular.copy( appointment.start ),
              slots: 1
            };
            // see if this already exists in the event list
            if( !eventList.some( function( checkEvent ) {
              if( checkEvent.start.isSame( beforeEvent.start, 'minute' ) &&
                  checkEvent.end.isSame( beforeEvent.end, 'minute' ) ) {
                checkEvent.slots++;
                return true;
              }
            } ) ) eventList.push( beforeEvent );
          }
          if( workingEvent.end.isAfter( appointment.end, 'minute' ) ) {
            // create a new event that comes after the appointment
            var afterEvent = {
              start: angular.copy( appointment.end ),
              end: angular.copy( workingEvent.end ),
              slots: 1
            };
            // see if this already exists in the event list
            if( !eventList.some( function( checkEvent ) {
              if( checkEvent.start.isSame( afterEvent.start, 'minute' ) &&
                  checkEvent.end.isSame( afterEvent.end, 'minute' ) ) {
                checkEvent.slots++;
                return true;
              }
            } ) ) eventList.push( afterEvent );
          }
        }
      } );

      // now create the slots
      eventList.sort( sortByStart ).forEach( function( event ) {
        slots.push( {
          title: event.slots + ' opeartor' + ( 1 < event.slots ? 's' : '' ),
          start: event.start,
          end: event.end
        } );
      } );
    }

    return slots;
  }

  // add an extra operation for each of the appointment-based calendars the user has access to
  [ 'appointment', 'capacity', 'shift', 'shift_template' ].forEach( function( name ) {
    var calendarModule = cenozoApp.module( name );
    if( angular.isDefined( calendarModule.actions.calendar ) ) {
      module.addExtraOperation( 'calendar', {
        title: calendarModule.subject.snake.replace( "_", " " ).ucWords(),
        operation: function( $state, model ) {
          $state.go( name + '.calendar', { identifier: model.site.getIdentifier() } );
        },
        classes: 'capacity' == name ? 'btn-warning' : undefined // highlight current model
      } );
    }
  } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnCapacityCalendar', [
    'CnCapacityModelFactory', 'CnAppointmentModelFactory',
    'CnShiftModelFactory', 'CnShiftTemplateModelFactory',
    function( CnCapacityModelFactory, CnAppointmentModelFactory,
              CnShiftModelFactory, CnShiftTemplateModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'calendar.tpl.html' ),
        restrict: 'E',
        scope: {
          model: '=?',
          preventSiteChange: '@'
        },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnCapacityModelFactory.instance();
          $scope.model.calendarModel.heading = $scope.model.site.name.ucWords() + ' Capacity Calendar';
        },
        link: function( scope ) {
          // factory name -> object map used below
          var factoryList = {
            appointment: CnAppointmentModelFactory,
            capacity: CnCapacityModelFactory,
            shift: CnShiftModelFactory,
            shift_template: CnShiftTemplateModelFactory
          };

          // synchronize appointment/shift-based calendars
          scope.$watch( 'model.calendarModel.currentDate', function( date ) {
            Object.keys( factoryList ).filter( function( name ) {
              return angular.isDefined( cenozoApp.moduleList[name].actions.calendar );
            } ).forEach( function( name ) {
               var calendarModel = factoryList[name].forSite( scope.model.site ).calendarModel;
               if( !calendarModel.currentDate.isSame( date, 'day' ) ) calendarModel.currentDate = date;
            } );
          } );
          scope.$watch( 'model.calendarModel.currentView', function( view ) {
            Object.keys( factoryList ).filter( function( name ) {
              return angular.isDefined( cenozoApp.moduleList[name].actions.calendar );
            } ).forEach( function( name ) {
               var calendarModel = factoryList[name].forSite( scope.model.site ).calendarModel;
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
    'CnAppointmentModelFactory', 'CnShiftModelFactory', 'CnShiftTemplateModelFactory', '$q',
    function( CnBaseCalendarFactory,
              CnAppointmentModelFactory, CnShiftModelFactory, CnShiftTemplateModelFactory, $q ) {
      var object = function( parentModel, site ) {
        var self = this;
        CnBaseCalendarFactory.construct( this, parentModel );

        // remove day and event click callbacks
        delete this.settings.dayClick;
        delete this.settings.eventClick;

        // show to-from times in month view
        if( angular.isUndefined( this.settings.views ) )
          this.settings.views = {};
        if( angular.isUndefined( this.settings.views.month ) )
          this.settings.views.month = {};
        this.settings.views.month.displayEventEnd = true;

        // extend onCalendar to transform templates into events
        this.onCalendar = function( replace, minDate, maxDate, ignoreParent ) {
          // always replace, otherwise the calendar won't update when new appointments/shifts/etc are made
          replace = true;

          // unlike other calendars we don't cache events
          var appointmentCalendarModel = CnAppointmentModelFactory.forSite( parentModel.site ).calendarModel;
          var shiftCalendarModel = CnShiftModelFactory.forSite( parentModel.site ).calendarModel;
          var shiftTemplateCalendarModel = CnShiftTemplateModelFactory.forSite( parentModel.site ).calendarModel;

          // instead of calling $$onCalendar we determine events from the events in other calendars
          return $q.all( [
            appointmentCalendarModel.onCalendar( replace, minDate, maxDate, true ),
            shiftCalendarModel.onCalendar( replace, minDate, maxDate, true ),
            shiftTemplateCalendarModel.onCalendar( replace, minDate, maxDate, true )
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
              } )
            );
          } );
        };
      };

      return { instance: function( parentModel, site ) { return new object( parentModel, site ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnCapacityModelFactory', [
    'CnBaseModelFactory', 'CnCapacityCalendarFactory', 'CnSession', '$state',
    function( CnBaseModelFactory, CnCapacityCalendarFactory, CnSession, $state ) {
      var object = function( site ) {
        if( !angular.isObject( site ) || angular.isUndefined( site.id ) )
          throw new Error( 'Tried to create CnCapacityModel without specifying the site.' );

        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.calendarModel = CnCapacityCalendarFactory.instance( this, site );
        this.site = site;
      };

      // get the siteColumn to be used by a site's identifier
      var siteModule = cenozoApp.module( 'site' );
      var siteColumn = angular.isDefined( siteModule.identifier.column ) ? siteModule.identifier.column : 'id';

      return {
        siteInstanceList: {},
        forSite: function( site ) {
          if( !angular.isObject( site ) ) {
            $state.go( 'error.404' );
            throw new Error( 'Cannot find site matching identifier "' + site + '", redirecting to 404.' );
          }
          if( angular.isUndefined( this.siteInstanceList[site.id] ) ) {
            if( angular.isUndefined( site.getIdentifier ) )
              site.getIdentifier = function() { return siteColumn + '=' + this[siteColumn]; };
            this.siteInstanceList[site.id] = new object( site );
          }
          return this.siteInstanceList[site.id];
        },
        instance: function() {
          var site = null;
          if( 'calendar' == $state.current.name.split( '.' )[1] ) {
            if( angular.isDefined( $state.params.identifier ) ) {
              var identifier = $state.params.identifier.split( '=' );
              if( 2 == identifier.length )
                site = CnSession.siteList.findByProperty( identifier[0], identifier[1] );
            }
          } else {
            site = CnSession.site;
          }
          return this.forSite( site );
        }
      };
    }
  ] );

} );
