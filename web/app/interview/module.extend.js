// extend the framework's module
define( [ cenozoApp.module( 'interview' ).getFileUrl( 'module.js' ) ], function() {
  'use strict';

  var module = cenozoApp.module( 'interview' );

  cenozo.insertPropertyAfter( module.columnList, 'uid', 'qnaire', {
    column: 'script.name',
    title: 'Questionnaire'
  } );

  cenozo.insertPropertyAfter( module.columnList, 'qnaire', 'method', {
    column: 'interview.method',
    title: 'Method'
  } );

  // add future_appointment as a hidden input (to be used below)
  module.addInput( '', 'future_appointment', { type: 'hidden' } );
  module.addInput( '', 'last_participation_consent', { type: 'hidden' } );

  // add whether the script is pine based or not (needed to enable/disable qnaire method)
  module.addInput( '', 'pine_qnaire_id', { column: 'script.pine_qnaire_id', type: 'hidden' } );

  module.addInput( '', 'qnaire_id', {
    title: 'Questionnaire',
    type: 'enum',
    isConstant: true
  }, 'participant' );

  module.addInput( '', 'method', {
    title: 'Interviewing Method',
    type: 'enum',
    // don't allow the interviewing method to be changed if it is done or not doing a pine interview
    isConstant: function( $state, model ) {
      return null != model.viewModel.record.end_datetime || null == model.viewModel.record.pine_qnaire_id;
    }
  }, 'qnaire_id' );

  module.addExtraOperation( 'view', {
    title: 'Force Complete',
    operation: function( $state, model ) { model.viewModel.forceComplete(); },
    isDisabled: function( $state, model ) { return null !== model.viewModel.record.end_datetime; },
    isIncluded: function( $state, model ) { return model.viewModel.forceCompleteAllowed; },
    help: 'Force completes the interview. ' +
          'This will end the interview\'s questionnaire leaving any remaining questions unanswered. ' +
          'You should only force-close an interview when you are sure that as many questions in the ' +
          'questionnaire has been answered as possible and there is no reason to re-assign the participant.'
  } );

  // extend the list factory
  cenozo.providers.decorator( 'CnInterviewListFactory', [
    '$delegate', 'CnHttpFactory',
    function( $delegate, CnHttpFactory ) {
      var instance = $delegate.instance;
      $delegate.instance = function( parentModel ) {
        var object = instance( parentModel );

        // enable the add button if:
        //   1) the interview list's parent is a participant model
        //   2) all interviews are complete for this participant
        //   3) another qnaire is available for this participant
        object.afterList( async function() {
          object.parentModel.getAddEnabled = function() { return false; };

          var path = object.parentModel.getServiceCollectionPath();
          if( 'participant' == object.parentModel.getSubjectFromState() &&
              null !== path.match( /participant\/[^\/]+\/interview/ ) ) {
            var queueRank = null;
            var qnaireRank = null;
            var lastInterview = null;

            // get the participant's last interview
            var response = await CnHttpFactory.instance( {
              path: path,
              data: {
                modifier: { order: { 'qnaire.rank': true }, limit: 1 },
                select: { column: [ { table: 'qnaire', column: 'rank' }, 'end_datetime' ] }
              },
              onError: function( error ) {} // ignore errors
            } ).query();

            if( 0 < response.data.length ) lastInterview = response.data[0];

            // get the participant's current queue rank
            var response = await CnHttpFactory.instance( {
              path: path.replace( '/interview', '' ),
              data: {
                select: { column: [
                  { table: 'queue', column: 'rank', alias: 'queueRank' },
                  { table: 'qnaire', column: 'rank', alias: 'qnaireRank' }
                ] }
              },
              onError: function( error ) {} // ignore errors
            } ).query();

            queueRank = response.data.queueRank;
            qnaireRank = response.data.qnaireRank;

            object.parentModel.getAddEnabled = function() {
              return object.parentModel.$$getAddEnabled() &&
                     null != queueRank &&
                     null != qnaireRank && (
                       null == lastInterview || (
                         null != lastInterview.end_datetime &&
                         lastInterview.rank != qnaireRank
                       )
                     );
            };
          }
        } );

        return object;
      };
      return $delegate;
    }
  ] );

  // extend the view factory
  cenozo.providers.decorator( 'CnInterviewViewFactory', [
    '$delegate', 'CnSession', 'CnHttpFactory', 'CnModalConfirmFactory', 'CnModalMessageFactory',
    function( $delegate, CnSession, CnHttpFactory, CnModalConfirmFactory, CnModalMessageFactory ) {
      var instance = $delegate.instance;
      $delegate.instance = function( parentModel, root ) {
        var object = instance( parentModel, root );

        // force the default tab to be "appointment"
        object.defaultTab = 'appointment';

        object.forceCompleteAllowed = 2 < CnSession.role.tier;
        object.forceComplete = function() {
          CnModalConfirmFactory.instance( {
            title: 'Force Complete Interview?',
            message: 'Are you sure you wish to force-complete the interview?\n\n' +
                     'Note that the interview\'s questionnaire will be closed and unanswered questions will ' +
                     'no longer be accessible.  This operation cannot be undone.'
          } ).show().then( function( response ) {
            if( response ) {
              CnHttpFactory.instance( {
                path: 'interview/' + object.record.id + '?operation=force_complete',
                data: {},
                onError: function( response ) {
                  if( 409 == response.status ) {
                    // 409 means there is an open assignment (or some other problem which we can report)
                    CnModalMessageFactory.instance( {
                      title: 'Unable to close interview',
                      message: response.data,
                      error: true
                    } ).show();
                  } else { CnModalMessageFactory.httpError( response ); }
                }
              } ).patch().then( object.onView );
            }
          } );
        };

        function getAppointmentEnabled( type ) {
          var completed = null !== object.record.end_datetime;
          var participating = false !== object.record.last_participation_consent;
          var future = object.record.future_appointment;
          return 'add' == type ? ( !completed && participating && !future ) : future;
        }

        function updateEnableFunctions() {
          object.appointmentModel.getAddEnabled = function() {
            return angular.isDefined( object.appointmentModel.module.actions.add ) &&
                   getAppointmentEnabled( 'add' );
          };
          object.appointmentModel.getDeleteEnabled = function() {
            return angular.isDefined( object.appointmentModel.module.actions.delete ) &&
                   getAppointmentEnabled( 'delete' ) &&
                   'vacancy' != parentModel.getSubjectFromState();
          };
        }

        // override onView
        object.onView = function( force ) {
          return object.$$onView( force ).then( function() {
            if( angular.isDefined( object.appointmentModel ) ) updateEnableFunctions();
          } );
        };

        // override appointment list's onDelete
        object.deferred.promise.then( function() {
          if( angular.isDefined( object.appointmentModel ) ) {
            object.appointmentModel.listModel.onDelete = function( record ) {
              return object.appointmentModel.listModel.$$onDelete( record ).then( function() { object.onView(); } );
            };
          }
        } );

        return object;
      };
      return $delegate;
    }
  ] );

  // extend the model factory
  cenozo.providers.decorator( 'CnInterviewModelFactory', [
    '$delegate', 'CnHttpFactory', 'CnSession',
    function( $delegate, CnHttpFactory, CnSession ) {
      var instance = $delegate.instance;
      // extend getBreadcrumbTitle
      // (metadata's promise will have already returned so we don't have to wait for it)
      function extendObject( object ) {
        angular.extend( object, {
          getBreadcrumbTitle: function() {
            var qnaire = object.metadata.columnList.qnaire_id.enumList.findByProperty(
              'value', object.viewModel.record.qnaire_id );
            return qnaire ? qnaire.name : 'unknown';
          },

          getEditEnabled: function() {
            return object.$$getEditEnabled() && ['administrator', 'helpline'].includes( CnSession.role.name );
          },

          // extend getMetadata
          getMetadata: function() {
            return object.$$getMetadata().then( function() {
              return CnHttpFactory.instance( {
                path: 'qnaire',
                data: {
                  select: { column: [ 'id', { table: 'script', column: 'name' } ] },
                  modifier: { order: 'rank' }
                }
              } ).query().then( function success( response ) {
                object.metadata.columnList.qnaire_id.enumList = [];
                response.data.forEach( function( item ) {
                  object.metadata.columnList.qnaire_id.enumList.push( { value: item.id, name: item.name } );
                } );
              } );
            } );
          }
        } );
      }

      extendObject( $delegate.root );

      $delegate.instance = function() {
        var object = instance();
        extendObject( object );
        return object;
      };

      return $delegate;
    }
  ] );

} );
