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
 * Common library for tests (behat + phpunit)
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Replace autoloading.
require_once(__DIR__.'/fixtures/simple/entity.php');
require_once(__DIR__.'/fixtures/simple/entities_list.php');
require_once(__DIR__.'/fixtures/simple/exporter.php');
require_once(__DIR__.'/fixtures/simple/external.php');
require_once(__DIR__.'/fixtures/simple/form.php');
require_once(__DIR__.'/fixtures/simple/list_filters.php');
