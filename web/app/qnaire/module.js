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
      title: 'Rank',
      type: 'rank'
    },
    delay: {
      title: 'Delay',
      type: 'number'
    }
  },
  defaultOrder: {
    column: 'rank',
    reverse: false
  }
} );
