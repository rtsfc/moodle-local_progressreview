<?php

if (!defined('MOODLE_INTERNAL')) {
    die();
}

class progressreview_intentions extends progressreview_plugin_tutor {

    protected $name = 'intentions';

    static public $type = PROGRESSREVIEW_TUTOR;

    protected $valid_properties = array('id', 'reviewid', 'intentid', 'istop', 'cont');

    private $currentcourses;

    private $intentions;

    public function __construct(&$review) {
        parent::__construct($review);

    }

    public function update($intentions) {
        global $DB;
        foreach ($intentions as $key => $intention) {
            if (is_object($intention)) {
                $intention = (array)$intention;
            }

            foreach ($intention as $field => $datum) {
                if(!in_array($field, $this->valid_properties)) {
                    $intention[$field] = false;
                }
            }

            $intention = (object)array_filter($intention, function($datum) {
                return $datum !== false;
            });

            if (!empty($intention->id)) {
                if (!$DB->update_record('progressreview_intent_select', $intention)) {
                    throw new progressreview_autosave_exception('Intention Update Failed');
                }
                if (!$DB->set_field('progressreview', 'datemodified', time(), array('id' => $this->progressreview->id))) {
                    throw new progressreview_autosave_exception('Timestamp Update Failed');
                }
            } else {
                $intention->id = $DB->insert_record('progressreview_intent_select', $intention);

                if ($intention->id) {
                    if (!$DB->set_field('progressreview', 'datemodified', time(), array('id' => $this->progressreview->id))) {
                        throw new progressreview_autosave_exception('Timestamp Update Failed');
                    }
                }
            }
            foreach ((array)$intention as $field => $datum) {
                $this->currentcoursese[$key]->progression->intention->$field = $datum;
            }
        }
        return true;
    }

    /**
     * return the intentions data as an array of records
     *
     * @return
     * @access public
     */
    public function get_review() {
        return $this->intentions;
    } // end of member function get_intentions

    public function delete() {
        global $DB;
        foreach ($this->intentions as $intention) {
            $DB->delete_records('progressreview_intent_select', array('id' => $target->id));
        }
    }

    protected function retrieve_review() {
        global $DB;

        $select = 'SELECT DISTINCT c.* ';
        $from = 'FROM {progressreview_course} AS c
            JOIN {progressreview} p ON p.courseid = c.originalid
            JOIN {progressreview_subject} s ON s.reviewid = p.id
            JOIN {progressreview_session} sess ON sess.id = p.sessionid ';
        $where = 'WHERE p.sessionid = ?
            AND p.studentid = ? ';
        $order = 'ORDER BY c.shortname ASC';
        $params = array(
            $this->progressreview->get_session()->id,
            $this->progressreview->get_student()->id
        );

        $this->currentcourses = $DB->get_records_sql($select.$from.$where.$order, $params);

        foreach ($this->currentcourses as $currentcourse) {
            $params = array('currentcode' => substr($currentcourse->shortname, 2, 3));
            if ($currentcourse->progression = $DB->get_record('progressreview_intent', $params)) {
                $params = array(
                    'reviewid' => $this->progressreview->id,
                    'intentid' => $currentcourse->progression->id
                );
                $currentcourse->progression->intention = $DB->get_record('progressreview_intent_select', $params);
            }
        }
    }

    public function add_form_fields($mform) {
        global $OUTPUT;
        $strcurrentcourse = get_string('currentcourse', 'progressreview_intentions');
        $currentcoursehelp = $OUTPUT->help_icon('currentcourse', 'progressreview_intentions');
        $strprogressioncourse = get_string('progressioncourse', 'progressreview_intentions');
        $progressioncoursehelp = $OUTPUT->help_icon('progressioncourse', 'progressreview_intentions');
        $strcontinue = get_string('continue', 'progressreview_intentions');
        $continuehelp = $OUTPUT->help_icon('continue', 'progressreview_intentions');
        $stristop = get_string('istop', 'progressreview_intentions');
        $istophelp = $OUTPUT->help_icon('istop', 'progressreview_intentions');
        $hw = 'html_writer';
        $table = $hw::start_tag('table');
        $headings = $hw::tag('th', $strcurrentcourse.$currentcoursehelp)
            .$hw::tag('th', $strprogressioncourse.$progressioncoursehelp)
            .$hw::tag('th', $strcontinue.$continuehelp)
            .$hw::tag('th', $stristop.$istophelp);
        $table .= $hw::tag('tr', $headings);

        $mform->addElement('html', $table);

        $contattrs = array('group' => null, 'class' => 'intentions cont');
        $istopattrs = array('group' => null, 'class' => 'intentions istop');

        foreach ($this->currentcourses as $key => $currentcourse) {
            $mform->addElement('html', $hw::start_tag('tr'));
            $mform->addElement('html', $hw::tag('td', $currentcourse->fullname));
            if ($currentcourse->progression) {
                $progression = $currentcourse->progression->newname;
                $cells = $hw::tag('td', $progression).$hw::start_tag('td');
                $mform->addElement('html', $cells);
                $mform->addElement('advcheckbox', 'intentions['.$key.'][cont]', '', '', $contattrs);
                $mform->addElement('html', $hw::end_tag('td').$hw::start_tag('td'));
                $mform->addElement('advcheckbox', 'intentions['.$key.'][istop]', '', '', $istopattrs);
                $mform->addElement('html', $hw::end_tag('td'));
            } else {
                $cells = $hw::tag('td', 'None').$hw::empty_tag('td').$hw::empty_tag('td');
                $mform->addElement('html', $cells);
            }
            $mform->addElement('html', $hw::end_tag('tr'));

            $mform->setType('intentions['.$key.'][cont]', PARAM_BOOL);
            $mform->setType('intentions['.$key.'][istop]', PARAM_BOOL);
            $mform->disabledIf('intentions['.$key.'][istop]', 'intentions['.$key.'][cont]');
        }
        $mform->addElement('html', $hw::end_tag('table'));
    }

    public function add_form_data($data) {
        $intentions = array();
        foreach ($this->currentcourses as $key => $currentcourse) {
            $progression = $currentcourse->progression;
            if ($progression) {
                if (!empty($progression->intention)) {
                    $intentions[$key] = array(
                        'cont' => $progression->intention->cont,
                        'istop' => $progression->intention->istop
                    );
                }
            }
        }
        $data->intentions = $intentions;
        return $data;
    }

    public function validate($data) {
        if (is_object($data)) {
            $data = (array)$data;
        }

        $topcount = 0;
        if (array_key_exists('intentions', $data)) {
            foreach ($data['intentions'] as $intention) {
                if ($intention['cont']) {
                    $topcount += $intention['istop'];
                    if ($topcount > 3) {
                        $error = get_string('toomanytop', 'progressreview_intentions');
                        throw new progressreview_invalidvalue_exception($error);
                    }
                }
            }
        }
    }

    public function process_form_fields($data) {
        $intentions = array();

        $this->validate($data);
        foreach ($data->intentions as $key => $intention) {

            if (!$intention['cont'] && $intention['istop']) {
                $intention['istop'] = false;
            }

            $progression = $this->currentcourses[$key]->progression;
            $intentions[$key] = array(
                'reviewid' => $this->progressreview->id,
                'intentid' => $progression->id,
                'istop' => $intention['istop'],
                'cont' => $intention['cont']
            );
            if (!empty($progression->intention)) {
                $intentions[$key]['id'] = $progression->intention->id;
            }

        }
        return $this->update($intentions);
    }

    public function autosave($field, $value) {

        $fieldparts = explode('_', $field);
        $fieldname = $fieldparts[3];
        $fieldkey = $fieldparts[2];

        $fieldname = clean_param($fieldname, PARAM_ALPHA);
        $value = clean_param($value, PARAM_BOOL);

        $data = array('intentions' => array());
        if ($fieldname == 'istop') {
            foreach ($this->currentcourses as $key => $currentcourse) {
                if ($currentcourse->progression) {
                    $intention = $currentcourse->progression->intention;
                    if (!empty($intention)) {
                        $data['intentions'][$key] = array('cont' => true, 'istop' => $intention->istop);
                    }
                }
            }
            $data['intentions'][$fieldkey] = array('cont' => true, 'istop' => $value);
        }

        $this->validate($data);

        $intentions = array();
        $currentintention = $this->currentcourses[$fieldkey]->progression->intention;
        if (!empty($currentintention)) {
            $update = (object)array(
                'id' => $currentintention->id,
                $fieldname => $value
            );
            $intentions[$fieldkey] = $update;
        } else {
            if (!empty($value)) {
                $newintention = (object)array(
                    'reviewid' => $this->progressreview->id,
                    'intentid' => $this->currentcourses[$fieldkey]->progression->id,
                    'cont' => 1
                );
                $newintention->$fieldname = $value;
                $intentions[$fieldkey] = $newintention;
            }
        }

        $this->update($intentions);
    }

}