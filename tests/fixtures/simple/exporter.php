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

namespace local_cltools\simple;

use context_system;
use local_cltools\local\crud\entity_exporter;
use local_cltools\local\crud\entity_utils;
use renderer_base;

/**
 * Sample entity exporter
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class exporter extends entity_exporter {
    /**
     * Returns the specific class the persistent should be an instance of.
     *
     * @return string
     */
    protected static function define_class(): string {
        return entity::class;
    }

    /**
     * Get other values
     *
     * @param renderer_base $output
     * @return array
     */
    protected function get_other_values(renderer_base $output): array {
        $values = parent::get_other_values($output);
        $exportedimage = $this->export_file('simple_image', null, 'web_image');
        if ($exportedimage) {
            $values['image'] = $exportedimage->out();
        }
        return $values;
    }

    /**
     * Get formatting parameters for description
     *
     * @return array
     */
    protected function get_format_parameters_for_description(): array {
        return [
                'context' => context_system::instance(),
                'component' => entity_utils::get_component(get_class($this->persistent)),
                'filearea' => 'simple_description',
                'itemid' => empty($this->data->id) ? 0 : $this->data->id,
        ];
    }
}
