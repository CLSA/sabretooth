// we need the participant module for the special CnAssignmentControlFactory
define( [ 'participant' ].reduce( function( list, name ) {
  return list.concat( cenozoApp.module( name ).getRequiredFiles() );
}, [ cenozoApp.module( 'assignment' ).getFileUrl( 'module.js' ) ] ), function() {
  'use strict';

  var module = cenozoApp.module( 'assignment' );

  module.identifier.parent.friendly = 'qnaire';

  module.addInput( '', 'qnaire', {
    column: 'script.name',
    title: 'Questionnaire',
    type: 'string',
    isConstant: true
  }, 'participant' );
  module.addInput( '', 'queue', {
    column: 'queue.title',
    title: 'Queue',
    type: 'string',
    isConstant: true
  }, 'site' );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAssignmentControl', [
    'CnAssignmentControlFactory', 'CnSession', '$window',
    function( CnAssignmentControlFactory, CnSession, $window ) {
      return {
        templateUrl: cenozoApp.getFileUrl( 'assignment', 'control.tpl.html' ),
        restrict: 'E',
        controller: function( $scope ) {
          $scope.model = CnAssignmentControlFactory.instance();
          $scope.model.onLoad( false ); // breadcrumbs are handled by the service
        },
        link: function( scope ) {
          // update the script list whenever we regain focus since there may have been script activity
          var focusFn = function() { if( null != scope.model.assignment ) scope.model.loadScriptList(); };
          var win = angular.element( $window ).on( 'focus', focusFn );
          scope.$on( '$destroy', function() { win.off( 'focus', focusFn ); } );

          // close the session's script window whenever this page is unloaded (refreshed or closed)
          $window.onunload = function() { CnSession.closeScript(); };
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAssignmentControlFactory', [
    '$q', '$state', '$window', 'CnSession', 'CnHttpFactory',
    'CnParticipantModelFactory', 'CnScriptLauncherFactory', 'CnModalMessageFactory', 'CnModalConfirmFactory',
    function( $q, $state, $window, CnSession, CnHttpFactory,
              CnParticipantModelFactory, CnScriptLauncherFactory, CnModalMessageFactory, CnModalConfirmFactory ) {
      var object = function( root ) {
        var self = this;
        this.scriptLauncher = null;

        this.reset = function() {
          self.isOperator = false;
          self.assignment = null;
          self.prevAssignment = null;
          self.participant = null;
          self.phoneList = null;
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
          self.isForbidden = false;
          self.isPrevAssignmentLoading = false;
        };

        CnSession.promise.then( function() {
          self.isOperator = 'operator' == CnSession.role.name || 'operator+' == CnSession.role.name;
          self.application = CnSession.application.title;

          // add additional columns to the model
          var index = 0;
          self.participantModel.addColumn( 'rank', {
            title: 'Rank',
            column: 'queue.rank',
            type: 'rank',
            type: 'string'
          }, index++ );
          self.participantModel.addColumn( 'queue', {
            title: 'Queue',
            column: 'queue.name',
            type: 'string'
          }, index++ );
          self.participantModel.addColumn(
            'qnaire', { title: 'Questionnaire', column: 'script.name' }, index++ );
          self.participantModel.addColumn(
            'language', { title: 'Language', column: 'language.name' }, index++ );
          self.participantModel.addColumn(
            'availability', { title: 'Availability', column: 'availability_type.name' } );

          // add the reserved row as a hidden column to be used for highlighting reserved appointments
          self.participantModel.addColumn( 'reserved', { type: 'hidden', highlight: true } );
        } );

        this.participantModel = CnParticipantModelFactory.instance();

        // map assignment-control query parameters to participant-list
        this.participantModel.queryParameterSubject = 'assignment';

        // override the default column order for the participant list to rank
        this.participantModel.listModel.order = { column: 'rank', reverse: false };

        this.reset();

        // override the default order and set the heading
        this.participantModel.listModel.heading = 'Participant Selection List';

        // override model functions
        this.participantModel.getServiceCollectionPath = function() { return 'participant'; }
        this.participantModel.getServiceData = function( type, columnRestrictList ) {
          var data = this.$$getServiceData( type, columnRestrictList );
          data.assignment = true;
          return data;
        };

        // start a new assignment with a participant (provided by record) or whoever is available next
        this.beginAssignment = function( record ) {
          // attempt to assign the participant to the user
          CnModalConfirmFactory.instance( {
            title: 'Begin Assignment',
            message: angular.isDefined( record ) ?
              'Are you sure you wish to start a new assignment with participant ' + record.uid + '?' :
              'Are you sure you wish to start a new assignment with the next available participant?'
          } ).show().then( function( response ) {
            if( response ) {
              self.isAssignmentLoading = true; // show loading screen right away
              CnHttpFactory.instance( {
                path: 'assignment?operation=open',
                data: angular.isDefined( record ) ? { participant_id: record.id } : undefined,
                onError: function( response ) {
                  if( 408 == response.status ) {
                    // 408 means there are currently no participants available (this only happens with no record)
                    CnModalMessageFactory.instance( {
                      title: 'No participants available',
                      message: response.data,
                      error: true
                    } ).show().then( self.onLoad );
                  } else if( 409 == response.status ) {
                    // 409 means there is a conflict (the assignment can't be made)
                    CnModalMessageFactory.instance( {
                      title: angular.isDefined( record ) ?
                        'Unable to start assignment with ' + record.uid :
                        'Unable to start a new assignment',
                      message: response.data,
                      error: true
                    } ).show().then( self.onLoad );
                  } else {
                    CnModalMessageFactory.httpError( response ).then( function() {
                      self.isAssignmentLoading = false;
                    } );
                  }
                }
              } ).post().then( self.onLoad );
            }
          } );
        }

        // override the onChoose function
        this.participantModel.listModel.onSelect = this.beginAssignment;

        this.onLoad = function( closeScript ) {
          if( angular.isUndefined( closeScript ) ) closeScript = true;
          self.reset();
          self.isAssignmentLoading = true;
          self.isPrevAssignmentLoading = true;
          if( closeScript ) CnSession.closeScript();

          var column = [ 'id', 'interview_id', 'start_datetime',
            { table: 'participant', column: 'id', alias: 'participant_id' },
            { table: 'qnaire', column: 'id', alias: 'qnaire_id' },
            { table: 'script', column: 'id', alias: 'script_id' },
            { table: 'script', column: 'name', alias: 'qnaire' },
            { table: 'queue', column: 'title', alias: 'queue' }
          ];

          if( CnSession.application.checkForMissingHin ) column.push( 'missing_hin' );

          return CnHttpFactory.instance( {
            path: 'assignment/0',
            data: { select: { column: column } },
            onError: function( response ) {
              CnSession.updateData().then( function() {
                self.assignment = null;
                self.participant = null;
                self.isAssignmentLoading = false;
                self.isPrevAssignmentLoading = false;
                self.isForbidden = false;
                if( 307 == response.status ) {
                  // 307 means the user has no active assignment, so load the participant select list
                  CnSession.alertHeader = undefined;
                  self.participantModel.listModel.afterList( function() {
                    if( self.isOperator && 0 < self.participantModel.listModel.cache.length ) {
                      self.participantModel.listModel.heading =
                        'Participant Selection List (' + self.participantModel.listModel.cache[0].queue + ')';
                    }
                    CnSession.setBreadcrumbTrail( [ { title: 'Assignment' }, { title: 'Select' } ] );
                  } );
                } else if( 403 == response.status ) {
                  CnSession.alertHeader = 'You are currently in an assignment';
                  CnSession.setBreadcrumbTrail( [ { title: 'Assignment' }, { title: 'Wrong Site' } ] );
                  self.isForbidden = true;
                } else { CnModalMessageFactory.httpError( response ); }
              } );
            }
          } ).get().then( function( response ) {
            CnSession.updateData().then( function() {
              self.assignment = response.data;
              CnSession.alertHeader = 'You are currently in an assignment';

              // show a popup if the participant is missing HIN data
              // Note: this will only show if the participant has consented to provide HIN but hasn't provided an HIN number
              if( CnSession.application.checkForMissingHin && self.assignment.missing_hin ) {
                CnModalMessageFactory.instance( {
                  title: 'Missing HIN',
                  message:
                    'The participant has consented to provide their Health Insurance Number (HIN) but their number is not on file.\n\n' +
                    'Please ask the participant to provide their HIN number.  The details can be added in the participant\'s file ' +
                    'under the "HIN List" section.'
                } ).show();
              }

              // get the assigned participant's details
              CnHttpFactory.instance( {
                path: 'participant/' + self.assignment.participant_id,
                data: { select: { column: [
                  'id', 'uid', 'honorific', 'first_name', 'other_name', 'last_name', 'global_note',
                  { table: 'language', column: 'code', alias: 'language_code' },
                  { table: 'language', column: 'name', alias: 'language' }
                ] } }
              } ).get().then( function( response ) {
                self.participant = response.data;
                self.participant.getIdentifier = function() {
                  return self.participantModel.getIdentifierFromRecord( self.participant );
                };
                CnSession.setBreadcrumbTrail( [ { title: 'Assignment' }, { title: self.participant.uid } ] );
                self.isAssignmentLoading = false;
              } ).then( function() {
                CnHttpFactory.instance( {
                  path: 'assignment/0/phone_call',
                  data: { select: { column: [ 'end_datetime', 'status',
                    { table: 'phone', column: 'rank' },
                    { table: 'phone', column: 'type' },
                    { table: 'phone', column: 'number' }
                  ] } }
                } ).query().then( function( response ) {
                  self.phoneCallList = response.data;
                  var len = self.phoneCallList.length
                  self.activePhoneCall = 0 < len && null === self.phoneCallList[len-1].end_datetime
                                       ? self.phoneCallList[len-1]
                                       : null;
                } );
              } ).then( function() {
                if( null === self.qnaireList ) {
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
                  } );
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
                } ).query().then( function( response ) {
                  self.prevAssignment = 1 == response.data.length ? response.data[0] : null;
                  self.isPrevAssignmentLoading = false;
                } );
              } ).then( function() {
                CnHttpFactory.instance( {
                  path: 'participant/' + self.assignment.participant_id + '/phone',
                  data: {
                    select: { column: [ 'id', 'rank', 'type', 'number', 'international', 'note' ] },
                    modifier: {
                      where: { column: 'phone.active', operator: '=', value: true },
                      order: 'rank'
                    }
                  }
                } ).query().then( function( response ) {
                  self.phoneList = response.data;
                } );
              } ).then( function() {
                CnHttpFactory.instance( {
                  path: 'phone_call'
                } ).head().then( function( response ) {
                  self.phoneCallStatusList =
                    cenozo.parseEnumList( angular.fromJson( response.headers( 'Columns' ) ).status );
                } );
              } );
            } );
          } );
        };

        this.changeSiteRole = function() { CnSession.showSiteRoleModal(); };

        this.openNotes = function() {
          if( null != self.participant )
            $state.go( 'participant.notes', { identifier: self.participant.getIdentifier() } );
        };

        this.openHistory = function() {
          if( null != self.participant )
            $state.go( 'participant.history', { identifier: self.participant.getIdentifier() } );
        };

        this.useTimezone = function() {
          if( null != self.participant ) {
            CnSession.setTimezone( { 'participant_id': this.participant.id } ).then( function() {
              $state.go( 'self.wait' ).then( function() { $window.location.reload(); } );
            } );
          }
        };

        this.loadScriptList = function() {
          var promiseList = [];
          if( null != self.assignment ) {
            self.isScriptListLoading = true;

            promiseList.push(
              CnHttpFactory.instance( {
                path: 'participant/' + self.assignment.participant_id,
                data: { select: { column: [
                  { table: 'hold_type', column: 'name', alias: 'hold' },
                  { table: 'proxy_type', column: 'name', alias: 'proxy' }
                ] } }
              } ).get().then( function( response ) {
                if( null != self.participant )
                  self.participant.withdrawn = 'Withdrawn' == response.data.hold;
                  self.participant.proxy = null != response.data.proxy;
              } )
            );

            promiseList.push(
              CnHttpFactory.instance( {
                path: 'application/0/script?participant_id=' + self.assignment.participant_id,
                data: {
                  modifier: { order: ['repeated','name'] },
                  select: { column: [
                    'id', 'name', 'repeated', 'supporting', 'url', 'description',
                    { table: 'started_event', column: 'datetime', alias: 'started_datetime' },
                    { table: 'finished_event', column: 'datetime', alias: 'finished_datetime' }
                  ] }
                }
              } ).query().then( function( response ) {
                // put qnaire scripts in separate list and only include the current qnaire script in the main list
                self.scriptList = [];
                self.qnaireScriptList = [];
                response.data.forEach( function( item ) {
                  if( angular.isArray( self.qnaireList ) &&
                      null != self.qnaireList.findByProperty( 'script_id', item.id ) ) {
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
                      null == self.scriptList.findByProperty( 'id', self.activeScript.id ) ) {
                    self.activeScript = self.scriptList[0];
                  } else {
                    var activeScriptName = self.activeScript.name;
                    self.scriptList.forEach( function( item ) {
                      if( activeScriptName == item.name ) self.activeScript = item;
                    } );
                  }
                }
              } )
            );
          }

          return $q.all( promiseList ).finally( function() {
            self.isScriptListLoading = false;
          } );
        };

        this.launchingScript = false;
        this.launchScript = function( script ) {
          this.launchingScript = true;
          this.scriptLauncher = CnScriptLauncherFactory.instance( {
            script: script,
            identifier: 'uid=' + self.participant.uid,
            lang: self.participant.language_code
          } )
          this.scriptLauncher.launch().then( function() {
            self.loadScriptList();
          } ).finally( function() {
            self.launchingScript = false;
          } );

          // check for when the window gets focus back and update the participant details
          if( null != script.name.match( /withdraw|proxy/i ) ) {
            var win = angular.element( $window ).on( 'focus', function() {
              // the following will process the withdraw or proxy script (in case it was finished)
              CnHttpFactory.instance( {
                path: 'script/' + script.id + '/token/uid=' + self.participant.uid
              } ).get().then( function() { self.loadScriptList(); } );
              win.off( 'focus' );
            } );
          }
        };

        this.advanceQnaire = function() {
          return CnHttpFactory.instance( {
            path: 'assignment/0?operation=advance', data: {}
          } ).patch().then( self.onLoad );
        };

        this.startCall = function( phone ) {
          function postCall() {
            CnHttpFactory.instance( {
              path: 'phone_call?operation=open',
              data: { phone_id: phone.id }
            } ).post().then( self.onLoad );
          }

          // start by updating the voip status
          CnSession.updateVoip().finally( function() {
            if( !CnSession.voip.enabled ) {
              postCall();
            } else {
              if( !CnSession.voip.info ) {
                if( CnSession.setting.callWithoutWebphone ) {
                  postCall();
                } else if( !phone.international ) {
                  CnModalConfirmFactory.instance( {
                    title: 'Webphone Not Found',
                    message: 'You are about to place a call with no webphone connection. ' +
                             'If you choose to proceed you will have to contact the participant without the use ' +
                             'of the software-based telephone system. ' +
                             'If you wish to use the built-in telephone system click "No", then click on the ' +
                             '"Webphone" link under the "Utilities" submenu to connect to the webphone.\n\n' +
                             'Do you wish to proceed without a webphone connection?',
                  } ).show().then( function( response ) {
                    if( response ) postCall();
                  } );
                } else {
                  CnModalMessageFactory.instance( {
                    title: 'Webphone Not Found',
                    message: 'You cannot start a call without a webphone connection. ' +
                             'To use the built-in telephone system click on the "Webphone" link under the ' +
                             '"Utilities" submenu and make sure the webphone client is connected.',
                    error: true
                  } ).show();
                }
              } else {
                if( phone.international ) {
                  CnModalConfirmFactory.instance( {
                    title: 'International Phone Number',
                    message: 'The phone number you are about to call is international. ' +
                             'The VoIP system cannot place international calls so if you choose to proceed you ' +
                             'will have to contact the participant without the use of the software-based ' +
                             'telephone system.\n\n' +
                             'Do you wish to proceed without a webphone connection?',
                  } ).show().then( function( response ) {
                    if( response ) postCall();
                  } );
                } else {
                  CnHttpFactory.instance( {
                    path: 'voip',
                    data: { phone_id: phone.id }
                  } ).post().then( function( response ) {
                    if( 201 == response.status ) {
                      postCall();
                    } else {
                      CnModalMessageFactory.instance( {
                        title: 'Webphone Error',
                        message: 'The webphone was unable to place your call, please try again. ' +
                                 'If this problem persists then please contact support.',
                        error: true
                      } ).show();
                    }
                  } );
                }
              }
            }
          } );
        };

        this.endCall = function( status ) {
          if( CnSession.voip.enabled && CnSession.voip.info && !this.activePhoneCall.international ) {
            CnHttpFactory.instance( {
              path: 'voip/0',
              onError: function( response ) {
                if( 404 == response.status ) {
                  // ignore 404 errors, it just means there was no phone call found to hang up
                } else { CnModalMessageFactory.httpError( response ); }
              }
            } ).delete();
          }

          CnHttpFactory.instance( {
            path: 'phone_call/0?operation=close',
            data: { status: status }
          } ).patch().then( self.onLoad );
        };

        this.endAssignment = function() {
          if( null != self.assignment ) {
            CnHttpFactory.instance( {
              path: 'assignment/0',
              onError: function( response ) {
                if( 307 == response.status ) {
                  // 307 means the user has no active assignment, so just refresh the page data
                  self.onLoad();
                } else { CnModalMessageFactory.httpError( response ); }
              }
            } ).get().then( function( response ) {
              return CnHttpFactory.instance( {
                path: 'assignment/0?operation=close', data: {}
              } ).patch().then( self.onLoad );
            } );
          }
        };
      };

      return { instance: function() { return new object( false ); } };
    }
  ] );

} );
