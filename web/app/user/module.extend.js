// extend the framework's module
define( [ 'appointment', 'shift' ].reduce( function( list, name ) {
  return list.concat( cenozoApp.module( name ).getRequiredFiles() );
}, [ cenozoApp.module( 'user' ).getFileUrl( 'module.js' ) ] ), function() {
  'use strict';

  var module = cenozoApp.module( 'user' );

  if( angular.isDefined( module.actions.calendar ) ) {
    module.addExtraOperation( 'view', {
      title: 'Calendar',
      operation: function( $state, model ) {
        $state.go( 'user.calendar', { identifier: model.viewModel.record.getIdentifier() } );
      }
    } );
  }

  // converts appointments into events
  function getEventFromAppointment( appointment, timezone ) {
    var event = {};
    if( angular.isUndefined( appointment.subject ) ) appointment.subject = 'appointment';

    if( angular.isDefined( appointment.start ) && angular.isDefined( appointment.end ) ) {
      event = appointment;
    } else {
      var date = moment( appointment.datetime );
      var offset = moment.tz.zone( timezone ).offset( date.unix() );

      // adjust the appointment for daylight savings time
      if( date.tz( timezone ).isDST() ) offset += -60;

      event = {
        title: ( angular.isDefined( appointment.uid ) ? appointment.uid : 'new appointment' ) +
               ( angular.isDefined( appointment.qnaire_rank ) ? ' (' + appointment.qnaire_rank + ')' : '' ),
        getIdentifier: function() { return appointment.getIdentifier() },
        start: moment( appointment.datetime ).subtract( offset, 'minutes' ),
        end: moment( appointment.datetime ).subtract( offset - appointment.duration, 'minutes' )
      };
    }

    angular.extend( event, {
      title: event.title.replace( /\nfor .+$/, '' ),
      color: undefined
    } );

    return event;
  }

  // converts shifts into events
  function getEventFromShift( shift, timezone ) {
    var event = {};
    if( angular.isUndefined( shift.subject ) ) shift.subject = 'shift';
    if( angular.isDefined( shift.start ) && angular.isDefined( shift.end ) ) {
      event = shift;
    } else {
      var date = moment( shift.start_datetime );
      var offset = moment.tz.zone( timezone ).offset( date.unix() );

      // adjust the appointment for daylight savings time
      if( date.tz( timezone ).isDST() ) offset += -60;

      event = {
        getIdentifier: function() { return shift.getIdentifier() },
        start: moment( shift.start_datetime ).subtract( offset, 'minutes' ),
        end: moment( shift.end_datetime ).subtract( offset, 'minutes' )
      };
    }

    angular.extend( event, {
      title: 'shift',
      color: 'gray'
    } );

    return event;
  }

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnUserCalendar', [
    'CnUserModelFactory', 'CnUserCalendarFactory',
    function( CnUserModelFactory, CnUserCalendarFactory ) {
      return {
        templateUrl: cenozoApp.getFileUrl( 'user', 'calendar.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) {
            $scope.model = CnUserModelFactory.instance();
            $scope.model.calendarModel = CnUserCalendarFactory.instance( $scope.model );
          }

          if( angular.isDefined( $scope.model.calendarModel ) )
            $scope.model.calendarModel.heading = 'Personal Calendar';
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnUserCalendarFactory', [
    'CnBaseCalendarFactory', 'CnAppointmentModelFactory', 'CnShiftModelFactory', 'CnSession', '$state', '$q',
    function( CnBaseCalendarFactory, CnAppointmentModelFactory, CnShiftModelFactory, CnSession, $state, $q ) {
      var object = function( parentModel ) {
        var self = this;
        CnBaseCalendarFactory.construct( this, parentModel );

        // show to-from times in month view
        if( angular.isUndefined( this.settings.views ) ) this.settings.views = {};
        if( angular.isUndefined( this.settings.views.month ) ) this.settings.views.month = {};
        this.settings.views.month.displayEventEnd = true;

        // create instances of shift and appointment models
        var shiftModule = cenozoApp.module( 'shift' );
        var appointmentModule = cenozoApp.module( 'appointment' );
        var shiftModel = null;
        var appointmentModel = null;

        // Use the view model's onView function to get the record and create shift and appointment models from it.
        // Note that to do this we must make sure that viewing has been enabled in the parent-model, even if only
        // temporarily.
        var getViewEnabled = parentModel.getViewEnabled;
        parentModel.getViewEnabled = function() { return true; };
        var promise = parentModel.viewModel.onView().then( function() {
          shiftModel = CnShiftModelFactory.forUser( parentModel.viewModel.record );
          appointmentModel = CnAppointmentModelFactory.forUser( parentModel.viewModel.record );
        } );
        parentModel.getViewEnabled = getViewEnabled;

        // remove day click callback
        delete this.settings.dayClick;
        this.settings.eventClick = function( record ) {
          return promise.then( function() {
            if( angular.isUndefined( record.subject ) ) {
              console.warn( 'Clicked on personal calendar event which is neither an appointment or a shift.' );
            } else {
              if( ( 'appointment' == record.subject && angular.isDefined( appointmentModule.actions.view ) ) ||
                  ( 'shift' == record.subject && angular.isDefined( shiftModule.actions.view ) ) )
                return $state.go( record.subject + '.view', { identifier: record.getIdentifier() } );
            }
          } );
        };

        // extend onCalendar to transform templates into events
        this.onCalendar = function( replace, minDate, maxDate, ignoreParent ) {
          // we must get the load dates before calling $$onCalendar
          var loadMinDate = self.getLoadMinDate( replace, minDate );
          var loadMaxDate = self.getLoadMaxDate( replace, maxDate );

          return promise.then( function() {
            return $q.all( [
              shiftModel.calendarModel.onCalendar( replace, minDate, maxDate, ignoreParent ),
              appointmentModel.calendarModel.onCalendar( replace, minDate, maxDate, ignoreParent )
            ] ).then( function() {
              shiftModel.calendarModel.cache.forEach( function( item, index, array ) {
                array[index] = getEventFromShift( item, CnSession.user.timezone );
              } );
              appointmentModel.calendarModel.cache.forEach( function( item, index, array ) {
                array[index] = getEventFromAppointment( item, CnSession.user.timezone );
              } );

              // make the cache a combination of appointment and shift events
              self.cache = appointmentModel.calendarModel.cache.concat( shiftModel.calendarModel.cache );
            } );
          } );
        };
      };

      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

} );
