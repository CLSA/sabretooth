'use strict';

var sabretoothApp = angular.module( 'sabretoothApp', [ 'cenozoApp' ] );

sabretoothApp.config( [
  '$stateProvider',
  function( $stateProvider ) {
    var subModuleList = [
      'Assignment',
      'CedarInstance',
      'Interview',
      'OpalInstance',
      'Qnaire',
      'Queue'
    ];

    for( var i = 0; i < subModuleList.length; i++ ) cnRouteModule( $stateProvider, subModuleList[i] );
  }
] );

sabretoothApp.controller( 'StMenuCtrl', [
  '$scope', '$state', '$location', 'CnHttpFactory',
  function( $scope, $state, $location, CnHttpFactory ) {
    $scope.isCurrentState = function isCurrentState( state ) { return $state.is( state ); };

    $scope.lists = [
      { sref: 'Activity', title: 'Activities' },
      { sref: 'Assignment', title: 'Assignments' },
      { sref: 'CedarInstance', title: 'Cedar Instances' },
      { sref: 'Collection', title: 'Collections' },
      { sref: 'Interview', title: 'Interviews' },
      { sref: 'Language', title: 'Languages' },
      { sref: 'OpalInstance', title: 'Opal Instances' },
      { sref: 'Participant', title: 'Participants' },
      { sref: 'Qnaire', title: 'Questionnaires' },
      { sref: 'Queue', title: 'Queues' },
      { sref: 'Quota', title: 'Quotas' },
      { sref: 'RegionSite', title: 'Region Sites' },
      { sref: 'Setting', title: 'Settings' },
      { sref: 'Site', title: 'Sites' },
      { sref: 'State', title: 'States' },
      { sref: 'SystemMessage', title: 'System Messages' },
      { sref: 'User', title: 'Users' }
    ];

    $scope.utilities = [
      { sref: 'ParticipantMultiedit', title: 'Participant Multiedit' },
      { sref: 'ParticipantMultinote', title: 'Participant Note' },
      { sref: 'ParticipantReassign', title: 'Participant Reassign' },
      { sref: 'ParticipantSearch', title: 'Participant Search' },
      { sref: 'ParticipantTree', title: 'Participant Tree' }
    ];

    $scope.reports = [
      { sref: 'CallHistory', title: 'Call History' },
      { sref: 'ConsentRequired', title: 'Consent Required' },
      { sref: 'Email', title: 'Email' },
      { sref: 'MailoutRequired', title: 'Mailout Required' },
      { sref: 'Participant', title: 'Participant' },
      { sref: 'ParticipantStatus', title: 'Participant Status' },
      { sref: 'ParticipantTree', title: 'Participant Tree' },
      { sref: 'Productivity', title: 'Productivity' },
      { sref: 'Sample', title: 'Sample' },
      { sref: 'Timing', title: 'Timing' }
    ];
  }
] );
