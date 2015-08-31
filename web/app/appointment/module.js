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
      column: 'script.name',
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
    user_id: {
      column: 'appointment.user_id',
      title: 'Reserved for',
      type: 'lookup-typeahead',
      typeahead: {
        table: 'user',
        select: 'CONCAT( first_name, " ", last_name, " (", name, ")" )',
        where: [ 'first_name', 'last_name', 'name' ]
      },
      help: 'The user the appointment is specifically reserved for. ' +
            'Cannot be changed once the appointment has passed.'
    },
    assignment_user: {
      column: 'assignment_user.name',
      title: 'Assigned to',
      type: 'string',
      constant: true,
      help: 'This will remain blank until the appointment has been assigned. The assigned user can only be ' +
            ' different from the reserved user when the appointment was missed.'
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
      title: 'Number'
    },
    user: {
      column: 'user.name',
      type: 'string',
      title: 'Reserved For'
    },
    assignment_user: {
      column: 'assignment_user.name',
      type: 'string',
      title: 'Assigned to'
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
