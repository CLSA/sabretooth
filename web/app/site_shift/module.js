define( cenozoApp.module( 'shift' ).getRequiredFiles().concat(
          cenozoApp.module( 'shift_template' ).getRequiredFiles()
        ), function() {
  'use strict';

  try { var module = cenozoApp.module( 'site_shift', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( module, {
    identifier: {},
    name: {
      singular: 'site shift',
      plural: 'site shifts',
      possessive: 'site shift\'s',
      pluralPossessive: 'site shifts\''
    }
  } );

  module.addExtraOperation(
    'calendar',
    'Shift Template Calendar',
    function( calendarModel, $state ) { $state.go( 'shift_template.calendar' ); }
  );

  module.addExtraOperation(
    'calendar',
    'Shift Calendar',
    function( calendarModel, $state ) { $state.go( 'shift.calendar' ); }
  );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnSiteShiftCalendar', [
    'CnSiteShiftModelFactory', 'CnShiftModelFactory', 'CnShiftTemplateModelFactory', 'CnSession',
    function( CnSiteShiftModelFactory, CnShiftModelFactory, CnShiftTemplateModelFactory, CnSession ) {
      return {
        templateUrl: module.url + 'calendar.tpl.html',
        restrict: 'E',
        controller: function( $scope ) {
          $scope.model = CnSiteShiftModelFactory.root;
          CnSession.promise.then( function() { $scope.timezone = CnSession.site.timezone; } );
          $scope.model.setupBreadcrumbTrail( 'calendar' );
        },
        link: function( scope ) {
          // synchronize shift and site_shift calendar date and view
          scope.$watch( 'model.calendarModel.currentDate', function( date ) {
            var shiftCalendarModel = CnShiftModelFactory.root.calendarModel;
            if( !shiftCalendarModel.currentDate.isSame( date, 'day' ) ) 
              shiftCalendarModel.currentDate = date;
            var shiftTemplateCalendarModel = CnShiftTemplateModelFactory.root.calendarModel;
            if( !shiftTemplateCalendarModel.currentDate.isSame( date, 'day' ) )
              shiftTemplateCalendarModel.currentDate = date;
          } );
          scope.$watch( 'model.calendarModel.currentView', function( view ) {
            var shiftCalendarModel = CnShiftModelFactory.root.calendarModel;
            if( shiftCalendarModel.currentView != view )
              shiftCalendarModel.currentView = view;
            var shiftTemplateCalendarModel = CnShiftTemplateModelFactory.root.calendarModel;
            if( shiftTemplateCalendarModel.currentView != view )
              shiftTemplateCalendarModel.currentView = view;
          } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnSiteShiftCalendarFactory', [
    'CnBaseCalendarFactory', 'CnShiftModelFactory', 'CnShiftTemplateModelFactory', 'CnSession', '$q',
    function( CnBaseCalendarFactory, CnShiftModelFactory, CnShiftTemplateModelFactory, CnSession, $q ) {
      var object = function( parentModel ) {
        var self = this;
        CnBaseCalendarFactory.construct( this, parentModel );

        // extend onList to transform templates into events
        this.onList = function( replace, minDate, maxDate ) {
          // we must get the load dates before getting shifts and shift templates
          var loadMinDate = self.getLoadMinDate( replace, minDate );
          var loadMaxDate = self.getLoadMaxDate( replace, maxDate );

          // instead of calling $$onList we're going to get all shifts and shift templates instead
          var shiftCalendarModel = CnShiftModelFactory.root.calendarModel;
          var shiftTemplateCalendarModel = CnShiftTemplateModelFactory.root.calendarModel;
          return $q.all( [
            shiftCalendarModel.onList( replace, minDate, maxDate ),
            shiftTemplateCalendarModel.onList( replace, minDate, maxDate )
          ] ).then( function() {
            // start with all shift events
            self.cache = shiftCalendarModel.cache;

            // now add any shift templates on days that have no shift events
            var shiftTemplateEvents = [];
            shiftTemplateCalendarModel.cache.forEach( function( shiftTemplate ) {
              if( !self.cache.some(
                    function( shift ) { return shift.start.isSame( shiftTemplate.start, 'day' ); }
                  ) ) shiftTemplateEvents.push( shiftTemplate );
            } );
            self.cache = self.cache.concat( shiftTemplateEvents );
          } );
        };
      };

      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnSiteShiftModelFactory', [
    'CnBaseModelFactory', 'CnSiteShiftCalendarFactory', 'CnSession', '$state',
    function( CnBaseModelFactory, CnSiteShiftCalendarFactory, CnSession, $state ) {
      var object = function( root ) {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.calendarModel = CnSiteShiftCalendarFactory.instance( this );
      };

      return {
        root: new object( true ),
        instance: function() { return new object(); }
      };
    }
  ] );

} );
