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

namespace local_cltools\output;

use local_cltools\output\table\entity_table_renderable;
use plugin_renderer_base;

/**
 * Renderer for CLTools
 *
 * @package    local_cltools
 * @copyright  2020 CALL Learning 2²020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    // This is used as a default renderer for the crud_helper.

    /**
     * Renderer for table
     *
     * @param entity_table_renderable $entitytable
     * @return string
     */
    public function render_entity_table(entity_table_renderable $entitytable): string {
        return $this->render_from_template('local_cltools/dynamic_table_sql',
                $entitytable->export_for_template($this));
    }
}
