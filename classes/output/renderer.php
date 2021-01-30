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
 * Renderer for CL Tools
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;
use local_cltools\local\crud\entity_list_renderable;

/**
 * Renderer for CompetVetEval
 *
 * @package    local_resourcelibrary
 * @copyright  2020 CALL Learning 2²020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    // This is used as a default renderer for the crud_helper.

    /**
     * @param entity_list_renderable $entitylist
     */
    public function render_entity_list(entity_list_renderable $entitylist) {
        ob_start();
        $entitylist->entitylist->out($entitylist->perpage, true);
        $o = ob_get_contents();
        ob_end_clean();
        return $o;
    }
}