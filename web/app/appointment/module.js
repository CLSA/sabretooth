define( [ 'availability', 'capacity', 'shift', 'shift_template', 'site' ].reduce( function( list, name ) {
  return list.concat( cenozoApp.module( name ).getRequiredFiles() );
}, [] ), function() {
  'use strict';

  try { var module = cenozoApp.module( 'appointment', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( module, {
    identifier: {
      parent: {
        subject: 'interview',
        column: 'interview_id',
        friendly: 'qnaire'
      }
    },
    name: {
      singular: 'appointment',
      plural: 'appointments',
      possessive: 'appointment\'s',
      pluralPossessive: 'appointments\''
    },
    columnList: {
      datetime: {
        type: 'datetime',
        title: 'Date & Time'
      },
      phone: {
        column: 'phone.name',
        type: 'string',
        title: 'Number'
      },
      user: {
        column: 'user.name',
        type: 'string',
        title: 'Reserved For'
      },
      assignment_user: {
        column: 'assignment_user.name',
        type: 'string',
        title: 'Assigned to'
      },
      type: {
        type: 'string',
        title: 'Type'
      },
      state: {
        type: 'string',
        title: 'State',
        help: 'One of reached, not reached, upcoming, assignable, missed, incomplete, assigned or in progress'
      }
    },
    defaultOrder: {
      column: 'datetime',
      reverse: true
    }
  } );

  module.addInputGroup( null, {
    datetime: {
      title: 'Date & Time',
      type: 'datetime',
      min: 'now',
      help: 'Cannot be changed once the appointment has passed.'
    },
    override: {
      title: 'Override Calendar',
      type: 'boolean',
      help: 'Whether to ignore if an operator is available for the appointment'
    },
    participant: {
      column: 'participant.uid',
      title: 'Participant',
      type: 'string',
      exclude: 'add',
      constant: true
    },
    qnaire: {
      column: 'script.name',
      title: 'Questionnaire',
      type: 'string',
      exclude: 'add',
      constant: true
    },
    phone_id: {
      title: 'Phone Number',
      type: 'enum',
      help: 'Which number should be called for the appointment, or leave this field blank if any of the ' +
            'participant\'s phone numbers can be called.'
    },
    user_id: {
      column: 'appointment.user_id',
      title: 'Reserved for',
      type: 'lookup-typeahead',
      typeahead: {
        table: 'user',
        select: 'CONCAT( first_name, " ", last_name, " (", name, ")" )',
        where: [ 'first_name', 'last_name', 'name' ]
      },
      help: 'The user the appointment is specifically reserved for. ' +
            'Cannot be changed once the appointment has passed.'
    },
    assignment_user: {
      column: 'assignment_user.name',
      title: 'Assigned to',
      type: 'string',
      exclude: 'add',
      constant: true,
      help: 'This will remain blank until the appointment has been assigned. The assigned user can only be ' +
            ' different from the reserved user when the appointment was missed.'
    },
    state: {
      title: 'State',
      type: 'string',
      exclude: 'add',
      constant: true,
      help: 'One of reached, not reached, upcoming, assignable, missed, incomplete, assigned or in progress'
    },
    type: {
      title: 'Type',
      type: 'enum'
    }
  } );

  // add an extra operation for each of the appointment-based calendars the user has access to
  [ 'appointment', 'availability', 'capacity', 'shift', 'shift_template' ].forEach( function( name ) {
    var calendarModule = cenozoApp.module( name );
    if( -1 < calendarModule.actions.indexOf( 'calendar' ) ) {
      module.addExtraOperation( 'calendar', {
        title: calendarModule.subject.snake.replace( '_', ' ' ).ucWords(),
        operation: function( $state, model ) {
          $state.go( name + '.calendar', { identifier: model.site.getIdentifier() } );
        },
        classes: 'appointment' == name ? 'btn-warning' : undefined // highlight current model
      } );
    }
  } );

  module.addExtraOperation( 'view', {
    title: 'Appointment Calendar',
    operation: function( $state, model ) {
      $state.go( 'appointment.calendar', { identifier: model.metadata.participantSite.getIdentifier() } );
    }
  } );

  // converts appointments into events
  function getEventFromAppointment( appointment, timezone, duration ) {
    if( angular.isDefined( appointment.start ) && angular.isDefined( appointment.end ) ) {
      return appointment;
    } else {
      var date = moment( appointment.datetime );
      var offset = moment.tz.zone( timezone ).offset( date.unix() ) - ( date.tz( timezone ).isDST() ? 60 : 0 );

      var event = {
        getIdentifier: function() { return appointment.getIdentifier() },
        title: ( angular.isDefined( appointment.uid ) ? appointment.uid : 'new appointment' ) +
               ( angular.isDefined( appointment.qnaire_rank ) ? ' (' + appointment.qnaire_rank + ')' : '' ),
        start: moment( appointment.datetime ).subtract( offset, 'minutes' ),
        end: moment( appointment.datetime ).subtract( offset, 'minutes' ).add( duration, 'minute' )
      };
      if( appointment.override ) {
        event.override = true;
        event.color = 'green';
      }
      return event;
    }
  }

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentAdd', [
    'CnAppointmentModelFactory', 'CnAvailabilityModelFactory', 'CnSession',
    function( CnAppointmentModelFactory, CnAvailabilityModelFactory, CnSession ) {
      return {
        templateUrl: module.getFileUrl( 'add.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentModelFactory.instance();
          $scope.model.addModel.afterNew( function() {
            if( -1 < cenozoApp.module( 'availability' ).actions.indexOf( 'calendar' ) &&
                angular.isObject( $scope.model.metadata.participantSite ) ) {
              $scope.model.metadata.getPromise().then( function() {
                // get the availability model linked to the participant's site
                $scope.availabilityModel =
                  CnAvailabilityModelFactory.forSite( $scope.model.metadata.participantSite );

                // connect the availability calendar's event click callback to the appointments datetime
                $scope.availabilityModel.calendarModel.settings.eventClick = function( availability ) {
                  var offset = moment.tz.zone( CnSession.user.timezone ).offset( availability.start.unix() )
                             - ( moment( availability.start ).tz( CnSession.user.timezone ).isDST() ? 60 : 0 );
                  var availabilityStart = moment( availability.start ).add( offset, 'minutes' );
                  var availabilityEnd = moment( availability.end ).add( offset, 'minutes' );
                  if( availabilityEnd.isAfter( moment() ) ) {
                    // find the add directive's scope
                    var cnRecordAddScope = cenozo.findChildDirectiveScope( $scope, 'cnRecordAdd' );
                    if( null == cnRecordAddScope )
                      throw new Exception( 'Unable to find appointment\'s cnRecordAdd scope.' );

                    // if the start is after the current time then use the next rounded hour
                    var datetime = moment( availabilityStart );
                    if( !datetime.isAfter( moment() ) ) {
                      datetime = moment().minute( 0 ).second( 0 ).millisecond( 0 ).add( 1, 'hours' );
                      if( !datetime.isAfter( moment() ) )
                        datetime = moment( availabilityEnd.format() );
                    }

                    // set the datetime in the record and formatted record
                    cnRecordAddScope.record.datetime = datetime.format();
                    cnRecordAddScope.formattedRecord.datetime =
                      CnSession.formatValue( datetime, 'datetime', true );
                    $scope.$apply(); // needed otherwise the new datetime takes seconds before it appears
                  }
                };
              } );
            }
          } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentCalendar', [
    'CnAppointmentModelFactory',
    'CnAvailabilityModelFactory', 'CnCapacityModelFactory',
    'CnShiftModelFactory', 'CnShiftTemplateModelFactory',
    'CnSession',
    function( CnAppointmentModelFactory,
              CnAvailabilityModelFactory, CnCapacityModelFactory,
              CnShiftModelFactory, CnShiftTemplateModelFactory,
              CnSession ) {
      return {
        templateUrl: module.getFileUrl( 'calendar.tpl.html' ),
        restrict: 'E',
        scope: {
          model: '=?',
          preventSiteChange: '@'
        },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentModelFactory.instance();
          $scope.heading = $scope.model.site.name.ucWords() + ' Appointment Calendar';
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
  cenozo.providers.directive( 'cnAppointmentList', [
    'CnAppointmentModelFactory', 'CnSession',
    function( CnAppointmentModelFactory, CnSession ) {
      return {
        templateUrl: module.getFileUrl( 'list.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentModelFactory.instance();
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentView', [
    'CnAppointmentModelFactory', 'CnAvailabilityModelFactory', 'CnSession',
    function( CnAppointmentModelFactory, CnAvailabilityModelFactory, CnSession ) {
      return {
        templateUrl: module.getFileUrl( 'view.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentModelFactory.instance();

          $scope.model.viewModel.afterView( function() {
            // no need to wait for metadata's promise to return, onView does that already
            if( -1 < cenozoApp.module( 'availability' ).actions.indexOf( 'calendar' ) &&
                angular.isObject( $scope.model.metadata.participantSite ) ) {
              $scope.model.metadata.getPromise().then( function() {
                // get the availability model linked to the participant's site
                $scope.availabilityModel =
                  CnAvailabilityModelFactory.forSite( $scope.model.metadata.participantSite );

                // connect the availability calendar's event click callback to the appointments datetime
                $scope.availabilityModel.calendarModel.settings.eventClick = function( availability ) {
                  var offset = moment.tz.zone( CnSession.user.timezone ).offset( availability.start.unix() )
                             - ( moment( availability.start ).tz( CnSession.user.timezone ).isDST() ? 60 : 0 );
                  var availabilityStart = moment( availability.start ).add( offset, 'minutes' );
                  var availabilityEnd = moment( availability.end ).add( offset, 'minutes' );
                  if( availabilityEnd.isAfter( moment() ) ) {
                    var cnRecordViewScope = cenozo.findChildDirectiveScope( $scope, 'cnRecordView' );
                    if( null == cnRecordViewScope )
                      throw new Exception( 'Unable to find appointment\'s cnRecordView scope.' );

                    // if the start is after the current time then use the next rounded hour
                    var datetime = moment( availabilityStart.format() );
                    if( !datetime.isAfter( moment() ) ) {
                      datetime = moment().minute( 0 ).second( 0 ).millisecond( 0 ).add( 1, 'hours' );
                      if( !datetime.isAfter( moment() ) )
                        datetime = moment( availabilityEnd.format() );
                    }

                    // set the datetime in the record and formatted record
                    $scope.model.viewModel.record.datetime = datetime.format();
                    $scope.model.viewModel.formattedRecord.datetime =
                      CnSession.formatValue( datetime, 'datetime', true );
                    $scope.$apply(); // needed otherwise the new datetime takes seconds before it appears
                    cnRecordViewScope.patch( 'datetime' );
                  }
                };
              } );
            }
          } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentAddFactory', [
    'CnBaseAddFactory', 'CnSession',
    function( CnBaseAddFactory, CnSession ) {
      var object = function( parentModel ) {
        var self = this;
        CnBaseAddFactory.construct( this, parentModel );

        // add the new appointment's events to the calendar cache
        this.onAdd = function( record ) {
          return this.$$onAdd( record ).then( function() {
            var duration = 'long' == record.type
                         ? CnSession.setting.longAppointment
                         : CnSession.setting.shortAppointment;
            record.getIdentifier = function() { return parentModel.getIdentifierFromRecord( record ); };
            var minDate = parentModel.calendarModel.cacheMinDate;
            var maxDate = parentModel.calendarModel.cacheMaxDate;
            parentModel.calendarModel.cache.push(
              getEventFromAppointment( record, CnSession.user.timezone, duration )
            );
          } );
        };
      };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentCalendarFactory', [
    'CnBaseCalendarFactory', 'CnSession',
    function( CnBaseCalendarFactory, CnSession ) {
      var object = function( parentModel ) {
        var self = this;
        CnBaseCalendarFactory.construct( this, parentModel );

        // remove day click callback
        delete this.settings.dayClick;

        // extend onCalendar to transform templates into events
        this.onCalendar = function( replace, minDate, maxDate, ignoreParent ) {
          // we must get the load dates before calling $$onCalendar
          var loadMinDate = self.getLoadMinDate( replace, minDate );
          var loadMaxDate = self.getLoadMaxDate( replace, maxDate );
          return self.$$onCalendar( replace, minDate, maxDate, ignoreParent ).then( function() {
            self.cache.forEach( function( item, index, array ) {
              var duration = 'long' == item.type
                           ? CnSession.setting.longAppointment
                           : CnSession.setting.shortAppointment;
              array[index] = getEventFromAppointment( item, CnSession.user.timezone, duration );
            } );
          } );
        };
      };

      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) {
      var self = this;
        CnBaseListFactory.construct( this, parentModel );

        // override onDelete
        this.onDelete = function( record ) {
          return this.$$onDelete( record ).then( function() {
            parentModel.calendarModel.cache = parentModel.calendarModel.cache.filter( function( e ) {
              return e.getIdentifier() != record.getIdentifier();
            } );
            self.parentModel.enableAdd( 0 == self.total );
          } );
        };
      };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentViewFactory', [
    'CnBaseViewFactory', 'CnSession',
    function( CnBaseViewFactory, CnSession ) {
      var args = arguments;
      var CnBaseViewFactory = args[0];
      var object = function( parentModel, root ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, root );

        // remove the deleted appointment's events from the calendar cache
        this.onDelete = function() {
          return this.$$onDelete().then( function() {
            parentModel.calendarModel.cache = parentModel.calendarModel.cache.filter( function( e ) {
              return e.getIdentifier() != self.record.getIdentifier();
            } );
          } );
        };

        // remove and re-add the appointment's events from the calendar cache
        this.onPatch = function( data ) {
          return this.$$onPatch( data ).then( function() {
            var minDate = parentModel.calendarModel.cacheMinDate;
            var maxDate = parentModel.calendarModel.cacheMaxDate;
            parentModel.calendarModel.cache = parentModel.calendarModel.cache.filter( function( e ) {
              return e.getIdentifier() != self.record.getIdentifier();
            } );
            parentModel.calendarModel.cache.push(
              getEventFromAppointment( self.record, CnSession.user.timezone )
            );
          } );
        };

        this.onView = function() {
          return this.$$onView().then( function() {
            var upcoming = moment().isBefore( self.record.datetime, 'minute' );
            parentModel.enableDelete( upcoming );
            parentModel.enableEdit( upcoming );
          } );
        };
      }
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentModelFactory', [
    'CnBaseModelFactory',
    'CnAppointmentAddFactory', 'CnAppointmentCalendarFactory',
    'CnAppointmentListFactory', 'CnAppointmentViewFactory',
    'CnSession', 'CnHttpFactory', '$q', '$state',
    function( CnBaseModelFactory,
              CnAppointmentAddFactory, CnAppointmentCalendarFactory,
              CnAppointmentListFactory, CnAppointmentViewFactory,
              CnSession, CnHttpFactory, $q, $state ) {
      var object = function( site ) {
        if( !angular.isObject( site ) || angular.isUndefined( site.id ) )
          throw new Error( 'Tried to create CnAppointmentModel without specifying the site.' );

        var self = this;

        // before constructing the model set whether the override input is constant
        if( 2 > CnSession.role.tier ) module.inputGroupList[null].override.constant = true;

        CnBaseModelFactory.construct( this, module );
        this.addModel = CnAppointmentAddFactory.instance( this );
        this.calendarModel = CnAppointmentCalendarFactory.instance( this );
        this.listModel = CnAppointmentListFactory.instance( this );
        this.viewModel = CnAppointmentViewFactory.instance( this, site.id == CnSession.site.id );
        this.site = site;

        // customize service data
        this.getServiceData = function( type, columnRestrictLists ) {
          var data = this.$$getServiceData( type, columnRestrictLists );
          if( 'calendar' == type ) data.restricted_site_id = self.site.id;
          return data;
        };

        // extend getMetadata
        this.getMetadata = function() {
          var promiseList = [ this.$$getMetadata() ];

          var parent = this.getParentIdentifier();
          if( angular.isDefined( parent.subject ) && angular.isDefined( parent.identifier ) ) {
            promiseList.push(
              CnHttpFactory.instance( {
                path: [ parent.subject, parent.identifier ].join( '/' ),
                data: { select: { column: { column: 'participant_id' } } }
              } ).query().then( function( response ) {
                // get the participant's effective site and list of phone numbers
                return $q.all( [
                  CnHttpFactory.instance( {
                    path: ['participant', response.data.participant_id ].join( '/' ),
                    data: { select: { column: [
                      { table: 'site', column: 'id', alias: 'site_id' },
                      { table: 'site', column: 'name' },
                      { table: 'site', column: 'timezone' }
                    ] } }
                  } ).get().then( function( response ) {
                    self.metadata.participantSite =
                      CnSession.siteList.findByProperty( 'id', response.data.site_id );
                  } ),

                  CnHttpFactory.instance( {
                    path: ['participant', response.data.participant_id, 'phone' ].join( '/' ),
                    data: {
                      select: { column: [ 'id', 'rank', 'type', 'number' ] },
                      modifier: { order: { rank: false } }
                    }
                  } ).query().then( function( response ) {
                    self.metadata.columnList.phone_id.enumList = [];
                    response.data.forEach( function( item ) {
                      self.metadata.columnList.phone_id.enumList.push( {
                        value: item.id,
                        name: '(' + item.rank + ') ' + item.type + ': ' + item.number
                      } );
                    } );
                  } )
                ] );
              } )
            );
          }

          return $q.all( promiseList );
        };
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
            var parts = $state.params.identifier.split( '=' );
            if( 1 == parts.length && parseInt( parts[0] ) == parts[0] ) // int identifier
              site = CnSession.siteList.findByProperty( 'id', parseInt( parts[0] ) );
            else if( 2 == parts.length ) // key=val identifier
              site = CnSession.siteList.findByProperty( parts[0], parts[1] );
          } else {
            site = CnSession.site;
          }
          return this.forSite( site );
        }
      };
    }
  ] );

} );
