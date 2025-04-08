<?php
// This file is part of Additional tools library for Moodle™.

/**
 * Behat task execution helper.
 *
 * @package     tool_mulib
 * @copyright   2022 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This is a fake CLI script, it is a really ugly hack which emulates CLI via web interface.
define('CLI_SCRIPT', true);
define('WEB_CRON_EMULATED_CLI', 'defined'); // Ugly ugly hack.
define('NO_OUTPUT_BUFFERING', true);

require('../../../../../config.php');

if (!defined('BEHAT_SITE_RUNNING')) {
    die;
}

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', '1');
$CFG->debug = (E_ALL | E_STRICT);
$CFG->debugdisplay = 1;

require_once($CFG->libdir.'/clilib.php');
require_once($CFG->libdir.'/cronlib.php');

\core\session\manager::write_close();

$taskname = required_param('behat_task', PARAM_RAW);
$taskname = ltrim($taskname, '\\');
$record = $DB->get_record('task_scheduled', ['classname' => '\\' . $taskname], '*', MUST_EXIST);

$task = \core\task\manager::get_scheduled_task($taskname);

// Do setup for cron task.
raise_memory_limit(MEMORY_EXTRA);
\core\cron::setup_user();

// Get lock.
$cronlockfactory = \core\lock\lock_config::get_lock_factory('cron');
if (!$cronlock = $cronlockfactory->get_lock('core_cron', 10)) {
    throw new Exception('Unable to obtain core_cron lock for scheduled task');
}
if (!$lock = $cronlockfactory->get_lock('\\' . get_class($task), 10)) {
    $cronlock->release();
    throw new Exception('Unable to obtain task lock for scheduled task');
}
$task->set_lock($lock);
$cronlock->release();

@header('Content-Type: text/plain; charset=utf-8');
@ini_set('html_errors', 'off');

try {
    // Prepare the renderer.
    \core\cron::prepare_core_renderer();

    $task->execute();

    // Restore the previous renderer.
    \core\cron::prepare_core_renderer();

    // Mark task complete.
    \core\task\manager::scheduled_task_complete($task);

    echo "\nScheduled task '$taskname' completed";

} catch (Throwable $e) {
    // Restore the previous renderer.
    \core\cron::prepare_core_renderer();

    // Mark task failed and throw exception.
    \core\task\manager::scheduled_task_failed($task);

    throw new Exception('Scheduled task failed', 0, $e);
}
