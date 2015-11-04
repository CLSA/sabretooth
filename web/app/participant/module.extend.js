// extend the framework's module
var module = cenozoApp.module( 'participant' );
define( [ module.url + 'module.js' ], function() {

  module.addInputGroup( 'Queue Details', {
    title: {
      title: 'Current Questionnaire',
      column: 'qnaire.title',
      type: 'string',
      constant: true
    },
    start_date: {
      title: 'Questionnaire Start',
      column: 'qnaire.start_date',
      type: 'date',
      constant: true
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

    // appointments and callbacks are added in the assignment's promise function below
    Appointment: { active: true },
    Callback: { active: true },

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
                    column: [ 'datetime', 'type', 'reached', 'assignment_id', 'user_id', {
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
                  description += item.assignment_id
                               ? 'been met.\nDuring the call the participant was ' +
                                 ( item.reached ? 'reached' : 'not reached' ) + '.\n'
                               : 'not yet been met.';
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

            // callbacks
            promiseArray.push(
              CnHttpFactory.instance( {
                path: 'interview/' + item.id + '/callback',
                data: {
                  modifier: { order: { start_datetime: true } },
                  select: { column: [ 'datetime', 'reached', 'assignment_id' ] }
                }
              } ).query().then( function( response ) {
                response.data.forEach( function( item ) {
                  var description = 'A callback scheduled for this time has ';
                  description += item.assignment_id
                               ? 'been met.\nDuring the call the participant was ' +
                                 ( item.reached ? 'reached' : 'not reached' ) + '.\n'
                               : 'not yet been met.';
                  historyList.push( {
                    datetime: item.datetime,
                    category: 'Callback',
                    title: 'scheduled',
                    description: description
                  } );
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
