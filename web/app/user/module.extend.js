// extend the framework's module
define( [ 'appointment' ].reduce( function( list, name ) {
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
      var date = moment( appointment.start_datetime );
      var offset = moment.tz.zone( timezone ).offset( date.unix() );

      // adjust the appointment for daylight savings time
      if( date.tz( timezone ).isDST() ) offset += -60;

      event = {
        title: ( angular.isDefined( appointment.uid ) ? appointment.uid : 'new appointment' ) +
               ( angular.isDefined( appointment.qnaire_rank ) ? ' (' + appointment.qnaire_rank + ')' : '' ),
        getIdentifier: function() { return appointment.getIdentifier() },
        start: moment( appointment.start_datetime ).subtract( offset, 'minutes' ),
        end: moment( appointment.end_datetime ).subtract( offset, 'minutes' )
      };
    }

    angular.extend( event, {
      title: event.title.replace( /\nfor .+$/, '' ),
      color: undefined
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
    'CnBaseCalendarFactory', 'CnAppointmentModelFactory', 'CnSession', '$state', '$q',
    function( CnBaseCalendarFactory, CnAppointmentModelFactory, CnSession, $state, $q ) {
      var object = function( parentModel ) {
        var self = this;
        CnBaseCalendarFactory.construct( this, parentModel );

        // show to-from times in month view
        if( angular.isUndefined( this.settings.views ) ) this.settings.views = {};
        if( angular.isUndefined( this.settings.views.month ) ) this.settings.views.month = {};
        this.settings.views.month.displayEventEnd = true;

        // create appointment model
        var appointmentModule = cenozoApp.module( 'appointment' );
        var appointmentModel = null;

        // Use the view model's onView function to get the record and create an appointment model from it.
        // Note that to do this we must make sure that viewing has been enabled in the parent-model, even if only
        // temporarily.
        var getViewEnabled = parentModel.getViewEnabled;
        parentModel.getViewEnabled = function() { return true; };
        var promise = parentModel.viewModel.onView().then( function() {
          appointmentModel = CnAppointmentModelFactory.forUser( parentModel.viewModel.record );
        } );
        parentModel.getViewEnabled = getViewEnabled;

        // remove day click callback
        delete this.settings.dayClick;
        this.settings.eventClick = function( record ) {
          // close the popover (this does nothing if there is no popover)
          angular.element( this ).popover( 'hide' );
          return promise.then( function() {
            if( angular.isUndefined( record.subject ) ) {
              console.warn( 'Clicked on personal calendar event which is not an appointment.' );
            } else if( appointmentModel.getViewEnabled() ) {
              if( 'appointment' == record.subject && angular.isDefined( appointmentModule.actions.view ) )
                return $state.go( record.subject + '.view', { identifier: record.getIdentifier() } );
            }
          } );
        };

        // extend onCalendar to transform into events
        this.onCalendar = function( replace, minDate, maxDate, ignoreParent ) {
          // we must get the load dates before calling $$onCalendar
          var loadMinDate = self.getLoadMinDate( replace, minDate );
          var loadMaxDate = self.getLoadMaxDate( replace, maxDate );

          return promise.then( function() {
            return appointmentModel.calendarModel.onCalendar(
              replace, minDate, maxDate, ignoreParent
            ).then( function() {
              appointmentModel.calendarModel.cache.forEach( function( item, index, array ) {
                array[index] = getEventFromAppointment( item, CnSession.user.timezone );
              } );
            } );
          } );
        };
      };

      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

} );
