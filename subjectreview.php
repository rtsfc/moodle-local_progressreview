<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/local/progressreview/lib.php');
require_once($CFG->dirroot.'/local/progressreview/renderer.php');

$sessionid = required_param('sessionid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$coursecontext = get_context_instance(CONTEXT_COURSE, $courseid);

if (!$DB->record_exists('progressreview_session', array('id' => $sessionid))) {
    print_error('invalidsession', 'local_progressreview');
}

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'local_progressreview');
}

if (has_capability('moodle/local_progressreview:write', $coursecontext)) {
    $mode = PROGRESSREVIEW_TEACHER;
} else if (has_capability('moodle/local_progressreview:viewown', $coursecontext)) {
    $mode = PROGRESSREVIEW_STUDENT;
} else {
    print_error('noaccess');
}

require_login($course);
$PAGE->set_url('/local/progressreview/subjectreview.php', array('sessionid' => $sessionid, 'courseid' => $courseid));
$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', array('id' => $course->id)));
$PAGE->navbar->add(get_string('pluginname', 'local_progressreview'));
$PAGE->navbar->add(get_string('writereviews', 'local_progressreview'));

$output = $PAGE->get_renderer('local_progressreview');

if ($mode == PROGRESSREVIEW_TEACHER) {
    $reviews = array();
    $reviewdata = array();
    $students = get_users_by_capability($coursecontext, 'moodle/local_progressreview:viewown', '', 'lastname, firstname');
    foreach ($students as $student) {
        $reviews[$student->id] = new progressreview($student->id, $sessionid, $courseid, $USER->id, PROGRESSREVIEW_SUBJECT);
        $reviewdata[] = $reviews[$student->id]->get_plugin('subject')->get_review();
    }

    $content = $output->subject_review_table($reviewdata, true);
}

echo $OUTPUT->header();

echo $content;

echo $OUTPUT->footer();