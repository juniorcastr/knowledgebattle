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
        // Assume content_scope: 1=topic, 2=section, 3=resource
        $scope = $knowledgebattle->content_scope ?? 1;
        
        switch ($scope) {
            case 1:
                return self::extract_from_topic($knowledgebattle->topic_text ?? '');
            case 2:
                return self::extract_from_section($courseid, $knowledgebattle->sectionnum ?? 0);
            case 3:
                return self::extract_from_resource($knowledgebattle->source_cmid ?? 0);
            default:
                return '';
        }
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
            return '';
        }

        $content = self::strip_and_clean($section->summary);

        $modinfo = get_fast_modinfo($courseid);
        if (isset($modinfo->sections[$sectionnum])) {
            foreach ($modinfo->sections[$sectionnum] as $cmid) {
                $cm = $modinfo->cms[$cmid];
                $content .= " " . $cm->name . ". " . self::strip_and_clean((string)$cm->intro);
            }
        }

        return self::truncate_words($content);
    }

    /**
     * Extracts text from a specific resource (page or book).
     *
     * @param int $cmid
     * @return string
     */
    public static function extract_from_resource(int $cmid): string {
        global $DB;
        $cm = get_coursemodule_from_id('', $cmid);
        if (!$cm) {
            return '';
        }

        $content = '';
        if ($cm->modname === 'page') {
            $page = $DB->get_record('page', ['id' => $cm->instance]);
            if ($page) {
                $content = $page->name . ". " . $page->intro . " " . $page->content;
            }
        } elseif ($cm->modname === 'book') {
            $book = $DB->get_record('book', ['id' => $cm->instance]);
            if ($book) {
                $content = $book->name . ". " . $book->intro . " ";
                $chapters = $DB->get_records('book_chapters', ['bookid' => $book->id, 'hidden' => 0], 'pagenum');
                foreach ($chapters as $chapter) {
                    $content .= $chapter->title . ". " . $chapter->content . " ";
                }
            }
        }

        return self::truncate_words(self::strip_and_clean($content));
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
