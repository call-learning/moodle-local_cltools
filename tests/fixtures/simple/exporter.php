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
 * Rotation entity exporter
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\simple;
defined('MOODLE_INTERNAL') || die();

use local_cltools\local\crud\persistent_exporter;
use local_cltools\local\crud\persistent_utils;
use renderer_base;

class exporter extends persistent_exporter {

    /**
     * Returns the specific class the persistent should be an instance of.
     *
     * @return string
     */
    protected static function define_class() {
        return entity::class;
    }

    protected function get_other_values(renderer_base $output) {
        $values = parent::get_other_values($output);
        $exportedimage = $this->export_file('image', null, 'web_image');
        if ($exportedimage) {
            $values['image'] = $exportedimage;
        }
        return $values;
    }

    protected function get_format_parameters_for_description() {
        return [
            'context' => \context_system::instance(),
            'component' => persistent_utils::get_component(get_class($this->persistent)),
            'filearea' => 'description',
            'itemid' => empty($this->data->id) ? 0 : $this->data->id
        ];
    }
}