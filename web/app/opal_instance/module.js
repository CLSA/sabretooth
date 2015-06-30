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
    active: {
      title: 'Active',
      type: 'boolean'
    },
    username: {
      title: 'Username',
      type: 'string'
    },
    password: {
      title: 'Password',
      type: 'string',
      noview: true
    }
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
    last_access_datetime: {
      title: 'Last Activity',
      type: 'datetime'
    }
  },
  defaultOrder: {
    column: 'name',
    reverse: false
  }
} );
