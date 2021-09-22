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
 * Create related table for this entity
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../../../../../config.php');
global $CFG;
require_once($CFG->dirroot . '/local/cltools/tests/lib.php');
// Only run through behat or if we are in debug mode.
debugging() || (defined('PHPUNIT_TEST') && PHPUNIT_TEST) || defined('BEHAT_SITE_RUNNING') || die();

use local_cltools\local\crud\helper\base as crud_helper;
use local_cltools\local\crud\helper\crud_add;

global $CFG, $OUTPUT, $PAGE;
require_login();;

// To make sure the table is created.
\local_cltools\local\simple\entity::delete_table();
\local_cltools\local\simple\entity::create_table();
/* @var core_renderer $OUTPUT */
echo $OUTPUT->header();
echo $OUTPUT->single_button(new moodle_url('/local/cltools/test/fixtures/index.php'), get_string('continue'));
echo $OUTPUT->footer();
