var eventoDraftBase = {
	"id": "",
	"title": "arenseigner",
	"place": "",
	"description": "arenseigner",
	"created": "",
	"updated": "",
	"closed": "",
	"settings": {
		"limit_participants": 0,
		"limit_participants_nb": "10",
		"disable_answer_edition": 0,
		"dont_notify_on_reply": 0,
		"hide_answers": 0,
		"hide_comments": 0,
		"time_zone": "Europe/Paris",
		"auto_close": "arenseigner timestampunix",
		"reply_access": "opened_to_everyone",
		"enable_anonymous_answer": 0,
		"dont_receive_invitation_copy": 0
	},
	"questions": [
		{
			"title": "",
			"type": "date",
			"proposition_type": "default",
			"position": 0,
			"options": {
				"force_unique_choice": 0,
				"enable_maybe_choices": 0
			},
			"propositions": [
				{
//					"tmp_id": "ui-id-13",
					"base_day": 1718409600,
					"local_base_day": 1718409600,
					"type": "range_of_hours",
					"base_time": 57600,
					"end_time": 61200,
					"label": "à formatter 15/06/2024 - 18:00 à 19:00 achanger"
				}
			],
			"constraints": [],
			"tmp_id": "ui-id-3"
		}
	],
	"owners": [],
	"guests": [],
	"new_guests": [],
	"notify_new_guests": false,
	"notify_update": false,
	"initialStep": "general",
	"all_calendars": 1,
	"force_unique_choice": 0,
	"enable_maybe_choices": 0,
	"question_limit_choice": 0,
	"question_limit_choice_nb": "1",
	"same_variants_for_all": 0,
	"same_hours_for_all": 1,
	"whole_day": 0,
	"slot": 1,
	"accept_terms": 0,
	"is_draft": 1
};


var eventoSurveyDraftPropositions = {
	"id": null,
	"title": null,
	"path": null,
	"place": "",
	"description": null,
	"created": {
		"raw": 1718977569,
		"formatted": "21/06/2024"
	},
	"updated": {
		"raw": null,
		"formatted": "21/06/2024"
	},
	"closed": null,
	"settings": {
		"limit_participants": 0,
		"limit_participants_nb": "10",
		"disable_answer_edition": 0,
		"dont_notify_on_reply": 0,
		"hide_answers": 0,
		"hide_comments": 0,
		"time_zone": "Europe/Paris",
		"auto_close": 1721520000,
		"reply_access": "opened_to_everyone",
		"enable_anonymous_answer": 0,
		"dont_receive_invitation_copy": 0,
		"auto_close_max": 1750464000,
		"auto_close_min": 1719014400
	},
	"questions": [
		{
			"title": "",
			"type": "date",
			"proposition_type": "default",
			"position": 0,
			"options": {
				"force_unique_choice": 0,
				"enable_maybe_choices": 0
			},
			"propositions": [
				{
					"base_day": 1718928000,
					"local_base_day": 1718928000,
					"type": "range_of_hours",
					"base_time": 50400,
					"end_time": 54000,
					"label": "21/06/2024 - 16:00 à 17:00"
				},
				{
					"base_day": 1719273600,
					"local_base_day": 1719273600,
					"type": "range_of_hours",
					"base_time": 50400,
					"end_time": 54000,
					"label": "25/06/2024 - 16:00 à 17:00"
				}
			],
			"constraints": [],
			"id": "1652385",
		}
	],
	"is_draft": 1,
	"is_closed": false,
	"owners": [
		{
			"name": "Etienne Bohm",
			"email": "Etienne.Bohm@univ-paris1.fr"
		}
	],
	"guests": [],
	"nb_participants": 0,
	"new_guests": []
}

var eventoSurveyBase = {
    "id": "",
    "title": "arenseigner",
    "place": "",
    "description": "arenseigner",
    "settings": {
      "limit_participants": 0,
      "limit_participants_nb": "100",
      "disable_answer_edition": 0,
      "dont_notify_on_reply": 0,
      "hide_answers": 0,
      "hide_comments": 0,
      "time_zone": "Europe/Paris",
      "auto_close": "arenseigner timestampunix",
      "reply_access": "opened_to_everyone",
      "enable_anonymous_answer": 1,
      "dont_receive_invitation_copy": 0
    },
    "questions": [
      {
        "title": "",
        "type": "date",
        "proposition_type": "default",
        "position": 0,
        "options": {
          "force_unique_choice": 0,
          "enable_maybe_choices": 0
        },
        "propositions": [
          {
            "base_day": 1711929600,
            "local_base_day": 1711929600,
            "type": "range_of_hours",
            "base_time": 43200,
            "end_time": 46800,
          }
        ],
        "constraints": [],
      }
    ],
    "owners": [],
    "guests": [],
    "new_guests": [],
    "notify_new_guests": false,
    "notify_update": false,
    "accept_terms": 1,
    "is_draft": 0
};