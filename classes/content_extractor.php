<?php
/**
 * Content Extractor for generating AI contexts.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio / Knowledge Battle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle;

defined('MOODLE_INTERNAL') || die();

class content_extractor {

    /**
     * Extracts context based on the content scope settings.
     *
     * @param object $knowledgebattle
     * @param int $courseid
     * @return string
     */
    public static function extract_context(object $knowledgebattle, int $courseid): string {
        $scope = (int)($knowledgebattle->content_scope ?? 1);

        switch ($scope) {
            case 1:
                return self::extract_from_topic($knowledgebattle->topic_text ?? '');
            case 2:
                $sectionnum = $knowledgebattle->sectionnum ?? null;
                if ($sectionnum === null && !empty($knowledgebattle->id)) {
                    $cm = get_coursemodule_from_instance('knowledgebattle', $knowledgebattle->id, $courseid, false, IGNORE_MISSING);
                    if ($cm && !empty($cm->section)) {
                        global $DB;
                        $sec = $DB->get_record('course_sections', ['id' => $cm->section]);
                        if ($sec) {
                            $sectionnum = (int)$sec->section;
                        }
                    }
                }
                return self::extract_from_section($courseid, (int)($sectionnum ?? 0));
            case 3:
                $cmid = (int)($knowledgebattle->source_cmid ?? 0);
                if ($cmid <= 0 && !empty($knowledgebattle->id)) {
                    $cmid = self::find_first_resource_cmid($knowledgebattle, $courseid);
                }
                return self::extract_from_resource($cmid);
            case 4:
                return self::extract_from_question_bank($courseid);
            default:
                return '';
        }
    }

    /**
     * Finds the first available resource (page, book, resource) in the course or section.
     *
     * @param object $knowledgebattle
     * @param int $courseid
     * @return int
     */
    public static function find_first_resource_cmid(object $knowledgebattle, int $courseid): int {
        global $DB;

        $sectionnum = null;
        if (!empty($knowledgebattle->id)) {
            $cm = get_coursemodule_from_instance('knowledgebattle', $knowledgebattle->id, $courseid, false, IGNORE_MISSING);
            if ($cm && !empty($cm->section)) {
                $sec = $DB->get_record('course_sections', ['id' => $cm->section]);
                if ($sec) {
                    $sectionnum = (int)$sec->section;
                }
            }
        }

        $supported = ['page', 'book', 'resource'];
        $modinfo = get_fast_modinfo($courseid);

        if ($sectionnum !== null && isset($modinfo->sections[$sectionnum])) {
            foreach ($modinfo->sections[$sectionnum] as $cmid) {
                $cm = $modinfo->cms[$cmid];
                if (in_array($cm->modname, $supported, true)) {
                    return (int)$cmid;
                }
            }
        }

        foreach ($modinfo->cms as $cmid => $cm) {
            if (in_array($cm->modname, $supported, true)) {
                return (int)$cmid;
            }
        }

        return 0;
    }

    /**
     * Extracts from a direct topic text.
     *
     * @param string $topic_text
     * @return string
     */
    public static function extract_from_topic(string $topic_text): string {
        return self::strip_and_clean($topic_text);
    }

    /**
     * Extracts text from course section summary and activities.
     *
     * @param int $courseid
     * @param int $sectionnum
     * @return string
     */
    public static function extract_from_section(int $courseid, int $sectionnum): string {
        global $DB;
        $section = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $sectionnum]);
        if (!$section) {
            $section = $DB->get_record('course_sections', ['course' => $courseid, 'id' => $sectionnum]);
        }
        if (!$section) {
            return '';
        }

        $actualsectionnum = (int)$section->section;
        $content = self::strip_and_clean($section->summary ?? '');

        $modinfo = get_fast_modinfo($courseid);
        if (isset($modinfo->sections[$actualsectionnum])) {
            foreach ($modinfo->sections[$actualsectionnum] as $cmid) {
                $cm = $modinfo->cms[$cmid];
                if ($cm->modname === 'knowledgebattle') {
                    continue;
                }
                if ($cm->modname === 'page' || $cm->modname === 'book') {
                    $rescontent = self::extract_from_resource($cmid);
                    if (!empty($rescontent)) {
                        $content .= " " . $rescontent;
                        continue;
                    }
                }
                $content .= " " . $cm->name;
                if (!empty($cm->content)) {
                    $content .= ". " . self::strip_and_clean($cm->content);
                }
            }
        }

        return self::truncate_words(trim($content));
    }

    /**
     * Extracts text from a specific resource (page, book or file resource).
     *
     * @param int $cmid
     * @return string
     */
    public static function extract_from_resource(int $cmid): string {
        global $DB;
        if ($cmid <= 0) {
            return '';
        }
        $cm = get_coursemodule_from_id('', $cmid);
        if (!$cm) {
            return '';
        }

        $content = '';
        if ($cm->modname === 'page') {
            $page = $DB->get_record('page', ['id' => $cm->instance]);
            if ($page) {
                $content = $page->name . ". " . ($page->intro ?? '') . " " . ($page->content ?? '');
            }
        } elseif ($cm->modname === 'book') {
            $book = $DB->get_record('book', ['id' => $cm->instance]);
            if ($book) {
                $content = $book->name . ". " . ($book->intro ?? '') . " ";
                $chapters = $DB->get_records('book_chapters', ['bookid' => $book->id, 'hidden' => 0], 'pagenum');
                foreach ($chapters as $chapter) {
                    $content .= $chapter->title . ". " . ($chapter->content ?? '') . " ";
                }
            }
        } elseif ($cm->modname === 'resource') {
            $resource = $DB->get_record('resource', ['id' => $cm->instance]);
            if ($resource) {
                $content = $resource->name . ". " . ($resource->intro ?? '');
            }
        } else {
            $content = $cm->name;
        }

        return self::truncate_words(self::strip_and_clean($content));
    }

    /**
     * Extracts text/questions from the course Question Bank.
     *
     * @param int $courseid
     * @return string
     */
    public static function extract_from_question_bank(int $courseid): string {
        global $DB;

        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return '';
        }

        $categories = $DB->get_records('question_categories', ['contextid' => $context->id]);
        if (empty($categories)) {
            return '';
        }

        $catids = array_keys($categories);
        list($insql, $inparams) = $DB->get_in_or_equal($catids);

        if ($DB->get_manager()->table_exists('question_bank_entries')) {
            $sql = "SELECT q.id, q.name, q.questiontext
                      FROM {question} q
                      JOIN {question_versions} qv ON qv.questionid = q.id
                      JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                     WHERE qbe.questioncategoryid $insql
                  ORDER BY q.id DESC";
            $questions = $DB->get_records_sql($sql, $inparams, 0, 30);
        } else {
            $sql = "SELECT q.id, q.name, q.questiontext
                      FROM {question} q
                     WHERE q.category $insql
                  ORDER BY q.id DESC";
            $questions = $DB->get_records_sql($sql, $inparams, 0, 30);
        }

        if (empty($questions)) {
            return '';
        }

        $content = '';
        foreach ($questions as $q) {
            $content .= " " . $q->name . ": " . self::strip_and_clean($q->questiontext ?? '');
        }

        return self::truncate_words(trim($content));
    }

    /**
     * Truncates text to max words.
     *
     * @param string $text
     * @param int $max_words
     * @return string
     */
    private static function truncate_words(string $text, int $max_words = 1500): string {
        $words = preg_split('/\s+/', $text, $max_words + 1);
        if (count($words) > $max_words) {
            array_pop($words);
            return implode(' ', $words) . '...';
        }
        return $text;
    }

    /**
     * Strips HTML and normalizes whitespaces.
     *
     * @param string $html
     * @return string
     */
    private static function strip_and_clean(string $html): string {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/\s+/', ' ', $text));
    }
}
