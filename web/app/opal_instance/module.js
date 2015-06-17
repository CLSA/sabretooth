define( {
  subject: 'opal_instance',
  identifier: {}, // standard
  name: {
    singular: 'opal instance',
    plural: 'opal instances',
    possessive: 'opal instance\'s',
    pluralPossessive: 'opal instances\''
  },
  inputList: {
    // TODO: fill out
  },
  columnList: {
    name: {
      column: 'user.name',
      title: 'Name'
    },
    active: {
      column: 'user.active',
      title: 'Active',
      type: 'boolean'
    },
    last_datetime: {
      title: 'Last Activity',
      type: 'datetime'
    }
  },
  defaultOrder: {
    column: 'name',
    reverse: false
  }
} );
