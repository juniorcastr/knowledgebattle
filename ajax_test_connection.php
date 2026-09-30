<?php
/**
 * AJAX endpoint for testing AI provider and model connection.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio / Knowledge Battle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}

require_once(__DIR__ . '/../../config.php');

require_login();
require_sesskey();

$context = \context_system::instance();
require_capability('moodle/site:config', $context);

header('Content-Type: application/json; charset=utf-8');

$provider = optional_param('provider', '', PARAM_ALPHANUMEXT);
$model = optional_param('model', '', PARAM_RAW);
$apikey = optional_param('apikey', '', PARAM_RAW);
$baseurl = optional_param('baseurl', '', PARAM_RAW);
if ($provider !== 'local_llm') {
    $baseurl = '';
}

try {
    $result = \mod_knowledgebattle\external\test_ai_connection::execute(
        $provider,
        $model,
        $apikey,
        $baseurl
    );
    echo json_encode($result);
} catch (\Throwable $e) {
    echo json_encode([
        'success' => false,
        'latency' => 0,
        'provider' => $provider,
        'model' => $model,
        'http_code' => 0,
        'reply' => '',
        'error' => $e->getMessage()
    ]);
}
exit;
