define( {
  subject: 'callback',
  identifier: {
    parent: {
      subject: 'interview',
      column: 'interview_id',
      friendly: 'qnaire'
    }
  },
  name: {
    singular: 'callback',
    plural: 'callbacks',
    possessive: 'callback\'s',
    pluralPossessive: 'callbacks\''
  },
  inputList: {
    datetime: {
      title: 'Date & Time',
      type: 'datetime',
      min: 'now',
      help: 'Cannot be changed once the callback has passed.'
    },
    participant: {
      column: 'participant.uid',
      title: 'Participant',
      type: 'string',
      constant: true
    },
    qnaire: {
      column: 'script.name',
      title: 'Questionnaire',
      type: 'string',
      constant: true
    },
    phone_id: {
      title: 'Phone Number',
      type: 'enum',
      help: 'Which number should be called for the callback, or leave this field blank if any of the ' +
            'participant\'s phone numbers can be called.'
    },
    assignment_user: {
      column: 'assignment_user.name',
      title: 'Assigned to',
      type: 'string',
      constant: true,
      help: 'This will remain blank until the callback has been assigned. The assigned user can only be ' +
            ' different from the reserved user when the callback was missed.'
    },
    state: {
      title: 'State',
      type: 'string',
      constant: true,
      help: 'One of reached, not reached, upcoming, assignable, missed, incomplete, assigned or in progress'
    }
  },
  columnList: {
    datetime: {
      type: 'datetime',
      title: 'Date & Time'
    },
    phone: {
      column: 'phone.name',
      type: 'string',
      title: 'Phone Number'
    },
    assignment_user: {
      column: 'assignment_user.name',
      type: 'string',
      title: 'Assigned to'
    },
    state: {
      type: 'string',
      title: 'State',
      help: 'One of reached, not reached, upcoming, assignable, missed, incomplete, assigned or in progress'
    }
  },
  defaultOrder: {
    column: 'callback.datetime',
    reverse: true
  }
} );
