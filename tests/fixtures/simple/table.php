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
 * @copyright   2020 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\simple;

use local_cltools\local\crud\entity_table;
use local_cltools\local\crud\entity_utils;
use moodle_url;
use pix_icon;
use popup_action;

defined('MOODLE_INTERNAL') || die();
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
     * List columns
     *
     * @return array|array[]
     * @throws \coding_exception
     */
    public static function define_properties() {
        $properties = parent::define_properties();
        return $properties;
    }

    /**
     * @param $rotation
     * @return string
     * @throws \coding_exception
     */
    public function col_starttime($rotation) {
        return $this->get_time($rotation->starttime);
    }

    /**
     * @param $rotation
     * @return string
     * @throws \coding_exception
     */
    public function col_endtime($rotation) {
        return $this->get_time($rotation->endtime);
    }

    /**
     * @param $rotation
     * @return mixed
     */
    public function col_mineval($rotation) {
        return $rotation->mineval;
    }

    /**
     * Evaluation template id
     *
     * @param $rotation
     * @return string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function col_evaluationtemplateid($rotation) {
        global $OUTPUT;
        try {
            $fullname = '';
            $actionhtml = '';
            $evaluationtemplate = new \local_competveteval\evaluation_template\entity($rotation->evaluationtemplateid);
            if ($evaluationtemplate) {
                $fullname = $evaluationtemplate->get('fullname');;
                $url = persistent_navigation::get_view_url(get_class($evaluationtemplate));
                $url = new moodle_url($url, ['id' => $evaluationtemplate->get('id')]);
                $popupaction = new popup_action('click', $url);
                $actionhtml = $OUTPUT->action_icon(
                    $url,
                    new pix_icon('e/search',
                        get_string('view', 'local_cltools')),
                    $popupaction
                );
            }
            return $fullname . $actionhtml;
        } catch (\moodle_exception $e) {
            return '';
        }
    }

    /**
     * Evaluation template id
     *
     * @param $rotation
     * @return string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function col_finalevalscaleid($rotation) {
        global $OUTPUT, $DB;
        try {
            $fullname = '';
            $actionhtml = '';
            $finalevalscale = $DB->get_record('scale', array('id' => $rotation->finalevalscaleid));
            if ($finalevalscale) {
                $fullname = $finalevalscale->name;;
                $url = new moodle_url('/grade/edit/scale/edit.php', array('courseid' => 0, 'id' => $finalevalscale->id));
                $popupaction = new popup_action('click', $url);
                $actionhtml = $OUTPUT->action_icon(
                    $url,
                    new pix_icon('e/search',
                        get_string('view', 'local_cltools')),
                    $popupaction
                );
            }
            return $fullname . $actionhtml;
        } catch (\moodle_exception $e) {
            return '';
        }
    }

    /**
     * Files
     *
     * @param $entity
     * @return string
     * @throws \ReflectionException
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function col_files($entity) {
        $component = entity_utils::get_component(self::$persistentclass);
        return $this->internal_col_files($entity,
            entity_utils::get_persistent_prefix(static::$persistentclass),
            $component
        );
    }

    /**
     * Evaluation template id
     *
     * @param $rotation
     * @return string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function col_description($rotation) {
        global $OUTPUT;
        $formatparams = [
            'context' => \context_system::instance(),
            'component' => entity_utils::get_component(static::$persistentclass),
            'filearea' => 'rotation_description',
            'itemid' => $rotation->id
        ];
        list($text, $format) = external_format_text($rotation->description,
            $rotation->descriptionformat,
            $formatparams['context'],
            $formatparams['component'],
            $formatparams['filearea'],
            $formatparams['itemid'],
            []);
        return $text;
    }
}
