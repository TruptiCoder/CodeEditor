<?php
require_once('../../config.php');
require_once($CFG->libdir.'/formslib.php');

$id = required_param('id', PARAM_INT); // Question ID
$question = $DB->get_record('local_codejudge_questions', array('id' => $id), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $question->courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id);

require_login($course);
require_capability('local/codejudge:submit', $context);

$PAGE->set_url(new moodle_url('/local/codejudge/view.php', array('id' => $id)));
$PAGE->set_context($context);
$PAGE->set_title($question->title);
$PAGE->set_heading($course->fullname);

// Simple form for submission
class submission_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $languages = array(
            'c' => 'C',
            'cpp' => 'C++',
            'python' => 'Python',
            'java' => 'Java'
        );
        $mform->addElement('select', 'language', get_string('language', 'local_codejudge'), $languages);

        $mform->addElement('textarea', 'code', get_string('code', 'local_codejudge'), 'rows="20" cols="80" id="editor_textarea"');
        
        $mform->addElement('submit', 'submitbutton', get_string('run_code', 'local_codejudge'));
    }
}

$mform = new submission_form();
$mform->set_data(array('id' => $id));

echo $OUTPUT->header();
echo $OUTPUT->heading($question->title);
echo $OUTPUT->box($question->description);

// Ace Editor Setup
echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.3/ace.js"></script>';
echo '<div id="editor" style="height: 400px; width: 100%;"></div>';
echo '<script>
    var editor = ace.edit("editor");
    editor.setTheme("ace/theme/monokai");
    editor.session.setMode("ace/mode/python");
    
    var textarea = document.getElementById("editor_textarea");
    textarea.style.display = "none";
    
    editor.getSession().on("change", function () {
        textarea.value = editor.getValue();
    });
    
    // Change mode based on language select
    var langSelect = document.querySelector("select[name=\'language\']");
    langSelect.addEventListener("change", function() {
        var mode = "ace/mode/python";
        if(this.value == "c" || this.value == "cpp") mode = "ace/mode/c_cpp";
        if(this.value == "java") mode = "ace/mode/java";
        editor.session.setMode(mode);
    });
</script>';

if ($data = $mform->get_data()) {
    // Process Submission
    $client = new \local_codejudge\api_client();
    
    // Get Test Cases
    $testcases = $DB->get_records('local_codejudge_testcases', array('questionid' => $id));
    
    echo $OUTPUT->heading(get_string('result', 'local_codejudge'));
    
    $results_table = new html_table();
    $results_table->head = array('Input', 'Expected', 'Actual', 'Status');
    
    $all_passed = true;
    
    foreach ($testcases as $tc) {
        $api_result = $client->submit_code($data->language, $data->code, $tc->input_data);
        
        $status = 'Fail';
        $actual = '';
        
        if (isset($api_result['output'])) {
            $actual = trim($api_result['output']);
            if ($actual == trim($tc->output_data)) {
                $status = 'Pass';
            } else {
                $all_passed = false;
            }
        } else {
            $all_passed = false;
            $actual = isset($api_result['error']) ? $api_result['error'] : 'Error';
        }
        
        $results_table->data[] = array(
            $tc->input_data,
            $tc->output_data,
            $actual,
            $status
        );
    }
    
    echo html_writer::table($results_table);
    
    // Save submission
    $sub = new stdClass();
    $sub->userid = $USER->id;
    $sub->questionid = $id;
    $sub->language = $data->language;
    $sub->code = $data->code;
    $sub->status = $all_passed ? 'passed' : 'failed';
    $sub->timecreated = time();
    $DB->insert_record('local_codejudge_submissions', $sub);
}

$mform->display();

echo $OUTPUT->footer();
