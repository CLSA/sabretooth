define( [ 'capacity', 'vacancy', 'site' ].reduce( function( list, name ) {
  return list.concat( cenozoApp.module( name ).getRequiredFiles() );
}, [] ), function() {
  'use strict';

  try { var module = cenozoApp.module( 'appointment', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( module, {
    identifier: {
      parent: [ {
        subject: 'interview',
        column: 'interview_id',
        friendly: 'qnaire'
      }, {
        subject: 'participant',
        column: 'participant.uid'
      } ]
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
      language: {
        column: 'language.name',
        type: 'string',
        title: 'Language',
        isIncluded: function( $state, model ) { return 'appointment' == model.getSubjectFromState(); }
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
        help: 'Will either be reached, not reached, upcoming, assignable, missed, assigned or in progress'
      }
    },
    defaultOrder: {
      column: 'datetime',
      reverse: true
    }
  } );

  module.addInputGroup( '', {
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
        select: 'CONCAT( user.first_name, " ", user.last_name, " (", user.name, ")" )',
        where: [ 'user.first_name', 'user.last_name', 'user.name' ]
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
      help: 'One of reached, not reached, upcoming, assignable, missed, assigned or in progress'
    },
    type: {
      title: 'Type',
      type: 'enum'
    }
  } );

  // add an extra operation for each of the appointment-based calendars the user has access to
  [ 'appointment', 'capacity', 'vacancy' ].forEach( function( name ) {
    var calendarModule = cenozoApp.module( name );
    if( angular.isDefined( calendarModule.actions.calendar ) ) {
      module.addExtraOperation( 'calendar', {
        title: calendarModule.subject.snake.replace( '_', ' ' ).ucWords(),
        operation: function( $state, model ) {
          $state.go( name + '.calendar', { identifier: model.site.getIdentifier() } );
        },
        classes: 'appointment' == name ? 'btn-warning' : undefined // highlight current model
      } );
    }
  } );

  if( angular.isDefined( module.actions.calendar ) ) {
    module.addExtraOperation( 'view', {
      title: 'Appointment Calendar',
      operation: function( $state, model ) {
        $state.go( 'appointment.calendar', { identifier: model.metadata.participantSite.getIdentifier() } );
      }
    } );
  };

  // converts appointments into events
  function getEventFromAppointment( appointment, timezone ) {
    if( angular.isDefined( appointment.start ) && angular.isDefined( appointment.end ) ) {
      return appointment;
    } else {
      var date = moment( appointment.datetime );
      var offset = moment.tz.zone( timezone ).offset( date.unix() );

      // adjust the appointment for daylight savings time
      if( date.tz( timezone ).isDST() ) offset += -60;

      var event = {
        getIdentifier: function() { return appointment.getIdentifier() },
        title: ( angular.isDefined( appointment.uid ) ? appointment.uid : 'new appointment' ) +
               ( angular.isDefined( appointment.language_code ) ? ' (' + appointment.language_code + ')' : '' ) +
               ( angular.isDefined( appointment.qnaire_rank ) ? ' (' + appointment.qnaire_rank + ')' : '' ) +
               ( null != appointment.username ? '\nfor ' + appointment.username : '' ),
        start: moment( appointment.datetime ).subtract( offset, 'minutes' ),
        end: moment( appointment.datetime ).subtract( offset - appointment.duration, 'minutes' ),
        help: appointment.help
      };

      if( appointment.override ) {
        event.override = true;
        event.color = 'green';
      } else if( null != appointment.outcome ) {
        if( 'cancelled' == appointment.outcome ) event.className = 'calendar-event-cancelled';
        event.textColor = 'lightgray';
      }
      return event;
    }
  }

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentAdd', [
    'CnAppointmentModelFactory', 'CnSession', 'CnHttpFactory', 'CnModalConfirmFactory', '$q',
    function( CnAppointmentModelFactory, CnSession, CnHttpFactory, CnModalConfirmFactory, $q ) {
      return {
        templateUrl: module.getFileUrl( 'add.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentModelFactory.instance();

          // connect the capacity calendar's event click callback to the appointment's datetime
          if( $scope.model.getEditEnabled() ) {
            var listener = $scope.$watch( 'model.addModel.capacityModel', function( capacityModel ) {
              if( angular.isDefined( capacityModel ) ) {
                capacityModel.calendarModel.settings.eventClick = function( capacity ) {
                  // close the popover (this does nothing if there is no popover)
                  angular.element( this ).popover( 'hide' );
                  var date = moment( capacity.start );
                  var offset = moment.tz.zone( CnSession.user.timezone ).offset( date.unix() );

                  // adjust the appointment for daylight savings time
                  if( date.tz( CnSession.user.timezone ).isDST() ) offset += -60;

                  var capacityStart = moment( capacity.start ).add( offset, 'minutes' );
                  var capacityEnd = moment( capacity.end ).add( offset, 'minutes' );
                  if( capacityEnd.isAfter( moment() ) ) {
                    // find the add directive's scope
                    var cnRecordAddScope = cenozo.findChildDirectiveScope( $scope, 'cnRecordAdd' );
                    if( null == cnRecordAddScope )
                      throw new Error( 'Unable to find appointment\'s cnRecordAdd scope.' );

                    // if the start is after the current time then use the next rounded hour
                    var datetime = moment( capacityStart );
                    if( !datetime.isAfter( moment() ) ) {
                      datetime = moment().minute( 0 ).second( 0 ).millisecond( 0 ).add( 1, 'hours' );
                      if( !datetime.isAfter( moment() ) )
                        datetime = moment( capacityEnd.format() );
                    }

                    // set the datetime in the record and formatted record
                    cnRecordAddScope.record.datetime = datetime.format();
                    cnRecordAddScope.formattedRecord.datetime =
                      CnSession.formatValue( datetime, 'datetime', true );
                    $scope.$apply(); // needed otherwise the new datetime takes seconds before it appears
                  }
                };

                listener(); // your watch has ended
              }
            } );
          }

          $scope.model.addModel.afterNew( function() {
            // warn if old appointment will be cancelled
            var addDirective = cenozo.findChildDirectiveScope( $scope, 'cnRecordAdd' );
            if( null == addDirective ) throw new Error( 'Unable to find appointment\'s cnRecordAdd scope.' );
            var saveFn = addDirective.save;
            addDirective.save = function() {
              CnHttpFactory.instance( {
                path: 'interview/' + $scope.model.getParentIdentifier().identifier,
                data: { select: { column: [ 'missed_appointment' ] } }
              } ).get().then( function( response ) {
                var proceed = false;
                var promise =
                  response.data.missed_appointment ?
                  CnModalConfirmFactory.instance( {
                    title: 'Cancel Missed Appointment?',
                    message: 'There already exists a passed appointment for this interview, ' +
                             'do you wish to cancel it and create a new one?'
                  } ).show().then( function( response ) { proceed = response; } ) :
                  $q.all().then( function() { proceed = true; } );

                // proceed with the usual save function if we are told to proceed
                promise.then( function() { if( proceed ) saveFn(); } );
              } );
            };
          } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentCalendar', [
    'CnAppointmentModelFactory', 'CnCapacityModelFactory', 'CnVacancyModelFactory', 'CnSession',
    function( CnAppointmentModelFactory, CnCapacityModelFactory, CnVacancyModelFactory, CnSession ) {
      return {
        templateUrl: module.getFileUrl( 'calendar.tpl.html' ),
        restrict: 'E',
        scope: {
          model: '=?',
          preventSiteChange: '@'
        },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentModelFactory.instance();
          $scope.model.calendarModel.heading = $scope.model.site.name.ucWords() + ' Appointment Calendar';
        },
        link: function( scope ) {
          // factory name -> object map used below
          var factoryList = {
            appointment: CnAppointmentModelFactory,
            capacity: CnCapacityModelFactory,
            vacancy: CnVacancyModelFactory
          };

          // synchronize appointment/vacancy-based calendars
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
    'CnAppointmentModelFactory', 'CnSession',
    function( CnAppointmentModelFactory, CnSession ) {
      return {
        templateUrl: module.getFileUrl( 'view.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentModelFactory.instance();

          // connect the capacity calendar's event click callback to the appointment's datetime
          if( $scope.model.getEditEnabled() ) {
            var listener = $scope.$watch( 'model.viewModel.capacityModel', function( capacityModel ) {
              if( angular.isDefined( capacityModel ) ) {
                capacityModel.calendarModel.settings.eventClick = function( capacity ) {
                  // close the popover (this does nothing if there is no popover)
                  angular.element( this ).popover( 'hide' );
                  var date = moment( capacity.start );
                  var offset = moment.tz.zone( CnSession.user.timezone ).offset( date.unix() );

                  // adjust the appointment for daylight savings time
                  if( date.tz( CnSession.user.timezone ).isDST() ) offset += -60;

                  var capacityStart = moment( capacity.start ).add( offset, 'minutes' );
                  var capacityEnd = moment( capacity.end ).add( offset, 'minutes' );
                  if( capacityEnd.isAfter( moment() ) ) {
                    var cnRecordViewScope = cenozo.findChildDirectiveScope( $scope, 'cnRecordView' );
                    if( null == cnRecordViewScope )
                      throw new Error( 'Unable to find appointment\'s cnRecordView scope.' );

                    // if the start is after the current time then use the next rounded hour
                    var datetime = moment( capacityStart.format() );
                    if( !datetime.isAfter( moment() ) ) {
                      datetime = moment().minute( 0 ).second( 0 ).millisecond( 0 ).add( 1, 'hours' );
                      if( !datetime.isAfter( moment() ) )
                        datetime = moment( capacityEnd.format() );
                    }

                    if( !datetime.isSame( moment( $scope.model.viewModel.record.datetime ) ) ) {
                      // set the datetime in the record and formatted record
                      $scope.model.viewModel.record.datetime = datetime.format();
                      $scope.model.viewModel.formattedRecord.datetime =
                        CnSession.formatValue( datetime, 'datetime', true );
                      $scope.$apply(); // needed otherwise the new datetime takes seconds before it appears
                      cnRecordViewScope.patch( 'datetime' );
                    }
                  }
                };

                listener(); // your watch has ended
              }
            } );
          }
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentAddFactory', [
    'CnBaseAddFactory', 'CnSession', 'CnHttpFactory', '$q', '$injector',
    function( CnBaseAddFactory, CnSession, CnHttpFactory, $q, $injector ) {
      var object = function( parentModel ) {
        var self = this;
        CnBaseAddFactory.construct( this, parentModel );

        // add the new appointment's events to the calendar cache
        this.onAdd = function( record ) {
          return this.$$onAdd( record ).then( function() {
            CnHttpFactory.instance( {
              path: 'appointment/' + record.id
            } ).get().then( function( response ) {
              record.uid = response.data.uid;
              record.qnaire_rank = response.data.qnaire_rank;
              record.duration = response.data.duration;
              record.getIdentifier = function() { return parentModel.getIdentifierFromRecord( record ); };
              var minDate = parentModel.calendarModel.cacheMinDate;
              var maxDate = parentModel.calendarModel.cacheMaxDate;
              parentModel.calendarModel.cache.push(
                getEventFromAppointment( record, CnSession.user.timezone )
              );
            } );
          } );
        };

        this.onNew = function( record ) {
          var parent = parentModel.getParentIdentifier();
          return CnHttpFactory.instance( {
            path: [ parent.subject, parent.identifier ].join( '/' ),
            data: { select: { column: { column: 'participant_id' } } }
          } ).query().then( function( response ) {
            // get the participant's effective site and list of phone numbers
            return $q.all( [
              CnHttpFactory.instance( {
                path: ['participant', response.data.participant_id ].join( '/' ),
                data: { select: { column: [
                  { table: 'site', column: 'id', alias: 'site_id' },
                  { table: 'site', column: 'name' }
                ] } }
              } ).get().then( function( response ) {
                parentModel.metadata.participantSite =
                  CnSession.siteList.findByProperty( 'id', response.data.site_id );
              } ),

              CnHttpFactory.instance( {
                path: ['participant', response.data.participant_id, 'phone' ].join( '/' ),
                data: {
                  select: { column: [ 'id', 'rank', 'type', 'number' ] },
                  modifier: {
                    where: { column: 'phone.active', operator: '=', value: true },
                    order: { rank: false }
                  }
                }
              } ).query().then( function( response ) {
                parentModel.metadata.getPromise().then( function() {
                  parentModel.metadata.columnList.phone_id.enumList = [];
                  response.data.forEach( function( item ) {
                    parentModel.metadata.columnList.phone_id.enumList.push( {
                      value: item.id,
                      name: '(' + item.rank + ') ' + item.type + ': ' + item.number
                    } );
                  } );
                } );
              } )
            ] ).then( function() {
              return self.$$onNew( record ).then( function() {
                if( angular.isUndefined( self.capacityModel ) &&
                    angular.isDefined( cenozoApp.module( 'capacity' ).actions.calendar ) &&
                    angular.isObject( parentModel.metadata.participantSite ) ) {
                  // to avoid a circular dependency we have to get the CnCapacityModelFactory here instead of
                  // in this service's injection list
                  var CnCapacityModelFactory = $injector.get( 'CnCapacityModelFactory' );

                  // get the capacity model linked to the participant's site
                  self.capacityModel =
                    CnCapacityModelFactory.forSite( parentModel.metadata.participantSite );
                }
              } );
            } );
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

        // extend onCalendar to transform into events
        this.onCalendar = function( replace, minDate, maxDate, ignoreParent ) {
          // we must get the load dates before calling $$onCalendar
          var loadMinDate = self.getLoadMinDate( replace, minDate );
          var loadMaxDate = self.getLoadMaxDate( replace, maxDate );
          return self.$$onCalendar( replace, minDate, maxDate, ignoreParent ).then( function() {
            self.cache.forEach( function( item, index, array ) {
              array[index] = getEventFromAppointment( item, CnSession.user.timezone );
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
          } );
        };
      };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentViewFactory', [
    'CnBaseViewFactory', '$injector', '$q', 'CnSession', 'CnHttpFactory',
    function( CnBaseViewFactory, $injector, $q, CnSession, CnHttpFactory ) {
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
            // only allow delete/edit if the appointment is in the future
            var upcoming = moment().isBefore( self.record.datetime, 'minute' );
            parentModel.getDeleteEnabled = function() { return parentModel.$$getDeleteEnabled() && upcoming; };
            parentModel.getEditEnabled = function() { return parentModel.$$getEditEnabled() && upcoming; };

            if( angular.isUndefined( self.capacityModel ) &&
                angular.isDefined( cenozoApp.module( 'capacity' ).actions.calendar ) &&
                angular.isObject( parentModel.metadata.participantSite ) ) {
              // to avoid a circular dependency we have to get the CnCapacityModelFactory here instead of
              // in this service's injection list
              var CnCapacityModelFactory = $injector.get( 'CnCapacityModelFactory' );

              // get the capacity model linked to the participant's site
              self.capacityModel = CnCapacityModelFactory.forSite( parentModel.metadata.participantSite );
            }

            // update the phone list based on the parent interview
            return CnHttpFactory.instance( {
              path: 'interview/' + self.record.interview_id,
              data: { select: { column: { column: 'participant_id' } } }
            } ).query().then( function( response ) {
              // get the participant's effective site and list of phone numbers
              return $q.all( [
                CnHttpFactory.instance( {
                  path: ['participant', response.data.participant_id ].join( '/' ),
                  data: { select: { column: [
                    { table: 'site', column: 'id', alias: 'site_id' },
                    { table: 'site', column: 'name' }
                  ] } }
                } ).get().then( function( response ) {
                  parentModel.metadata.participantSite =
                    CnSession.siteList.findByProperty( 'id', response.data.site_id );
                } ),

                CnHttpFactory.instance( {
                  path: ['participant', response.data.participant_id, 'phone' ].join( '/' ),
                  data: {
                    select: { column: [ 'id', 'rank', 'type', 'number' ] },
                    modifier: {
                      where: { column: 'phone.active', operator: '=', value: true },
                      order: { rank: false }
                    }
                  }
                } ).query().then( function( response ) {
                  parentModel.metadata.getPromise().then( function() {
                    parentModel.metadata.columnList.phone_id.enumList = [];
                    response.data.forEach( function( item ) {
                      parentModel.metadata.columnList.phone_id.enumList.push( {
                        value: item.id,
                        name: '(' + item.rank + ') ' + item.type + ': ' + item.number
                      } );
                    } );
                  } );
                } )
              ] );
            } );
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
        if( 2 > CnSession.role.tier && 'operator+' != CnSession.role.name ) {
          module.inputGroupList.findByProperty( 'title', '' ).inputList.override.constant = true;
        }

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

        // don't show add button when viewing full appointment list (must override root $$ function)
        this.$$getAddEnabled = function() {
          return !( 'appointment' == this.getSubjectFromState() && 'list' == this.getActionFromState() ) &&
                 angular.isDefined( module.actions.add );
        };

        // extend getTypeaheadData
        this.getTypeaheadData = function( input, viewValue ) {
          var data = this.$$getTypeaheadData( input, viewValue );

          // only include active users
          if( 'user' == input.typeahead.table ) {
            data.modifier.where.unshift( { bracket: true, open: true } );
            data.modifier.where.push( { bracket: true, open: false } );
            data.modifier.where.push( { column: 'user.active', operator: '=', value: true } );

            // restrict to the current site
            if( this.site ) data.restricted_site_id = this.site.id;
          }

          return data;
        };
      };

      // get the siteColumn to be used by a site's identifier
      var siteModule = cenozoApp.module( 'site' );
      var siteColumn = angular.isDefined( siteModule.identifier.column ) ? siteModule.identifier.column : 'id';

      return {
        siteInstanceList: {},
        userInstanceList: {},
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
        forUser: function( user ) {
          if( !angular.isObject( user ) ) {
            $state.go( 'error.404' );
            throw new Error( 'Cannot find user matching identifier "' + user + '", redirecting to 404.' );
          }
          if( angular.isUndefined( this.userInstanceList[user.id] ) ) {
            var site = CnSession.site;
            if( angular.isUndefined( site.getIdentifier ) )
              site.getIdentifier = function() { return siteColumn + '=' + this[siteColumn]; };
            this.userInstanceList[user.id] = new object( site );
          }
          return this.userInstanceList[user.id];
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
