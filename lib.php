<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Returns the navigation nodes for the plugin.
 */
function local_codejudge_extend_navigation(global_navigation $navigation) {
    $context = context_system::instance();

    if (has_capability('local/codejudge:managequestions', $context)) {
        $node = $navigation->add(
            get_string('pluginname', 'local_codejudge'),
            new moodle_url('/local/codejudge/teacher/dashboard.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'codejudge_teacher'
        );
    } else if (isloggedin() && !isguestuser()) {
        $node = $navigation->add(
            get_string('pluginname', 'local_codejudge'),
            new moodle_url('/local/codejudge/index.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'codejudge_student'
        );
    }
}

/**
 * Call the Python judge microservice.
 */
function local_codejudge_call_judge(string $code, string $language, array $test_cases): array {
    $judge_url = get_config('local_codejudge', 'judge_url') ?: 'http://localhost:5000/run';

    $payload = json_encode([
        'code'       => $code,
        'language'   => $language,
        'test_cases' => $test_cases,
    ]);

    $ch = curl_init($judge_url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $http_code !== 200) {
        return ['error' => 'Judge service unavailable. HTTP ' . $http_code];
    }

    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'Invalid JSON from judge service'];
    }

    return $decoded;
}

/**
 * Sanitize code input to prevent XSS in display.
 */
function local_codejudge_sanitize_output(string $output): string {
    return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
}
