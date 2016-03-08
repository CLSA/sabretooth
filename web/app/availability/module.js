define( [ 'appointment', 'capacity', 'shift', 'shift_template', 'site' ].reduce( function( list, name ) {
  return list.concat( cenozoApp.module( name ).getRequiredFiles() );
}, [] ), function() {
  'use strict';

  try { var module = cenozoApp.module( 'availability', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( module, {
    identifier: {},
    name: {
      singular: 'availability',
      plural: 'availabilities',
      possessive: 'availability\'s',
      pluralPossessive: 'availabilities\''
    }
  } );

  function getAvailabilityFromEvents( appointmentEvents, shiftEvents, shiftTemplateEvents ) {
    var availability = [];

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

    // now go through each day and determine the availability
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
        // process shift templates if there are no shifts
        events[date].templates.forEach( function( shiftTemplate ) {
          var time = moment( shiftTemplate.start ).format( 'HH:mm' );
          if( angular.isUndefined( diffs[time] ) ) diffs[time] = 0;
          diffs[time] += parseInt( shiftTemplate.title );
          var time = moment( shiftTemplate.end ).format( 'HH:mm' );
          if( angular.isUndefined( diffs[time] ) ) diffs[time] = 0;
          diffs[time] -= parseInt( shiftTemplate.title );
        } );
      }

      // remove slots taken up by non-overridden appointments
      events[date].appointments.filter( function( appointment ) {
        return !appointment.override;
      } ).forEach( function( appointment ) {
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
        
        if( 0 < lastNumber ) {
          if( 0 == number && null != lastTime ) {
            var colon = time.indexOf( ':' );
            var lastColon = lastTime.indexOf( ':' );
            var tempDate = moment( date );
            availability.push( {
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
            lastTime = null;
          }
        }
        lastNumber = number;
        if( null == lastTime && 0 < number ) lastTime = time;
      }
    }

    return availability;
  }

  // add an extra operation for each of the appointment-based calendars the user has access to
  [ 'appointment', 'availability', 'capacity', 'shift', 'shift_template' ].forEach( function( name ) {
    var calendarModule = cenozoApp.module( name );
    if( -1 < calendarModule.actions.indexOf( 'calendar' ) ) {
      module.addExtraOperation( 'calendar', {
        title: calendarModule.subject.snake.replace( "_", " " ).ucWords(),
        operation: function( $state, model ) {
          $state.go( name + '.calendar', { identifier: model.site.getIdentifier() } );
        },
        classes: 'availability' == name ? 'btn-warning' : undefined // highlight current model
      } );
    }
  } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAvailabilityCalendar', [
    'CnAvailabilityModelFactory',
    'CnAppointmentModelFactory', 'CnCapacityModelFactory',
    'CnShiftModelFactory', 'CnShiftTemplateModelFactory',
    function( CnAvailabilityModelFactory,
              CnAppointmentModelFactory, CnCapacityModelFactory,
              CnShiftModelFactory, CnShiftTemplateModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'calendar.tpl.html' ),
        restrict: 'E',
        scope: {
          model: '=?',
          preventSiteChange: '@'
        },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAvailabilityModelFactory.instance();
          $scope.heading = $scope.model.site.name.ucWords() + ' Availability Calendar';
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
               var calendarModel = factoryList[name].forSite( scope.model.site ).calendarModel;
               if( !calendarModel.currentDate.isSame( date, 'day' ) ) calendarModel.currentDate = date;
            } );
          } );
          scope.$watch( 'model.calendarModel.currentView', function( view ) {
            Object.keys( factoryList ).filter( function( name ) {
              return -1 < cenozoApp.moduleList[name].actions.indexOf( 'calendar' );
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
  cenozo.providers.factory( 'CnAvailabilityCalendarFactory', [
    'CnBaseCalendarFactory',
    'CnAppointmentModelFactory', 'CnShiftModelFactory', 'CnShiftTemplateModelFactory',
    'CnSession', '$q',
    function( CnBaseCalendarFactory,
              CnAppointmentModelFactory, CnShiftModelFactory, CnShiftTemplateModelFactory,
              CnSession, $q ) {
      var object = function( parentModel, site ) {
        var self = this;
        CnBaseCalendarFactory.construct( this, parentModel );

        // remove day and event click callbacks
        delete this.settings.dayClick;
        delete this.settings.eventClick;

        // extend onCalendar to transform templates into events
        this.onCalendar = function( replace, minDate, maxDate, ignoreParent ) {
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
            self.cache = getAvailabilityFromEvents(
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
  cenozo.providers.factory( 'CnAvailabilityModelFactory', [
    'CnBaseModelFactory', 'CnAvailabilityCalendarFactory', 'CnSession', '$state',
    function( CnBaseModelFactory, CnAvailabilityCalendarFactory, CnSession, $state ) {
      var object = function( site ) {
        if( !angular.isObject( site ) || angular.isUndefined( site.id ) )
          throw new Error( 'Tried to create CnAvailabilityModel without specifying the site.' );

        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.calendarModel = CnAvailabilityCalendarFactory.instance( this, site );
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
          var parts = $state.params.identifier.split( '=' );
          return this.forSite(
              1 == parts.length && parseInt( parts[0] ) == parts[0] // int identifier
            ? CnSession.siteList.findByProperty( 'id', parseInt( parts[0] ) )
            : 2 == parts.length // key=val identifier
            ? CnSession.siteList.findByProperty( parts[0], parts[1] )
            : null
          );
        }
      };
    }
  ] );

} );
