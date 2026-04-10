<?php
require_once('../../config.php');
require_once($CFG->libdir.'/formslib.php');

class question_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('text', 'title', get_string('title', 'local_codejudge'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');

        $mform->addElement('editor', 'description', get_string('description', 'local_codejudge'));
        $mform->setType('description', PARAM_RAW);

        $mform->addElement('text', 'marks', get_string('marks', 'local_codejudge'));
        $mform->setType('marks', PARAM_INT);

        // Test Cases
        $mform->addElement('header', 'testcases_header', get_string('test_cases', 'local_codejudge'));
        
        $mform->addElement('textarea', 'testcase_input', get_string('input', 'local_codejudge'));
        $mform->addElement('textarea', 'testcase_output', get_string('expected_output', 'local_codejudge'));

        $this->add_action_buttons();
    }
}

$courseid = required_param('courseid', PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id);

require_login($course);
require_capability('local/codejudge:manage', $context);

$PAGE->set_url(new moodle_url('/local/codejudge/add_question.php', array('courseid' => $course->id)));
$PAGE->set_context($context);
$PAGE->set_title(get_string('add_question', 'local_codejudge'));
$PAGE->set_heading($course->fullname);

$mform = new question_form();

if ($fromform = $mform->get_data()) {
    $question = new stdClass();
    $question->courseid = $courseid;
    $question->title = $fromform->title;
    $question->description = $fromform->description['text'];
    $question->marks = $fromform->marks;
    $question->timecreated = time();
    $question->timemodified = time();

    $qid = $DB->insert_record('local_codejudge_questions', $question);

    if (!empty($fromform->testcase_output)) {
        $tc = new stdClass();
        $tc->questionid = $qid;
        $tc->input_data = $fromform->testcase_input;
        $tc->output_data = $fromform->testcase_output;
        $DB->insert_record('local_codejudge_testcases', $tc);
    }

    redirect(new moodle_url('/local/codejudge/index.php', array('courseid' => $courseid)));
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
