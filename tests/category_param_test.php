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
 * Tests for the question bank 'cat' parameter of the Hub browser link.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \qbank_questionhub\category_param
 */
final class category_param_test extends \advanced_testcase {
    /**
     * Creates a question category in a course question bank.
     *
     * @return \stdClass the category
     */
    private function create_category(): \stdClass {
        $course = $this->getDataGenerator()->create_course();
        if (class_exists('\core_question\local\bank\question_bank_helper')) {
            $bank = $this->getDataGenerator()->create_module('qbank', ['course' => $course->id]);
            $context = \context_module::instance($bank->cmid);
        } else {
            $context = \context_course::instance($course->id);
        }
        return $this->getDataGenerator()->get_plugin_generator('core_question')
            ->create_question_category(['contextid' => $context->id]);
    }

    /**
     * The Hub browser link carries "categoryid,contextid", as question_edit_setup() requires.
     */
    public function test_for_category_adds_context_id(): void {
        $this->resetAfterTest();
        $category = $this->create_category();
        $this->assertSame($category->id . ',' . $category->contextid, category_param::for_category((int)$category->id));
        $this->assertSame('', category_param::for_category(0));
        $this->assertSame('', category_param::for_category(999999));
    }

    /**
     * A bare category id from an old link is completed; complete and empty values stay unchanged.
     */
    public function test_normalise_completes_bare_category_id(): void {
        $this->resetAfterTest();
        $category = $this->create_category();
        $full = $category->id . ',' . $category->contextid;
        $this->assertSame($full, category_param::normalise((string)$category->id));
        $this->assertSame($full, category_param::normalise($full));
        $this->assertSame('', category_param::normalise(''));
        $this->assertSame('', category_param::normalise('999999'));
    }

    /**
     * The completed value of a bare category id is accepted by question_edit_setup().
     */
    public function test_question_edit_setup_accepts_normalised_value(): void {
        global $CFG;
        require_once($CFG->dirroot . '/question/editlib.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        $category = $this->create_category();
        $context = \context::instance_by_id($category->contextid);
        $params = $context->contextlevel == CONTEXT_MODULE ? ['cmid' => $context->instanceid]
            : ['courseid' => $context->instanceid];

        $_GET = $params + ['cat' => category_param::normalise((string)$category->id)];
        [, , , , , $pagevars] = question_edit_setup('questions', '/question/bank/questionhub/view.php');
        $this->assertSame($category->id . ',' . $category->contextid, $pagevars['cat']);
    }
}
