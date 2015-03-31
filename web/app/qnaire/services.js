define( [], function() {

  'use strict';

  var moduleSubject = 'qnaire';
  var moduleNames = {
    singular: 'questionnaire',
    plural: 'questionnaires',
    possessive: 'questionnaire\'s',
    pluralPossessive: 'questionnaires\''
  };

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnQnaireAddFactory', [
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
  cnCachedProviders.factory( 'CnQnaireListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( params ) {
        var base = CnBaseListFactory.instance( params );
        for( var p in base ) if( base.hasOwnProperty( p ) ) this[p] = base[p];

        ////////////////////////////////////
        // factory customizations start here
        this.columnList = {
          name: {
            column: 'qnaire.name',
            title: 'Name'
          },
          rank: {
            column: 'qnaire.rank',
            title: 'Rank' },
          method: {
            column: 'default_interview_method.name',
            title: 'Method'
          },
          previous: {
            column: 'prev_qnaire.name',
            title: 'Previous'
          },
          delay: {
            column: 'qnaire.delay',
            title: 'Delay'
          },
          phase_count: { title: 'Phases' }
        };
        this.order = { column: 'rank', reverse: false };
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
  cnCachedProviders.factory( 'CnQnaireViewFactory', [
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
  cnCachedProviders.factory( 'CnQnaireSingleton', [
    'CnBaseSingletonFactory', 'CnQnaireListFactory', 'CnQnaireAddFactory', 'CnQnaireViewFactory',
    function( CnBaseSingletonFactory, CnQnaireListFactory, CnQnaireAddFactory, CnQnaireViewFactory ) {
      var object = function() {
        var base = CnBaseSingletonFactory.instance( {
          subject: moduleSubject,
          name: moduleNames,
          cnAdd: CnQnaireAddFactory.instance(),
          cnList: CnQnaireListFactory.instance(),
          cnView: CnQnaireViewFactory.instance()
        } );
        for( var p in base ) if( base.hasOwnProperty( p ) ) this[p] = base[p];
      };

      object.prototype = CnBaseSingletonFactory.prototype;
      // don't return a method to create instances, create and return the singleton
      return new object();
    }
  ] );

} );
