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

namespace local_cltools\local\field;

use moodle_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Field exception
 *
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class field_exception extends moodle_exception {
    /**
     * Constructor
     *
     * @param string $errorcode The name of the string from error.php to print
     * @param mixed $a Extra words and phrases that might be required in the error string
     */
    public function __construct($errorcode, $a = null) {
        parent::__construct($errorcode, 'local_cltools', $a);
    }
}
