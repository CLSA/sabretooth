define( {
  subject: 'cedar_instance',
  name: {
    singular: 'cedar instance',
    plural: 'cedar instances',
    possessive: 'cedar instance\'s',
    pluralPossessive: 'cedar instances\''
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
