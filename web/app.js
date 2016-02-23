'use strict';

var cenozo = angular.module( 'cenozo' );

cenozo.controller( 'HeaderCtrl', [
  '$scope', 'CnBaseHeader', 'CnHttpFactory', 'CnModalMessageFactory',
  function( $scope, CnBaseHeader, CnHttpFactory, CnModalMessageFactory ) {
    // copy all properties from the base header
    CnBaseHeader.construct( $scope );

    // add custom operations here by adding a new property to $scope.operationList
  }
] );
