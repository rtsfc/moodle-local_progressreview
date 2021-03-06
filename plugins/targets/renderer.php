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
 * Defines the plugin's renderers to generate all HTML and PDF output
 *
 * @package   local_progressreview
 * @subpackage progressreview_targets
 * @copyright 2011 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class progressreview_targets_renderer extends plugin_renderer_base {
    public function review($targets) {
        $output = $this->output->heading(get_string('pluginname', 'progressreview_targets'), 4);
        $count = 1;
        foreach ($targets as $target) {
            $output .= $this->output->heading(get_string('modulename', 'ilptarget').' '.$count, 5);
            $deadline = date('l d/m/Y', $target->deadline);
            $output .= html_writer::tag('p', $target->targetset);
            $output .= html_writer::tag('p', get_string('deadline', 'ilptarget').': '.$deadline);
            $count++;
        }
        return $output;
    }
}

class progressreview_targets_print_renderer extends plugin_print_renderer_base {

    public function review($targets) {
        $this->output->heading(get_string('pluginname', 'progressreview_targets'), 4);
        $count = 1;
        foreach ($targets as $target) {
            $this->output->heading(get_string('modulename', 'ilptarget').' '.$count, 5);
            $deadline = date('l d/m/Y', $target->deadline);
            $options = array('font' => (object)array('size' => 12));
            pdf_writer::div($target->targetset."\n", $options);
            pdf_writer::div(get_string('deadline', 'ilptarget').': '.$deadline);
            $count++;
        }
        return pdf_writer::$pdf;
    }
}
