define( {
  subject: 'setting',
  identifier: {
    parent: {
      subject: 'site',
      column: 'site_id',
      friendly: 'site'
    }
  },
  name: {
    singular: 'setting',
    plural: 'settings',
    possessive: 'setting\'s',
    pluralPossessive: 'settings\''
  },
  inputList: {
    site: {
      column: 'site.name',
      title: 'Site',
      type: 'string',
      constant: true
    },
    survey_without_sip: {
      title: 'Allow No-Call Interviewing',
      type: 'boolean',
      help: 'Allow operators to interview participants without being in an active VoIP call'
    },
    calling_start_time: {
      title: 'Earliest Call Time',
      type: 'time',
      help: 'The earliest time to assign participants (in their local time)'
    },
    calling_end_time: {
      title: 'Latest Call Time',
      type: 'time',
      help: 'The latest time to assign participants (in their local time)'
    },
    short_appointment: {
      title: 'Short Appointment Length',
      type: 'string',
      format: 'integer',
      minValue: 0,
      help: 'The length of time, in minutes, of a short appointment'
    },
    long_appointment: {
      title: 'Long Appointment Length',
      type: 'string',
      format: 'integer',
      minValue: 0,
      help: 'The length of time, in minutes, of a long appointment'
    },
    pre_call_window: {
      title: 'Pre-Appointment Call Window',
      type: 'string',
      format: 'integer',
      minValue: 0,
      help: 'How many minutes before an appointment or callback that a participant can be assigned'
    },
    post_call_window: {
      title: 'Post-Appointment Call Window',
      type: 'string',
      format: 'integer',
      minValue: 0,
      help: 'How many minutes after an appointment before it is considered missed'
    }
  },
  columnList: {
    site: {
      column: 'site.name',
      title: 'Site'
    },
    survey_without_sip: {
      title: 'No-Call',
      type: 'boolean'
    },
    calling_start_time: {
      title: 'Start Call',
      type: 'time'
    },
    calling_end_time: {
      title: 'End Call',
      type: 'time'
    },
    short_appointment: {
      title: 'Short Ap.',
      type: 'number'
    },
    long_appointment: {
      title: 'Long Ap.',
      type: 'number'
    },
    pre_call_window: {
      title: 'Pre-Call',
      type: 'number'
    },
    post_call_window: {
      title: 'Post-Call',
      type: 'number'
    }
  },
  defaultOrder: {
    column: 'site.name',
    reverse: false
  }
} );
