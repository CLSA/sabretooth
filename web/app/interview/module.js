define( function() {
  'use strict';

  try { var module = cenozoApp.module( 'interview', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( module, {
    identifier: {
      parent: {
        subject: 'participant',
        column: 'participant.uid'
      }
    },
    name: {
      singular: 'interview',
      plural: 'interviews',
      possessive: 'interview\'s',
      pluralPossessive: 'interviews\''
    },
    columnList: {
      uid: {
        column: 'participant.uid',
        title: 'UID'
      },
      qnaire: {
        column: 'script.name',
        title: 'Questionnaire'
      },
      site: {
        column: 'site.name',
        title: 'Credited Site'
      },
      start_datetime: {
        title: 'Start',
        type: 'datetimesecond'
      },
      end_datetime: {
        title: 'End',
        type: 'datetimesecond'
      }
    },
    defaultOrder: {
      column: 'start_datetime',
      reverse: true
    }
  } );

  module.addInputGroup( null, {
    participant: {
      column: 'participant.uid',
      title: 'Participant',
      type: 'string',
      constant: true
    },
    qnaire_id: {
      title: 'Questionnaire',
      type: 'enum',
      constant: true
    },
    site_id: {
      title: 'Credited Site',
      type: 'enum',
      help: 'This determines which site is credited with the completed interview.'
    },
    start_datetime: {
      column: 'interview.start_datetime',
      title: 'Start Date & Time',
      type: 'datetimesecond',
      max: 'end_datetime',
      help: 'When the first call from the first assignment was made for this interview.'
    },
    end_datetime: {
      column: 'interview.end_datetime',
      title: 'End Date & Time',
      type: 'datetimesecond',
      min: 'start_datetime',
      max: 'now',
      help: 'Will remain blank until the questionnaire is complete.'
    },
    open_appointment_count: {
      type: 'hidden'
    },
    open_callback_count: {
      type: 'hidden'
    }
  } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnInterviewList', [
    'CnInterviewModelFactory',
    function( CnInterviewModelFactory ) {
      return {
        templateUrl: module.url + 'list.tpl.html',
        restrict: 'E',
        controller: function( $scope ) {
          $scope.model = CnInterviewModelFactory.root;
          $scope.model.listModel.onList( true ).then( function() {
            $scope.model.setupBreadcrumbTrail( 'list' );
          } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnInterviewView', [
    'CnInterviewModelFactory',
    function( CnInterviewModelFactory ) {
      return {
        templateUrl: module.url + 'view.tpl.html',
        restrict: 'E',
        controller: function( $scope ) {
          $scope.model = CnInterviewModelFactory.root;
          $scope.model.viewModel.onView().then( function() {
            $scope.model.setupBreadcrumbTrail( 'view' );
          } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      var args = arguments;
      var CnBaseViewFactory = args[0];
      var object = function( parentModel, root ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, root );

        // override onPatch
        this.onPatch = function( data ) {
          return this.$$onPatch( data ).then( function() {
            // if the end datetime has changed then reload then update the appointment/callback list actions
            if( angular.isDefined( data.end_datetime ) ) {
              var completed = null !== self.record.end_datetime;
              if( angular.isDefined( self.appointmentModel ) ) {
                self.appointmentModel.enableAdd( !completed );
                self.appointmentModel.enableDelete( !completed );
                self.appointmentModel.enableEdit( !completed );
                self.appointmentModel.enableView( !completed );
              }
              if( angular.isDefined( self.callbackModel ) ) {
                self.callbackModel.enableAdd( !completed );
                self.callbackModel.enableDelete( !completed );
                self.callbackModel.enableEdit( !completed );
                self.callbackModel.enableView( !completed );
              }
            }
          } );
        };

        // override onView
        this.onView = function() {
          return this.$$onView().then( function() {
            // if the end datetime has changed then reload then update the appointment/callback list actions
            var completed = null !== self.record.end_datetime;
            var existing = 0 < self.record.open_appointment_count || 0 < self.record.open_callback_count;
            if( angular.isDefined( self.appointmentModel ) ) {
              self.appointmentModel.enableAdd( !completed && !existing );
              self.appointmentModel.enableDelete( !completed );
              self.appointmentModel.enableEdit( !completed );
              self.appointmentModel.enableView( !completed );
            }
            if( angular.isDefined( self.callbackModel ) ) {
              self.callbackModel.enableAdd( !completed && !existing );
              self.callbackModel.enableDelete( !completed );
              self.callbackModel.enableEdit( !completed );
              self.callbackModel.enableView( !completed );
            }
          } );
        };
      }
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewModelFactory', [
    'CnBaseModelFactory', 'CnInterviewListFactory', 'CnInterviewViewFactory',
    'CnHttpFactory', '$q',
    function( CnBaseModelFactory, CnInterviewListFactory, CnInterviewViewFactory,
              CnHttpFactory, $q ) {
      var object = function( root ) {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.listModel = CnInterviewListFactory.instance( this );
        this.viewModel = CnInterviewViewFactory.instance( this, root );

        // extend getBreadcrumbTitle
        this.getBreadcrumbTitle = function() {
          var qnaire = self.metadata.columnList.qnaire_id.enumList.findByProperty(
            'value', this.viewModel.record.qnaire_id );
          return qnaire ? qnaire.name : 'unknown';
        };

        // extend getMetadata
        this.getMetadata = function() {
          this.metadata.loadingCount++;
          return $q.all( [

            this.$$getMetadata(),

            CnHttpFactory.instance( {
              path: 'qnaire',
              data: {
                select: { column: [ 'id', { table: 'script', column: 'name' } ] },
                modifier: { order: 'rank' }
              }
            } ).query().then( function success( response ) {
              self.metadata.columnList.qnaire_id.enumList = [];
              response.data.forEach( function( item ) {
                self.metadata.columnList.qnaire_id.enumList.push( { value: item.id, name: item.name } );
              } );
            } ),

            CnHttpFactory.instance( {
              path: 'site',
              data: {
                select: { column: [ 'id', 'name' ] },
                modifier: { order: 'name' }
              }
            } ).query().then( function success( response ) {
              self.metadata.columnList.site_id.enumList = [];
              response.data.forEach( function( item ) {
                self.metadata.columnList.site_id.enumList.push( { value: item.id, name: item.name } );
              } );
            } )

          ] ).finally( function finished() { self.metadata.loadingCount--; } );
        };
      };

      return {
        root: new object( true ),
        instance: function() { return new object( false ); }
      };
    }
  ] );

} );
