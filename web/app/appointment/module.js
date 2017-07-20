define( [ 'site', 'vacancy' ].reduce( function( list, name ) {
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
      uid: {
        column: 'participant.uid',
        type: 'string',
        title: 'UID'
      },
      start_datetime: {
        type: 'datetime',
        title: 'Date & Time'
      },
      duration: {
        type: 'string',
        title: 'Duration'
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
        title: 'Phone Number'
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
      state: {
        type: 'string',
        title: 'State',
        help: 'Will either be reached, not reached, upcoming, assignable, missed, assigned or in progress'
      }
    },
    defaultOrder: {
      column: 'start_datetime',
      reverse: true
    }
  } );

  module.addInputGroup( '', {
    start_datetime: {
      title: 'Start Date & Time',
      type: 'datetime',
      constant: true,
      help: 'Set by clicking a vacancy in the calendar below'
    },
    duration: {
      title: 'Duration',
      type: 'enum',
      help: 'Not all durations are necessarily available, check the vacancy calendar for details'
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
    }
  } );

  // add an extra operation for each of the appointment-based calendars the user has access to
  [ 'appointment', 'vacancy' ].forEach( function( name ) {
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

  // get the vacancy's start time adjusted for daylight savings time
  function convertDatetime( datetime, timezone, forward ) {
    if( angular.isUndefined( forward ) ) forward = false;
    var date = moment( datetime );
    var offset = moment.tz.zone( timezone ).offset( date.unix() );
    if( date.tz( timezone ).isDST() ) offset += -60;
    return forward ? moment( datetime ).add( offset, 'minute' ) : moment( datetime ).subtract( offset, 'minute' );
  }

  // converts appointments into events
  function getEventFromAppointment( appointment, timezone ) {
    if( angular.isDefined( appointment.start ) && angular.isDefined( appointment.end ) ) {
      return appointment;
    } else {
      var event = {
        getIdentifier: function() { return appointment.getIdentifier() },
        title: ( angular.isDefined( appointment.uid ) ? appointment.uid : 'new appointment' ) +
               ( angular.isDefined( appointment.language_code ) ? ' (' + appointment.language_code + ')' : '' ) +
               ( angular.isDefined( appointment.qnaire_rank ) ? ' (' + appointment.qnaire_rank + ')' : '' ) +
               ( null != appointment.username ? '\nfor ' + appointment.username : '' ),
        start: convertDatetime( appointment.start_datetime, timezone, false ),
        end: convertDatetime( appointment.end_datetime, timezone, false ),
        help: appointment.help
      };

      if( null != appointment.outcome ) {
        if( 'cancelled' == appointment.outcome ) event.className = 'calendar-event-cancelled';
        event.textColor = 'lightgray';
      }
      return event;
    }
  }

  // determines if all vacancies are available (using local time, not UTC)
  function vacancyAvailable( oldDatetime, oldDuration, newDatetime, newDuration, cache ) {
    var available = true;
    if( 'same' == newDatetime ) newDatetime = oldDatetime.clone();
    if( 'same' == newDuration ) newDuration = oldDuration;
    var oldFromDatetime = null == oldDatetime ? null : oldDatetime.clone();
    var oldToDatetime = null == oldDatetime ? null : oldDatetime.clone().add( oldDuration, 'minute' );
    var newFromDatetime = newDatetime.clone();
    var newToDatetime = newDatetime.clone().add( newDuration, 'minute' );
    var total = newDuration / 30;
    var found = 0;
    cache.some( function( vacancy ) {
      if( vacancy.start.isBetween( newFromDatetime, newToDatetime, 'minute', '[)' ) ) {
        found++;
        if( vacancy.start.isBetween( oldFromDatetime, oldToDatetime, 'minute', '[)' ) ) {
          // this vacancy is already used by this appointment
          if( vacancy.appointments > vacancy.operators ) available = false;
        } else {
          // this vacancy is not used by this appointment
          if( vacancy.appointments >= vacancy.operators ) available = false;
        }
      }
      return found == total || !available; // quit once all are found or one is unavailable
    } );

    // if all vacancies are available do a last check that we found them all
    return available ? found == total : false;
  }

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentAdd', [
    'CnAppointmentModelFactory', 'CnSession', 'CnHttpFactory',
    'CnModalConfirmFactory', 'CnModalMessageFactory', '$q',
    function( CnAppointmentModelFactory, CnSession, CnHttpFactory,
              CnModalConfirmFactory, CnModalMessageFactory, $q ) {
      return {
        templateUrl: module.getFileUrl( 'add.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentModelFactory.instance();

          // connect the vacancy calendar's event click callback to the appointment
          var listener = $scope.$watch( 'model.addModel.vacancyModel', function( vacancyModel ) {
            if( angular.isDefined( vacancyModel ) ) {
              vacancyModel.calendarModel.settings.dayClick = function( date, event, view ) {
                // if we are not looking at an appointment (parent interview) then do nothing
                if( 'interview' != $scope.model.getSubjectFromState() ) return;

                // close the popover (this does nothing if there is no popover)
                angular.element( this ).popover( 'hide' );

                if( 1 < CnSession.role.tier || 'operator+' == CnSession.role.name ) {
                  // get the clicked start time adjusted for daylight savings time
                  var datetime = convertDatetime( date, CnSession.user.timezone, true );
                  if( !datetime.isAfter( moment() ) ) {
                    CnModalMessageFactory.instance( {
                      title: 'Invalid Appointment Time',
                      message: 'The time you have selected is in the past.  You can only create new ' +
                               'appointment for a time in the future.'
                    } ).show();
                  } else {
                    // find the add directive's scope
                    var cnRecordAddScope = cenozo.findChildDirectiveScope( $scope, 'cnRecordAdd' );
                    if( null == cnRecordAddScope )
                      throw new Error( 'Unable to find appointment\'s cnRecordAdd scope.' );

                    // set the regular and formatted start datetime, and start vacancy ID
                    cnRecordAddScope.record.start_datetime = datetime;
                    cnRecordAddScope.formattedRecord.start_datetime =
                      CnSession.formatValue( datetime, 'datetime', true );
                    cnRecordAddScope.record.start_vacancy_id = null;
                    $scope.$apply(); // needed otherwise the new datetime takes seconds before it appears
                  }
                }
              };

              vacancyModel.calendarModel.settings.eventClick = function( vacancy ) {
                // if we are not looking at an appointment (parent interview) then view the vacancy
                if( 'interview' != $scope.model.getSubjectFromState() )
                  return vacancyModel.getViewEnabled() ?
                    vacancyModel.transitionToViewState( vacancy ) : null;

                // close the popover (this does nothing if there is no popover)
                angular.element( this ).popover( 'hide' );

                // get the vacancy's start time adjusted for daylight savings time
                var datetime = convertDatetime( vacancy.start, CnSession.user.timezone, true );
                if( !datetime.isAfter( moment() ) ) {
                  CnModalMessageFactory.instance( {
                    title: 'Invalid Appointment Time',
                    message: 'The vacancy you have selected is in the past.  You can only create new ' +
                             'appointment using a vacancy in the future.'
                  } ).show();
                } else if( vacancy.appointments >= vacancy.operators &&
                           2 > CnSession.role.tier &&
                          'operator+' != CnSession.role.name ) {
                  CnModalMessageFactory.instance( {
                    title: 'No Vacancy',
                    message: 'The start time you have selected does not have any vacancy.  You may only ' +
                      'create an appointment using a vacancy which has at least one unbooked operator.'
                  } ).show();
                } else {
                  // find the add directive's scope
                  var cnRecordAddScope = cenozo.findChildDirectiveScope( $scope, 'cnRecordAdd' );
                  if( null == cnRecordAddScope )
                    throw new Error( 'Unable to find appointment\'s cnRecordAdd scope.' );

                  // set the regular and formatted start datetime, and start vacancy ID
                  cnRecordAddScope.record.start_datetime = datetime;
                  cnRecordAddScope.formattedRecord.start_datetime =
                    CnSession.formatValue( datetime, 'datetime', true );
                  cnRecordAddScope.record.start_vacancy_id = vacancy.id;
                  $scope.$apply(); // needed otherwise the new datetime takes seconds before it appears
                }
              };

              listener(); // your watch has ended
            }
          } );

          $scope.model.addModel.afterNew( function() {
            var addDirective = cenozo.findChildDirectiveScope( $scope, 'cnRecordAdd' );
            if( null == addDirective ) throw new Error( 'Unable to find appointment\'s cnRecordAdd scope.' );
            var saveFn = addDirective.save;
            addDirective.save = function() {
              // see if there are vacancies to fulfill the appointment's timespan
              var cache = $scope.model.addModel.vacancyModel.calendarModel.cache;
              var vacancy = null != addDirective.record.start_vacancy_id
                          ? cache.findByProperty( 'id', addDirective.record.start_vacancy_id )
                          : null;
              var available = vacancy
                            ? vacancyAvailable( null, null, vacancy.start, addDirective.record.duration, cache )
                            : false;

              var promiseList = [];
              var proceed1 = true;
              if( !available ) {
                if( 2 > CnSession.role.tier && 'operator+' != CnSession.role.name ) {
                  proceed1 = false;
                  CnModalMessageFactory.instance( {
                    title: 'No Vacancy',
                    message:
                      'The appointment time and duration you have selected is missing vacancy.  You may only ' +
                      'create an appointment such that all vacancies have at least one unbooked operator.'
                  } ).show();
                } else {
                  var hours = $scope.model.viewModel.record.duration / 60;
                  promiseList.push(
                    CnModalConfirmFactory.instance( {
                      title: 'Overbook Appointment',
                      message:
                      'NOTE: The appointment time and duration you have chosen will require the vacancy ' +
                      'calendar to be overbooked!\n\nAre you sure you wish to create the appointment?'
                    } ).show().then( function( response ) {
                      proceed1 = response;
                    } )
                  );
                }
              }

              $q.all( promiseList ).then( function() {
                if( proceed1 ) {
                  // warn if old appointment will be cancelled
                  CnHttpFactory.instance( {
                    path: 'interview/' + $scope.model.getParentIdentifier().identifier,
                    data: { select: { column: [ 'missed_appointment' ] } }
                  } ).get().then( function( response ) {
                    var proceed2 = false;
                    var promise =
                      response.data.missed_appointment ?
                      CnModalConfirmFactory.instance( {
                        title: 'Cancel Missed Appointment?',
                        message: 'There already exists a passed appointment for this interview, ' +
                                 'do you wish to cancel it and create a new one?'
                      } ).show().then( function( response ) { proceed2 = response; } ) :
                      $q.all().then( function() { proceed2 = true; } );

                    // proceed with the usual save function if we are told to proceed
                    promise.then( function() { if( proceed2 ) saveFn(); } );
                  } );
                }
              } );
            };
          } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentCalendar', [
    'CnAppointmentModelFactory', 'CnVacancyModelFactory', 'CnSession',
    function( CnAppointmentModelFactory, CnVacancyModelFactory, CnSession ) {
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
    'CnAppointmentModelFactory', 'CnSession', 'CnModalConfirmFactory', 'CnModalMessageFactory', '$q',
    function( CnAppointmentModelFactory, CnSession, CnModalConfirmFactory, CnModalMessageFactory, $q ) {
      return {
        templateUrl: module.getFileUrl( 'view.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentModelFactory.instance();

          $scope.model.viewModel.afterView( function() {
            var cnRecordViewScope = cenozo.findChildDirectiveScope( $scope, 'cnRecordView' );

            // override the regular patch function
            var patchFn = cnRecordViewScope.patch;
            cnRecordViewScope.patch = function( property ) {
              // if we're changing the duration we have to check if there is vacancy available
              var promiseList = [];
              var proceed = true;
              if( 'duration' == property ) {
                var available = vacancyAvailable(
                  convertDatetime(
                    $scope.model.viewModel.record.start_datetime,
                    CnSession.user.timezone,
                    false
                  ),
                  $scope.model.viewModel.backupRecord.duration,
                  'same',
                  $scope.model.viewModel.record.duration,
                  $scope.model.viewModel.vacancyModel.calendarModel.cache
                );

                if( !available ) {
                  if( 2 > CnSession.role.tier && 'operator+' != CnSession.role.name ) {
                    return CnModalMessageFactory.instance( {
                      title: 'No Vacancy',
                      message:
                        'The duration you have selected is missing vacancy.  You may only set an ' +
                        'appointment\'s duration such that all vacancies have at least one unbooked operator.'
                    } ).show();
                  } else {
                    var hours = $scope.model.viewModel.record.duration / 60;
                    var message = 'NOTE: The duration you have chosen will require the vacancy calendar to ' +
                      'be overbooked!\n\nAre you sure you wish to change the appointment\'s duration to ' +
                      hours.toFixed(1) + ' hours?';

                    promiseList.push(
                      CnModalConfirmFactory.instance( {
                        title: ( available ? 'Change' : 'Overbook' ) + ' Appointment',
                        message: message
                      } ).show().then( function( response ) {
                        proceed = response;
                      } )
                    );
                  }
                }
              }

              return $q.all( promiseList ).then( function() {
                if( proceed ) patchFn( property );
                else $scope.model.viewModel.record[property] = $scope.model.viewModel.backupRecord[property];
              } );
            };
          } );

          // connect the vacancy calendar's event click callback to the appointment
          if( $scope.model.getEditEnabled() ) {
            var listener = $scope.$watch( 'model.viewModel.vacancyModel', function( vacancyModel ) {
              if( angular.isDefined( vacancyModel ) ) {
                vacancyModel.calendarModel.settings.dayClick = function( date, event, view ) {
                  // if we are not looking at an appointment then do nothing
                  if( 'appointment' != $scope.model.getSubjectFromState() ) return;

                  // close the popover (this does nothing if there is no popover)
                  angular.element( this ).popover( 'hide' );

                  if( 1 < CnSession.role.tier || 'operator+' == CnSession.role.name ) {
                    // get the clicked start time adjusted for daylight savings time
                    var datetime = convertDatetime( date, CnSession.user.timezone, true );
                    if( !datetime.isAfter( moment() ) ) {
                      CnModalMessageFactory.instance( {
                        title: 'Invalid Appointment Time',
                        message: 'The time you have selected is in the past.  You can only change the ' +
                          'appointment\'s start time to a time in the future.'
                      } ).show();
                    } else {
                      CnModalConfirmFactory.instance( {
                        title: 'Overbook Appointment',
                        message: 'NOTE: The time you have chosen will require the vacancy calendar to be ' +
                                 'overbooked!\n\nAre you sure you wish to change the appointment\'s start ' +
                                 'time to ' + CnSession.formatValue( datetime, 'datetime', true ) + '?'
                      } ).show().then( function( response ) {
                        if( response ) {
                          // find the view directive's scope
                          var cnRecordViewScope = cenozo.findChildDirectiveScope( $scope, 'cnRecordView' );
                          if( null == cnRecordViewScope )
                            throw new Error( 'Unable to find appointment\'s cnRecordView scope.' );

                          // set the datetime in the record and formatted record
                          $scope.model.viewModel.record.start_datetime = datetime;
                          $scope.model.viewModel.formattedRecord.start_datetime =
                            CnSession.formatValue( datetime, 'datetime', true );
                          $scope.model.viewModel.record.start_vacancy_id = null;
                          cnRecordViewScope.patch( 'start_datetime' );
                        }
                      } );
                    }
                  }
                };

                vacancyModel.calendarModel.settings.eventClick = function( vacancy ) {
                  // if we are not looking at an appointment then view the vacancy
                  if( 'appointment' != $scope.model.getSubjectFromState() )
                    return vacancyModel.getViewEnabled() ?
                      vacancyModel.transitionToViewState( vacancy ) : null;

                  // close the popover (this does nothing if there is no popover)
                  angular.element( this ).popover( 'hide' );

                  // get the vacancy's start time adjusted for daylight savings time
                  var datetime = convertDatetime( vacancy.start, CnSession.user.timezone, true );
                  if( moment( $scope.model.viewModel.record.start_datetime ).isSame( datetime, 'minute' ) ) {
                   // do nothing, the user selected the appointment's current start vacancy
                  } else if( !datetime.isAfter( moment() ) ) {
                    CnModalMessageFactory.instance( {
                      title: 'Invalid Appointment Time',
                      message: 'The vacancy you have selected is in the past.  You can only change the ' +
                        'appointment\'s start time to a vacancy in the future.'
                    } ).show();
                  } else {
                    // determine if all vacancies are available
                    var available = vacancyAvailable(
                      convertDatetime(
                        $scope.model.viewModel.record.start_datetime,
                        CnSession.user.timezone,
                        false
                      ),
                      $scope.model.viewModel.record.duration,
                      moment( vacancy.start ),
                      'same',
                      vacancyModel.calendarModel.cache
                    );

                    if( !available && 2 > CnSession.role.tier && 'operator+' != CnSession.role.name ) {
                      CnModalMessageFactory.instance( {
                        title: 'No Vacancy',
                        message:
                          'The start time you have selected does not have any vacancy.  You may only set an ' +
                          'appointment\'s start time to a vacancy which has at least one unbooked operator.'
                      } ).show();
                    } else {
                      var message = ( available ? '' : 'NOTE: The time you have chosen will require the ' +
                                                       'vacancy calendar to be overbooked!\n\n' ) +
                        'Are you sure you wish to change the appointment\'s start time to ' +
                        CnSession.formatValue( datetime, 'datetime', true ) + '?';

                      CnModalConfirmFactory.instance( {
                        title: ( available ? 'Change' : 'Overbook' ) + ' Appointment',
                        message: message
                      } ).show().then( function( response ) {
                        if( response ) {
                          // find the view directive's scope
                          var cnRecordViewScope = cenozo.findChildDirectiveScope( $scope, 'cnRecordView' );
                          if( null == cnRecordViewScope )
                            throw new Error( 'Unable to find appointment\'s cnRecordView scope.' );

                          // set the datetime in the record and formatted record
                          $scope.model.viewModel.record.start_datetime = datetime;
                          $scope.model.viewModel.formattedRecord.start_datetime =
                            CnSession.formatValue( datetime, 'datetime', true );
                          $scope.model.viewModel.record.start_vacancy_id = vacancy.id;
                          cnRecordViewScope.patch( 'start_vacancy_id' );
                        }
                      } );
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
    'CnBaseAddFactory', 'CnSession', 'CnHttpFactory', 'CnVacancyModelFactory', '$q',
    function( CnBaseAddFactory, CnSession, CnHttpFactory, CnVacancyModelFactory, $q ) {
      var object = function( parentModel ) {
        var self = this;
        CnBaseAddFactory.construct( this, parentModel );

        this.onAdd = function( record ) {
          return this.$$onAdd( record ).then( function() {
            parentModel.updateVacancyCalendars();
            // add the new appointment's events to the calendar cache
            CnHttpFactory.instance( {
              path: 'appointment/' + record.id
            } ).get().then( function( response ) {
              record.uid = response.data.uid;
              record.qnaire_rank = response.data.qnaire_rank;
              record.getIdentifier = function() { return parentModel.getIdentifierFromRecord( record ); };
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
                parentModel.metadata.getPromise().then( function() {
                  parentModel.metadata.participantSite =
                    CnSession.siteList.findByProperty( 'id', response.data.site_id );

                  // get the vacancy model linked to the participant's site
                  self.vacancyModel = CnVacancyModelFactory.forSite( parentModel.metadata.participantSite );
                } );
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
              return self.$$onNew( record );
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
            parentModel.updateVacancyCalendars();
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
    'CnBaseViewFactory', 'CnSession', 'CnHttpFactory', 'CnVacancyModelFactory', '$q',
    function( CnBaseViewFactory, CnSession, CnHttpFactory, CnVacancyModelFactory, $q ) {
      var object = function( parentModel, root ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, root );

        // remove the deleted appointment's events from the calendar cache
        this.onDelete = function() {
          return this.$$onDelete().then( function() {
            parentModel.updateVacancyCalendars();
            parentModel.calendarModel.cache = parentModel.calendarModel.cache.filter( function( e ) {
              return e.getIdentifier() != self.record.getIdentifier();
            } );
          } );
        };

        // remove and re-add the appointment's events from the calendar cache
        this.onPatch = function( data ) {
          return self.$$onPatch( data ).then( function() {
            parentModel.updateVacancyCalendars();
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
            var upcoming = moment().isBefore( self.record.start_datetime, 'minute' );
            parentModel.getDeleteEnabled = function() {
              return 'vacancy' != parentModel.getSubjectFromState() &&
                     parentModel.$$getDeleteEnabled() && upcoming;
            };
            parentModel.getEditEnabled = function() { return parentModel.$$getEditEnabled() && upcoming; };

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

                  // get the vacancy model linked to the participant's site
                  self.vacancyModel = CnVacancyModelFactory.forSite( parentModel.metadata.participantSite );
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
    'CnSession', 'CnHttpFactory', '$state',
    function( CnBaseModelFactory,
              CnAppointmentAddFactory, CnAppointmentCalendarFactory,
              CnAppointmentListFactory, CnAppointmentViewFactory,
              CnSession, CnHttpFactory, $state ) {
      var object = function( site ) {
        if( !angular.isObject( site ) || angular.isUndefined( site.id ) )
          throw new Error( 'Tried to create CnAppointmentModel without specifying the site.' );

        var self = this;

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

        // customize when to enable adding appointments
        this.getAddEnabled = function() {
          var subject = this.getSubjectFromState();
          var action = this.getActionFromState();
          return !( 'appointment' == subject && 'list' == action ) &&
                 'vacancy' != subject &&
                 angular.isDefined( module.actions.add );
        };

        // customize when to enable deleting appointments
        this.getDeleteEnabled = function() {
          return this.$$getDeleteEnabled() && 'vacancy' != this.getSubjectFromState();
        };

        // extend getMetadata
        this.getMetadata = function() {
          return this.$$getMetadata().then( function() {
            // add the start_datetime and duration metadata details
            angular.extend( self.metadata.columnList, {
              start_datetime: { required: true },
              duration: {
                required: true,
                default: 60,
                enumList: [
                  { value: 30, name: '0.5 hours' },
                  { value: 60, name: '1.0 hours' },
                  { value: 90, name: '1.5 hours' },
                  { value: 120, name: '2.0 hours' },
                  { value: 150, name: '2.5 hours' },
                  { value: 180, name: '3.0 hours' },
                  { value: 210, name: '3.5 hours' },
                  { value: 240, name: '4.0 hours' }
                ]
              }
            } );
          } );
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

        // convenience method to update vacancy calendars
        this.updateVacancyCalendars = function() {
          if( angular.isDefined( self.addModel.vacancyModel ) )
            self.addModel.vacancyModel.calendarModel.onCalendar( true );
          if( angular.isDefined( self.viewModel.vacancyModel ) )
            self.viewModel.vacancyModel.calendarModel.onCalendar( true );

          // refresh any visible calendars
          var cnRecordCalendar = cenozo.getScopeByQuerySelector( '.record-calendar' );
          if( null != cnRecordCalendar ) cnRecordCalendar.refresh();
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
