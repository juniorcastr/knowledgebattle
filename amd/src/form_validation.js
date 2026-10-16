// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Client-side validation of the activity settings form.
 *
 * The topic text is mandatory when the content scope is "Custom topic" (value 1).
 *
 * @module     mod_knowledgebattle/form_validation
 * @copyright  2024 Junio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/str'], function(Str) {
    'use strict';

    var ERROR_ID = 'id_error_topic_text';

    /**
     * Marks the topic field as invalid and shows the message.
     *
     * @param {HTMLElement} topic Topic text input.
     * @param {String} message Error message.
     */
    var showError = function(topic, message) {
        var parent = topic.closest('.fitem');
        if (parent) {
            parent.classList.add('has-danger', 'text-danger');
        }
        topic.classList.add('is-invalid');

        var error = document.getElementById(ERROR_ID);
        if (!error) {
            error = document.createElement('div');
            error.id = ERROR_ID;
            error.className = 'form-control-feedback invalid-feedback';
            topic.parentNode.appendChild(error);
        }
        error.textContent = message;
        error.style.display = 'block';
        topic.focus();
    };

    /**
     * Clears the error state of the topic field.
     *
     * @param {HTMLElement} topic Topic text input.
     */
    var clearError = function(topic) {
        var parent = topic.closest('.fitem');
        if (parent) {
            parent.classList.remove('has-danger', 'text-danger');
        }
        topic.classList.remove('is-invalid');
        var error = document.getElementById(ERROR_ID);
        if (error) {
            error.style.display = 'none';
        }
    };

    /**
     * Initialise the validation.
     */
    var init = function() {
        var form = document.querySelector('form.mform');
        if (!form) {
            return;
        }

        form.addEventListener('submit', function(e) {
            var scope = form.querySelector('[name="content_scope"]');
            var topic = form.querySelector('[name="topic_text"]');
            if (scope && topic && scope.value === '1' && topic.value.trim() === '') {
                e.preventDefault();
                e.stopPropagation();
                Str.get_string('required', 'moodle').then(function(message) {
                    showError(topic, message);
                    return true;
                }).catch(function() {
                    // The server side validation still applies.
                    return false;
                });
            }
        }, true);

        var topicEl = form.querySelector('[name="topic_text"]');
        if (topicEl) {
            topicEl.addEventListener('input', function() {
                if (topicEl.value.trim() !== '') {
                    clearError(topicEl);
                }
            });
        }
    };

    return {
        init: init
    };
});
