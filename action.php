<?php
/**
 * Syntax Plugin: Action on Ajax requests, My submissions page and export CSV
 *
 * @license GPL 3 (http://www.gnu.org/licenses/gpl.html)
 * @author  Masoud Sadrnezhaad <masoud@sadrnezhaad.ir>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) {
    die();
}

class action_plugin_judge extends DokuWiki_Action_Plugin
{

    /**
     * Register the events
     *
     * @param Doku_Event_Handler $controller
     */
    public function register(Doku_Event_Handler $controller)
    {

        /**
         * Submission button in top user menu bar
         */
        $controller->register_hook('TEMPLATE_USERTOOLS_DISPLAY', 'BEFORE', $this, 'addButton');

        /**
         * Submissions page content
         */
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'submissionsPageAction');
        $controller->register_hook('TPL_ACT_UNKNOWN', 'BEFORE', $this, 'submissionsPageContent');

        /**
         * Remove page cache after login
         */
        $controller->register_hook('AUTH_LOGIN_CHECK', 'AFTER', $this, 'removePageCache');

        /**
         * export to csv icon in submissions page
         */
        $controller->register_hook('TEMPLATE_PAGETOOLS_DISPLAY', 'BEFORE', $this, 'addCsvButton', array());
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'exportToCSV');

        /**
         * Ajax calls
         */
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'ajaxHandler');


    }

    public function addButton(Doku_Event $event)
    {
        if ($_SERVER['REMOTE_USER']) {
            $event->data['items'] = array_slice($event->data['items'], 0, -1, true)
                + array('submissions' => '<li ><a href="' . DOKU_URL . '?do=submissions" id="user_submissions" rel="nofollow" title="' . $this->getLang('btn_my_submissions') . '">' . $this->getLang('btn_my_submissions') . '</a></li>')
                + array_slice($event->data['items'], -1, 1, true);
        }
    }

    public function submissionsPageAction(&$event)
    {
        if ($event->data != 'submissions') {
            return;
        }
        $event->preventDefault();
        return true;
    }

    public function submissionsPageContent(&$event)
    {
        if ($event->data != 'submissions') {
            return;
        }
        $event->preventDefault();

        $table_html = $this->buildResultTable();
        print $table_html;
    }

    public function buildResultTable()
    {
        $crud = plugin_load('helper', 'judge_crud');
        $table_html = '
            <h1>' . $this->getLang('programming_questions') . '</h1>
            <div>
                <div class="table sectionedit1">
                    <table class="inline">
                        <thead>
                            <tr class="row0">
                                <th class="col0">' . $this->getLang('count_number') . '</th><th class="col1">' . $this->getLang('question_name') . '</th><th class="col2">' . $this->getLang('timestamp') . '</th><th class="col3">' . $this->getLang('language') . '</th><th class="col4">' . $this->getLang('status') . '</th>
                            </tr>
                        </thead>
                        <tbody>';

        $table_html .= $crud->tableRender(array('type' => "test-case", 'user' => $_SERVER['REMOTE_USER']), "html", 1, "timestamp")["submissions_table"];

        $table_html .= '
                        </tbody>
                    </table>
                </div>
            </div>';

        $table_html .= '
            <h1>' . $this->getLang('outputonly_questions') . '</h1>
            <div>
                <div class="table sectionedit1">
                    <table class="inline">
                        <thead>
                            <tr class="row0">
                                <th class="col0">' . $this->getLang('count_number') . '</th><th class="col1">' . $this->getLang('question_name') . '</th><th class="col2">' . $this->getLang('timestamp') . '</th><th class="col3">' . $this->getLang('status') . '</th>
                            </tr>
                        </thead>
                    <tbody>';

        $table_html .= $crud->tableRender(array('type' => "output-only", 'user' => $_SERVER['REMOTE_USER']), "html", 1, "timestamp")["submissions_table"];

        $table_html .= '
                    </tbody>
                    </table>
                </div>
                    <input class="button" type="button" onClick="history.go(0)" value="' . $this->getLang('table_update') . '" tabindex="4" />
            </div>';
        return $table_html;
    }


    public function addCsvButton(Doku_Event $event)
    {
        global $ID, $ACT;
        if ($ACT != 'submissions') {
            return;
        }
        if ($event->data['view'] == 'main') {
            $params = array('do' => 'export_csv');

            // insert button at position before last (up to top)
            $event->data['items'] = array_slice($event->data['items'], 0, -1, true) +
                array('export_pdf' =>
                    '<li>'
                    . '<a href="' . wl($ID, $params) . '"  class="action export_pdf" rel="nofollow" title="' . $this->getLang('btn_export_csv') . '">'
                    . '<span>' . $this->getLang('btn_export_csv') . '</span>'
                    . '</a>'
                    . '</li>'
                ) +
                array_slice($event->data['items'], -1, 1, true);
        }
    }

    public function exportToCSV(&$event)
    {
        if ($event->data != 'export_csv') {
            return;
        }
        $event->preventDefault();
        $crud = plugin_load('helper', 'judge_crud');
        ob_start('ob_gzhandler');
        ob_clean();
        $csvOutput = "#\tType\tProblem name\tTimestamp\tLanguage\tStatus\n";
        $csvOutput .= $crud->tableRender(array('type' => "output-only", 'user' => $_SERVER['REMOTE_USER']), "csv", 1, "timestamp")["submissions_table"];
        $csvOutput .= $crud->tableRender(array('type' => "test-case", 'user' => $_SERVER['REMOTE_USER']), "csv", 1, "timestamp")["submissions_table"];
        print $csvOutput;
        ob_end_flush();
        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=" . $_SERVER['REMOTE_USER'] . "_submissions_" . date("Y-m-d_H-i", time()) . ".csv");
        header("Pragma: no-cache");
        header("Expires: 0");
    }

    public function removePageCache(&$event)
    {
        global $config_cascade;
        @touch(reset($config_cascade['main']['local']));
    }

    function ajaxHandler(Doku_Event $event, $param)
    {
        if ($event->data !== 'plugin_judge') {
            return;
        }
        //no other ajax call handlers needed
        $event->stopPropagation();
        $event->preventDefault();

        global $INPUT;
        $task = $INPUT->str('name');

        //data
        $data = array();

        switch ($task) {
            case "submit":
                define('DBFILE', dirname(__FILE__) . '/submissions.sqlite');
                define('DBENGINE', 'sqlite3');

                if (file_exists(DBFILE)) {
                    $data = $this->submit_to_db();
                }
            case "outputonlyResult":
                $data[] = $this->compare($INPUT->str('user_output'), $INPUT->str('problem_name'));
                break;
            case "resultRefresh":
                $crud = plugin_load('helper', 'judge_crud');
                $data[] = $crud->tableRender(array('problem_name' => $INPUT->str('problem_name'), 'type' => $INPUT->str('type'), 'user' => $INPUT->str('user')), "html", 1, "timestamp")["submissions_table"];
                break;
            case "judge":
                sleep(10);
                $run_status = rand(0, 4);
                define('DBFILE', dirname(__FILE__) . '/submissions.sqlite');
                $db = new SQLite3(DBFILE);
                $query = 'UPDATE submissions SET status_code =1 WHERE submit_id = ' . $_POST['id'] . ';';
                $db->exec($query);
                $query = 'UPDATE submission_test_case SET valid =' . $run_status . ' WHERE submit_id = ' . $_POST['id'] . ';';
                $db->exec($query);
                break;
            case "upload":
                $data[] = $this->upload($INPUT->str('file_name'), $INPUT->str('path'), $INPUT->str('code'));
                break;
        }

        //json library of DokuWiki
        $json = new JSON();

        //set content type
        header('Content-Type: application/json');
        echo $json->encode($data);
    }

    function submit_to_db()
    {

        if (!defined('DOKU_INC')) {
            define('DOKU_INC', dirname(__FILE__) . '/../../../../');
            include_once DOKU_INC . 'inc/init.php';
            include_once DOKU_INC . 'inc/plugin.php';
        }

        global $conf;

        include_once dirname(__FILE__) . '/helper/jdatetime.class.php';
        $pdate = new jDateTime(false, true, 'Asia/Tehran');
        date_default_timezone_set('Asia/Tehran');
        $query = 'INSERT INTO submissions VALUES (NULL,"' . time() . '","' . $_POST['problem_name'] . '","' . $_POST['user'] . '","' . $_POST['type'] . '",' . $_POST['status_code'] . ')';

        $db = new SQLite3(DBFILE);
        $db->exec($query);
        $id = $db->lastInsertRowID();
        $result_id_query = 'SELECT COUNT(*) FROM submissions WHERE problem_name = "' . $_POST['problem_name'] . '"AND username="' . $_POST['user'] . '"';
        $row_number = $db->querySingle($result_id_query);

        if ($conf['lang'] == "fa") {
            $date = $pdate->date("l j F Y H:i:s");
        } else {
            $date = date('l j F Y H:i:s');
        }

        if ($_POST['type'] === "output-only") {
            $result = array('date' => $date, 'row_number' => $row_number);
        } elseif ($_POST['type'] === "test-case") {
            $test_case_query = 'INSERT INTO submission_test_case VALUES (' . $id . ',"' . $_POST['language'] . '",' . '0,"' . $_POST['runtime'] . '")';
            $db->exec($test_case_query);
            $result = array('date' => $date, 'row_number' => $row_number, 'id' => $id);
        }
        return $result;
    }

    public function compare($user_output, $problem_name)
    {
        $extension = $this->loadHelper('judge_numbers');
        $answer = $extension->parseNumber(rawWiki("داوری:" . $problem_name));
        if ($answer == $extension->parseNumber($user_output)) {
            return true;
        } else {
            return false;
        }
    }

    public function upload($filename, $path, $code)
    {
        $allowedExts = array("java", "py", "c", "cpp");

        if (!in_array(pathinfo(basename($filename), PATHINFO_EXTENSION), $allowedExts, true)) {
            return $this->getLang('err_file_format');
        }

        $targetdir = $path;
        if (substr($targetdir, -1) != "/") {
            $targetdir .= "/";
        }
        if (substr($targetdir, 1) != "/") {
            $targetdir = "/" . $targetdir;
        }

        $temp = explode(".", $filename);

        if (file_exists(TARGETFILE)) {
            return $filename . $this->getLang('err_file_exist');
        } elseif (!file_exists($targetdir)) {
            return $this->getLang('err_dir') . $targetdir . $this->getLang('err_upload_dir');
        } else {
            define('DBFILE', dirname(__FILE__) . '/submissions.sqlite');
            $db = new SQLite3(DBFILE);
            $query = 'SELECT submit_id FROM submissions ORDER BY submit_id DESC LIMIT 1';
            $id = $db->exec($query);
            define('TARGETFILE', $targetdir . $id . "." . end($temp));
            file_put_contents(TARGETFILE, $code);
        }
    }

}

