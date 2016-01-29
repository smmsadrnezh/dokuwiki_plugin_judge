<?php

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class syntax_plugin_judge extends DokuWiki_Syntax_Plugin
{
    /**
     * @return string Syntax mode type. container indicates that this plugin tag can be nested in listblock, table, quote, hr tags.
     */
    public function getType()
    {
        return 'container';
    }

    /**
     * @return block indicates that open paragraphs need to be closed before plugin output. it can't be used inside open paragraph.
     */
    public function getPType()
    {
        return 'block';
    }

    /**
     * @return int Sort order - Low numbers go before high numbers
     */
    public function getSort()
    {
        return 10;
    }

    /**
     * Connect lookup pattern to lexer. SpecialPattern indicates that this pattern don't have exit pattern and pattern data is not to be processed.
     *
     * @param string $mode Parser mode
     */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('\{\{[judge|داوری|scoreboard][ ]*[^}]*\}\}', $mode, 'plugin_judge');
    }

    /**
     * Handle matches of the judge syntax
     *
     * @param string $match The text matched by the patterns
     * @param int $state Type of pattern which triggered this call to handle()
     * @param int $pos The character position of the matched text.
     * @param Doku_Handler $handler The handler
     * @return array Data for the renderer
     */

    public function handle($match, $state, $pos, Doku_Handler &$handler)
    {

        if (substr($match, 2, 10) == "scoreboard") {
            /** strip markup from start and end $match */
            $match = substr($match, 13, -2);
            return $this->scoreboard_handle($match);
        } elseif (substr($match, 2, 5) == "judge") {
            /** strip markup from start and end $match */
            $match = substr($match, 8, -2);
            return $this->judge_handle($match);
        } elseif (substr($match, 2, 10) == "داوری") {
            /** strip markup from start and end $match */
            $match = substr($match, 13, -2);
            return $this->judge_handle($match);
        }

    }

    public function scoreboard_handle($match)
    {

        /** extract problem names into array */
        $parameters = explode(' ', $match);

        return array('mode' => "scoreboard", 'questions' => $parameters);

    }

    public function judge_handle($match)
    {
        global $ID, $conf;

        /** extract problem parameters into array */
        $parameters = explode(' ', $match);

        /** set problem parameters */
        foreach ($parameters as $parameter) {
            if (strpos($parameter, '=') == false) {

                /** find variable type and set it */
                switch ($parameter) {
                    case "test-case":
                        $type = $parameter;
                        break;
                    case "output-only":
                        $type = $parameter;
                        break;
                    case "tester.cpp":
                        $method = $parameter;
                        break;
                    case "tester.py":
                        $method = $parameter;
                        break;
                    case "diff":
                        $method = $parameter;
                        break;
                    case "برنامه-نویسی":
                        $type = "test-case";
                        break;
                    case "فقط-خروجی":
                        $type = "output-only";
                        break;
                    case "java":
                        $language = "Java";
                        break;
                    case "Java":
                        $language = "Java";
                        break;
                    case "python2":
                        $language = "Python 2";
                        break;
                    case "python3":
                        $language = "Python 3";
                        break;
                    case "cpp":
                        $language = "C++";
                        break;
                    case "c++":
                        $language = "C++";
                        break;
                    case "C++":
                        $language = "C++";
                        break;
                    case "c":
                        $language = "C";
                        break;
                    case "C":
                        $language = "C";
                        break;
                    default:
                        if (is_numeric($parameter)) {
                            $runtime = $parameter;
                        } else {
                            $problem_name = $parameter;
                        }
                }
            } else {

                list($key, $value) = explode('=', $parameter);

                switch ($key) {

                    /** remove key string if included */
                    case "problem":
                        $problem_name = $value;
                        break;
                    case "type":
                        $type = $value;
                        break;
                    case "time":
                        $runtime = $value;
                        break;
                    case "method":
                        switch ($value) {
                            case "tester.cpp":
                                $method = $value;
                                break;
                            case "tester.py":
                                $method = $value;
                                break;
                            case "diff":
                                $method = $value;
                        }
                    case "language":
                        switch ($value) {
                            case "java":
                                $language = "Java";
                                break;
                            case "Java":
                                $language = "Java";
                                break;
                            case "python2":
                                $language = "Python 2";
                                break;
                            case "python3":
                                $language = "Python 3";
                                break;
                            case "cpp":
                                $language = "C++";
                                break;
                            case "c++":
                                $language = "C++";
                                break;
                            case "C++":
                                $language = "C++";
                                break;
                            case "c":
                                $language = "C";
                                break;
                            case "C":
                                $language = "C";
                                break;
                        }
                }
            }
        }

        /** set default value for parameters */
        if ($problem_name == NULL)
            $problem_name = $ID;

        $judge = "داوری:" . $problem_name;

        if ($type === NULL)
            $type = "test-case";

        if ($runtime === NULL)
            $runtime = 0;

        if ($language === NULL)
            $language = "all";

        if ($method === NULL)
            $method = "diff";

        if ($conf['useslash']) {
            $problem_name = str_replace('/', ':', $problem_name);
        }

        return array('mode' => "judge", 'problem_name' => $problem_name, 'type' => $type, 'runtime' => $runtime, 'language' => $language, 'judge' => $judge, 'method' => $method);

    }

    /**
     * Render xhtml output or metadata
     *
     * @param string $mode Renderer mode (supported modes: xhtml)
     * @param Doku_Renderer $renderer The renderer
     * @param array $data The data from the handler() function
     * @return bool If rendering was successful.
     */
    public
    function render($mode, Doku_Renderer &$renderer, $data)
    {
        if ($mode != 'xhtml') return false;

        if ($data["mode"] == "judge") {
            $renderer->doc .= $this->render_judge($data);
        } elseif ($data["mode"] == "scoreboard") {
            $renderer->doc .= $this->render_scoreboard($data);
        }

        return true;
    }

    public function render_judge($data)
    {
        global $conf, $USERINFO;

        $html = '<script src="lib/plugins/judge/ajax.js" charset="utf-8" type="text/javascript"></script>';

//         DOKU_BASE . DOKU_SCRIPT
//        $numbers = plugin_load('helper', 'judge_numbers');
//        $answer = $numbers->parseNumber("۱۲۳");
//        $html .= '<h1>' . DOKU_SCRIPT .  '</h1>';

        /** show plugin to logged in user */
        if ($_SERVER['REMOTE_USER']) {

            /** show plugin if problem_name page exists */
            if (page_exists($data['problem_name'])) {
                $page_answer_exist = page_exists($data['judge']);
                $media_files = array_filter(glob($conf['mediadir'] . "/" . str_replace("%3A", "/", urlencode($data['judge'])) . "/" . "*"));
                $media_answer_exist = !empty($media_files);
                if (($page_answer_exist || $media_answer_exist) && in_array("user", $USERINFO['grps'])) {
                    $html .= '
                    <div class="judge">
                        <p onclick="jQuery(this).next().slideToggle();">' . $this->getLang('btn_submit_answer') . '</p>
                        <div>
                <form onsubmit="return false;" name="judge-submit-' . $data['problem_name'] . '" method="post">';
                    if ($data['type'] === "output-only") {
                        $html .= '
                        <div>
                            <label class="block">
                                <input type="text" style="margin-left: 2px;" id="user-output-' . $data['problem_name'] . '" size="25" tabindex="1" value="">';
                        $html .=
                            '
                                <input class="button" type="submit" onclick="submitKey(' . "'" . $data['problem_name'] . "','" . $_SERVER['REMOTE_USER'] . "','" . $data['language'] . "','" . $data['type'] . "','" . $data['runtime'] . "','" . "','" . $this->getConf('upload_path') . "'" . '); return false;" value="' . $this->getLang('btn_submit') . '" />
                            </label>
                        </div>
                        </form>
                    ';
                    } elseif ($data['type'] === "test-case") {
                        $html .= '
                        <label class="block">
                            <input id="code-file-' . $data['problem_name'] . '"' . ' onclick="inputFileKey(' . "'" . $data['problem_name'] . "'" . '); return false;">
                            <input class="button"  onclick="inputFileKey(' . "'" . $data['problem_name'] . "'" . '); return false;" type="reset" value="' . $this->getLang('btn_choose_file') . '">
                            <input onchange="changeFilePath(' . "'" . $data['problem_name'] . "'" . ');" style="display: none;" name="code-' . $data['problem_name'] . '" id="code-' . $data['problem_name'] . '" type=file>
                    ';

                        if ($data['language'] === "all") {
                            $html .= '
                            </label>
                            <label class="block" style="margin-top: 5px;">
                                <span>' . $this->getLang('choose_language') . '</span>
                                    <select style="width: auto;" id="language-' . $data['problem_name'] . '">
                                        <option value="Java">Java</option>
                                        <option value="Python 2">Python 2</option>
                                        <option value="Python 3">Python 3</option>
                                        <option value="C++">C++</option>
                                        <option value="C">C</option>
                                    </select>
                        ';
                        }

                        $html .= '
                        <input class="button" type="submit" onclick="submitKey(' . "'" . $data['problem_name'] . "','" . $_SERVER['REMOTE_USER'] . "','" . $data['language'] . "','" . $data['type'] . "','" . $data['runtime'] . "','" . $this->getConf('upload_path') . "'" . '); return false;" value="' . $this->getLang('btn_submit') . '" tabindex="4" />
                        </label>
                        </form>
                    ';
                    }

                    $html .= '
                        <div>
                            <label class="block">
                                <span id="result-label-' . $data['problem_name'] . '"></span>
                                <span id="result-' . $data['problem_name'] . '"></span>
                            </label>
                        </div>
                    ';

                    $html .= '
                    </div></div>
                ';

                    define('DBFILE', dirname(__FILE__) . '/submissions.sqlite');
                    date_default_timezone_set('Asia/Tehran');
                    $db = new SQLite3(DBFILE);
                    $submissions = $db->querySingle('SELECT COUNT(*) FROM submissions WHERE problem_name = "' . $data['problem_name'] . '"AND username="' . $_SERVER['REMOTE_USER'] . '";');
                    if (!empty($submissions))
                        $html .= '
                        <div class="judge" id="previous_submissions-' . $data['problem_name'] . '">
                    ';
                    else
                        $html .= '
                        <div class="judge" style="display: none;" id="previous_submissions-' . $data['problem_name'] . '">
                    ';

                    $html .= '
                        <p onclick="jQuery(this).next().slideToggle();">' . $this->getLang('btn_previous_submissions') . '</p>
                            <div style="display: none;" id="previous_submissions-table-' . $data['problem_name'] . '">
                                <div class="table sectionedit1">
                                    <table class="inline">
                    ';

                    $crud = plugin_load('helper', 'judge_crud', true);
                    if ($data['type'] === "test-case") {

                        $html .= '
                            <thead>
                                <tr class="row0">
                                    <th class="col0">' . $this->getLang('count_number') . '</th><th class="col1">' . $this->getLang('timestamp') . '</th><th class="col2">' . $this->getLang('language') . '</th><th class="col3">' . $this->getLang('status') . '</th>
                                </tr>
                            </thead>
                            <tbody  id="result-row-' . $data['problem_name'] . '">';

                        $html .= $crud->tableRender(array('problem_name' => $data["problem_name"], 'type' => $data["type"], 'user' => $_SERVER['REMOTE_USER']), "html", 1, "timestamp")["submissions_table"];

                        $html .= '</tbody>';
                    } else {
                        $html .= '
                            <thead>
                                <tr class="row0">
                                    <th class="col0">' . $this->getLang('count_number') . '</th><th class="col1">' . $this->getLang('timestamp') . '</th><th class="col2">' . $this->getLang('status') . '</th>
                                </tr>
                            </thead>
                            <tbody  id="result-row-' . $data['problem_name'] . '">
                        ';

                        /** get output-only submissions */
                        $html .= $crud->tableRender(array('problem_name' => $data["problem_name"], 'type' => $data["type"], 'user' => $_SERVER['REMOTE_USER']), "html", 1, "timestamp")["submissions_table"];
                        $html .= '</tbody>';
                    }

                    $html .= '
                                    </table>
                                </div>
                                <input class="button" type="submit" onclick="resultRefresh(' . "'" . $data['problem_name'] . "','" . $data['type'] . "','" . $_SERVER['REMOTE_USER'] . "'" . '); return false;" value="' . $this->getLang('table_update') . '" tabindex="4" />
                            </div>
                        </div>
                    ';
                }
            };


            if (in_array($this->getConf('editors_group'), $USERINFO['grps'])) {
                if (page_exists($data['problem_name'])) {
                    if (auth_quickaclcheck($data['judge']) >= AUTH_EDIT) {
                        if ($data['type'] === "test-case") {
                            if ($media_answer_exist)
                                $html .= '<div class="judge"><p><a target="_blank" href="?tab_files=files&do=media&ns=' . $data['judge'] . '">' . $this->getLang('btn_edit_testcase_long') . '</a></p></div>';
                            elseif ($page_answer_exist)
                                $html .= '<div class="judge"><p><a target="_blank" href="' . $data['judge'] . '">' . $this->getLang('btn_edit_testcase_short') . '</a></p></div>';
                            else
                                $html .= '<div class="judge"><p>' . $this->getLang('btn_submit_testcase') . ' (<a target="_blank" href="' . $data['judge'] . '?do=edit">' . $this->getLang('btn_submit_testcase_short') . '</a> - <a target="_blank" href="?tab_files=upload&do=media&ns=' . $data['judge'] . '">' . $this->getLang('btn_submit_testcase_long') . '</a>)</p></div>';

                        } else {
                            if ($page_answer_exist)
                                $html .= '<div class="judge"><p><a target="_blank" href="' . $data['judge'] . '">' . $this->getLang('btn_edit_correct_answer') . '</a></p></div>';
                            else
                                $html .= '<div class="judge"><p><a target="_blank" href="' . $data['judge'] . "?do=edit" . '">' . $this->getLang('btn_submit_correct_answer') . '</a></p></div>';
                        }
                    }
                } else
                    $html .= '<div class="judge"><p><a target="_blank" href="' . $data['problem_name'] . "?do=edit" . '">' . $this->getLang('btn_create_question_page') . '</a></p></div>';
            }
        }

        return $html;
    }

    public function render_scoreboard($data)
    {
        $crud = plugin_load('helper', 'judge_crud', TRUE);

        $html = '
                <div class="table sectionedit1">
                    <table class="inline">
                            <thead>
                                <tr class="row0">
                                    <th class="col0">' . $this->getLang('count_number') . '</th><th class="col1">' . $this->getLang('question_name') . '</th><th class="col1">' . $this->getLang('sender') . '</th><th class="col2">' . $this->getLang('timestamp') . '</th><th class="col3">' . $this->getLang('language') . '</th><th class="col4">' . $this->getLang('status') . '</th>
                                </tr>
                            </thead>
                            <tbody  id="result-row-' . $data['problem_name'] . '">';

        $i = 1;
        foreach ($data["questions"] as &$problem_name) {

            /** get output-only submissions */
            $submissions = $crud->tableRender(array('problem_name' => $problem_name, 'type' => "test-case", 'user' => NULL), "html", $i, "timestamp");
            $html .= $submissions["submissions_table"];
            $i = $submissions["count"];

        }

        $html .= '
                            </tbody>
                    </table>
                </div>';

        return $html;
    }

}

// vim:ts=4:sw=4:et: