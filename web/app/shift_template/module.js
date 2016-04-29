define( [ 'appointment', 'availability', 'capacity', 'shift', 'site' ].reduce( function( list, name ) {
  return list.concat( cenozoApp.module( name ).getRequiredFiles() );
}, [] ), function() {
  'use strict';

  try { var module = cenozoApp.module( 'shift_template', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( module, {
    identifier: {},
    name: {
      singular: 'shift template',
      plural: 'shift templates',
      possessive: 'shift template\'s',
      pluralPossessive: 'shift templates\''
    },
    columnList: {
      start_time: {
        type: 'time',
        title: 'Start Time',
        help: 'When the shift starts'
      },
      end_time: {
        type: 'time',
        title: 'End Time',
        help: 'When the shift ends'
      },
      start_date: {
        type: 'date',
        title: 'Start Date'
      },
      end_date: {
        type: 'date',
        title: 'End Date'
      },
      operators: {
        type: 'string',
        title: 'Operators'
      },
      repeat_type: {
        type: 'string',
        title: 'Repeat'
      },
      week: {
        type: 'string',
        title: 'Week'
      }
    },
    defaultOrder: {
      column: 'end_date',
      reverse: true
    }
  } );

  module.addInputGroup( '', {
    start_time: {
      title: 'Start Time',
      type: 'time',
      max: 'end_time',
      help: 'When the shift starts'
    },
    end_time: {
      title: 'End Time',
      type: 'time',
      min: 'start_time',
      help: 'When the shift ends'
    },
    start_date: {
      title: 'Start Date',
      type: 'date',
      max: 'end_date'
    },
    end_date: {
      title: 'End Date',
      type: 'date',
      min: 'start_date'
    },
    operators: {
      title: 'Operators',
      type: 'string',
      format: 'integer',
      minValue: 0,
      help: "How many operators will be available during this shift"
    },
    repeat_type: {
      title: 'Repeat Type',
      type: 'enum'
    },
    repeat_every: {
      title: 'Repeat Every',
      type: 'string',
      format: 'integer',
      minValue: 1
    },
    days: {
      title: 'Active Days',
      type: 'days'
    }
  } );

  // add an extra operation for each of the appointment-based calendars the user has access to
  [ 'appointment', 'availability', 'capacity', 'shift', 'shift_template' ].forEach( function( name ) {
    var calendarModule = cenozoApp.module( name );
    if( angular.isDefined( calendarModule.actions.calendar ) ) {
      module.addExtraOperation( 'calendar', {
        title: calendarModule.subject.snake.replace( "_", " " ).ucWords(),
        operation: function( $state, model ) {
          $state.go( name + '.calendar', { identifier: model.site.getIdentifier() } );
        },
        classes: 'shift_template' == name ? 'btn-warning' : undefined // highlight current model
      } );
    }
  } );

  module.addExtraOperation( 'list', {
    title: 'Shift Template Calendar',
    operation: function( $state, model ) {
      $state.go( 'shift_template.calendar', { identifier: model.site.getIdentifier() } );
    }
  } );

  // function used by add and view directives (below)
  function onRepeatTypeChange( elementList, newValue, oldValue ) {
    elementList.forEach( function( element ) {
      var el = angular.element( element );
      if( 'weekly' == newValue && el.hasClass( 'collapse' )) {
        el.removeClass( 'collapse' );
      } else if( 'weekly' != newValue && !el.hasClass( 'collapse' ) ) {
        el.addClass( 'collapse' );
      }
    } );
  };

  // converts shift templates into events for the given datespan
  function getEventsFromShiftTemplate( shiftTemplate, minDate, maxDate, timezone ) {
    var eventList = [];

    // no date span means no templates to transform
    if( null == minDate || null == maxDate ) return eventList;

    // do not adjust for daylight savings
    var offset = moment.tz.zone( timezone ).offset( moment( shiftTemplate.start_date ).unix() );

    // replace template record with concrete events
    if( angular.isDefined( shiftTemplate.repeat_type ) ) {
      var itemStartDate = moment( shiftTemplate.start_date );
      var itemStartWeek = itemStartDate.week();
      var itemStartWeekday = itemStartDate.weekday();
      var itemStartDayOfMonth = itemStartDate.date();
      var itemEndDate = moment( shiftTemplate.end_date );
      var baseEvent = {
        getIdentifier: function() { return shiftTemplate.getIdentifier() },
        title: shiftTemplate.operators + ' operator' + ( 1 != shiftTemplate.operators ? 's' : '' )
      };

      if( 'weekly' == shiftTemplate.repeat_type ) {
        // restrict dates to those included by the shift template's repeat_every property
        var weekDateList = [];
        for( var date = moment( minDate );
             date.isBefore( maxDate, 'day' );
             date.add( 1, 'week' ) ) {
          var weekDiff = date.week() - itemStartDate.week();
          if( 0 <= weekDiff && 0 == weekDiff % shiftTemplate.repeat_every ) {

            // create an event for every day of the week the shift template belongs to
            var colon = shiftTemplate.start_time.search( ':' );
            var startDate = moment( date ).hour( shiftTemplate.start_time.substring( 0, colon ) )
                                          .minute( shiftTemplate.start_time.substring( colon+1, colon+3 ) )
                                          .second( 0 )
                                          .millisecond( 0 )
                                          .subtract( offset, 'minutes' )
                                          .date( date.date() );
            colon = shiftTemplate.end_time.search( ':' );

            var endDate = moment( date ).hour( shiftTemplate.end_time.substring( 0, colon ) )
                                        .minute( shiftTemplate.end_time.substring( colon+1, colon+3 ) )
                                        .second( 0 )
                                        .millisecond( 0 )
                                        .subtract( offset, 'minutes' )
                                        .date( date.date() );

            var dayList = [];
            if( shiftTemplate.sunday ) dayList.push( 0 );
            if( shiftTemplate.monday ) dayList.push( 1 );
            if( shiftTemplate.tuesday ) dayList.push( 2 );
            if( shiftTemplate.wednesday ) dayList.push( 3 );
            if( shiftTemplate.thursday ) dayList.push( 4 );
            if( shiftTemplate.friday ) dayList.push( 5 );
            if( shiftTemplate.saturday ) dayList.push( 6 );

            dayList.forEach( function( day ) {
              startDate.day( day );
              endDate.day( day );

              if( !startDate.isBefore( minDate, 'day' ) && !startDate.isAfter( maxDate, 'day' ) &&
                  !startDate.isBefore( itemStartDate, 'day' ) && !startDate.isAfter( itemEndDate, 'day' ) ) {
                eventList.push(
                  angular.extend( {}, baseEvent, {
                    start: moment( startDate ),
                    end: moment( endDate )
                  } )
                );
              }
            } );
          }
        }
      } else {
        var monthDateList = [];
        for( var date = moment( minDate );
             date.format( 'YYYYMM' ) <= maxDate.format( 'YYYYMM' );
             date.add( 1, 'month' ) ) monthDateList.push( moment( date ) );

        if( 'day of month' == shiftTemplate.repeat_type ) {
          // add a monthly event for the day of month
          monthDateList.forEach( function( date ) {
            var colon = shiftTemplate.start_time.search( ':' );
            var startDate = moment( date ).date( itemStartDayOfMonth )
                                          .hour( shiftTemplate.start_time.substring( 0, colon ) )
                                          .minute( shiftTemplate.start_time.substring( colon+1, colon+3 ) )
                                          .second( 0 )
                                          .millisecond( 0 )
                                          .subtract( offset, 'minutes' );
            colon = shiftTemplate.end_time.search( ':' );
            var endDate = moment( date ).date( itemStartDayOfMonth )
                                        .hour( shiftTemplate.end_time.substring( 0, colon ) )
                                        .minute( shiftTemplate.end_time.substring( colon+1, colon+3 ) )
                                        .second( 0 )
                                        .millisecond( 0 )
                                        .subtract( offset, 'minutes' );

            if( !startDate.isBefore( minDate, 'day' ) && !startDate.isAfter( maxDate, 'day' ) ) {
              eventList.push( angular.extend( {}, baseEvent, {
                start: moment( startDate ),
                end: moment( endDate )
              } ) );
            }
          } );
        } else { // 'day of week'
          // add a month event for the day of week
          var weekOfMonth = Math.ceil( itemStartDayOfMonth / 7 );
          monthDateList.forEach( function( date ) {
            var colon = shiftTemplate.start_time.search( ':' );
            var startDate = moment( date ).date( 7*( weekOfMonth - 1 ) )
                                          .weekday( itemStartWeekday )
                                          .hour( shiftTemplate.start_time.substring( 0, colon ) )
                                          .minute( shiftTemplate.start_time.substring( colon+1, colon+3 ) )
                                          .second( 0 )
                                          .millisecond( 0 )
                                          .subtract( offset, 'minutes' );
            colon = shiftTemplate.end_time.search( ':' );
            var endDate = moment( date ).date( 7*( weekOfMonth - 1 ) )
                                        .weekday( itemStartWeekday )
                                        .hour( shiftTemplate.end_time.substring( 0, colon ) )
                                        .minute( shiftTemplate.end_time.substring( colon+1, colon+3 ) )
                                        .second( 0 )
                                        .millisecond( 0 )
                                        .subtract( offset, 'minutes' );

            if( Math.ceil( startDate.date() / 7 ) < weekOfMonth ) {
              startDate.add( 7, 'days' );
              endDate.add( 7, 'days' );
            }

            if( !startDate.isBefore( minDate, 'day' ) && !startDate.isAfter( maxDate, 'day' ) ) {
              eventList.push( angular.extend( {}, baseEvent, {
                start: moment( startDate ),
                end: moment( endDate )
              } ) );
            }
          } );
        }
      }
    } else { eventList.push( shiftTemplate ); }

    return eventList;
  };

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnShiftTemplateAdd', [
    'CnShiftTemplateModelFactory', 'CnSession', '$timeout',
    function( CnShiftTemplateModelFactory, CnSession, $timeout ) {
      return {
        templateUrl: module.getFileUrl( 'add.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnShiftTemplateModelFactory.instance();
        },
        link: function( scope, element ) {
          $timeout( function() {
            // watch the repeat type and hide the repeat_every and days checkboxes
            // if the value changes from "weekly"
            scope.$watch( '$$childHead.record.repeat_type', function( newValue, oldValue ) {
              var elementList = [].filter.call( element[0].querySelectorAll( '.form-group' ), function( el ) {
                return null !== el.querySelector( '#repeat_every' ) || null !== el.querySelector( '#monday' );
              } );
              onRepeatTypeChange( elementList, newValue, oldValue );
            } );

            // set the start date in the record and formatted record (if passed here from the calendar)
            scope.model.metadata.getPromise().then( function() {
              if( angular.isDefined( scope.model.addModel.calendarDate ) ) {
                var cnRecordAddScope = cenozo.findChildDirectiveScope( scope, 'cnRecordAdd' );
                if( null == cnRecordAddScope )
                  throw new Exception( 'Unable to find shift_template\'s cnRecordAdd scope.' );

                cnRecordAddScope.record.start_date = moment( scope.model.addModel.calendarDate ).format();
                cnRecordAddScope.formattedRecord.start_date = CnSession.formatValue(
                  scope.model.addModel.calendarDate, 'date', true );
                delete scope.model.addModel.calendarDate;
              }
            } );
          }, 200 );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnShiftTemplateCalendar', [
    'CnShiftTemplateModelFactory',
    'CnAppointmentModelFactory', 'CnAvailabilityModelFactory',
    'CnCapacityModelFactory', 'CnShiftModelFactory',
    'CnSession',
    function( CnShiftTemplateModelFactory,
              CnAppointmentModelFactory, CnAvailabilityModelFactory,
              CnCapacityModelFactory, CnShiftModelFactory,
              CnSession ) {
      return {
        templateUrl: module.getFileUrl( 'calendar.tpl.html' ),
        restrict: 'E',
        scope: {
          model: '=?',
          preventSiteChange: '@'
        },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnShiftTemplateModelFactory.instance();
          $scope.model.calendarModel.heading = $scope.model.site.name.ucWords() + ' Shift Template Calendar';
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
  cenozo.providers.directive( 'cnShiftTemplateList', [
    'CnShiftTemplateModelFactory', 'CnSession',
    function( CnShiftTemplateModelFactory, CnSession ) {
      return {
        templateUrl: module.getFileUrl( 'list.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnShiftTemplateModelFactory.instance();
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnShiftTemplateView', [
    'CnShiftTemplateModelFactory', 'CnSession', '$timeout',
    function( CnShiftTemplateModelFactory, CnSession, $timeout ) {
      return {
        templateUrl: module.getFileUrl( 'view.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnShiftTemplateModelFactory.instance();
        },
        link: function( scope, element ) {
          // watch the repeat type and hide the repeat_every and days checkboxes if the value changes from "weekly"
          $timeout( function() {
            scope.$watch( 'model.viewModel.record.repeat_type', function( newValue, oldValue ) {
              var elementList = [].filter.call( element[0].querySelectorAll( '.form-group' ), function( el ) {
                return null !== el.querySelector( '#repeat_every' ) || null !== el.querySelector( '#monday' );
              } );
              onRepeatTypeChange( elementList, newValue, oldValue );
            } )
          }, 200 );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnShiftTemplateAddFactory', [
    'CnBaseAddFactory', 'CnSession',
    function( CnBaseAddFactory, CnSession ) {
      var object = function( parentModel ) {
        var self = this;
        CnBaseAddFactory.construct( this, parentModel );

        // add the new shift template's events to the calendar cache
        this.onAdd = function( record ) {
          // shift templates are tricky: we need to ignore the DST shift in order for them to display correctly
          // across both standard and daylight savings time
          if( moment().tz( CnSession.user.timezone ).isDST() ) {
            record.start_time = moment(
              new Date( moment().format( 'YYYY-MM-DD' ) + 'T' + record.start_time + 'Z' )
            ).add( 1, 'hours' ).format( 'HH:mm' );
            record.end_time = moment(
              new Date( moment().format( 'YYYY-MM-DD' ) + 'T' + record.end_time + 'Z' )
            ).add( 1, 'hours' ).format( 'HH:mm' );
          }

          return this.$$onAdd( record ).then( function() {
            record.getIdentifier = function() { return parentModel.getIdentifierFromRecord( record ); };
            var minDate = parentModel.calendarModel.cacheMinDate;
            var maxDate = parentModel.calendarModel.cacheMaxDate;
            parentModel.calendarModel.cache = parentModel.calendarModel.cache.concat(
              getEventsFromShiftTemplate( record, minDate, maxDate, CnSession.user.timezone )
            );
          } );
        };
      };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnShiftTemplateCalendarFactory', [
    'CnBaseCalendarFactory', 'CnSession',
    function( CnBaseCalendarFactory, CnSession ) {
      var object = function( parentModel ) {
        var self = this;
        CnBaseCalendarFactory.construct( this, parentModel );

        // extend onCalendar to transform templates into events
        this.onCalendar = function( replace, minDate, maxDate, ignoreParent ) {
          // we must get the load dates before calling $$onCalendar
          var loadMinDate = self.getLoadMinDate( replace, minDate );
          var loadMaxDate = self.getLoadMaxDate( replace, maxDate );
          return self.$$onCalendar( replace, minDate, maxDate, ignoreParent ).then( function() {
            self.cache = self.cache.reduce( function( cache, item ) {
              return cache.concat(
                getEventsFromShiftTemplate( item, loadMinDate, loadMaxDate, CnSession.user.timezone )
              );
            }, [] );
            // make sure we make the calendar's timezone the site's (instead of the user's)
            self.settings.timezone = CnSession.site.timezone;
          } );
        };
      };

      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnShiftTemplateListFactory', [
    'CnBaseListFactory', 'CnSession',
    function( CnBaseListFactory, CnSession ) {
      var object = function( parentModel ) {
        var self = this;
        CnBaseListFactory.construct( this, parentModel );

        // adjust for DST
        this.onList = function( replace ) {
          return this.$$onList( replace ).then( function() {
            // shift templates are tricky: we need to ignore the DST shift in order for them to display correctly
            // across both standard and daylight savings time
            if( moment().tz( CnSession.user.timezone ).isDST() ) {
              self.cache.filter( function( record ) {
                return true !== record.adjustedForDST;
              } ).forEach( function( record ) {
                record.start_time = moment(
                  new Date( moment().format( 'YYYY-MM-DD' ) + 'T' + record.start_time + 'Z' )
                ).subtract( 1, 'hours' ).format( 'HH:mm' );

                record.end_time = moment(
                  new Date( moment().format( 'YYYY-MM-DD' ) + 'T' + record.end_time + 'Z' )
                ).subtract( 1, 'hours' ).format( 'HH:mm' );

                record.adjustedForDST = true;
              } );
            }
          } );
        };

        // remove the deleted shift template from the calendar cache
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
  cenozo.providers.factory( 'CnShiftTemplateViewFactory', [
    'CnBaseViewFactory', 'CnSession',
    function( CnBaseViewFactory, CnSession ) {
      var object = function( parentModel, root ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, root );

        // adjust for DST
        this.onView = function() {
          return this.$$onView().then( function() {
            // shift templates are tricky: we need to ignore the DST shift in order for them to display correctly
            // across both standard and daylight savings time
            if( moment().tz( CnSession.user.timezone ).isDST() ) {
              self.record.start_time = moment(
                new Date( moment().format( 'YYYY-MM-DD' ) + 'T' + self.record.start_time + 'Z' )
              ).subtract( 1, 'hours' ).format( 'HH:mm' );
              self.backupRecord.start_time = self.record.start_time;
              self.updateFormattedRecord( 'start_time' );

              self.record.end_time = moment(
                new Date( moment().format( 'YYYY-MM-DD' ) + 'T' + self.record.end_time + 'Z' )
              ).subtract( 1, 'hours' ).format( 'HH:mm' );
              self.backupRecord.end_time = self.record.end_time;
              self.updateFormattedRecord( 'end_time' );
            }
          } );
        };

        // remove the deleted shift template's events from the calendar cache
        this.onDelete = function() {
          return this.$$onDelete().then( function() {
            parentModel.calendarModel.cache = parentModel.calendarModel.cache.filter( function( e ) {
              return e.getIdentifier() != self.record.getIdentifier();
            } );
          } );
        };

        // remove and re-add the shift template's events from the calendar cache
        this.onPatch = function( data ) {
          return this.$$onPatch( data ).then( function() {
            var minDate = parentModel.calendarModel.cacheMinDate;
            var maxDate = parentModel.calendarModel.cacheMaxDate;
            parentModel.calendarModel.cache = parentModel.calendarModel.cache.filter( function( e ) {
              return e.getIdentifier() != self.record.getIdentifier();
            } );
            parentModel.calendarModel.cache = parentModel.calendarModel.cache.concat(
              getEventsFromShiftTemplate( self.record, minDate, maxDate, CnSession.user.timezone )
            );
          } );
        };
      }
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnShiftTemplateModelFactory', [
    'CnBaseModelFactory',
    'CnShiftTemplateAddFactory', 'CnShiftTemplateCalendarFactory',
    'CnShiftTemplateListFactory', 'CnShiftTemplateViewFactory',
    'CnSession', '$state',
    function( CnBaseModelFactory,
              CnShiftTemplateAddFactory, CnShiftTemplateCalendarFactory,
              CnShiftTemplateListFactory, CnShiftTemplateViewFactory,
              CnSession, $state ) {
      var object = function( site ) {
        if( !angular.isObject( site ) || angular.isUndefined( site.id ) )
          throw new Error( 'Tried to create CnShiftTemplateModel without specifying the site.' );

        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.addModel = CnShiftTemplateAddFactory.instance( this );
        this.calendarModel = CnShiftTemplateCalendarFactory.instance( this );
        this.listModel = CnShiftTemplateListFactory.instance( this );
        this.viewModel = CnShiftTemplateViewFactory.instance( this, site.id == CnSession.site.id );
        this.site = site;
        
        // customize service data
        this.getServiceData = function( type, columnRestrictLists ) {
          var data = this.$$getServiceData( type, columnRestrictLists );
          if( 'calendar' == type ) data.restricted_site_id = self.site.id;
          return data;
        };

        this.getMetadata = function() {
          return this.$$getMetadata().then( function() {
            self.metadata.columnList.repeat_type.enumList.forEach( function( item, index, array ) {
              if( 'day of' == item.name.substring( 0, 6 ) ) array[index].name = 'monthly (' + item.name + ')';
            } );
          } );
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
