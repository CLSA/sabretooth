define( {
  subject: 'qnaire',
  identifier: { column: 'rank' },
  name: {
    singular: 'questionnaire',
    plural: 'questionnaires',
    possessive: 'questionnaire\'s',
    pluralPossessive: 'questionnaires\''
  },
  inputList: {
    rank: {
      column: 'qnaire.rank',
      title: 'Rank',
      type: 'rank'
    },
    script_id: {
      title: 'Script',
      type: 'enum',
      noedit: true
    },
    prev_qnaire_id: {
      title: 'Previous Questionnaire',
      type: 'enum',
      noself: true // previous qnaire cannot be itself
    },
    delay: {
      title: 'Delay (weeks)',
      type: 'string',
      format: 'integer',
      minValue: 0
    }
  },
  columnList: {
    name: {
      column: 'script.name',
      title: 'Name'
    },
    rank: {
      column: 'qnaire.rank',
      title: 'Rank',
      type: 'rank'
    },
    previous: {
      column: 'prev_script.name',
      title: 'Previous'
    },
    delay: {
      column: 'qnaire.delay',
      title: 'Delay',
      type: 'number'
    }
  },
  defaultOrder: {
    column: 'qnaire.rank',
    reverse: false
  }
} );
