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
 * Tests that conflicts kept by an import are recorded, so the library can show them.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \qbank_questionhub\package_importer
 */
final class conflict_test extends \advanced_testcase {
    /**
     * A question bank context of a course (mod_qbank on Moodle 5.0, the course on 4.5).
     *
     * @param \stdClass $course
     * @return \context
     */
    private function bank_context(\stdClass $course): \context {
        if (class_exists('\core_question\local\bank\question_bank_helper')) {
            $bank = \core_question\local\bank\question_bank_helper::get_default_open_instance_system_type($course, true);
            return \context_module::instance($bank->id);
        }
        return \context_course::instance($course->id);
    }

    /**
     * Package with one true/false question "Q1" in the category "Package".
     *
     * @param string $text Question text.
     * @return string Moodle XML.
     */
    private function package_xml(string $text): string {
        return '<?xml version="1.0" encoding="UTF-8"?>
<quiz>
  <question type="category"><category><text>$course$/top/Package</text></category></question>
  <question type="truefalse">
    <name><text>Q1</text></name>
    <questiontext format="html"><text>' . $text . '</text></questiontext>
    <generalfeedback format="html"><text></text></generalfeedback>
    <defaultgrade>1</defaultgrade><penalty>1</penalty><hidden>0</hidden><idnumber></idnumber>
    <answer fraction="100" format="moodle_auto_format"><text>true</text><feedback format="html"><text></text></feedback></answer>
    <answer fraction="0" format="moodle_auto_format"><text>false</text><feedback format="html"><text></text></feedback></answer>
  </question>
</quiz>';
    }

    /**
     * A kept conflict is counted in the result and the history; taking the Hub version clears it.
     */
    public function test_kept_conflicts_are_recorded(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $context = $this->bank_context($course);
        $questions = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questions->create_question_category(['contextid' => $context->id, 'name' => 'Package']);
        $questions->create_question('truefalse', null, ['category' => $category->id, 'name' => 'Q1',
            'questiontext' => ['text' => 'Lokal', 'format' => FORMAT_HTML]]);

        $xml = $this->package_xml('Hub');
        $metadata = ['id' => 'ch.test.conflicts', 'version' => '1.0.0', 'title' => 'Test'];
        $importer = new package_importer((int)$course->id, (int)$category->id, (int)$context->id);
        $items = $importer->preview_package($xml, $metadata, 'keep');
        $conflicts = array_values(array_filter($items, static fn($item) => $item['status'] === 'conflict'));
        $this->assertCount(1, $conflicts);
        $key = $conflicts[0]['sourcekey'];

        $result = $importer->import_package($xml, true, $metadata, 'keep', true, [$key => 'keep']);
        $this->assertSame(1, $result->keptcount);
        $history = $DB->get_records('qbank_questionhub_history', ['package_id' => 'ch.test.conflicts'], 'id DESC', '*', 0, 1);
        $this->assertSame(1, (int)reset($history)->conflicts);

        $importer = new package_importer((int)$course->id, (int)$category->id, (int)$context->id);
        $result = $importer->import_package($xml, true, $metadata, 'keep', true, [$key => 'hub']);
        $this->assertSame(0, $result->keptcount);
        $history = $DB->get_records('qbank_questionhub_history', ['package_id' => 'ch.test.conflicts'], 'id DESC', '*', 0, 1);
        $this->assertSame(0, (int)reset($history)->conflicts);
    }
}
