const classes = await import(`${CENOZO_URL}/js/model/setting.mjs`);

export class CN_model_setting extends classes.CN_model_setting {
  /**
   * Extend parent method
   */
  async clone_columns() {
    return {
      ...await super.clone_columns(),
      ...{
        mail_name: {
          title: "Default Email Name",
          help: "The default sender's name that emails will be sent from.",
        },
        mail_address: {
          title: "Default Email Address",
          help: "The default email address that emails will be sent from.",
        },
        call_without_webphone: {
          title: "No-Webphone",
          type: "boolean",
          help: "Allow users to make calls without being connected to the webphone.",
        },
        last_contacted: {
          title: "Contacted Column",
          type: "boolean",
          help: "Whether to show to Last Contacted column in the assignment control participant selection list.",
        },
        calling_start_time: {
          title: "Start Call",
          type: "time",
          help: "The earliest time to assign participants (in their local time).",
        },
        calling_end_time: {
          title: "End Call",
          type: "time",
          help: "The latest time to assign participants (in their local time).",
        },
        appointment_duration: {
          title: "Default Appointment",
          type: "integer",
          help: `
            The length of time, in minutes, of the default appointment.
            Value must be in 30-minute increments.
          `,
        },
        pre_call_window: {
          title: "Pre-Call",
          type: "integer",
          help: "How many minutes before an appointment that a participant can be assigned.",
        },
        post_call_window: {
          title: "Post-Call",
          type: "integer",
          help: "How many minutes after an appointment before it is considered missed.",
        },
      },
    };
  }

  /**
   * Extend parent method
   */
  async clone_properties() {
    return {
      ...await super.clone_properties(),
      ...{
        mail_name: {
          title: "Default Email Name",
          help: "The default sender's name that emails will be sent from.",
        },
        mail_address: {
          title: "Default Email Address",
          help: "The default email address that emails will be sent from.",
        },
        call_without_webphone: {
          title: "Allow calls without a webphone",
          type: "boolean",
          help: "Allow users to make calls without being connected to the webphone.",
        },
        last_contacted: {
          title: "Show Last Contacted Column",
          type: "boolean",
          help: "Whether to show to Last Contacted column in the assignment control participant selection list.",
        },
        calling_start_time: {
          title: "Earliest Call Time",
          type: "time",
          help: "The earliest time to assign participants (in their local time).",
        },
        calling_end_time: {
          title: "Latest Call Time",
          type: "time",
          help: "The latest time to assign participants (in their local time).",
        },
        appointment_duration: {
          title: "Default Appointment Length",
          type: "integer",
          help: `
            The length of time, in minutes, of the default appointment.
            Value must be in 30-minute increments.
          `,
        },
        pre_call_window: {
          title: "Pre-Appointment Window",
          type: "integer",
          get_min: () => 0,
          help: "How many minutes before an appointment that a participant can be assigned.",
        },
        post_call_window: {
          title: "Post-Appointment Window",
          type: "integer",
          get_min: () => 0,
          help: "How many minutes after an appointment before it is considered missed.",
        },

        call_wait_times: {
          title: "Last Call Wait Times",
          properties: {
            contacted_wait: {
              title: "Contacted Wait",
              type: "integer",
              get_min: () => 0,
              help: 'How many minutes after a "contacted" call result to allow a participant to be called',
            },  
            busy_wait: {
              title: "Busy Wait",
              type: "integer",
              get_min: () => 0,
              help: 'How many minutes after a "busy" call result to allow a participant to be called',
            },  
            fax_wait: {
              title: "Fax Wait",
              type: "integer",
              get_min: () => 0,
              help: 'How many minutes after a "fax" call result to allow a participant to be called',
            },  
            no_answer_wait: {
              title: "No Answer Wait",
              type: "integer",
              get_min: () => 0,
              help: 'How many minutes after a "no answer" call result to allow a participant to be called',
            },  
            not_reached_wait: {
              title: "Not Reached Wait",
              type: "integer",
              get_min: () => 0,
              help: 'How many minutes after a "not reached" call result to allow a participant to be called',
            },  
            hang_up_wait: {
              title: "Hang Up Wait",
              type: "integer",
              get_min: () => 0,
              help: 'How many minutes after a "hang up" call result to allow a participant to be called',
            },
            soft_refusal_wait: {
              title: "Soft Refusal Wait",
              type: "integer",
              get_min: () => 0,
              help: 'How many minutes after a "soft refusal" call result to allow a participant to be called',
            },
          },
        },
      },
    };
  }
}
