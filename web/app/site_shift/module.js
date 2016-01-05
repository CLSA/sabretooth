define( [].concat(
          cenozoApp.module( 'appointment' ).getRequiredFiles(),
          cenozoApp.module( 'shift' ).getRequiredFiles(),
          cenozoApp.module( 'shift_template' ).getRequiredFiles()
        ), function() {
  'use strict';

  try { var module = cenozoApp.module( 'site_shift', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( module, {
    identifier: {},
    name: {
      singular: 'site shift',
      plural: 'site shifts',
      possessive: 'site shift\'s',
      pluralPossessive: 'site shifts\''
    }
  } );

  function getSlotsFromEvents( appointmentEvents, shiftEvents, shiftTemplateEvents, offset ) {
    var slots = [];

    // create an object grouping all events for each day
    var events = {};
    appointmentEvents.forEach( function( item ) {
      var date = item.start.date();
      if( angular.isUndefined( events[date] ) )
        events[date] = { appointments: [], shifts: [], templates: [] };
      events[date].appointments.push( item );
    } );
    shiftEvents.forEach( function( item ) {
      var date = item.start.date();
      if( angular.isUndefined( events[date] ) )
        events[date] = { appointments: [], shifts: [], templates: [] };
      events[date].shifts.push( item );
    } );
    shiftTemplateEvents.forEach( function( item ) {
      var date = item.start.date();
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
          slots.push( {
            title: lastNumber + ' slot' + ( 1 < lastNumber ? 's' : '' ),
            start: moment().date( date )
                           .hour( lastTime.substring( 0, lastColon ) )
                           .minute( lastTime.substring( lastColon + 1 ) )
                           .second( 0 ),
            end: moment().date( date )
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

  module.addExtraOperation(
    'calendar',
    'Appointment',
    function( calendarModel, $state ) { $state.go( 'appointment.calendar' ); }
  );

  module.addExtraOperation(
    'calendar',
    'Shift',
    function( calendarModel, $state ) { $state.go( 'shift.calendar' ); }
  );

  module.addExtraOperation(
    'calendar',
    'Shift Template',
    function( calendarModel, $state ) { $state.go( 'shift_template.calendar' ); }
  );

  module.addExtraOperation(
    'calendar',
    'Availability',
    function( calendarModel, $state ) { $state.go( 'site_shift.calendar' ); },
    true // disabled
  );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnSiteShiftCalendar', [
    'CnSiteShiftModelFactory',
    'CnAppointmentModelFactory', 'CnShiftModelFactory', 'CnShiftTemplateModelFactory',
    'CnSession',
    function( CnSiteShiftModelFactory,
              CnAppointmentModelFactory, CnShiftModelFactory, CnShiftTemplateModelFactory,
              CnSession ) {
      return {
        templateUrl: module.url + 'calendar.tpl.html',
        restrict: 'E',
        controller: function( $scope ) {
          $scope.model = CnSiteShiftModelFactory.root;
          CnSession.promise.then( function() { $scope.timezone = CnSession.site.timezone; } );
          $scope.model.setupBreadcrumbTrail( 'calendar' );
        },
        link: function( scope ) {
          // synchronize appointment, shift, shift_template and site_shift calendars
          scope.$watch( 'model.calendarModel.currentDate', function( date ) {
            var appointmentCalendarModel = CnAppointmentModelFactory.root.calendarModel;
            if( !appointmentCalendarModel.currentDate.isSame( date, 'day' ) )
              appointmentCalendarModel.currentDate = date;
            var shiftCalendarModel = CnShiftModelFactory.root.calendarModel;
            if( !shiftCalendarModel.currentDate.isSame( date, 'day' ) )
              shiftCalendarModel.currentDate = date;
            var shiftTemplateCalendarModel = CnShiftTemplateModelFactory.root.calendarModel;
            if( !shiftTemplateCalendarModel.currentDate.isSame( date, 'day' ) )
              shiftTemplateCalendarModel.currentDate = date;
          } );
          scope.$watch( 'model.calendarModel.currentView', function( view ) {
            var appointmentCalendarModel = CnAppointmentModelFactory.root.calendarModel;
            if( appointmentCalendarModel.currentView != view )
              appointmentCalendarModel.currentView = view;
            var shiftCalendarModel = CnShiftModelFactory.root.calendarModel;
            if( shiftCalendarModel.currentView != view )
              shiftCalendarModel.currentView = view;
            var shiftTemplateCalendarModel = CnShiftTemplateModelFactory.root.calendarModel;
            if( shiftTemplateCalendarModel.currentView != view )
              shiftTemplateCalendarModel.currentView = view;
          } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnSiteShiftCalendarFactory', [
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
        this.onList = function( replace, minDate, maxDate ) {
          // we must get the load dates before getting shifts and shift templates
          var query = false;
          var loadMinDate = self.getLoadMinDate( replace, minDate );
          var loadMaxDate = self.getLoadMaxDate( replace, maxDate );
          if( replace || null == this.cacheMinDate || null == this.cacheMaxDate ||
              6 < Math.abs( this.cacheMinDate.diff( minDate, 'months' ) ) ) {
            // rebuild the cache for the requested date span
            this.cache = [];
            this.cacheMinDate = null == loadMinDate ? null : moment( loadMinDate );
            this.cacheMaxDate = null == loadMaxDate ? null : moment( loadMaxDate );
            query = null != minDate && null != maxDate;
          } else if( null != minDate && null != maxDate ) {
            // if the min date comes after the cache's min date then load from the new min date
            if( this.cacheMinDate.isAfter( minDate ) ) {
              this.cacheMinDate = moment( minDate );
              query = true;
            }

            // if the max date comes before the cache's max date then load to the new max date
            if( this.cacheMaxDate.isBefore( maxDate ) ) {
              this.cacheMaxDate = moment( maxDate );
              query = true;
            }
          }

          var appointmentCalendarModel = CnAppointmentModelFactory.root.calendarModel;
          var shiftCalendarModel = CnShiftModelFactory.root.calendarModel;
          var shiftTemplateCalendarModel = CnShiftTemplateModelFactory.root.calendarModel;
          if( query ) {
            // instead of calling $$onList we're going to get all shifts and shift templates instead
            return $q.all( [
              appointmentCalendarModel.onList( replace, minDate, maxDate ),
              shiftCalendarModel.onList( replace, minDate, maxDate ),
              shiftTemplateCalendarModel.onList( replace, minDate, maxDate )
            ] ).then( function() {
              self.cache = self.cache.concat(
                getSlotsFromEvents(
                  // get all appointments inside the load date span
                  appointmentCalendarModel.cache.filter( function( item ) {
                    return !item.start.isBefore( loadMinDate ) && !item.end.isAfter( loadMaxDate );
                  } ),
                  // get all shift events inside the load date span
                  shiftCalendarModel.cache.filter( function( item ) {
                    return !item.start.isBefore( loadMinDate ) && !item.end.isAfter( loadMaxDate );
                  } ),
                  // get all shift template events inside the load date span
                  shiftTemplateCalendarModel.cache.filter( function( item ) {
                    return !item.start.isBefore( loadMinDate ) && !item.end.isAfter( loadMaxDate );
                  } ),
                  // get the offset between the user's current timezone and the site's timezone
                  moment().tz( CnSession.user.timezone ).utcOffset() -
                  moment().tz( CnSession.site.timezone ).utcOffset()
                )
              );
            } );
          } else if( replace ) {
            // we're replacing with no min/max date (no query) so flush the shift and shift templates as well
            return $q.all( [
              shiftCalendarModel.onList( true, null, null ),
              shiftTemplateCalendarModel.onList( true, null, null )
            ] );
          } else {
            return $q.all();
          }
        };
      };

      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnSiteShiftModelFactory', [
    'CnBaseModelFactory', 'CnSiteShiftCalendarFactory', 'CnSession',
    function( CnBaseModelFactory, CnSiteShiftCalendarFactory, CnSession ) {
      var object = function( root ) {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.calendarModel = CnSiteShiftCalendarFactory.instance( this );
      };

      return {
        root: new object( true ),
        instance: function() { return new object(); }
      };
    }
  ] );

} );
