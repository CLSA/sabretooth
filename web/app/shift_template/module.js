define( function() {
  'use strict';

  try { var url = cenozoApp.module( 'shift_template', true ).url; } catch( err ) { console.warn( err ); return; }
  angular.extend( cenozoApp.module( 'shift_template' ), {
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
        title: 'Start Time'
      },
      end_time: {
        type: 'time',
        title: 'End Time'
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

  cenozoApp.module( 'shift_template' ).addInputGroup( null, {
    start_time: {
      title: 'Start Time',
      type: 'time',
      max: 'end_time',
      help: 'When the shift starts (local site\'s timezone)'
    },
    end_time: {
      title: 'End Time',
      type: 'time',
      min: 'start_time',
      help: 'When the shift ends (local site\'s timezone)'
    },
    start_date: {
      title: 'Start Date',
      type: 'date'
    },
    end_date: {
      title: 'End Date',
      type: 'date'
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

  cenozoApp.module( 'shift_template' ).addViewOperation( 'Shift Template Calendar', function( viewModel, $state ) {
    $state.go( 'shift_template.calendar' );
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
  function getEventsFromShiftTemplate( item, minDate, maxDate ) {
    var eventList = [];

    // no date span means no templates to transform
    if( null == minDate || null == maxDate ) return eventList;

    // replace template record with concrete events
    if( angular.isDefined( item.repeat_type ) ) {
      var itemStartDate = moment( item.start_date );
      var itemStartWeek = itemStartDate.week();
      var itemStartWeekday = itemStartDate.weekday();
      var itemStartDayOfMonth = itemStartDate.date();
      var itemEndDate = moment( item.end_date );
      var baseEvent = {
        getIdentifier: function() { return item.getIdentifier() },
        title: item.operators + ' operator' + ( 1 != item.operators ? 's' : '' )
      };

      if( 'weekly' == item.repeat_type ) {
        // restrict dates to those included by the shift template's repeat_every property
        var weekDateList = [];
        for( var date = moment( minDate ); !date.isAfter( maxDate ); date.add( item.repeat_every, 'week' ) ) {
          if( !date.weekday( itemStartDate.weekday() ).isBefore( itemStartDate ) ) {
            // create an event for every day of the week the shift template belongs to
            var colon = item.start_time.search( ':' );
            var startDate = moment( date ).hour( item.start_time.substring( 0, colon ) )
                                          .minute( item.start_time.substring( colon+1, colon+3 ) )
                                          .second( 0 )
                                          .millisecond( 0 );
            colon = item.end_time.search( ':' );
            var endDate = moment( date ).hour( item.end_time.substring( 0, colon ) )
                                        .minute( item.end_time.substring( colon+1, colon+3 ) )
                                        .second( 0 )
                                        .millisecond( 0 );

            if( item.sunday ) {
              startDate.day( 0 );
              endDate.day( 0 );
              if( !startDate.isBefore( minDate ) && !startDate.isAfter( maxDate ) ) {
                eventList.push( angular.extend( {}, baseEvent, {
                  start: startDate.format(),
                  end: endDate.format()
                } ) );
              }
            }
            if( item.monday ) {
              startDate.day( 1 );
              endDate.day( 1 );
              if( !startDate.isBefore( minDate ) && !startDate.isAfter( maxDate ) ) {
                eventList.push( angular.extend( {}, baseEvent, {
                  start: startDate.format(),
                  end: endDate.format()
                } ) );
              }
            }
            if( item.tuesday ) {
              startDate.day( 2 );
              endDate.day( 2 );
              if( !startDate.isBefore( minDate ) && !startDate.isAfter( maxDate ) ) {
                eventList.push( angular.extend( {}, baseEvent, {
                  start: startDate.format(),
                  end: endDate.format()
                } ) );
              }
            }
            if( item.wednesday ) {
              startDate.day( 3 );
              endDate.day( 3 );
              if( !startDate.isBefore( minDate ) && !startDate.isAfter( maxDate ) ) {
                eventList.push( angular.extend( {}, baseEvent, {
                  start: startDate.format(),
                  end: endDate.format()
                } ) );
              }
            }
            if( item.thursday ) {
              startDate.day( 4 );
              endDate.day( 4 );
              if( !startDate.isBefore( minDate ) && !startDate.isAfter( maxDate ) ) {
                eventList.push( angular.extend( {}, baseEvent, {
                  start: startDate.format(),
                  end: endDate.format()
                } ) );
              }
            }
            if( item.friday ) {
              startDate.day( 5 );
              endDate.day( 5 );
              if( !startDate.isBefore( minDate ) && !startDate.isAfter( maxDate ) ) {
                eventList.push( angular.extend( {}, baseEvent, {
                  start: startDate.format(),
                  end: endDate.format()
                } ) );
              }
            }
            if( item.saturday ) {
              startDate.day( 6 );
              endDate.day( 6 );
              if( !startDate.isBefore( minDate ) && !startDate.isAfter( maxDate ) ) {
                eventList.push( angular.extend( {}, baseEvent, {
                  start: startDate.format(),
                  end: endDate.format()
                } ) );
              }
            }
          }
        }
      } else {
        var monthDateList = [];
        for( var date = moment( minDate );
             date.format( 'YYYYMM' ) <= maxDate.format( 'YYYYMM' );
             date.add( 1, 'month' ) ) monthDateList.push( moment( date ) );

        if( 'day of month' == item.repeat_type ) {
          // add a monthly event for the day of month
          monthDateList.forEach( function( date ) {
            var colon = item.start_time.search( ':' );
            var startDate = moment( date ).date( itemStartDayOfMonth )
                                          .hour( item.start_time.substring( 0, colon ) )
                                          .minute( item.start_time.substring( colon+1, colon+3 ) )
                                          .second( 0 )
                                          .millisecond( 0 );
            colon = item.end_time.search( ':' );
            var endDate = moment( date ).date( itemStartDayOfMonth )
                                        .hour( item.end_time.substring( 0, colon ) )
                                        .minute( item.end_time.substring( colon+1, colon+3 ) )
                                        .second( 0 )
                                        .millisecond( 0 );

            if( !startDate.isBefore( minDate ) && !startDate.isAfter( maxDate ) ) {
              eventList.push( angular.extend( {}, baseEvent, {
                start: startDate.format(),
                end: endDate.format()
              } ) );
            }
          } );
        } else { // 'day of week'
          // add a month event for the day of week
          var weekOfMonth = Math.ceil( itemStartDayOfMonth / 7 );
          monthDateList.forEach( function( date ) {
            var colon = item.start_time.search( ':' );
            var startDate = moment( date ).date( 7*( weekOfMonth - 1 ) )
                                          .weekday( itemStartWeekday )
                                          .hour( item.start_time.substring( 0, colon ) )
                                          .minute( item.start_time.substring( colon+1, colon+3 ) )
                                          .second( 0 )
                                          .millisecond( 0 );
            colon = item.end_time.search( ':' );
            var endDate = moment( date ).date( 7*( weekOfMonth - 1 ) )
                                        .weekday( itemStartWeekday )
                                        .hour( item.end_time.substring( 0, colon ) )
                                        .minute( item.end_time.substring( colon+1, colon+3 ) )
                                        .second( 0 )
                                        .millisecond( 0 );

            if( Math.ceil( startDate.date() / 7 ) < weekOfMonth ) {
              startDate.add( 7, 'days' );
              endDate.add( 7, 'days' );
            }

            if( !startDate.isBefore( minDate ) && !startDate.isAfter( maxDate ) ) {
              eventList.push( angular.extend( {}, baseEvent, {
                start: startDate.format(),
                end: endDate.format()
              } ) );
            }
          } );
        }
      }
    } else { eventList.push( item ); }

    return eventList;
  };

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnShiftTemplateAdd', [
    'CnShiftTemplateModelFactory', 'CnSession', '$timeout',
    function( CnShiftTemplateModelFactory, CnSession, $timeout ) {
      return {
        templateUrl: url + 'add.tpl.html',
        restrict: 'E',
        controller: function( $scope ) {
          $scope.model = CnShiftTemplateModelFactory.root;
          $scope.record = {};
          $scope.model.addModel.onNew( $scope.record ).then( function() {
            if( null != $scope.model.addModel.calendarStartDate ) {
              var addDirective = $scope.$$childHead;
              // set the start date in the record and formatted record
              $scope.record.start_date = moment( $scope.model.addModel.calendarStartDate ).format();
              addDirective.formattedRecord.start_date = CnSession.formatValue(
                $scope.model.addModel.calendarStartDate, 'date', true );
              $scope.model.addModel.calendarStartDate = null;
            }
            $scope.model.setupBreadcrumbTrail( 'add' );
          } );
        },
        link: function( scope, element ) {
          // watch the repeat type and hide the repeat_every and days checkboxes if the value changes from "weekly"
          $timeout( function() {
            scope.$watch( 'record.repeat_type', function( newValue, oldValue ) {
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
  cenozo.providers.directive( 'cnShiftTemplateCalendar', [
    'CnShiftTemplateModelFactory',
    function( CnShiftTemplateModelFactory ) {
      return {
        templateUrl: url + 'calendar.tpl.html',
        restrict: 'E',
        controller: function( $scope ) {
          $scope.model = CnShiftTemplateModelFactory.root;
          $scope.model.setupBreadcrumbTrail( 'calendar' );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnShiftTemplateList', [
    'CnShiftTemplateModelFactory',
    function( CnShiftTemplateModelFactory ) {
      return {
        templateUrl: url + 'list.tpl.html',
        restrict: 'E',
        controller: function( $scope ) {
          $scope.model = CnShiftTemplateModelFactory.root;
          $scope.model.listModel.onList( true ).then( function() {
            $scope.model.setupBreadcrumbTrail( 'list' );
          } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnShiftTemplateView', [
    'CnShiftTemplateModelFactory', '$timeout',
    function( CnShiftTemplateModelFactory, $timeout ) {
      return {
        templateUrl: url + 'view.tpl.html',
        restrict: 'E',
        controller: function( $scope ) {
          $scope.model = CnShiftTemplateModelFactory.root;
          $scope.model.viewModel.onView().then( function() {
            $scope.model.setupBreadcrumbTrail( 'view' );
          } );
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
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      var object = function( parentModel ) {
        var self = this;
        CnBaseAddFactory.construct( this, parentModel );

        // used to communicate that a new shift template is being added from the calendar
        this.calendarStartDate = null;

        // add the new shift template's events to the calendar cache
        this.onAdd = function( record ) {
          return this.$$onAdd( record ).then( function() {
            record.getIdentifier = function() { return parentModel.getIdentifierFromRecord( record ); };
            var minDate = parentModel.calendarModel.cacheMinDate;
            var maxDate = parentModel.calendarModel.cacheMaxDate;
            parentModel.calendarModel.cache = parentModel.calendarModel.cache.concat(
              getEventsFromShiftTemplate( record, minDate, maxDate )
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

        // extend onList to transform templates into events
        this.onList = function( replace, minDate, maxDate ) {
          // we must get the load dates before calling $$onList
          var loadMinDate = self.getLoadMinDate( replace, minDate );
          var loadMaxDate = self.getLoadMaxDate( replace, maxDate );
          return self.$$onList( replace, minDate, maxDate ).then( function() {
            self.cache = self.cache.reduce( function( cache, item ) {
              return cache.concat( getEventsFromShiftTemplate( item, loadMinDate, loadMaxDate ) );
            }, [] );
          } );
        };
      };

      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnShiftTemplateListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) {
        CnBaseListFactory.construct( this, parentModel );

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
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      var args = arguments;
      var CnBaseViewFactory = args[0];
      var object = function( parentModel, root ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, root );

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
              getEventsFromShiftTemplate( self.record, minDate, maxDate )
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
    '$state',
    function( CnBaseModelFactory,
              CnShiftTemplateAddFactory, CnShiftTemplateCalendarFactory,
              CnShiftTemplateListFactory, CnShiftTemplateViewFactory,
              $state ) {
      var object = function( root ) {
        var self = this;
        CnBaseModelFactory.construct( this, cenozoApp.module( 'shift_template' ) );
        this.addModel = CnShiftTemplateAddFactory.instance( this );
        this.calendarModel = CnShiftTemplateCalendarFactory.instance( this );
        this.listModel = CnShiftTemplateListFactory.instance( this );
        this.viewModel = CnShiftTemplateViewFactory.instance( this, root );

        this.getMetadata = function() {
          return this.$$getMetadata().then( function() {
            self.metadata.columnList.repeat_type.enumList.forEach( function( item, index, array ) {
              if( 'day of' == item.name.substring( 0, 6 ) ) array[index].name = 'monthly (' + item.name + ')';
            } );
          } );
        };
      };

      return {
        root: new object( true ),
        instance: function() { return new object(); }
      };
    }
  ] );

} );
