define( [], function() {

  'use strict';

  var moduleSubject = 'interview';
  var moduleNames = {
    singular: 'interview',
    plural: 'interviews',
    possessive: 'interview\'s',
    pluralPossessive: 'interviews\''
  };

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnInterviewAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      return { instance: function( params ) {
        if( undefined === params ) params = {};
        params.subject = moduleSubject;
        params.name = moduleNames;
        return CnBaseAddFactory.instance( params );
      } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnInterviewListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( params ) {
        var base = CnBaseListFactory.instance( params );
        for( var p in base ) if( base.hasOwnProperty( p ) ) this[p] = base[p];

        ////////////////////////////////////
        // factory customizations start here
        this.columnList = {
          uid: {
            column: 'participant.uid',
            title: 'UID'
          },
          qnaire: {
            column: 'qnaire.name',
            title: 'Questionnaire'
          },
          method: {
            column: 'interview_method.name',
            title: 'Method'
          },
          completed: {
            title: 'Completed',
            filter: 'cnYesNo'
          },
          date: {
            column: 'interview.id',
            title: 'TODO'
          },
        };
        this.order = { column: 'uid', reverse: false };
        // factory customizations end here
        //////////////////////////////////

        cnCopyParams( this, params );
      };

      object.prototype = CnBaseListFactory.prototype;
      return { instance: function( params ) {
        if( undefined === params ) params = {};
        params.subject = moduleSubject;
        params.name = moduleNames;
        return new object( params );
      } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnInterviewViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      return { instance: function( params ) {
        if( undefined === params ) params = {};
        params.subject = moduleSubject;
        params.name = moduleNames;
        return CnBaseViewFactory.instance( params );
      } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnInterviewSingleton', [
    'CnBaseSingletonFactory', 'CnInterviewListFactory', 'CnInterviewAddFactory', 'CnInterviewViewFactory',
    function( CnBaseSingletonFactory, CnInterviewListFactory, CnInterviewAddFactory, CnInterviewViewFactory ) {
      var object = function() {
        var base = CnBaseSingletonFactory.instance( {
          subject: moduleSubject,
          name: moduleNames,
          cnAdd: CnInterviewAddFactory.instance(),
          cnList: CnInterviewListFactory.instance(),
          cnView: CnInterviewViewFactory.instance()
        } );
        for( var p in base ) if( base.hasOwnProperty( p ) ) this[p] = base[p];
      };

      object.prototype = CnBaseSingletonFactory.prototype;
      // don't return a method to create instances, create and return the singleton
      return new object();
    }
  ] );

} );
