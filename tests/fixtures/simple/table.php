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
 * Sample entity edit or add form
 *
 * @package     local_cltools
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\simple;

use local_cltools\local\crud\entity_table;

/**
 * Entity table/list
 *
 * @package     local_cltools
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class table extends entity_table {
    /** @var string The fully qualified classname. */
    protected static $persistentclass = entity::class;

    /**
     * Sets up the page_table parameters.
     *
     * @param string|null $uniqueid
     * @param array|null $actionsdefs
     * @param bool $editable
     */
    public function __construct(?string $uniqueid,
            ?array $actionsdefs,
            bool $editable = false
    ) {
        $actionsdefs = [
                [
                        'url' => 'edit.php',
                        'icon' => 't/edit',
                        'name' => 'edit'
                ]
        ];
        parent::__construct($uniqueid, $actionsdefs, $editable);
    }
}
