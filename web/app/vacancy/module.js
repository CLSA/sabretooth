define( [ 'appointment', 'site' ].reduce( function( list, name ) {
  return list.concat( cenozoApp.module( name ).getRequiredFiles() );
}, [] ), function() {
  'use strict';

  try { var module = cenozoApp.module( 'vacancy', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( module, {
    identifier: {},
    name: {
      singular: 'vacancy',
      plural: 'vacancies',
      possessive: 'vacancy\'s',
      pluralPossessive: 'vacancies\''
    },
    columnList: {
      datetime: {
        type: 'datetime',
        title: 'Date & Time'
      },
      operators: {
        type: 'string',
        title: 'Operators'
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
      minuteStep: 30,
      help: 'Can only be changed if the vacancy has no appointments.'
    },
    operators: {
      title: 'Operators',
      type: 'string',
      format: 'integer',
      minValue: 1,
      help: 'How many operators are available at this time'
    },
    appointments: { type: 'hidden' }
  } );

  // determines the offset given a date (moment object) and timezone
  function getTimezoneOffset( date, timezone ) {
    var offset = moment.tz.zone( timezone ).offset( date.unix() );
    if( date.tz( timezone ).isDST() ) offset += -60; // adjust the appointment for daylight savings time
    return offset;
  }

  // converts vacancies into events
  function getEventFromVacancy( vacancy, timezone ) {
    if( angular.isDefined( vacancy.start ) && angular.isDefined( vacancy.end ) ) {
      return vacancy;
    } else {
      var offset = getTimezoneOffset( moment( vacancy.datetime ), timezone );
      var remaining = vacancy.operators - vacancy.appointments;
      var color = 'blue';
      if( 0 == remaining ) color = 'gray';
      else if( 0 > remaining ) color = 'red';
      return {
        id: vacancy.id,
        getIdentifier: function() { return vacancy.getIdentifier() },
        title: vacancy.appointments + ' of ' + vacancy.operators + ' booked',
        start: moment( vacancy.datetime ).subtract( offset, 'minutes' ),
        end: moment( vacancy.datetime ).subtract( offset, 'minutes' ).add( 30, 'minutes' ),
        color: color,
        offset: offset,
        operators: vacancy.operators,
        appointments: vacancy.appointments
      };
    }
  }

  // add an extra operation for each of the appointment-based calendars the user has access to
  [ 'appointment', 'vacancy' ].forEach( function( name ) {
    var calendarModule = cenozoApp.module( name );
    if( angular.isDefined( calendarModule.actions.calendar ) ) {
      module.addExtraOperation( 'calendar', {
        title: calendarModule.subject.snake.replace( "_", " " ).ucWords(),
        operation: function( $state, model ) {
          $state.go( name + '.calendar', { identifier: model.site.getIdentifier() } );
        },
        classes: 'vacancy' == name ? 'btn-warning' : undefined // highlight current model
      } );
    }
  } );

  if( angular.isDefined( module.actions.calendar ) ) {
    module.addExtraOperation( 'list', {
      title: 'Vacancy Calendar',
      operation: function( $state, model ) {
        $state.go( 'vacancy.calendar', { identifier: model.site.getIdentifier() } );
      }
    } );
  }

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnVacancyAdd', [
    'CnVacancyModelFactory', 'CnSession', '$timeout',
    function( CnVacancyModelFactory, CnSession, $timeout ) {
      return {
        templateUrl: module.getFileUrl( 'add.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnVacancyModelFactory.instance();
        },
        link: function( scope, element ) {
          $timeout( function() {
            // set the datetime in the record and formatted record (if passed here from the calendar)
            scope.model.metadata.getPromise().then( function() {
              if( angular.isDefined( scope.model.addModel.calendarDate ) ) {
                var cnRecordAddScope = cenozo.findChildDirectiveScope( scope, 'cnRecordAdd' );
                if( null == cnRecordAddScope )
                  throw new Error( 'Unable to find vacancy\'s cnRecordAdd scope.' );

                cnRecordAddScope.record.datetime = moment.tz(
                  scope.model.addModel.calendarDate + ' 12:00:00', CnSession.user.timezone );
                cnRecordAddScope.formattedRecord.datetime = CnSession.formatValue(
                  cnRecordAddScope.record.datetime, 'datetime', true );
                delete scope.model.addModel.calendarDate;
              }
            } );
          }, 200 );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnVacancyCalendar', [
    'CnVacancyModelFactory', 'CnAppointmentModelFactory', 'CnSession', 'CnHttpFactory',
    'CnModalConfirmFactory', 'CnModalMessageFactory', 'CnModalInputFactory', '$q',
    function( CnVacancyModelFactory, CnAppointmentModelFactory, CnSession, CnHttpFactory,
              CnModalConfirmFactory, CnModalMessageFactory, CnModalInputFactory, $q ) {

      // Adds a block of vacancies between the start/end times (used below)
      function createVacancyBlock( calendarElement, calendarModel, start, end, operators ) {
        // split into 30-minute chunks
        var datetimeList = [];
        var datetime = angular.copy( start );
        while( datetime < end ) {
          datetimeList.push( angular.copy( datetime ) );
          datetime.add( 30, 'minutes' );
        }

        var eventList = [];
        return $q.all(
          datetimeList.reduce( function( list, datetime ) {
            list.push( CnHttpFactory.instance( {
              path: 'vacancy',
              data: { datetime: datetime.format(), operators: operators },
              onError: function( response ) {
                CnModalMessageFactory.httpError( response );
                revertFunc();
              }
            } ).post().then( function( response ) {
              var id = response.data;
              var newEvent = getEventFromVacancy( {
                id: id,
                getIdentifier: function() { return id; },
                datetime: datetime,
                operators: operators,
                appointments: 0
              }, CnSession.user.timezone );

              // add the new event to the event list and cache
              eventList.push( newEvent );
              calendarModel.cache.push( newEvent );
            } ) );
            return list;
          }, [] )
        ).then( function() {
          calendarElement.fullCalendar( 'renderEvents', eventList );
        } );
      }

      return {
        templateUrl: module.getFileUrl( 'calendar.tpl.html' ),
        restrict: 'E',
        scope: {
          model: '=?',
          preventSiteChange: '@'
        },
        controller: function( $scope, $element ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnVacancyModelFactory.instance();
          $scope.model.calendarModel.heading = $scope.model.site.name.ucWords() + ' Vacancy Calendar';

          angular.extend( $scope.model.calendarModel.settings, {
            eventOverlap: false,
            selectable: $scope.model.getAddEnabled() &&
                        $scope.model.getEditEnabled() &&
                        'vacancy' == $scope.model.getSubjectFromState(),
            editable: $scope.model.getAddEnabled() &&
                      $scope.model.getEditEnabled() &&
                      'vacancy' == $scope.model.getSubjectFromState(),
            selectHelper: true,
            select: function( start, end, jsEvent, view ) {
              // do not process selections in month mode
              if( 'month' == view.type ) return;

              var calendar = $element.find( 'div.calendar' );

              // determine if the selection overlaps any events
              var overlap = false;
              var overlapEventList =
                calendar.fullCalendar( 'clientEvents' ).filter( function( event ) {
                  return event.start >= start && event.end <= end;
                } );
                
              if( 0 == overlapEventList.length ) {
                // the selection did not overlap any events, create vacancies to fill the selection box
                if( 30 > end.diff( start, 'minutes' ) ) {
                  CnModalMessageFactory.instance( {
                    title: 'Unable to create vacancies',
                    message: 'There was a problem creating vacancies, please try again.',
                    error: true
                  } ).show().finally( function() {
                    calendar.fullCalendar( 'unselect' );
                  } );
                } else {
                  var offset = getTimezoneOffset( start, CnSession.user.timezone );
                  start.add( offset, 'minutes' );
                  end.add( offset, 'minutes' );

                  CnModalInputFactory.instance( {
                    title: 'Create Vacancy Block',
                    message: 'Would you like to create a block of vacancies from ' + 
                      CnSession.formatValue( start, 'datetime' ) + ' to ' +
                      CnSession.formatValue( end, 'datetime' ) + '?\n\n' +
                      'If you wish to proceed please provide the number of operators the vacancy has available:',
                    required: true,
                    format: 'integer',
                    minValue: 1,
                    value: 1
                  } ).show().then( function( response ) {
                    if( false !== response && 0 < response )
                      createVacancyBlock( calendar, $scope.model.calendarModel, start, end, response );
                  } ).finally( function() {
                    calendar.fullCalendar( 'unselect' );
                  } );
                }
              } else {
                // the selection overlaps with some event, only delete vacancies which have no appointments
                var removeEventList = overlapEventList.filter( function( event ) {
                  return 0 == event.appointments;
                } );
                console.log( overlapEventList );

                if( 0 == removeEventList ) {
                  CnModalMessageFactory.instance( {
                    title: 'Cannot Delete Vacancies',
                    message: 'None of the ' + overlapEventList.length + ' vacancies you have selected can be ' +
                      'deleted because they already have at least one appointment scheduled.',
                    error: true
                  } ).show().finally( function() {
                    calendar.fullCalendar( 'unselect' );
                  } );
                } else {
                  var message = 'Would you like to delete the ' + removeEventList.length +
                    ' vacancies which you have selected?';
                  if( removeEventList.length != overlapEventList.length )
                    message += '\n\nNote: only vacancies which do not have any appointments will be deleted.';
                  CnModalConfirmFactory.instance( {
                    title: 'Delete Vacancies?',
                    message: message
                  } ).show().then( function( response ) {
                    if( response ) {
                      removeEventList.forEach( function( event ) {
                        CnHttpFactory.instance( {
                          path: 'vacancy/' + event.getIdentifier()
                        } ).delete().then( function() {
                          calendar.fullCalendar( 'removeEvents', event.id );
                        } );
                      } );
                    }
                  } ).finally( function() {
                    calendar.fullCalendar( 'unselect' );
                  } );
                }
              }
            },
            eventDrop: function( event, delta, revertFunc ) {
              // time is in local timezone, convert back to UTC
              var datetime = angular.copy( event.start );
              datetime.add( event.offset, 'minutes' );

              var cacheEvent = $scope.model.calendarModel.cache.findByProperty( 'id', event.id );
              CnHttpFactory.instance( {
                path: 'vacancy/' + event.getIdentifier(),
                data: { datetime: datetime.format() },
                onError: function( response ) {
                  CnModalMessageFactory.httpError( response );
                  revertFunc();
                }
              } ).patch().then( function() {
                // now update this event in the vacancy cache
                cacheEvent.start = event.start;
                cacheEvent.end = event.end;
              } );
            },
            eventResize: function( event, delta, revertFunc ) {
              var calendar = $element.find( 'div.calendar' );

              // time is in local timezone, convert back to UTC
              var datetime = angular.copy( event.start );
              datetime.add( event.offset, 'minutes' );
              var end = angular.copy( event.end );
              end.add( event.offset, 'minutes' );

              if( 30 >= end.diff( datetime, 'minutes' ) ) {
                CnModalMessageFactory.instance( {
                  title: 'Unable to extend vacancy',
                  message: 'There was a problem extending the vacancy, please try again.',
                  error: true
                } ).show().then( revertFunc );
              } else {
                // convert the extended event back to 30 minutes
                var revertEnd = angular.copy( event.start );
                revertEnd.add( '30', 'minutes' );
                event.end = revertEnd;

                datetime.add( 30, 'minutes' ); // skip the first since it already exists
                createVacancyBlock( calendar, $scope.model.calendarModel, datetime, end, event.operators );
              }
            }
          } );
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
  cenozo.providers.directive( 'cnVacancyList', [
    'CnVacancyModelFactory',
    function( CnVacancyModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'list.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnVacancyModelFactory.instance();
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnVacancyView', [
    'CnVacancyModelFactory',
    function( CnVacancyModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'view.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnVacancyModelFactory.instance();
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnVacancyAddFactory', [
    'CnBaseAddFactory', 'CnSession',
    function( CnBaseAddFactory, CnSession ) {
      var object = function( parentModel ) {
        var self = this;
        CnBaseAddFactory.construct( this, parentModel );

        // add the new vacancy's events to the calendar cache
        this.onAdd = function( record ) {
          return this.$$onAdd( record ).then( function() {
            record.getIdentifier = function() { return parentModel.getIdentifierFromRecord( record ); };

            // fill in the user name so that it shows in the calendar
            return parentModel.calendarModel.cache.push( getEventFromVacancy( record, CnSession.user.timezone ) );
          } );
        };
      };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnVacancyCalendarFactory', [
    'CnBaseCalendarFactory', 'CnSession',
    function( CnBaseCalendarFactory, CnSession ) {
      var object = function( parentModel ) {
        var self = this;
        CnBaseCalendarFactory.construct( this, parentModel );

        // remove the day click event
        delete this.settings.dayClick;

        // extend onCalendar to transform vacancies into events
        this.onCalendar = function( replace, minDate, maxDate, ignoreParent ) {
          // we must get the load dates before calling $$onCalendar
          var loadMinDate = self.getLoadMinDate( replace, minDate );
          var loadMaxDate = self.getLoadMaxDate( replace, maxDate );
          // note that we ignore the ignoreParent parameter and always ignore the parent
          return self.$$onCalendar( replace, minDate, maxDate, true ).then( function() {
            self.cache.forEach( function( item, index, array ) {
              array[index] = getEventFromVacancy( item, CnSession.user.timezone );
            } );
          } );
        };
      };

      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnVacancyListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) {
        CnBaseListFactory.construct( this, parentModel );

        // remove the deleted vacancy from the calendar cache
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
  cenozo.providers.factory( 'CnVacancyViewFactory', [
    'CnBaseViewFactory', 'CnSession',
    function( CnBaseViewFactory, CnSession ) {
      var object = function( parentModel, root ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, root );

        // remove the deleted vacancy's events from the calendar cache
        this.onDelete = function() {
          return this.$$onDelete().then( function() {
            parentModel.calendarModel.cache = parentModel.calendarModel.cache.filter( function( e ) {
              return e.getIdentifier() != self.record.getIdentifier();
            } );
          } );
        };

        // remove and re-add the vacancy's events from the calendar cache
        this.onPatch = function( data ) {
          return this.$$onPatch( data ).then( function() {
            var minDate = parentModel.calendarModel.cacheMinDate;
            var maxDate = parentModel.calendarModel.cacheMaxDate;
            parentModel.calendarModel.cache = parentModel.calendarModel.cache.filter( function( e ) {
              return e.getIdentifier() != self.record.getIdentifier();
            } );
            parentModel.calendarModel.cache.push( getEventFromVacancy( self.record, CnSession.user.timezone ) );
          } );
        };

        // editing is only allowed if the vacancy has no appointments
        this.onView = function() {
          return this.$$onView().then( function() {
            self.parentModel.module.getInput( 'datetime' ).constant = 0 < self.record.appointments;
          } );
        };

        this.deferred.promise.then( function() {
          // disable appointment choosing
          self.appointmentModel.getChooseEnabled = function() { return false; };
        } );
      }
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnVacancyModelFactory', [
    'CnBaseModelFactory',
    'CnVacancyAddFactory', 'CnVacancyCalendarFactory',
    'CnVacancyListFactory', 'CnVacancyViewFactory',
    'CnSession', '$state',
    function( CnBaseModelFactory,
              CnVacancyAddFactory, CnVacancyCalendarFactory,
              CnVacancyListFactory, CnVacancyViewFactory,
              CnSession, $state ) {
      var object = function( site ) {
        if( !angular.isObject( site ) || angular.isUndefined( site.id ) )
          throw new Error( 'Tried to create CnVacancyModel without specifying the site.' );

        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.addModel = CnVacancyAddFactory.instance( this );
        this.calendarModel = CnVacancyCalendarFactory.instance( this );
        this.listModel = CnVacancyListFactory.instance( this );
        this.viewModel = CnVacancyViewFactory.instance( this, site.id == CnSession.site.id );
        this.site = site;

        // customize service data
        this.getServiceData = function( type, columnRestrictLists ) {
          var data = this.$$getServiceData( type, columnRestrictLists );
          if( 'calendar' == type ) data.restricted_site_id = self.site.id;
          return data;
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
