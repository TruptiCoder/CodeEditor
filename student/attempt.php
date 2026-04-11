<?php
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');

require_login();
$context = context_system::instance();
require_capability('local/codejudge:submit', $context);

$id = required_param('id', PARAM_INT);

$question = \local_codejudge\question_manager::get_question($id);
if (!$question) {
    print_error('invalidquestion', 'local_codejudge');
}

$languages = \local_codejudge\question_manager::get_languages();

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/codejudge/student/attempt.php', ['id' => $id]));
$PAGE->set_title(format_string($question->title));
$PAGE->set_heading(format_string($question->title));
$PAGE->set_pagelayout('standard');

// Load Ace Editor from CDN
$PAGE->requires->js(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/ace/1.23.4/ace.js'), true);

echo $OUTPUT->header();

// Prepare boilerplate map for JS
$boilerplates = [];
foreach ($languages as $lang) {
    $boilerplates[$lang->name] = $lang->boilerplate_code ?? '';
}
$boilerplates_json = json_encode($boilerplates);

$default_lang = 'python';
$default_bp   = $boilerplates[$default_lang] ?? '';

?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><?php echo format_string($question->title); ?></h4>
                </div>
                <div class="card-body">
                    <?php echo format_text($question->description, FORMAT_HTML); ?>
                    <hr>
                    <p><strong>Marks:</strong> <?php echo (int)$question->marks; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Code Editor</h5>
                        <div>
                            <label for="language-select" class="mr-2">Language:</label>
                            <select id="language-select" class="form-control d-inline-block" style="width:auto;">
                                <?php foreach ($languages as $lang): ?>
                                    <option value="<?php echo htmlspecialchars($lang->name); ?>">
                                        <?php echo htmlspecialchars(strtoupper($lang->name)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="ace-editor" style="height:400px;font-size:14px;"><?php echo htmlspecialchars($default_bp); ?></div>
                </div>
                <div class="card-footer">
                    <textarea id="code-hidden" name="code" style="display:none;"></textarea>
                    <button id="submit-btn" class="btn btn-success btn-lg" onclick="submitCode()">
                        <i class="fa fa-play"></i> Submit Code
                    </button>
                    <span id="submit-status" class="ml-2 text-muted"></span>
                </div>
            </div>

            <!-- Results section -->
            <div id="results-section" class="mt-3" style="display:none;">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Results</h5>
                    </div>
                    <div class="card-body" id="results-body"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var editor;
var boilerplates = <?php echo $boilerplates_json; ?>;

document.addEventListener('DOMContentLoaded', function() {
    editor = ace.edit("ace-editor");
    editor.setTheme("ace/theme/monokai");
    editor.session.setMode("ace/mode/python");
    editor.setValue(boilerplates['python'] || '', -1);

    document.getElementById('language-select').addEventListener('change', function() {
        var lang = this.value;
        var modeMap = {
            'python': 'ace/mode/python',
            'c':      'ace/mode/c_cpp',
            'cpp':    'ace/mode/c_cpp',
            'java':   'ace/mode/java'
        };
        editor.session.setMode(modeMap[lang] || 'ace/mode/text');
        editor.setValue(boilerplates[lang] || '', -1);
    });
});

function submitCode() {
    var btn = document.getElementById('submit-btn');
    var status = document.getElementById('submit-status');
    btn.disabled = true;
    status.textContent = 'Submitting...';

    var code     = editor.getValue();
    var language = document.getElementById('language-select').value;

    var formData = new FormData();
    formData.append('question_id', '<?php echo $question->id; ?>');
    formData.append('language', language);
    formData.append('code', code);
    formData.append('sesskey', M.cfg.sesskey);

    fetch('<?php echo (new moodle_url('/local/codejudge/submit.php'))->out(false); ?>', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        status.textContent = '';
        displayResults(data);
    })
    .catch(err => {
        btn.disabled = false;
        status.textContent = 'Error: ' + err.message;
    });
}

function displayResults(data) {
    var section = document.getElementById('results-section');
    var body    = document.getElementById('results-body');
    section.style.display = 'block';

    if (data.error) {
        body.innerHTML = '<div class="alert alert-danger">' + escHtml(data.error) + '</div>';
        return;
    }

    var html = '';
    if (data.total_marks !== undefined) {
        var cls = data.total_marks >= data.max_marks ? 'success' : (data.total_marks > 0 ? 'warning' : 'danger');
        html += '<div class="alert alert-' + cls + '">'
              + '<strong>Score: ' + data.total_marks + ' / ' + data.max_marks + ' marks</strong>'
              + '</div>';
    }

    (data.results || []).forEach(function(r, i) {
        var pass = r.result === 'PASS';
        html += '<div class="card mb-2 border-' + (pass ? 'success' : 'danger') + '">';
        html += '<div class="card-header ' + (pass ? 'bg-success' : 'bg-danger') + ' text-white py-2">';
        html += '<strong>Test Case ' + (i+1) + ': ' + escHtml(r.result) + '</strong></div>';
        html += '<div class="card-body py-2">';
        html += '<p class="mb-1"><strong>Input:</strong><br><pre class="mb-0">' + escHtml(r.input || '(none)') + '</pre></p>';
        html += '<p class="mb-1"><strong>Expected Output:</strong><br><pre class="mb-0">' + escHtml(r.expected_output) + '</pre></p>';
        html += '<p class="mb-0"><strong>Your Output:</strong><br><pre class="mb-0">' + escHtml(r.student_output) + '</pre></p>';
        html += '</div></div>';
    });

    body.innerHTML = html;
}

function escHtml(str) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(str || ''));
    return d.innerHTML;
}
</script>
<?php
echo $OUTPUT->footer();
