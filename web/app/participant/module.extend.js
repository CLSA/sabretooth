// extend the framework's module
define( [ cenozoApp.module( 'participant' ).getFileUrl( 'module.js' ) ], function() {
  'use strict';

  var module = cenozoApp.module( 'participant' );
  module.addInputGroup( 'Queue Details', {
    title: {
      title: 'Current Questionnaire',
      column: 'qnaire.title',
      type: 'string',
      constant: true
    },
    start_date: {
      title: 'Delayed Until',
      column: 'qnaire.start_date',
      type: 'date',
      constant: true,
      help: 'If not empty then the participant will not be permitted to begin this questionnaire until the ' +
            'date shown is reached.'
    },
    queue: {
      title: 'Current Queue',
      column: 'queue.name',
      type: 'string',
      constant: true
    },
    override_quota: {
      title: 'Override Quota',
      type: 'boolean'
    }
  } );

  angular.extend( module.historyCategoryList, {

    // appointments are added in the assignment's promise function below
    Appointment: { active: true },

    Assignment: {
      active: true,
      promise: function( historyList, $state, CnHttpFactory, $q ) {
        return CnHttpFactory.instance( {
          path: 'participant/' + $state.params.identifier + '/interview',
          data: {
            modifier: { order: { start_datetime: true } },
            select: { column: [ 'id' ] }
          }
        } ).query().then( function( response ) {
          var promiseArray = [];
          response.data.forEach( function( item ) {
            // appointments
            promiseArray.push(
              CnHttpFactory.instance( {
                path: 'interview/' + item.id + '/appointment',
                data: {
                  modifier: { order: { start_datetime: true } },
                  select: {
                    column: [ 'datetime', 'type', 'outcome', 'assignment_id', 'user_id', {
                      table: 'user',
                      column: 'first_name',
                      alias: 'user_first'
                    }, {
                      table: 'user',
                      column: 'last_name',
                      alias: 'user_last'
                    } ]
                  }
                }
              } ).query().then( function( response ) {
                response.data.forEach( function( item ) {
                  var description = 'A ' + item.type + ' appointment scheduled for this time has ';
                  if( 'cancelled' == item.outcome ) {
                    description += 'been cancelled.';
                  } else {
                    description += item.assignment_id
                                 ? 'been met.\nDuring the call the participant was ' + item.outcome + '.\n'
                                 : 'not yet been met.';
                  }
                  historyList.push( {
                    datetime: item.datetime,
                    category: 'Appointment',
                    title: 'scheduled for ' +
                      ( null == item.user_id ? 'any operator' : item.user_first + ' ' + item.user_last ),
                    description: description
                  } );
                } );
              } )
            );

            // assignments
            promiseArray.push(
              CnHttpFactory.instance( {
                path: 'interview/' + item.id + '/assignment',
                data: {
                  modifier: { order: { start_datetime: true } },
                  select: {
                    column: [ 'start_datetime', 'end_datetime', {
                      table: 'user',
                      column: 'first_name',
                      alias: 'user_first'
                    }, {
                      table: 'user',
                      column: 'last_name',
                      alias: 'user_last'
                    }, {
                      table: 'site',
                      column: 'name',
                      alias: 'site'
                    }, {
                      table: 'script',
                      column: 'name',
                      alias: 'script'
                    }, {
                      table: 'queue',
                      column: 'name',
                      alias: 'queue'
                    } ]
                  }
                }
              } ).query().then( function( response ) {
                response.data.forEach( function( item ) {
                  if( null != item.start_datetime ) {
                    historyList.push( {
                      datetime: item.start_datetime,
                      category: 'Assignment',
                      title: 'started by ' + item.user_first + ' ' + item.user_last,
                      description: 'Started an assignment for the "' + item.script + '" questionnaire.\n' +
                                   'Assigned from the ' + item.site + ' site ' +
                                   'from the "' + item.queue + '" queue.'
                    } );
                  }
                  if( null != item.end_datetime ) {
                    historyList.push( {
                      datetime: item.end_datetime,
                      category: 'Assignment',
                      title: 'completed by ' + item.user_first + ' ' + item.user_last,
                      description: 'Completed an assignment for the "' + item.script + '" questionnaire.\n' +
                                   'Assigned from the ' + item.site + ' site ' +
                                   'from the "' + item.queue + '" queue.'
                    } );
                  }
                } );
              } )
            );

          } );
          return $q.all( promiseArray );
        } );
      }
    },

  } );

} );
