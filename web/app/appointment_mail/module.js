define( [ 'trace' ].reduce( function( list, name ) {
  return list.concat( cenozoApp.module( name ).getRequiredFiles() );
}, [] ), function() {
  'use strict';

  try { var module = cenozoApp.module( 'appointment_mail', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( module, {
    identifier: {
      parent: {
        subject: 'site',
        column: 'site.name'
      }
    },
    name: {
      singular: 'appointment mail template',
      plural: 'appointment mail templates',
      possessive: 'appointment mail template\'s'
    },
    columnList: {
      site: {
        column: 'site.name',
        title: 'Site'
      },
      language: {
        column: 'language.name',
        title: 'Language'
      },
      delay: {
        title: 'Delay (days)'
      },
      title: {
        title: 'Title'
      }
    },
    defaultOrder: {
      column: 'delay',
      reverse: false
    }
  } );

  module.addInputGroup( '', {
    site_id: {
      title: 'Site',
      type: 'enum',
      isExcluded: function( $state, model ) { return model.hasAllSites() ? 'add' : true; },
      isConstant: 'view'
    },
    language_id: {
      title: 'Language',
      type: 'enum',
      isConstant: 'view'
    },
    from_name: {
      title: 'From name',
      type: 'string'
    },
    from_address: {
      title: 'From address',
      type: 'string',
      format: 'eappointment_mail',
      help: 'Must be in the format "account@domain.name".'
    },
    cc_address: {
      title: 'CC',
      type: 'string',
      help: 'May be a comma-delimited list of eappointment_mail addresses in the format "account@domain.name".'
    },
    bcc_address: {
      title: 'BCC',
      type: 'string',
      help: 'May be a comma-delimited list of eappointment_mail addresses in the format "account@domain.name".'
    },
    delay: {
      title: 'Delay (days)',
      type: 'string',
      format: 'integer'
    },
    title: {
      title: 'Title',
      type: 'string'
    },
    body: {
      title: 'Body',
      type: 'text'
    }
  } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentMailAdd', [
    'CnAppointmentMailModelFactory',
    function( CnAppointmentMailModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'add.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentMailModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentMailList', [
    'CnAppointmentMailModelFactory',
    function( CnAppointmentMailModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'list.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentMailModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentMailView', [
    'CnAppointmentMailModelFactory',
    function( CnAppointmentMailModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'view.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentMailModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentMailAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      var object = function( parentModel ) { CnBaseAddFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentMailListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentMailViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      var object = function( parentModel, root ) { CnBaseViewFactory.construct( this, parentModel, root ); };
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentMailModelFactory', [
    'CnBaseModelFactory', 'CnAppointmentMailListFactory', 'CnAppointmentMailAddFactory', 'CnAppointmentMailViewFactory',
    'CnSession', 'CnHttpFactory', '$q',
    function( CnBaseModelFactory, CnAppointmentMailListFactory, CnAppointmentMailAddFactory, CnAppointmentMailViewFactory,
              CnSession, CnHttpFactory, $q ) {
      var object = function( root ) {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.addModel = CnAppointmentMailAddFactory.instance( this );
        this.listModel = CnAppointmentMailListFactory.instance( this );
        this.viewModel = CnAppointmentMailViewFactory.instance( this, root );
        
        this.hasAllSites = function() { return CnSession.role.allSites; };

        // extend getMetadata
        this.getMetadata = function() {
          return this.$$getMetadata().then( function() {
            return $q.all( [

              CnHttpFactory.instance( {
                path: 'language',
                data: {
                  select: { column: [ 'id', 'name' ] },
                  modifier: {
                    where: { column: 'active', operator: '=', value: true },
                    order: 'name'
                  }
                }
              } ).query().then( function success( response ) {
                self.metadata.columnList.language_id.enumList = [];
                response.data.forEach( function( item ) {
                  self.metadata.columnList.language_id.enumList.push( { value: item.id, name: item.name } );
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

            ] );
          } );
        };
      };

      return {
        root: new object( true ),
        instance: function() { return new object( false ); }
      };
    }
  ] );

} );
