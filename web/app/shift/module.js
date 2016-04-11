define( [ 'appointment', 'availability', 'capacity', 'shift_template', 'site' ].reduce( function( list, name ) {
  return list.concat( cenozoApp.module( name ).getRequiredFiles() );
}, [] ), function() {
  'use strict';

  try { var module = cenozoApp.module( 'shift', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( module, {
    identifier: {},
    name: {
      singular: 'shift',
      plural: 'shifts',
      possessive: 'shift\'s',
      pluralPossessive: 'shifts\''
    },
    columnList: {
      username: {
        column: 'user.name',
        title: 'User'
      },
      firstname: {
        column: 'user.first_name',
        title: 'First Name'
      },
      lastname: {
        column: 'user.last_name',
        title: 'Last Name'
      },
      start_datetime: {
        type: 'datetime',
        title: 'Start Date & Time'
      },
      end_datetime: {
        type: 'datetime',
        title: 'End Date & Time'
      }
    },
    defaultOrder: {
      column: 'end_datetime',
      reverse: true
    }
  } );

  module.addInputGroup( null, {
    user_id: {
      title: 'User',
      type: 'lookup-typeahead',
      typeahead: {
        table: 'user',
        select: 'CONCAT( first_name, " ", last_name, " (", name, ")" )',
        where: [ 'first_name', 'last_name', 'name' ]
      }
    },
    start_datetime: {
      title: 'Start Date & Time',
      type: 'datetime',
      max: 'end_datetime'
    },
    end_datetime: {
      title: 'End Date & Time',
      type: 'datetime',
      min: 'start_datetime'
    },
  } );

  // converts shifts into events
  function getEventFromShift( shift, timezone ) {
    if( angular.isDefined( shift.start ) && angular.isDefined( shift.end ) ) {
      return shift;
    } else {
      var date = moment( shift.start_datetime );
      var offset = moment.tz.zone( timezone ).offset( date.unix() );

      // adjust the appointment for daylight savings time
      if( date.tz( timezone ).isDST() ) offset += -60;

      return {
        getIdentifier: function() { return shift.getIdentifier() },
        title: shift.username,
        start: moment( shift.start_datetime ).subtract( offset, 'minutes' ),
        end: moment( shift.end_datetime ).subtract( offset, 'minutes' )
      };
    }
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
        classes: 'shift' == name ? 'btn-warning' : undefined // highlight current model
      } );
    }
  } );

  module.addExtraOperation( 'list', {
    title: 'Shift Calendar',
    operation: function( $state, model ) {
      $state.go( 'shift.calendar', { identifier: model.site.getIdentifier() } );
    }
  } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnShiftAdd', [
    'CnShiftModelFactory', 'CnSession', '$timeout',
    function( CnShiftModelFactory, CnSession, $timeout ) {
      return {
        templateUrl: module.getFileUrl( 'add.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnShiftModelFactory.instance();
        },
        link: function( scope, element ) {
          $timeout( function() {
            // set the start date in the record and formatted record (if passed here from the calendar)
            scope.model.metadata.getPromise().then( function() {
              if( angular.isDefined( scope.model.addModel.calendarDate ) ) {
                var cnRecordAddScope = cenozo.findChildDirectiveScope( scope, 'cnRecordAdd' );
                if( null == cnRecordAddScope )
                  throw new Exception( 'Unable to find shift\'s cnRecordAdd scope.' );

                cnRecordAddScope.record.start_datetime =
                  moment( scope.model.addModel.calendarDate ).format();
                cnRecordAddScope.formattedRecord.start_datetime = CnSession.formatValue(
                  scope.model.addModel.calendarDate, 'datetime', true );
                delete scope.model.addModel.calendarDate;
              }
            } );
          }, 200 );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnShiftCalendar', [
    'CnShiftModelFactory',
    'CnAppointmentModelFactory', 'CnAvailabilityModelFactory',
    'CnCapacityModelFactory', 'CnShiftTemplateModelFactory',
    'CnSession',
    function( CnShiftModelFactory,
              CnAppointmentModelFactory, CnAvailabilityModelFactory,
              CnCapacityModelFactory, CnShiftTemplateModelFactory,
              CnSession ) {
      return {
        templateUrl: module.getFileUrl( 'calendar.tpl.html' ),
        restrict: 'E',
        scope: {
          model: '=?',
          preventSiteChange: '@'
        },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnShiftModelFactory.instance();
          $scope.heading = $scope.model.site.name.ucWords() + ' Shift Calendar';
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
  cenozo.providers.directive( 'cnShiftList', [
    'CnShiftModelFactory', 'CnSession',
    function( CnShiftModelFactory, CnSession ) {
      return {
        templateUrl: module.getFileUrl( 'list.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnShiftModelFactory.instance();
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnShiftView', [
    'CnShiftModelFactory', 'CnSession',
    function( CnShiftModelFactory, CnSession ) {
      return {
        templateUrl: module.getFileUrl( 'view.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnShiftModelFactory.instance();
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnShiftAddFactory', [
    'CnBaseAddFactory', 'CnSession', 'CnHttpFactory',
    function( CnBaseAddFactory, CnSession, CnHttpFactory ) {
      var object = function( parentModel ) {
        var self = this;
        CnBaseAddFactory.construct( this, parentModel );

        // add the new shift's events to the calendar cache
        this.onAdd = function( record ) {
          return this.$$onAdd( record ).then( function() {
            record.getIdentifier = function() { return parentModel.getIdentifierFromRecord( record ); };
            
            // fill in the user name so that it shows in the calendar
            return CnHttpFactory.instance( {
              path: 'user/' + record.user_id,
              data: { select: { column: [ 'name' ] } }
            } ).get().then( function( response ) {
              record.username = response.data.name;
              var minDate = parentModel.calendarModel.cacheMinDate;
              var maxDate = parentModel.calendarModel.cacheMaxDate;
              return parentModel.calendarModel.cache.push( getEventFromShift( record, CnSession.user.timezone ) );
            } );
          } );
        };
      };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnShiftCalendarFactory', [
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
            self.cache.forEach( function( item, index, array ) {
              array[index] = getEventFromShift( item, CnSession.user.timezone );
            } );
          } );
        };
      };

      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnShiftListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) {
        CnBaseListFactory.construct( this, parentModel );

        // remove the deleted shift from the calendar cache
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
  cenozo.providers.factory( 'CnShiftViewFactory', [
    'CnBaseViewFactory', 'CnSession',
    function( CnBaseViewFactory, CnSession ) {
      var args = arguments;
      var CnBaseViewFactory = args[0];
      var object = function( parentModel, root ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, root );

        // remove the deleted shift's events from the calendar cache
        this.onDelete = function() {
          return this.$$onDelete().then( function() {
            parentModel.calendarModel.cache = parentModel.calendarModel.cache.filter( function( e ) {
              return e.getIdentifier() != self.record.getIdentifier();
            } );
          } );
        };

        // remove and re-add the shift's events from the calendar cache
        this.onPatch = function( data ) {
          return this.$$onPatch( data ).then( function() {
            var minDate = parentModel.calendarModel.cacheMinDate;
            var maxDate = parentModel.calendarModel.cacheMaxDate;
            parentModel.calendarModel.cache = parentModel.calendarModel.cache.filter( function( e ) {
              return e.getIdentifier() != self.record.getIdentifier();
            } );
            parentModel.calendarModel.cache.push( getEventFromShift( self.record, CnSession.user.timezone ) );
          } );
        };
      }
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnShiftModelFactory', [
    'CnBaseModelFactory',
    'CnShiftAddFactory', 'CnShiftCalendarFactory',
    'CnShiftListFactory', 'CnShiftViewFactory',
    'CnSession', '$state',
    function( CnBaseModelFactory,
              CnShiftAddFactory, CnShiftCalendarFactory,
              CnShiftListFactory, CnShiftViewFactory,
              CnSession, $state ) {
      var object = function( site ) {
        if( !angular.isObject( site ) || angular.isUndefined( site.id ) )
          throw new Error( 'Tried to create CnShiftModel without specifying the site.' );

        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.addModel = CnShiftAddFactory.instance( this );
        this.calendarModel = CnShiftCalendarFactory.instance( this );
        this.listModel = CnShiftListFactory.instance( this );
        this.viewModel = CnShiftViewFactory.instance( this, site.id == CnSession.site.id );
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
