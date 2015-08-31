define( {
  subject: 'queue_state',
  identifier: {
    parent: [ {
      subject: 'queue',
      column: 'queue.name'
    }, {
      subject: 'site',
      column: 'site.name'
    }, {
      subject: 'qnaire',
      column: 'qnaire.rank'
    } ]
  },
  name: {
    singular: 'queue restriction',
    plural: 'queue restrictions',
    possessive: 'queue restriction\'s',
    pluralPossessive: 'queue restrictions\''
  },
  inputList: {
    queue_id: {
      title: 'Queue',
      type: 'enum'
    },
    site_id: {
      title: 'Site',
      type: 'enum'
    },
    qnaire_id: {
      title: 'Questionnaire',
      type: 'enum'
    }
  },
  columnList: {
    queue: {
      column: 'queue.title',
      title: 'Queue'
    },
    site: {
      column: 'site.name',
      title: 'Site'
    },
    qnaire: {
      column: 'script.name',
      title: 'Questionnaire'
    }
  },
  defaultOrder: {
    column: 'site.name',
    reverse: false
  }
} );
