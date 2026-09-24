<?php
/**
 * Notification Manager for knowledgebattle
 *
 * @package    mod_knowledgebattle
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle;

defined('MOODLE_INTERNAL') || die();

class notification_manager {

    public static function notify_challenge(object $match, object $battle, int $challengerid, int $challengedid): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/message/lib.php');
        
        $challenger = \core_user::get_user($challengerid);
        $challenged = \core_user::get_user($challengedid);
        $timeout = $battle->wo_timeout_hours ?? 24;
        
        $url = new \moodle_url('/mod/knowledgebattle/view.php', ['id' => $battle->coursemodule]);
        $url_string = $url->out(false);
        
        $message = "{$challenger->firstname} has challenged you to a Knowledge Battle in {$battle->name}! You have {$timeout} hours to respond.\n\nLink: {$url_string}";
        
        $msg = new \core\message\message();
        $msg->component = 'mod_knowledgebattle';
        $msg->name = 'challenge';
        $msg->userfrom = $challenger;
        $msg->userto = $challenged;
        $msg->subject = 'New Knowledge Battle Challenge!';
        $msg->fullmessage = $message;
        $msg->fullmessageformat = FORMAT_PLAIN;
        $msg->fullmessagehtml = "<p>{$challenger->firstname} has challenged you to a Knowledge Battle in <strong>{$battle->name}</strong>! You have {$timeout} hours to respond.</p><p><a href=\"{$url_string}\">Click here to respond</a></p>";
        $msg->smallmessage = "New Knowledge Battle challenge from {$challenger->firstname}";
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
        
        $url = new \moodle_url('/mod/knowledgebattle/view.php', ['id' => $battle->coursemodule]);
        $url_string = $url->out(false);
        
        $send_result = function($user, $opponent, $is_winner, $is_draw, $my_score, $opp_score) use ($battle, $noreply, $url_string) {
            $msg = new \core\message\message();
            $msg->component = 'mod_knowledgebattle';
            $msg->name = 'battleresult';
            $msg->userfrom = $noreply;
            $msg->userto = $user;
            $msg->subject = 'Knowledge Battle Result';
            
            if ($is_draw) {
                $content = "Your battle against {$opponent->firstname} ended in a draw! ({$my_score} - {$opp_score})";
            } elseif ($is_winner) {
                $content = "Congratulations! You won your battle against {$opponent->firstname}! ({$my_score} - {$opp_score})";
            } else {
                $content = "You lost your battle against {$opponent->firstname}. Keep trying! ({$my_score} - {$opp_score})";
            }
            
            $msg->fullmessage = $content . "\n\nLink: {$url_string}";
            $msg->fullmessageformat = FORMAT_PLAIN;
            $msg->fullmessagehtml = "<p>{$content}</p><p><a href=\"{$url_string}\">View Results</a></p>";
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
        
        $url = new \moodle_url('/mod/knowledgebattle/view.php', ['id' => $battle->coursemodule]);
        $url_string = $url->out(false);
        
        $content = "Warning: Your Knowledge Battle in {$battle->name} will expire soon! Respond to avoid losing by W.O.";
        
        $msg = new \core\message\message();
        $msg->component = 'mod_knowledgebattle';
        $msg->name = 'wowarning';
        $msg->userfrom = $noreply;
        $msg->userto = $user;
        $msg->subject = 'Knowledge Battle Expiring Soon!';
        $msg->fullmessage = $content . "\n\nLink: {$url_string}";
        $msg->fullmessageformat = FORMAT_PLAIN;
        $msg->fullmessagehtml = "<p>{$content}</p><p><a href=\"{$url_string}\">Click here to play</a></p>";
        $msg->smallmessage = $content;
        $msg->notification = 1;
        
        message_send($msg);
    }
}
