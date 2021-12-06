<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Administration page for topictagged question type
 *
 * @package   qtype_topictagged
 * @copyright 2021 Andrei David; Ștefan Jumărea
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');

require_once($CFG->libdir . '/questionlib.php');
require_once('utils.php');

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid]);
$coursecontext = context_course::instance($courseid);

require_login($course);
require_capability('moodle/course:manageactivities', $coursecontext);
/*
$PAGE->set_context($coursecontext);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('activity-edit-list');
$PAGE->set_title("Title"); //get_string('editactivities', 'qtype_topictagged'));
$PAGE->set_url('/question/type/topictagged/index.php', ['id' => $courseid]);
*/
echo $OUTPUT->header();

$utils = new \qtype_topictagged\utils();

$mform = new \qtype_topictagged\output\simple_form($courseid);
if ($formdata = $mform->get_data()) {
	if (!empty($formdata->download_button)) {
            // Download CSV
	
	    $categoryid = strtok($formdata->download_category, ',');
	    $contextid = strtok('');
			
	    if ($formdata->download_mode == '0') { // MXML
		$download_url = question_make_export_url($contextid, $categoryid, 'xml', 'withcategories', 'withcontexts', 'Question.xml');

	    }
	    else if ($formdata->download_mode == '1') { // CSV
		    $download_url = new moodle_url('/question/type/topictagged/download.php',
			    array(
				    'id' => $courseid,
				    'category' => $categoryid,
				    'context' => $contextid
			    ));

	    }
	    $admin_url = new moodle_url('/question/type/topictagged/index.php', array('id' => $courseid));

	    echo '
		<script>
		    window.location.href = "' . $download_url->out(false) . '";
			    setTimeout( () => { window.location.href = "' . $admin_url->out(false) . '"; }, 500);
		</script>
	    ';

	}
	else if (!empty($formdata->update_button)) {
            // Update DB

            $categoryid = strtok($formdata->update_category, ',');
            // Get all question from category having the tag `last_used` set
            global $DB;
            $query = '
                SELECT all_entries.itemid, all_entries.name
                FROM (
                    SELECT tag_instance.itemid, tag.name, tag_instance.contextid
                    FROM {tag} tag
                    JOIN {tag_instance} tag_instance
                    ON tag.id = tag_instance.tagid
                WHERE strcmp(upper(tag_instance.itemtype), \'QUESTION\') = 0
                    AND tag.name like "last_used%"
                ) AS all_entries
                JOIN {question} question
                ON question.id = all_entries.itemid
                WHERE question.category = ' . $categoryid . ';
            ';

            // iterate through question
            $records = $DB->get_records_sql($query);
            foreach ($records as $raw_record) {
                $record = [];
                $record['questionid'] = $raw_record->itemid;
                $record['lastused'] = intval(substr($raw_record->name, 10));
                $utils->insert_or_update_record('question_topictagged', $record, True);
            }

            // Display confirmation message and redirect to previous page
            echo '
                <script>
                    alert("Sync successful\n");
                    window.location.href = "' . $form->returnurl . '";
                </script>
            ';
	    die();
	}
}
echo $mform->render();

echo $OUTPUT->footer();
