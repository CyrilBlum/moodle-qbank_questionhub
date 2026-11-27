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

namespace qbank_questionhub;

/**
 * The question bank 'cat' request parameter.
 *
 * question_edit_setup() (Moodle 4.5 to 5.3) accepts 'cat' only as "categoryid,contextid" and throws
 * invalidcategory for a bare category id.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class category_param {
    /**
     * The 'cat' value for a question category.
     *
     * @param int $categoryid question category id
     * @return string "categoryid,contextid", or '' when the category does not exist
     */
    public static function for_category(int $categoryid): string {
        global $DB;
        if ($categoryid <= 0) {
            return '';
        }
        $contextid = $DB->get_field('question_categories', 'contextid', ['id' => $categoryid]);
        return $contextid ? $categoryid . ',' . $contextid : '';
    }

    /**
     * Completes a 'cat' value from a request.
     *
     * A bare category id (links of plugin v1.0.47 and older) gets its context id; a complete value stays as it is.
     *
     * @param string $cat value of the 'cat' parameter (PARAM_SEQUENCE)
     * @return string "categoryid,contextid", the unchanged value, or '' for an unknown bare category id
     */
    public static function normalise(string $cat): string {
        if ($cat === '' || str_contains($cat, ',')) {
            return $cat;
        }
        return self::for_category((int)$cat);
    }
}
