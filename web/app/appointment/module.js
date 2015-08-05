define( {
  subject: 'appointment',
  identifier: {
    parent: {
      subject: 'interview',
      column: 'interview_id',
      friendly: 'qnaire'
    }
  },
  name: {
    singular: 'appointment',
    plural: 'appointments',
    possessive: 'appointment\'s',
    pluralPossessive: 'appointments\''
  },
  inputList: {
    datetime: {
      title: 'Date & Time',
      type: 'datetime',
      min: 'now',
      help: 'Cannot be changed once the appointment has passed.'
    },
    participant: {
      column: 'participant.uid',
      title: 'Participant',
      type: 'string',
      constant: true
    },
    qnaire: {
      column: 'qnaire.name',
      title: 'Questionnaire',
      type: 'string',
      constant: true
    },
    phone_id: {
      title: 'Phone Number',
      type: 'enum',
      help: 'Which number should be called for the appointment, or leave this field blank if any of the ' +
            'participant\'s phone numbers can be called.'
    },
    user: {
      column: 'user.name',
      title: 'Assigned to',
      type: 'string',
      constant: true,
      help: 'This will remain blank until the appointment has been assigned.'
    },
    state: {
      title: 'State',
      type: 'string',
      constant: true,
      help: 'One of reached, not reached, upcoming, assignable, missed, incomplete, assigned or in progress'
    },
    type: {
      title: 'Type',
      type: 'enum'
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
    type: {
      type: 'string',
      title: 'Type'
    },
    state: {
      type: 'string',
      title: 'State',
      help: 'One of reached, not reached, upcoming, assignable, missed, incomplete, assigned or in progress'
    }
  },
  defaultOrder: {
    column: 'appointment.datetime',
    reverse: true
  }
} );
