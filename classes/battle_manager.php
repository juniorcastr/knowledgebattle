<?php
/**
 * Battle Manager for knowledgebattle
 *
 * @package    mod_knowledgebattle
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle;

defined('MOODLE_INTERNAL') || die();

class battle_manager {

    /**
     * Creates a match record, selects random approved questions, inserts match_questions.
     *
     * @param int $battleid
     * @param int $player1id
     * @param int $matchtype
     * @param int|null $player2id
     * @return object
     * @throws \moodle_exception
     */
    public static function create_match(int $battleid, int $player1id, int $matchtype, ?int $player2id = null): object {
        global $DB;

        $battle = $DB->get_record('knowledgebattle', ['id' => $battleid], '*', MUST_EXIST);
        $questions_count = $battle->questions_per_match ?? 5; // Fallback if missing
        
        $questionids = self::select_questions_for_match($battleid, $questions_count);
        
        $now = time();
        $is_bot_match = ($matchtype == 3 || $player2id === 0) ? 1 : 0;
        if ($is_bot_match) {
            $player2id = 0;
        }
        
        // Timeout for WO
        $timeout_hours = $battle->wo_timeout_hours ?? 24;
        
        $match = new \stdClass();
        $match->battleid = $battleid;
        $match->player1_id = $player1id;
        $match->player2_id = $player2id;
        $match->match_type = $matchtype;
        $match->is_bot_match = $is_bot_match;
        $match->status = 1; // Pending / In progress for P1
        $match->winner_id = 0;
        $match->p1_score = 0;
        $match->p2_score = 0;
        $match->p1_time_ms = 0;
        $match->p2_time_ms = 0;
        $match->timestarted = $now;
        $match->timeexpire = $now + ($timeout_hours * 3600);
        $match->timecreated = $now;
        $match->timemodified = $now;
        
        $match->id = $DB->insert_record('knowledgebattle_matches', $match);
        
        foreach ($questionids as $index => $qid) {
            $mq = new \stdClass();
            $mq->matchid = $match->id;
            $mq->questionid = $qid;
            $mq->question_order = $index + 1;
            $DB->insert_record('knowledgebattle_match_questions', $mq);
        }
        
        return $match;
    }

    /**
     * Finds an open matchmaking match.
     *
     * @param int $battleid
     * @param int $userid
     * @return object|null
     */
    public static function find_matchmaking_match(int $battleid, int $userid): ?object {
        global $DB;
        
        // Use IGNORE_MULTIPLE and a subquery/transaction if possible, but Moodle's get_record doesn't have FOR UPDATE natively without special methods.
        // We will fetch the first matching one.
        $sql = "SELECT * FROM {knowledgebattle_matches} 
                 WHERE battleid = ? 
                   AND status = 2 
                   AND match_type = 2 
                   AND player2_id IS NULL 
                   AND player1_id != ?";
        
        $match = $DB->get_record_sql($sql, [$battleid, $userid], IGNORE_MULTIPLE);
        
        if ($match) {
            // Lock it by setting player2_id right away
            $match->player2_id = $userid;
            $match->status = 1; // Back to active for player 2
            $match->timemodified = time();
            $DB->update_record('knowledgebattle_matches', $match);
            return $match;
        }
        
        return null;
    }

    /**
     * Selects random approved questions.
     *
     * @param int $battleid
     * @param int $count
     * @return array
     * @throws \moodle_exception
     */
    public static function select_questions_for_match(int $battleid, int $count): array {
        global $DB;
        
        $sql = "SELECT id FROM {knowledgebattle_questions} 
                 WHERE battleid = ? AND status = 1";
        $questions = $DB->get_fieldset_sql($sql, [$battleid]);
        
        if (count($questions) < $count) {
            throw new \moodle_exception('error_no_questions', 'mod_knowledgebattle');
        }
        
        shuffle($questions);
        return array_slice($questions, 0, $count);
    }

    /**
     * Finalizes the match.
     *
     * @param object $match
     * @param object $battle
     */
    public static function finalize_match(object $match, object $battle): void {
        global $DB;

        // Calculate scores and times
        $p1_stats = $DB->get_record_sql(
            "SELECT COUNT(id) as correct_count, SUM(response_time_ms) as total_time 
               FROM {knowledgebattle_turns} 
              WHERE matchid = ? AND userid = ? AND is_correct = 1", 
            [$match->id, $match->player1_id]
        );
        $p2_stats = $DB->get_record_sql(
            "SELECT COUNT(id) as correct_count, SUM(response_time_ms) as total_time 
               FROM {knowledgebattle_turns} 
              WHERE matchid = ? AND userid = ? AND is_correct = 1", 
            [$match->id, $match->player2_id]
        );

        $match->p1_score = $p1_stats->correct_count ?? 0;
        $match->p1_time_ms = $p1_stats->total_time ?? 0;
        
        $match->p2_score = $p2_stats->correct_count ?? 0;
        $match->p2_time_ms = $p2_stats->total_time ?? 0;

        // Determine winner
        $p2_winner_id = ($match->is_bot_match || empty($match->player2_id)) ? -1 : (int)$match->player2_id;

        if ($match->p1_score > $match->p2_score) {
            $match->winner_id = $match->player1_id;
            $p1_result = 'win';
            $p2_result = 'loss';
        } elseif ($match->p2_score > $match->p1_score) {
            $match->winner_id = $p2_winner_id;
            $p1_result = 'loss';
            $p2_result = 'win';
        } else {
            // Tiebreak on time
            $timediff = abs($match->p1_time_ms - $match->p2_time_ms);
            if ($timediff < 1000) {
                $match->winner_id = 0; // draw
                $p1_result = 'draw';
                $p2_result = 'draw';
            } elseif ($match->p1_time_ms < $match->p2_time_ms) {
                $match->winner_id = $match->player1_id;
                $p1_result = 'win';
                $p2_result = 'loss';
            } else {
                $match->winner_id = $p2_winner_id;
                $p1_result = 'loss';
                $p2_result = 'win';
            }
        }

        $match->status = 3;
        $match->timecompleted = time();
        $match->timemodified = $match->timecompleted;

        $DB->update_record('knowledgebattle_matches', $match);

        // Update stats
        $points_p1 = ($p1_result === 'win') ? ($battle->win_points ?? 3) : (($p1_result === 'loss') ? ($battle->loss_points ?? -1) : ($battle->draw_points ?? 1));
        self::update_player_stats($battle->id, $match->player1_id, $p1_result, $points_p1, $battle);

        if ($match->player2_id > 0) { // Not bot
            $points_p2 = ($p2_result === 'win') ? ($battle->win_points ?? 3) : (($p2_result === 'loss') ? ($battle->loss_points ?? -1) : ($battle->draw_points ?? 1));
            self::update_player_stats($battle->id, $match->player2_id, $p2_result, $points_p2, $battle);
        }

        // Send notifications
        \mod_knowledgebattle\notification_manager::notify_battle_result($match, $battle);

        // Update grades
        require_once(__DIR__.'/../lib.php');
        if (class_exists('\mod_knowledgebattle\grade_calculator')) {
            \mod_knowledgebattle\grade_calculator::update_all_grades($battle);
        } else {
            knowledgebattle_update_grades($battle);
        }

        // Trigger event
        if (class_exists('\mod_knowledgebattle\event\battle_completed')) {
            $cm = get_coursemodule_from_instance('knowledgebattle', $battle->id, 0, false);
            if ($cm) {
                $context = \context_module::instance($cm->id);
                $event = \mod_knowledgebattle\event\battle_completed::create([
                    'objectid' => $match->id,
                    'context' => $context
                ]);
                $event->trigger();
            }
        }
    }

    /**
     * Simulates bot answers for a match.
     *
     * @param object $match
     * @param object $battle
     */
    public static function simulate_bot_answers(object $match, object $battle): void {
        global $DB;
        
        $questions = $DB->get_records('knowledgebattle_match_questions', ['matchid' => $match->id], 'question_order ASC');
        $time_per_q = $battle->time_per_question ?? 15;
        $now = time();
        
        foreach ($questions as $mq) {
            $is_correct = (rand(1, 100) <= 70) ? 1 : 0; // 70% accuracy
            $response_time = rand(3000, $time_per_q * 800);
            
            $turn = new \stdClass();
            $turn->matchid = $match->id;
            $turn->userid = 0;
            $turn->questionid = $mq->questionid;
            $turn->selected_option = -1; // Not tracking bot exact selection
            $turn->is_correct = $is_correct;
            $turn->response_time_ms = $response_time;
            $turn->timecreated = $now;
            
            $DB->insert_record('knowledgebattle_turns', $turn);
        }
        
        self::finalize_match($match, $battle);
    }

    /**
     * Updates player stats.
     *
     * @param int $battleid
     * @param int $userid
     * @param string $result 'win', 'loss', 'draw'
     * @param int $points
     * @param object $battle
     */
    public static function update_player_stats(int $battleid, int $userid, string $result, int $points, object $battle): void {
        global $DB;
        
        if ($userid <= 0) return; // Ignore bots

        $stats = $DB->get_record('knowledgebattle_user_stats', ['battleid' => $battleid, 'userid' => $userid]);
        $now = time();
        $today = strtotime('today', $now);
        
        if (!$stats) {
            $stats = new \stdClass();
            $stats->battleid = $battleid;
            $stats->userid = $userid;
            $stats->current_points = 0;
            $stats->matches_played = 0;
            $stats->wins = 0;
            $stats->losses = 0;
            $stats->draws = 0;
            $stats->current_streak = 0;
            $stats->max_streak = 0;
            $stats->last_battle_date = $today;
            $stats->daily_battles_count = 0;
            $stats->timecreated = $now;
            $stats->id = $DB->insert_record('knowledgebattle_user_stats', $stats);
        }
        
        $stats->matches_played++;
        
        if ($result === 'win') {
            $stats->wins++;
            $stats->current_streak++;
            if ($stats->current_streak > $stats->max_streak) {
                $stats->max_streak = $stats->current_streak;
            }
        } elseif ($result === 'loss') {
            $stats->losses++;
            $stats->current_streak = 0;
        } else {
            $stats->draws++;
        }
        
        $stats->current_points += $points;
        $allow_negative = $battle->allow_negative_points ?? 0;
        if (!$allow_negative && $stats->current_points < 0) {
            $stats->current_points = 0;
        }
        
        if ($stats->last_battle_date != $today) {
            $stats->last_battle_date = $today;
            $stats->daily_battles_count = 1;
        } else {
            $stats->daily_battles_count++;
        }
        
        $stats->timemodified = $now;
        $DB->update_record('knowledgebattle_user_stats', $stats);
    }

    /**
     * Checks if user has exceeded daily limit.
     *
     * @param int $battleid
     * @param int $userid
     * @param int $maxdaily
     * @return bool
     */
    public static function check_daily_limit(int $battleid, int $userid, int $maxdaily): bool {
        global $DB;
        
        if ($maxdaily <= 0) return true; // Unlimited
        
        $stats = $DB->get_record('knowledgebattle_user_stats', ['battleid' => $battleid, 'userid' => $userid]);
        if (!$stats) return true;
        
        $today = strtotime('today', time());
        if ($stats->last_battle_date != $today) {
            return true;
        }
        
        return $stats->daily_battles_count < $maxdaily;
    }

    /**
     * Gets user rank position.
     *
     * @param int $battleid
     * @param int $userid
     * @return int
     */
    public static function get_user_rank(int $battleid, int $userid): int {
        global $DB;
        
        $stats = $DB->get_record('knowledgebattle_user_stats', ['battleid' => $battleid, 'userid' => $userid]);
        if (!$stats) return 0;
        
        $sql = "SELECT COUNT(id) FROM {knowledgebattle_user_stats} WHERE battleid = ? AND current_points > ?";
        $better_users = $DB->count_records_sql($sql, [$battleid, $stats->current_points]);
        
        return $better_users + 1;
    }

    /**
     * Shuffles options deterministically for a user and question.
     *
     * @param int $userid
     * @param int $questionid
     * @param array $options
     * @return array
     */
    public static function get_shuffled_options(int $userid, int $questionid, array $options): array {
        $seed = md5($userid . '_' . $questionid);
        $indices = array_keys($options);
        
        // Custom deterministic shuffle
        $shuffled_indices = $indices;
        $seed_num = crc32($seed);
        mt_srand($seed_num);
        
        for ($i = count($shuffled_indices) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            $tmp = $shuffled_indices[$i];
            $shuffled_indices[$i] = $shuffled_indices[$j];
            $shuffled_indices[$j] = $tmp;
        }
        mt_srand(); // reset seed
        
        $shuffled_options = [];
        $map = [];
        foreach ($shuffled_indices as $new_index => $original_index) {
            $shuffled_options[] = $options[$original_index];
            $map[$original_index] = $new_index;
        }
        
        return [
            'options' => $shuffled_options,
            'map' => $map
        ];
    }

    /**
     * Reverses the shuffle to get the original index.
     *
     * @param int $userid
     * @param int $questionid
     * @param int $shuffled_index
     * @param int $total_options
     * @return int
     */
    public static function unshuffle_option(int $userid, int $questionid, int $shuffled_index, int $total_options): int {
        $seed = md5($userid . '_' . $questionid);
        $indices = range(0, $total_options - 1);
        
        $shuffled_indices = $indices;
        $seed_num = crc32($seed);
        mt_srand($seed_num);
        
        for ($i = count($shuffled_indices) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            $tmp = $shuffled_indices[$i];
            $shuffled_indices[$i] = $shuffled_indices[$j];
            $shuffled_indices[$j] = $tmp;
        }
        mt_srand();
        
        return $shuffled_indices[$shuffled_index] ?? 0;
    }
}
