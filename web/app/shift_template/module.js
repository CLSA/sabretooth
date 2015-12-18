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

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnShiftTemplateAdd', [
    'CnShiftTemplateModelFactory',
    function( CnShiftTemplateModelFactory ) {
      return {
        templateUrl: url + 'add.tpl.html',
        restrict: 'E',
        controller: function( $scope ) {
          $scope.model = CnShiftTemplateModelFactory.root;
          $scope.record = {};
          $scope.model.addModel.onNew( $scope.record ).then( function() {
            $scope.model.setupBreadcrumbTrail( 'add' );
          } );
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
    'CnShiftTemplateModelFactory',
    function( CnShiftTemplateModelFactory ) {
      return {
        templateUrl: url + 'view.tpl.html',
        restrict: 'E',
        controller: function( $scope ) {
          $scope.model = CnShiftTemplateModelFactory.root;
          $scope.model.viewModel.onView().then( function() {
            $scope.model.setupBreadcrumbTrail( 'view' );
          } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnShiftTemplateAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      var object = function( parentModel ) { CnBaseAddFactory.construct( this, parentModel ); };
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

        // extend onList to format templates into events
        this.onList = function( replace, minDate, maxDate ) {
          return self.$$onList( replace, minDate, maxDate ).then( function() {
            var weekDateList = [];
            for( var date = moment( minDate ); !date.isAfter( maxDate ); date.add( 1, 'week' ) )
              weekDateList.push( moment( date ) );

            var monthDateList = [];
            for( var date = moment( minDate );
                 date.format( 'YYYYMM' ) <= maxDate.format( 'YYYYMM' );
                 date.add( 1, 'month' ) ) monthDateList.push( moment( date ) );

            self.cache = self.cache.reduce( function( cache, item ) {
              // replace template record with concrete events
              if( angular.isDefined( item.repeat_type ) ) {
                if( 'weekly' == item.repeat_type ) {
                  // restrict dates to those included by the shift template's repeat_every property
                  weekDateList.filter( function( date ) {
                    return 0 == ( date.isoWeek() - moment( item.start_date ).isoWeek() ) % item.repeat_every;
                  }, [] ).forEach( function( date ) {
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

                    if( item.monday ) cache.push( {
                      getIdentifier: item.getIdentifier,
                      title: item.title,
                      start: moment( startDate ).day( 1 ).format(),
                      end: moment( endDate ).day( 1 ).format()
                    } );
                    if( item.tuesday ) cache.push( {
                      getIdentifier: item.getIdentifier,
                      title: item.title,
                      start: moment( startDate ).day( 2 ).format(),
                      end: moment( endDate ).day( 2 ).format()
                    } );
                    if( item.wednesday ) cache.push( {
                      getIdentifier: item.getIdentifier,
                      title: item.title,
                      start: moment( startDate ).day( 3 ).format(),
                      end: moment( endDate ).day( 3 ).format()
                    } );
                    if( item.thursday ) cache.push( {
                      getIdentifier: item.getIdentifier,
                      title: item.title,
                      start: moment( startDate ).day( 4 ).format(),
                      end: moment( endDate ).day( 4 ).format()
                    } );
                    if( item.friday ) cache.push( {
                      getIdentifier: item.getIdentifier,
                      title: item.title,
                      start: moment( startDate ).day( 5 ).format(),
                      end: moment( endDate ).day( 5 ).format()
                    } );
                    if( item.saturday ) cache.push( {
                      getIdentifier: item.getIdentifier,
                      title: item.title,
                      start: moment( startDate ).day( 6 ).format(),
                      end: moment( endDate ).day( 6 ).format()
                    } );
                    if( item.sunday ) cache.push( {
                      getIdentifier: item.getIdentifier,
                      title: item.title,
                      start: moment( startDate ).day( 7 ).format(),
                      end: moment( endDate ).day( 7 ).format()
                    } );
                  } );
                } else if( 'day of month' == item.repeat_type ) {
                  // add the day of month for each month
                  monthDateList.forEach( function( date ) {
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
                    var dateNumber = moment( item.start_date ).date();

                    cache.push( {
                      getIdentifier: item.getIdentifier,
                      title: item.title,
                      start: moment( startDate ).date( dateNumber ).format(),
                      end: moment( endDate ).date( dateNumber ).format()
                    } );
                  } );
                } else { // 'day of week'
                  // TODO: implement
                  console.log( 'TODO: day of week', item );
                }
              } else { cache.push( item ); }
              return cache;
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
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnShiftTemplateViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      var args = arguments;
      var CnBaseViewFactory = args[0];
      var object = function( parentModel, root ) { CnBaseViewFactory.construct( this, parentModel, root ); }
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnShiftTemplateModelFactory', [
    'CnBaseModelFactory',
    'CnShiftTemplateAddFactory', 'CnShiftTemplateCalendarFactory',
    'CnShiftTemplateListFactory', 'CnShiftTemplateViewFactory',
    function( CnBaseModelFactory,
              CnShiftTemplateAddFactory, CnShiftTemplateCalendarFactory,
              CnShiftTemplateListFactory, CnShiftTemplateViewFactory ) {
      var object = function( root ) {
        var self = this;
        CnBaseModelFactory.construct( this, cenozoApp.module( 'shift_template' ) );
        this.addModel = CnShiftTemplateAddFactory.instance( this );
        this.calendarModel = CnShiftTemplateCalendarFactory.instance( this );
        this.listModel = CnShiftTemplateListFactory.instance( this );
        this.viewModel = CnShiftTemplateViewFactory.instance( this, root );
      };

      return {
        root: new object( true ),
        instance: function() { return new object(); }
      };
    }
  ] );

} );
