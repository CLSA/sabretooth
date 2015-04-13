define( {
  subject: 'opal_instance',
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
      filter: 'cnYesNo'
    },
    last_datetime: {
      title: 'Last Activity',
      filter: 'date:"MMM d, y HH:mm"'
    }
  },
  defaultOrder: {
    column: 'name',
    reverse: false
  }
} );
