/**
 * Query management code.
 *
 * @module      quiz_datawarehouse/managequeries
 * @copyright   2023 Luca Bösch <luca.boesch@bfh.ch>
 */
define(
    ['jquery', 'core/ajax', 'core/str', 'core/notification'],
    function($, ajax, str, notification) {
        var manager = {
            /**
             * Confirm removal of the specified template.
             *
             * @method removeTemplate
             * @param {EventFacade} e The EventFacade
             */
            removeQuery: function(e) {
                e.preventDefault();
                var targetUrl = $(e.currentTarget).attr('href');
                str.get_strings([
                    {
                        key:        'confirmqueryremovaltitle',
                        component:  'quiz_datawarehouse'
                    },
                    {
                        key:        'confirmqueryremovalquestion',
                        component:  'quiz_datawarehouse'
                    },
                    {
                        key:        'yes',
                        component:  'moodle'
                    },
                    {
                        key:        'no',
                        component:  'moodle'
                    }
                ])
                .then(function(s) {
                    notification.confirm(s[0], s[1], s[2], s[3], function() {
                        window.location = targetUrl;
                    });

                    return;
                })
                .catch();
            },

            /**
             * Setup the template management UI.
             *
             * @method setup
             */
            setup: function() {
                $('body').delegate('[data-action="delete"]', 'click', manager.removeQuery);
            }
        };

        return /** @alias module:quiz_datawarehouse/managequeries */ {
            /**
             * Setup the query management UI.
             *
             * @method setup
             */
            setup: manager.setup
        };
    });
