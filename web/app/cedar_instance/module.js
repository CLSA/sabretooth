define( {
  subject: 'cedar_instance',
  identifier: {}, // standard
  name: {
    singular: 'cedar instance',
    plural: 'cedar instances',
    possessive: 'cedar instance\'s',
    pluralPossessive: 'cedar instances\'',
    friendlyColumn: 'username'
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
      regex: '^((?!(password)).){8,}$', // length >= 8 and can't have "password"
      noview: true,
      help: 'Passwords must be at least 8 characters long and cannot contain the word "password"'
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
