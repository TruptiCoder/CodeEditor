<?php
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$courseid = required_param('courseid', PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id);

require_login($course);
require_capability('local/codejudge:view', $context);

$PAGE->set_url(new moodle_url('/local/codejudge/index.php', array('courseid' => $course->id)));
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_codejudge'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

if (has_capability('local/codejudge:manage', $context)) {
    echo $OUTPUT->single_button(
        new moodle_url('/local/codejudge/add_question.php', array('courseid' => $course->id)),
        get_string('add_question', 'local_codejudge'),
        'get'
    );
}

$questions = $DB->get_records('local_codejudge_questions', array('courseid' => $course->id));

if ($questions) {
    $table = new html_table();
    $table->head = array(
        get_string('title', 'local_codejudge'),
        get_string('marks', 'local_codejudge'),
        get_string('actions', 'local_codejudge')
    );

    foreach ($questions as $q) {
        $actions = '';
        if (has_capability('local/codejudge:manage', $context)) {
            // Edit/Delete links would go here
        }
        
        $solve_url = new moodle_url('/local/codejudge/view.php', array('id' => $q->id));
        $actions .= html_writer::link($solve_url, get_string('submit', 'local_codejudge'));

        $table->data[] = array(
            $q->title,
            $q->marks,
            $actions
        );
    }
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('noquestions', 'local_codejudge'), 'info');
}

echo $OUTPUT->footer();
