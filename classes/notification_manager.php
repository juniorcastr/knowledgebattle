<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Notification Manager for knowledgebattle
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle;

defined('MOODLE_INTERNAL') || die();

class notification_manager {

    private static function get_view_url(object $battle): string {
        $cmid = $battle->coursemodule ?? 0;
        if (!$cmid) {
            $cm = get_coursemodule_from_instance('knowledgebattle', $battle->id, 0, false);
            $cmid = $cm ? (int)$cm->id : 0;
        }
        $url = new \moodle_url('/mod/knowledgebattle/view.php', ['id' => $cmid]);
        return $url->out(false);
    }

    /**
     * Gets a string of this plugin in the language of the message recipient.
     *
     * @param string $key String identifier.
     * @param object $recipient Recipient user record.
     * @param mixed $a Optional string placeholders.
     * @return string
     */
    private static function str(string $key, object $recipient, $a = null): string {
        $lang = !empty($recipient->lang) ? $recipient->lang : $GLOBALS['CFG']->lang;
        return get_string_manager()->get_string($key, 'mod_knowledgebattle', $a, $lang);
    }

    public static function notify_challenge(object $match, object $battle, int $challengerid, int $challengedid): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/message/lib.php');
        
        $challenger = \core_user::get_user($challengerid);
        $challenged = \core_user::get_user($challengedid);
        $timeout = $battle->wo_timeout_hours ?? 24;
        
        $url_string = self::get_view_url($battle);
        
        $a = (object)[
            'firstname' => $challenger->firstname,
            'name' => $battle->name,
            'hours' => $timeout,
        ];
        $message = self::str('code_msg_challenge_body', $challenged, $a) . "\n\n" .
            self::str('code_msg_link', $challenged, $url_string);
        
        $msg = new \core\message\message();
        $msg->component = 'mod_knowledgebattle';
        $msg->name = 'challenge';
        $msg->userfrom = $challenger;
        $msg->userto = $challenged;
        $msg->subject = self::str('code_msg_challenge_subject', $challenged);
        $msg->fullmessage = $message;
        $msg->fullmessageformat = FORMAT_PLAIN;
        $a->name = '<strong>' . $battle->name . '</strong>';
        $msg->fullmessagehtml = '<p>' . self::str('code_msg_challenge_body', $challenged, $a) . '</p><p><a href="' .
            $url_string . '">' . self::str('code_msg_challenge_link', $challenged) . '</a></p>';
        $msg->smallmessage = self::str('code_msg_challenge_small', $challenged, $challenger->firstname);
        $msg->notification = 1;
        
        message_send($msg);
    }

    public static function notify_battle_result(object $match, object $battle): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/message/lib.php');
        
        if ($match->player2_id <= 0) return; // bot
        
        $p1 = \core_user::get_user($match->player1_id);
        $p2 = \core_user::get_user($match->player2_id);
        $noreply = \core_user::get_noreply_user();
        
        $url_string = self::get_view_url($battle);
        
        $send_result = function($user, $opponent, $is_winner, $is_draw, $my_score, $opp_score) use ($battle, $noreply, $url_string) {
            $msg = new \core\message\message();
            $msg->component = 'mod_knowledgebattle';
            $msg->name = 'battleresult';
            $msg->userfrom = $noreply;
            $msg->userto = $user;
            $msg->subject = self::str('code_msg_result_subject', $user);
            
            $a = (object)[
                'opponent' => $opponent->firstname,
                'myscore' => $my_score,
                'oppscore' => $opp_score,
            ];
            if ($is_draw) {
                $content = self::str('code_msg_result_draw', $user, $a);
            } elseif ($is_winner) {
                $content = self::str('code_msg_result_win', $user, $a);
            } else {
                $content = self::str('code_msg_result_loss', $user, $a);
            }
            
            $msg->fullmessage = $content . "\n\n" . self::str('code_msg_link', $user, $url_string);
            $msg->fullmessageformat = FORMAT_PLAIN;
            $msg->fullmessagehtml = "<p>{$content}</p><p><a href=\"{$url_string}\">" . self::str('code_msg_view_results', $user) . "</a></p>";
            $msg->smallmessage = $content;
            $msg->notification = 1;
            
            message_send($msg);
        };
        
        $p1_winner = ($match->winner_id == $match->player1_id);
        $p2_winner = ($match->winner_id == $match->player2_id);
        $is_draw = ($match->winner_id == 0);
        
        $send_result($p1, $p2, $p1_winner, $is_draw, $match->p1_score, $match->p2_score);
        $send_result($p2, $p1, $p2_winner, $is_draw, $match->p2_score, $match->p1_score);
    }

    public static function notify_wo_warning(object $match, object $battle, int $userid): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/message/lib.php');
        
        $user = \core_user::get_user($userid);
        $noreply = \core_user::get_noreply_user();
        
        $url_string = self::get_view_url($battle);
        
        $content = self::str('code_msg_wo_body', $user, $battle->name);
        
        $msg = new \core\message\message();
        $msg->component = 'mod_knowledgebattle';
        $msg->name = 'wowarning';
        $msg->userfrom = $noreply;
        $msg->userto = $user;
        $msg->subject = self::str('code_msg_wo_subject', $user);
        $msg->fullmessage = $content . "\n\n" . self::str('code_msg_link', $user, $url_string);
        $msg->fullmessageformat = FORMAT_PLAIN;
        $msg->fullmessagehtml = "<p>{$content}</p><p><a href=\"{$url_string}\">" . self::str('code_msg_wo_link', $user) . "</a></p>";
        $msg->smallmessage = $content;
        $msg->notification = 1;
        
        message_send($msg);
    }
}
