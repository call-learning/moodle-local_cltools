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
 * Contains the class used for the displaying the participants table.
 *
 * @package    core_user
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
declare(strict_types=1);

namespace local_cltools\table;

use DateTime;
use context;
use core_table\dynamic as dynamic_table;
use core_table\local\filter\filterset;
use core_user\output\status_field;
use moodle_url;
use user_picture;

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/user/lib.php');

/**
 * Class for the displaying the participants table.
 *
 * @package    core_user
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_list_selector extends \table_sql implements dynamic_table {

    /**
     * @var string[] The list of countries.
     */
    protected $countries;

    /**
     * @var string[] Extra fields to display.
     */
    protected $extrafields;

    /**
     * @var \stdClass $course The course details.
     */
    protected $course;

    /**
     * @var  context $context The course context.
     */
    protected $context;

    /**
     * @var \stdClass[] List of roles indexed by roleid.
     */
    protected $allroles;

    /**
     * @var \stdClass[] List of roles indexed by roleid.
     */
    protected $allroleassignments;

    /**
     * @var \stdClass[] Profile roles in this course.
     */
    protected $profileroles;

    /**
     * @var filterset Filterset describing which participants to include.
     */
    protected $filterset;

    /** @var \stdClass[] $viewableroles */
    private $viewableroles;

    /** @var moodle_url $baseurl The base URL for the report. */
    public $baseurl;

    /**
     * Render the participants table.
     *
     * @param int $pagesize Size of page for paginated displayed table.
     * @param bool $useinitialsbar Whether to use the initials bar which will only be used if there is a fullname column defined.
     * @param string $downloadhelpbutton
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {
        global $CFG, $OUTPUT, $PAGE;

        // Define the headers and columns.
        $headers = [];
        $columns = [];

        $headers[] = get_string('fullname');
        $columns[] = 'fullname';

        $extrafields = get_extra_user_fields($this->context);
        foreach ($extrafields as $field) {
            $headers[] = get_user_field_name($field);
            $columns[] = $field;
        }

        $headers[] = get_string('roles');
        $columns[] = 'roles';

        // Get the list of fields we have to hide.
        $hiddenfields = array();
        if (!has_capability('moodle/course:viewhiddenuserfields', $this->context)) {
            $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
        }

        // Do not show the columns if it exists in the hiddenfields array.
        if (!isset($hiddenfields['lastaccess'])) {
            $headers[] = get_string('lastsiteaccess');
            $columns[] = 'lastaccess';
        }

        $headers[] = get_string('select');
        $columns[] = 'select';
        $this->define_columns($columns);
        $this->define_headers($headers);

        // The name column is a header.
        $this->define_header_column('fullname');

        // Make this table sorted by last name by default.
        $this->sortable(true, 'lastname');

        $this->no_sorting('select');
        $this->no_sorting('roles');
        $this->set_attribute('id', 'participants');

        $this->countries = get_string_manager()->get_list_of_countries(true);
        $this->extrafields = $extrafields;
        $this->allroles = role_fix_names(get_all_roles($this->context), $this->context);
        $this->profileroles = get_profile_roles($this->context);
        $this->viewableroles = get_viewable_roles($this->context);

        parent::out($pagesize, $useinitialsbar, $downloadhelpbutton);

        if (has_capability('moodle/course:enrolreview', $this->context)) {
            $params = [
                'contextid' => $this->context->id,
                'uniqueid' => $this->uniqueid,
            ];
            $PAGE->requires->js_call_amd('core_user/status_field', 'init', [$params]);
        }
    }


    /**
     * Generate the fullname column.
     *
     * @param \stdClass $data
     * @return string
     */
    public function col_fullname($data) {
        global $OUTPUT;
        return $OUTPUT->user_picture($data, array('size' => 35, 'courseid' => SITEID, 'includefullname' => true, 'link'=>false));
    }

    /**
     * Generate a select link
     *
     * @param \stdClass $data
     * @return string
     */
    public function col_select($data) {
        $canviewfullnames = has_capability('moodle/site:viewfullnames', $this->context);
        $fullname = fullname($data, $canviewfullnames);
        return \html_writer::link('#', get_string('select'),
            [
                'data-user-id' => $data->id,
                'data-user-fullname' => $fullname,
                'class'=>'user-select'
            ]
        );
    }

    /**
     * User roles column.
     *
     * @param \stdClass $data
     * @return string
     */
    public function col_roles($data) {
        global $OUTPUT;

        $roles = isset($this->allroleassignments[$data->id]) ? $this->allroleassignments[$data->id] : [];
        $editable = new \core_user\output\user_roles_editable($this->course,
            $this->context,
            $data,
            $this->allroles,
            [],
            $this->profileroles,
            $roles,
            $this->viewableroles);

        return $OUTPUT->render_from_template('core/inplace_editable', $editable->export_for_template($OUTPUT));
    }

    /**
     * Generate the country column.
     *
     * @param \stdClass $data
     * @return string
     */
    public function col_country($data) {
        if (!empty($this->countries[$data->country])) {
            return $this->countries[$data->country];
        }
        return '';
    }

    /**
     * Generate the last access column.
     *
     * @param \stdClass $data
     * @return string
     * @throws \coding_exception
     */
    public function col_lastaccess($data) {
        if ($data->lastaccess) {
            return format_time(time() - $data->lastaccess);
        }

        return get_string('never');
    }

    /**
     * Generate the status column.
     *
     * @param \stdClass $data The data object.
     * @return string
     */
    public function col_status($data) {
        global $CFG, $OUTPUT, $PAGE;

        $enrolstatusoutput = '';
        $canreviewenrol = has_capability('moodle/course:enrolreview', $this->context);
        if ($canreviewenrol) {
            $canviewfullnames = has_capability('moodle/site:viewfullnames', $this->context);
            $fullname = fullname($data, $canviewfullnames);
            $coursename = format_string($this->course->fullname, true, array('context' => $this->context));
            require_once($CFG->dirroot . '/enrol/locallib.php');
            $manager = new \course_enrolment_manager($PAGE, $this->course);
            $userenrolments = $manager->get_user_enrolments($data->id);
            foreach ($userenrolments as $ue) {
                $timestart = $ue->timestart;
                $timeend = $ue->timeend;
                $timeenrolled = $ue->timecreated;
                $actions = $ue->enrolmentplugin->get_user_enrolment_actions($manager, $ue);
                $instancename = $ue->enrolmentinstancename;

                // Default status field label and value.
                $status = get_string('participationactive', 'enrol');
                $statusval = status_field::STATUS_ACTIVE;
                switch ($ue->status) {
                    case ENROL_USER_ACTIVE:
                        $currentdate = new DateTime();
                        $now = $currentdate->getTimestamp();
                        $isexpired = $timestart > $now || ($timeend > 0 && $timeend < $now);
                        $enrolmentdisabled = $ue->enrolmentinstance->status == ENROL_INSTANCE_DISABLED;
                        // If user enrolment status has not yet started/already ended or the enrolment instance is disabled.
                        if ($isexpired || $enrolmentdisabled) {
                            $status = get_string('participationnotcurrent', 'enrol');
                            $statusval = status_field::STATUS_NOT_CURRENT;
                        }
                        break;
                    case ENROL_USER_SUSPENDED:
                        $status = get_string('participationsuspended', 'enrol');
                        $statusval = status_field::STATUS_SUSPENDED;
                        break;
                }

                $statusfield = new status_field($instancename, $coursename, $fullname, $status, $timestart, $timeend,
                    $actions, $timeenrolled);
                $statusfielddata = $statusfield->set_status($statusval)->export_for_template($OUTPUT);
                $enrolstatusoutput .= $OUTPUT->render_from_template('core_user/status_field', $statusfielddata);
            }
        }
        return $enrolstatusoutput;
    }

    /**
     * This function is used for the extra user fields.
     *
     * These are being dynamically added to the table so there are no functions 'col_<userfieldname>' as
     * the list has the potential to increase in the future and we don't want to have to remember to add
     * a new method to this class. We also don't want to pollute this class with unnecessary methods.
     *
     * @param string $colname The column name
     * @param \stdClass $data
     * @return string
     */
    public function other_cols($colname, $data) {
        // Do not process if it is not a part of the extra fields.
        if (!in_array($colname, $this->extrafields)) {
            return '';
        }

        return s($data->{$colname});
    }

    /**
     * Query the database for results to display in the table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar.
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;
        list($twhere, $tparams) = $this->get_sql_where();

        list($where, $params)  = $this->get_users_list_sql($twhere, $tparams);
        $userfieldssql = user_picture::fields('u', get_extra_user_fields(\context_system::instance()));

        $sql = "SELECT COUNT(u.id)
                          FROM {user} u
                 {$where}";

        $total =  $DB->count_records_sql($sql, $params);
        $this->pagesize($pagesize, $total);

        $sort = $this->get_sql_sort();
        if ($sort) {
            $sort = 'ORDER BY ' . $sort;
        }
        $outerselect = "SELECT DISTINCT {$userfieldssql}, u.lastaccess";
        $sql = "{$outerselect}
                          FROM {user} u
                 {$where}
                 {$sort}
                 ";
        $rawdata =  $DB->get_recordset_sql($sql, $params, $this->get_page_start(), $this->get_page_size());

        $this->rawdata = [];
        foreach ($rawdata as $user) {
            $this->rawdata[$user->id] = $user;
        }
        $rawdata->close();

        if ($this->rawdata) {
            $this->allroleassignments = get_users_roles($this->context, array_keys($this->rawdata),
                true, 'c.contextlevel DESC, r.sortorder ASC');
        } else {
            $this->allroleassignments = [];
        }

        // Set initial bars.
        if ($useinitialsbar) {
            $this->initialbars(true);
        }
    }

    /**
     * Get main sql query for users
     * @param string $additionalwhere
     * @param array $additionalparams
     * @return array
     */
    protected function get_users_list_sql($additionalwhere = '', $additionalparams = []) {
        $params = [];
        $wheres = [];
        if ($this->filterset->has_filter('accesssince')) {
            $accesssince = $this->filterset->get_filter('accesssince')->current();

            // Last access filtering only supports matching or not matching, not any/all/none.
            $jointypenone = $this->filterset->get_filter('accesssince')::JOINTYPE_NONE;
            if ($this->filterset->get_filter('accesssince')->get_join_type() === $jointypenone) {
                $matchaccesssince = true;
            }
        }
        if ($accesssince) {
            $wheres[] = user_get_user_lastaccess_sql($accesssince, 'u', $matchaccesssince);
        }
        // Apply any role filtering.
        if ($this->filterset->has_filter('roles')) {
            [
                'where' => $roleswhere,
                'params' => $rolesparams,
            ] = $this->get_roles_sql();

            if (!empty($roleswhere)) {
                $wheres[] = "({$roleswhere})";
            }

            if (!empty($rolesparams)) {
                $params = array_merge($params, $rolesparams);
            }
        }

        // Apply any keyword text searches.
        if ($this->filterset->has_filter('keywords')) {
            [
                'where' => $keywordswhere,
                'params' => $keywordsparams,
            ] = $this->get_keywords_search_sql();

            if (!empty($keywordswhere)) {
                $wheres[] = $keywordswhere;
            }

            if (!empty($keywordsparams)) {
                $params = array_merge($params, $keywordsparams);
            }
        }
        // Add any supplied additional forced WHERE clauses.
        if ($wheres) {
            switch ($this->filterset->get_join_type()) {
                case $this->filterset::JOINTYPE_ALL:
                    $wherenot = '';
                    $wheresjoin = ' AND ';
                    break;
                case $this->filterset::JOINTYPE_NONE:
                    $wherenot = ' NOT ';
                    $wheresjoin = ' AND NOT ';

                    // Some of the $where conditions may begin with `NOT` which results in `AND NOT NOT ...`.
                    // To prevent this from breaking on Oracle the inner WHERE clause is wrapped in brackets, making it
                    // `AND NOT (NOT ...)` which is valid in all DBs.
                    $wheres = array_map(function($where) {
                        return "({$where})";
                    }, $wheres);

                    break;
                default:
                    // Default to 'Any' jointype.
                    $wherenot = '';
                    $wheresjoin = ' OR ';
                    break;
            }

            $where = 'WHERE ' . $wherenot . implode($wheresjoin, $wheres);
        } else {
            $where = '';
        }
        if (!empty($additionalwhere)) {
            if ($where) {
                $where .= " AND ";
            } else {
                $where = ' WHERE ';
            }
            $where .= "  ({$additionalwhere})";
            $params = array_merge($params, $additionalparams);
        }
        if ($where) {
            $where .= " AND ";
        } else {
            $where = ' WHERE ';
        }
        $where .= "  (u.deleted = 0)";
        return [$where, $params];
    }

    /**
     * Prepare SQL where clause and associated parameters for any roles filtering being performed.
     *
     * @return array SQL query data in the format ['where' => '', 'params' => []].
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function get_roles_sql(): array {
        global $DB;

        $where = '';
        $params = [];

        // Limit list to users with some role only.
        if ($this->filterset->has_filter('roles')) {
            $rolesfilter = $this->filterset->get_filter('roles');

            $roleids = $rolesfilter->get_filter_values();
            $jointype = $rolesfilter->get_join_type();

            // Determine how to match values in the query.
            $matchinsql = 'IN';
            switch ($jointype) {
                case $rolesfilter::JOINTYPE_ALL:
                    $wherejoin = ' AND ';
                    break;
                case $rolesfilter::JOINTYPE_NONE:
                    $wherejoin = ' AND NOT ';
                    $matchinsql = 'NOT IN';
                    break;
                default:
                    // Default to 'Any' jointype.
                    $wherejoin = ' OR ';
                    break;
            }

            // We want to query both the current context and parent contexts.
            $rolecontextids = $this->context->get_parent_context_ids(true);

            // Get users without any role, if needed.
            if (($withoutkey = array_search(-1, $roleids)) !== false) {
                list($relatedctxsql1, $norolectxparams) = $DB->get_in_or_equal($rolecontextids, SQL_PARAMS_NAMED, 'relatedctx');

                if ($jointype === $rolesfilter::JOINTYPE_NONE) {
                    $where .= "(u.id IN (SELECT userid FROM {role_assignments} WHERE contextid {$relatedctxsql1}))";
                } else {
                    $where .= "(u.id NOT IN (SELECT userid FROM {role_assignments} WHERE contextid {$relatedctxsql1}))";
                }

                $params = array_merge($params, $norolectxparams);

                if ($withoutkey !== false) {
                    unset($roleids[$withoutkey]);
                }

                // Join if any roles will be included.
                if (!empty($roleids)) {
                    // The NOT case is replaced with AND to prevent a double negative.
                    $where .= $jointype === $rolesfilter::JOINTYPE_NONE ? ' AND ' : $wherejoin;
                }
            }

            // Get users with specified roles, if needed.
            if (!empty($roleids)) {
                // All case - need one WHERE per filtered role.
                if ($rolesfilter::JOINTYPE_ALL === $jointype) {
                    $numroles = count($roleids);
                    $rolecount = 1;

                    foreach ($roleids as $roleid) {
                        list($relatedctxsql, $relctxparams) = $DB->get_in_or_equal($rolecontextids, SQL_PARAMS_NAMED, 'relatedctx');
                        list($roleidssql, $roleidparams) = $DB->get_in_or_equal($roleid, SQL_PARAMS_NAMED, 'roleids');

                        $where .= "(u.id IN (
                                     SELECT userid
                                       FROM {role_assignments}
                                      WHERE roleid {$roleidssql}
                                        AND contextid {$relatedctxsql})
                                   )";

                        if ($rolecount < $numroles) {
                            $where .= $wherejoin;
                            $rolecount++;
                        }

                        $params = array_merge($params, $roleidparams, $relctxparams);
                    }

                } else {
                    // Any / None cases - need one WHERE to cover all filtered roles.
                    list($relatedctxsql, $relctxparams) = $DB->get_in_or_equal($rolecontextids, SQL_PARAMS_NAMED, 'relatedctx');
                    list($roleidssql, $roleidsparams) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'roleids');

                    $where .= "(u.id {$matchinsql} (
                                 SELECT userid
                                   FROM {role_assignments}
                                  WHERE roleid {$roleidssql}
                                    AND contextid {$relatedctxsql})
                               )";

                    $params = array_merge($params, $roleidsparams, $relctxparams);
                }
            }
        }

        return [
            'where' => $where,
            'params' => $params,
        ];
    }

    /**
     * Prepare SQL where clause and associated parameters for any keyword searches being performed.
     *
     * @return array SQL query data in the format ['where' => '', 'params' => []].
     */
    protected function get_keywords_search_sql(): array {
        global $CFG, $DB, $USER;

        $keywords = [];
        $where = '';
        $params = [];
        $keywordsfilter = $this->filterset->get_filter('keywords');
        $jointype = $keywordsfilter->get_join_type();
        // None join types in both filter row and filterset require additional 'not null' handling for accurate keywords matches.
        $notjoin = false;

        // Determine how to match values in the query.
        switch ($jointype) {
            case $keywordsfilter::JOINTYPE_ALL:
                $wherejoin = ' AND ';
                break;
            case $keywordsfilter::JOINTYPE_NONE:
                $wherejoin = ' AND NOT ';
                $notjoin = true;
                break;
            default:
                // Default to 'Any' jointype.
                $wherejoin = ' OR ';
                break;
        }

        // Handle filterset None join type.
        if ($this->filterset->get_join_type() === $this->filterset::JOINTYPE_NONE) {
            $notjoin = true;
        }

        if ($this->filterset->has_filter('keywords')) {
            $keywords = $keywordsfilter->get_filter_values();
        }
        $userfields = get_extra_user_fields(\context_system::instance());
        foreach ($keywords as $index => $keyword) {
            $searchkey1 = 'search' . $index . '1';
            $searchkey2 = 'search' . $index . '2';
            $searchkey3 = 'search' . $index . '3';
            $searchkey4 = 'search' . $index . '4';
            $searchkey5 = 'search' . $index . '5';
            $searchkey6 = 'search' . $index . '6';
            $searchkey7 = 'search' . $index . '7';

            $conditions = [];
            // Search by fullname.
            $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');
            $conditions[] = $DB->sql_like($fullname, ':' . $searchkey1, false, false);

            // Search by email.
            $email = $DB->sql_like('email', ':' . $searchkey2, false, false);

            if ($notjoin) {
                $email = "(email IS NOT NULL AND {$email})";
            }

            if (!in_array('email', $userfields)) {
                $maildisplay = 'maildisplay' . $index;
                $userid1 = 'userid' . $index . '1';
                // Prevent users who hide their email address from being found by others
                // who aren't allowed to see hidden email addresses.
                $email = "(". $email ." AND (" .
                    "u.maildisplay <> :$maildisplay " .
                    "OR u.id = :$userid1". // Users can always find themselves.
                    "))";
                $params[$maildisplay] = core_user::MAILDISPLAY_HIDE;
                $params[$userid1] = $USER->id;
            }

            $conditions[] = $email;

            // Search by idnumber.
            $idnumber = $DB->sql_like('idnumber', ':' . $searchkey3, false, false);

            if ($notjoin) {
                $idnumber = "(idnumber IS NOT NULL AND  {$idnumber})";
            }

            if (!in_array('idnumber', $userfields)) {
                $userid2 = 'userid' . $index . '2';
                // Users who aren't allowed to see idnumbers should at most find themselves
                // when searching for an idnumber.
                $idnumber = "(". $idnumber . " AND u.id = :$userid2)";
                $params[$userid2] = $USER->id;
            }

            $conditions[] = $idnumber;

            if (!empty($CFG->showuseridentity)) {
                // Search all user identify fields.
                $extrasearchfields = explode(',', $CFG->showuseridentity);
                foreach ($extrasearchfields as $extrasearchfield) {
                    if (in_array($extrasearchfield, ['email', 'idnumber', 'country'])) {
                        // Already covered above. Search by country not supported.
                        continue;
                    }
                    $param = $searchkey3 . $extrasearchfield;
                    $condition = $DB->sql_like($extrasearchfield, ':' . $param, false, false);
                    $params[$param] = "%$keyword%";

                    if ($notjoin) {
                        $condition = "($extrasearchfield IS NOT NULL AND {$condition})";
                    }

                    if (!in_array($extrasearchfield, $userfields)) {
                        // User cannot see this field, but allow match if their own account.
                        $userid3 = 'userid' . $index . '3' . $extrasearchfield;
                        $condition = "(". $condition . " AND u.id = :$userid3)";
                        $params[$userid3] = $USER->id;
                    }
                    $conditions[] = $condition;
                }
            }

            // Search by middlename.
            $middlename = $DB->sql_like('middlename', ':' . $searchkey4, false, false);

            if ($notjoin) {
                $middlename = "(middlename IS NOT NULL AND {$middlename})";
            }

            $conditions[] = $middlename;

            // Search by alternatename.
            $alternatename = $DB->sql_like('alternatename', ':' . $searchkey5, false, false);

            if ($notjoin) {
                $alternatename = "(alternatename IS NOT NULL AND {$alternatename})";
            }

            $conditions[] = $alternatename;

            // Search by firstnamephonetic.
            $firstnamephonetic = $DB->sql_like('firstnamephonetic', ':' . $searchkey6, false, false);

            if ($notjoin) {
                $firstnamephonetic = "(firstnamephonetic IS NOT NULL AND {$firstnamephonetic})";
            }

            $conditions[] = $firstnamephonetic;

            // Search by lastnamephonetic.
            $lastnamephonetic = $DB->sql_like('lastnamephonetic', ':' . $searchkey7, false, false);

            if ($notjoin) {
                $lastnamephonetic = "(lastnamephonetic IS NOT NULL AND {$lastnamephonetic})";
            }

            $conditions[] = $lastnamephonetic;

            if (!empty($where)) {
                $where .= $wherejoin;
            } else if ($jointype === $keywordsfilter::JOINTYPE_NONE) {
                // Join type 'None' requires the WHERE to begin with NOT.
                $where .= ' NOT ';
            }

            $where .= "(". implode(" OR ", $conditions) .") ";
            $params[$searchkey1] = "%$keyword%";
            $params[$searchkey2] = "%$keyword%";
            $params[$searchkey3] = "%$keyword%";
            $params[$searchkey4] = "%$keyword%";
            $params[$searchkey5] = "%$keyword%";
            $params[$searchkey6] = "%$keyword%";
            $params[$searchkey7] = "%$keyword%";
        }

        return [
            'where' => $where,
            'params' => $params,
        ];
    }

    /**
     * Override the table show_hide_link to not show for select column.
     *
     * @param string $column the column name, index into various names.
     * @param int $index numerical index of the column.
     * @return string HTML fragment.
     */
    protected function show_hide_link($column, $index) {
        if ($index > 0) {
            return parent::show_hide_link($column, $index);
        }
        return '';
    }

    /**
     * Set filters and build table structure.
     *
     * @param filterset $filterset The filterset object to get the filters from.
     */
    public function set_filterset(filterset $filterset): void {
        // Process the filterset.
        $this->context = \context_system::instance();
        parent::set_filterset($filterset);
    }

    /**
     * Guess the base url for the participants table.
     */
    public function guess_base_url(): void {
        $this->baseurl = new moodle_url('');
    }

    /**
     * Get the context of the current table.
     *
     * Note: This function should not be called until after the filterset has been provided.
     *
     * @return context
     */
    public function get_context(): context {
        return $this->context;
    }
}
