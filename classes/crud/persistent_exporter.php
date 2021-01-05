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
 * Persistent utils class
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\crud;
defined('MOODLE_INTERNAL') || die();
use renderer_base;

class persistent_exporter extends \core\external\persistent_exporter {
    /**
     * Get value for persistent fields
     *
     * @param renderer_base $output
     * @return array
     */
    protected function get_other_values(renderer_base $output) {
        $values = [];
        $values['usermodifiedfullname'] = fullname($this->data->usermodified);
        return $values;
    }

    protected function export_file($filearea, $fileprefix=null, $filetypegroup=null) {
        // Retrieve the file from the Files API.
        $contextid = \context_system::instance()->id;
        $fs = get_file_storage();

        $files = $fs->get_area_files($contextid, 'local_cltools', $filearea);
        $returnedfiled = null;
        foreach($files as $file) {
            $foundfile = $fileprefix && strpos($file->get_filename(), $fileprefix)!==FALSE;
            $foundfile = $foundfile || ($filetypegroup &&
                    file_mimetype_in_typegroup($file->get_mimetype(), $filetypegroup));
            if ($foundfile) {
                $returnedfiled = $file;
                break;
            }
        }
        if (!$returnedfiled) {
            return null;
        }
        return \moodle_url::make_pluginfile_url($contextid,
            'local_cltools', $filearea,
            $returnedfiled->get_itemid(),
            $returnedfiled->get_filepath(),
            $returnedfiled->get_filename()
        );
    }

}