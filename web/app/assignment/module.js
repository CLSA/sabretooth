define( cenozo.getDependencyList( 'assignment' ), function() {
  'use strict';

  var module = cenozoApp.module( 'assignment' );
  angular.extend( module, {
    identifier: {
      parent: {
        subject: 'interview',
        column: 'interview_id',
        friendly: 'qnaire'
      }
    },
    name: {
      singular: 'assignment',
      plural: 'assignments',
      possessive: 'assignment\'s',
      pluralPossessive: 'assignments\''
    },
    columnList: {
      user: {
        column: 'user.name',
        title: 'Operator'
      },
      site: {
        column: 'site.name',
        title: 'Site'
      },
      start_datetime: {
        column: 'assignment.start_datetime',
        title: 'Start',
        type: 'datetimesecond'
      },
      end_datetime: {
        column: 'assignment.end_datetime',
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
    qnaire: {
      column: 'script.name',
      title: 'Questionnaire',
      type: 'string',
      constant: true
    },
    user: {
      column: 'user.name',
      title: 'User',
      type: 'string',
      constant: true
    },
    site: {
      column: 'site.name',
      title: 'Site',
      type: 'string',
      constant: true
    },
    queue: {
      column: 'queue.title',
      title: 'Queue',
      type: 'string',
      constant: true
    },
    start_datetime: {
      column: 'assignment.start_datetime',
      title: 'Start Date & Time',
      type: 'datetimesecond',
      max: 'end_datetime'
    },
    end_datetime: {
      column: 'assignment.end_datetime',
      title: 'End Date & Time',
      type: 'datetimesecond',
      min: 'start_datetime',
      max: 'now'
    }
  } );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'AssignmentListCtrl', [
    '$scope', 'CnAssignmentModelFactory', 'CnSession',
    function( $scope, CnAssignmentModelFactory, CnSession ) {
      $scope.model = CnAssignmentModelFactory.root;
      $scope.model.listModel.onList( true ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'AssignmentViewCtrl', [
    '$scope', 'CnAssignmentModelFactory', 'CnSession',
    function( $scope, CnAssignmentModelFactory, CnSession ) {
      $scope.model = CnAssignmentModelFactory.root;
      $scope.model.viewModel.onView().then( function() {
        $scope.model.setupBreadcrumbTrail( 'view' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'AssignmentHomeCtrl', [
    '$scope', 'CnAssignmentHomeFactory', 'CnSession',
    function( $scope, CnAssignmentHomeFactory, CnSession ) {
      $scope.model = CnAssignmentHomeFactory.instance();
      $scope.model.onLoad(); // breadcrumbs are handled by the service
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAssignmentView', function () {
    return {
      templateUrl: 'app/assignment/view.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAssignmentListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAssignmentViewFactory',
    cenozo.getViewModelInjectionList( 'assignment' ).concat( function() {
      var args = arguments;
      var CnBaseViewFactory = args[0];
      var object = function( parentModel ) { CnBaseViewFactory.construct( this, parentModel, args ); }
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    } )
  );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAssignmentModelFactory', [
    '$state', 'CnBaseModelFactory', 'CnAssignmentListFactory', 'CnAssignmentViewFactory',
    function( $state, CnBaseModelFactory, CnAssignmentListFactory, CnAssignmentViewFactory ) {
      var object = function() {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.listModel = CnAssignmentListFactory.instance( this );
        this.viewModel = CnAssignmentViewFactory.instance( this );
      };

      return {
        root: new object(),
        instance: function() { return new object(); }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAssignmentHomeFactory', [
    '$state', '$window', 'CnSession', 'CnHttpFactory',
    'CnParticipantModelFactory', 'CnModalMessageFactory',
    'CnModalConfirmFactory', 'CnModalParticipantNoteFactory',
    function( $state, $window, CnSession, CnHttpFactory,
              CnParticipantModelFactory, CnModalMessageFactory,
              CnModalConfirmFactory, CnModalParticipantNoteFactory ) {
      var object = function() {
        var self = this;

        this.reset = function() {
          self.assignment = null;
          self.prevAssignment = null;
          self.participant = null;
          self.activePhoneCall = false;
          self.qnaireList = null;
          self.activeQnaire = null;
          self.lastQnaire = null;
          self.isScriptListLoading = false;
          self.scriptList = null;
          self.activeScript = null;
          self.phoneCallStatusList = null;
          self.phoneCallList = null;
          self.isAssignmentLoading = false;
          self.isPrevAssignmentLoading = false;
        };

        this.application = CnSession.application.title;
        this.participantModel = CnParticipantModelFactory.instance();
        this.reset();
        
        // add additional columns to the model
        this.participantModel.addColumn( 'rank', { title: 'Rank', column: 'queue.rank', type: 'rank' }, 0 );
        this.participantModel.addColumn( 'queue', { title: 'Queue', column: 'queue.name' }, 1 );
        this.participantModel.addColumn( 'qnaire', { title: 'Questionnaire', column: 'script.name' }, 2 );

        // override the default order
        this.participantModel.listModel.orderBy( 'rank', true );

        // override model functions
        this.participantModel.getServiceCollectionPath = function() { return 'participant?assignment=true'; }

        // override the onChoose function
        this.participantModel.listModel.onSelect = function( record ) {
          // attempt to assign the participant to the user
          CnModalConfirmFactory.instance( {
            title: 'Begin Assignment',
            message: 'Are you sure you wish to start a new assignment with participant ' + record.uid + '?'
          } ).show().then( function( response ) {
            if( response ) {
              self.isAssignmentLoading = true; // show loading screen right away
              CnHttpFactory.instance( {
                path: 'assignment?operation=open',
                data: { participant_id: record.id }
              } ).post().then( function( response ) {
                self.onLoad();
              } ).catch( function( response ) {
                if( 409 == response.status ) {
                  // 409 means there is a conflict (the assignment can't be made)
                  CnModalMessageFactory.instance( {
                    title: 'Unable to start assignment with ' + record.uid,
                    message: response.data,
                    error: true
                  } ).show().then( self.onLoad );
                } else { CnSession.errorHandler( response ); }
              } );
            }
          } );
        };

        this.onLoad = function( showLoading ) {
          self.reset();
          if( angular.isUndefined( showLoading ) ) showLoading = true;
          self.isAssignmentLoading = showLoading;
          self.isPrevAssignmentLoading = showLoading;
          return CnHttpFactory.instance( {
            path: 'assignment/0',
            data: { select: { column: [ 'id', 'interview_id', 'start_datetime',
              { table: 'participant', column: 'id', alias: 'participant_id' },
              { table: 'qnaire', column: 'id', alias: 'qnaire_id' },
              { table: 'script', column: 'id', alias: 'script_id' },
              { table: 'script', column: 'name', alias: 'qnaire' },
              { table: 'queue', column: 'title', alias: 'queue' }
            ] } }
          } ).get().then( function success( response ) {
            self.assignment = response.data;

            // get the assigned participant's details
            CnHttpFactory.instance( {
              path: 'participant/' + self.assignment.participant_id,
              data: { select: { column: [ 'id', 'uid', 'first_name', 'other_name', 'last_name',
                { table: 'language', column: 'code', alias: 'language_code' },
                { table: 'language', column: 'name', alias: 'language' }
              ] } }
            } ).get().then( function success( response ) {
              self.participant = response.data;
              self.participant.getIdentifier = function() {
                return self.participantModel.getIdentifierFromRecord( self.participant );
              };
              CnSession.setBreadcrumbTrail( [ { title: 'Assignment' }, { title: self.participant.uid } ] );
              self.isAssignmentLoading = false;
            } );
          } ).then( function() {
            CnHttpFactory.instance( {
              path: 'assignment/0/phone_call',
              data: { select: { column: [ 'end_datetime', 'status',
                { table: 'phone', column: 'rank' },
                { table: 'phone', column: 'type' },
                { table: 'phone', column: 'number' }
              ] } }
            } ).query().then( function success( response ) {
              self.phoneCallList = response.data;
              var len = self.phoneCallList.length
              self.activePhoneCall = 0 < len && null === self.phoneCallList[len-1].end_datetime
                                   ? self.phoneCallList[len-1]
                                   : null;
            } );
          } ).then( function() {
            if( null === self.scriptList ) {
              // get the qnaire list and store the current and last qnaires
              CnHttpFactory.instance( {
                path: 'qnaire',
                data: {
                  select: { column: ['id', 'rank', 'script_id', 'delay'] },
                  modifier: { order: 'rank' }
                }
              } ).query().then( function( response ) {
                self.qnaireList = response.data;
                var len = self.qnaireList.length;
                if( 0 < len ) {
                  self.activeQnaire = self.qnaireList.findByProperty( 'id', self.assignment.qnaire_id );
                  self.lastQnaire = self.qnaireList[len-1];
                }
                self.loadScriptList(); // now load the script list
              } ).catch( CnSession.errorHandler );
            }
          } ).then( function() {
            CnHttpFactory.instance( {
              path: 'participant/' + self.assignment.participant_id +
                    '/interview/' + self.assignment.interview_id + '/assignment',
              data: {
                select: {
                  column: [
                    'start_datetime',
                    'end_datetime',
                    'phone_call_count',
                    { table: 'last_phone_call', column: 'status' },
                    { table: 'user', column: 'first_name' },
                    { table: 'user', column: 'last_name' },
                    { table: 'user', column: 'name' }
                  ]
                },
                modifier: { order: { start_datetime: true }, offset: 1, limit: 1 }
              }
            } ).query().then( function success( response ) {
              self.prevAssignment = 1 == response.data.length ? response.data[0] : null;
              self.isPrevAssignmentLoading = false;
            } );
          } ).then( function() {
            CnHttpFactory.instance( {
              path: 'participant/' + self.assignment.participant_id + '/phone',
              data: {
                select: { column: [ 'id', 'rank', 'type', 'number', 'international' ] },
                modifier: { order: 'rank' }
              }
            } ).query().then( function success( response ) {
              self.phoneList = response.data;
            } );
          } ).then( function() {
            CnHttpFactory.instance( {
              path: 'phone_call'
            } ).head().then( function success( response ) {
              self.phoneCallStatusList =
                cenozo.parseEnumList( angular.fromJson( response.headers( 'Columns' ) ).status );
            } );
          } ).catch( function( response ) {
            if( 307 == response.status ) {
              // 307 means the user has no active assignment, so load the participant select list
              self.assignment = null;
              self.participant = null;
              self.isAssignmentLoading = false;
              self.isPrevAssignmentLoading = false;
              self.participantModel.listModel.onList( true ).then( function() {
                CnSession.setBreadcrumbTrail( [ { title: 'Assignment' }, { title: 'Select' } ] );
              } );
            } else { CnSession.errorHandler( response ); }
          } );
        };

        this.openNotes = function() {
          if( null != self.participant )
            CnModalParticipantNoteFactory.instance( { participant: self.participant } ).show();
        };

        this.loadScriptList = function() {
          self.isScriptListLoading = true;
          return CnHttpFactory.instance( {
            path: 'application/' + CnSession.application.id +
                  '/script?participant_id=' + self.assignment.participant_id,
            data: {
              modifier: { order: 'name' },
              select: { column: [
                'id', 'name', 'repeated', 'url', 'description',
                { table: 'started_event', column: 'datetime', alias: 'started_datetime' },
                { table: 'completed_event', column: 'datetime', alias: 'completed_datetime' }
              ] }
            }
          } ).query().then( function success( response ) {
            // put qnaire scripts in separate list and only include the current qnaire script in the main list
            self.scriptList = [];
            self.qnaireScriptList = [];
            response.data.forEach( function( item ) {
              if( null != self.qnaireList.findByProperty( 'script_id', item.id ) ) {
                self.qnaireScriptList.push( item );
                if( item.id == self.assignment.script_id ) self.scriptList.unshift( item );
              } else {
                self.scriptList.push( item );
              }
            } );

            if( 0 == self.scriptList.length ) {
              self.activeScript = null;
            } else {
              if( null == self.activeScript ||
                  null == self.scriptList.findByProperty( 'script_id', self.activeScript.id ) ) {
                self.activeScript = self.scriptList[0];
              } else {
                var activeScriptName = self.activeScript.name;
                self.scriptList.forEach( function( item ) {
                  if( activeScriptName == item.name ) self.activeScript = item;
                } );
              }
            }
            self.isScriptListLoading = false;
          } ).catch( CnSession.errorHandler );
        };

        this.launchScript = function( script ) {
          var url = script.url + '&lang=' + self.participant.language_code + '&newtest=Y';

          // first see if a token already exists
          CnHttpFactory.instance( {
            path: 'script/' + script.id + '/token/uid=' + self.participant.uid,
            data: { select: { column: [ 'token', 'completed' ] } }
          } ).get().then( function( response ) {
            // launch the script
            url += '&token=' + response.data.token
            $window.open( url, 'cenozoScript' );
          } ).catch( function( response ) {
            if( 404 == response.status ) {
              console.info( 'The "404 (Not Found)" error found above is normal and can be ignored.' );

              // the token doesn't exist so create it
              var modal = CnModalMessageFactory.instance( {
                title: 'Please Wait',
                message: 'Please wait while the participant\'s data is retrieved.',
                block: true
              } );
              modal.show();

              CnHttpFactory.instance( {
                path: 'script/' + script.id + '/token',
                data: { uid: self.participant.uid }
              } ).post().then( function success( response ) {
                // close the wait message
                modal.close();

                // update the script list to reflect the new start datetime
                self.loadScriptList();

                // now get the new token string we just created and use it to open the script window
                CnHttpFactory.instance( {
                  path: 'script/' + script.id + '/token/' + response.data
                } ).get().then( function( response ) {
                  // launch the script
                  url += '&token=' + response.data.token
                  $window.open( url, 'cenozoScript' );
                } ).catch( CnSession.errorHandler );
              } ).catch( function( response ) {
                modal.close();
                CnSession.errorHandler( response );
              } );
            } else CnSession.errorHandler( response );
          } );
        };

        this.advanceQnaire = function() {
          return CnHttpFactory.instance( {
            path: 'assignment/0?operation=advance', data: {}
          } ).patch().then( self.onLoad ).catch( CnSession.errorHandler );
        };

        this.startCall = function( phone ) {
          if( CnSession.voip_enabled && !phone.international ) {
            // TODO VOIP: start call
          }

          CnHttpFactory.instance( {
            path: 'phone_call?operation=open',
            data: { phone_id: phone.id }
          } ).post().then( function() { self.onLoad( false ); } ).catch( CnSession.errorHandler );
        };

        this.endCall = function( status ) {
          if( CnSession.voip_enabled && !phone.international ) {
            // TODO VOIP: end call
          }

          CnHttpFactory.instance( {
            path: 'phone_call/0?operation=close',
            data: { status: status }
          } ).patch().then( function() { self.onLoad( false ); } ).catch( CnSession.errorHandler );
        };

        this.endAssignment = function() {
          if( null != self.assignment ) {
            CnHttpFactory.instance( {
              path: 'assignment/0'
            } ).get().then( function( response ) {
              return CnHttpFactory.instance( {
                path: 'assignment/0?operation=close', data: {}
              } ).patch().then( self.onLoad ).catch( CnSession.errorHandler );
            } ).catch( function( response ) {
              if( 307 == response.status ) {
                // 307 means the user has no active assignment, so just refresh the page data
                self.onLoad();
              } else { CnSession.errorHandler( response ); }
            } );
          }
        };
      };

      return { instance: function() { return new object(); } };
    }
  ] );

} );
