<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Adds the CodeJudge link to the course navigation.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course object
 * @param context_course $context The course context
 */
function local_codejudge_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('local/codejudge:view', $context)) {
        $url = new moodle_url('/local/codejudge/index.php', array('courseid' => $course->id));
        $navigation->add(
            get_string('pluginname', 'local_codejudge'),
            $url,
            navigation_node::TYPE_CUSTOM,
            null,
            'local_codejudge',
            new pix_icon('i/code', '')
        );
    }
}
