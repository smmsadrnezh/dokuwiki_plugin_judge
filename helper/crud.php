<?php

if (!defined('DOKU_INC')) {
    define('DOKU_INC', dirname(__FILE__) . '/../../../../');
    require_once(DOKU_INC . 'inc/init.php');
    require_once(DOKU_INC . 'inc/plugin.php');
}

class helper_plugin_judge_crud extends DokuWiki_Plugin
{

    function __construct()
    {
        define('DBFILE', dirname(dirname(__FILE__)) . '/submissions.sqlite');
        date_default_timezone_set('Asia/Tehran');
        $this->db = new SQLite3(DBFILE);
    }

    public function tableRender($data, $mode, $count = 1, $sort = "timestamp")
    {
        /** find submissions */
        $results = $this->getSubmissions($data, $sort);

        if (!$results) {
            return Array('submissions_table' => NULL, 'count' => 0);
        }

        /** building result table */
        $table = '';
        $i = $count;
        $table_data = Array();
        if ($data["type"] == "output-only") {
            while ($row = $results->fetchArray()) {
                if ($mode == "html" && is_null($data["problem_name"]))
                    $table_data[] = Array($i, '<a href="' . $row["problem_name"] . '">' . $row["problem_name"] . '</a>', $this->convert_time($row["timestamp"]), ($row["status_code"] == '1' ? "درست" : "نادرست"));
                elseif ($mode == "html" && !is_null($data["problem_name"]))
                    $table_data[] = Array($i, $this->convert_time($row["timestamp"]), ($row["status_code"] == '1' ? "درست" : "نادرست"));
                elseif ($mode == "csv")
                    $table .= $i . "\toutput-only\t" . $row["problem_name"] . "\t" . $this->convert_time($row["timestamp"]) . "\t \t" . ($row["status_code"] == '1' ? "درست" : "نادرست") . "\n";
                $i += 1;
            }
        } elseif ($data["type"] == "test-case") {
            while ($row = $results->fetchArray()) {
                $language = $this->
                db->query('SELECT language FROM submission_test_case WHERE submit_id = ' . $row["submit_id"] . ';')->fetchArray();
                if ($row["status_code"] == '0')
                    $valid_text = '<div class="loader"></div>';
                else
                    $valid_text = $this->valid_text($row["submit_id"]);

                /** table rendering */
                if ($mode == "html" && is_null($data["user"]))
                    $table_data[] = Array($i, '<a href="' . $row["problem_name"] . '">' . $row["problem_name"] . '</a>', $row['username'], $this->convert_time($row["timestamp"]), $language[0], $valid_text);
                elseif ($mode == "html" && is_null($data["problem_name"]))
                    $table_data[] = Array($i, '<a href="' . $row["problem_name"] . '">' . $row["problem_name"] . '</a>', $this->convert_time($row["timestamp"]), $language[0], $valid_text);
                elseif ($mode == "html" && !is_null($data["problem_name"]))
                    $table_data[] = Array($i, $this->convert_time($row["timestamp"]), $language[0], $valid_text);
                elseif ($mode == "csv")
                    $table .= $i . "\ttest-case\t" . $row["problem_name"] . "\t" . $this->convert_time($row["timestamp"]) . "\t" . $language[0] . "\t" . $this->valid_text($row["submit_id"]) . "\n";
                $i += 1;
            }
        } else {
            while ($row = $results->fetchArray()) {
                if ($row["type"] == "output-only") {
                    $table_data[] = Array($i, '<a href="' . $row["problem_name"] . '">' . $row["problem_name"] . '</a>', $this->getLang('outputonly_question'), $this->convert_time($row["timestamp"]), ($row["status_code"] == '1' ? "درست" : "نادرست"));
                } else {
                    if (!$row["status_code"]) {
                        $table_data[] = Array($i, '<a href="' . $row["problem_name"] . '">' . $row["problem_name"] . '</a>', $this->getLang('programming_question'), $this->convert_time($row["timestamp"]), '<div class="loader"></div>');
                    } else {
                        $table_data[] = Array($i, '<a href="' . $row["problem_name"] . '">' . $row["problem_name"] . '</a>', $this->getLang('programming_question'), $this->convert_time($row["timestamp"]), $this->valid_text($row["submit_id"]));
                    }

                }
                $i += 1;
            }
        }
        if ($mode == "html") {
            $table_row = Array();
            foreach ($table_data as &$data)
                $table_row[] = join($data, '</td><td class="col0">');
            $table = '<tr class="row0"><td class="col0">' . join($table_row, '</td></tr><tr class="row0"><td class="col0">') . '</td></tr>';
        }

        return array('submissions_table' => $table, 'count' => $i);
    }

    public function getSubmissions($data, $sort = "timestamp")
    {

        /** building the query */
        $query = array();
        if (!empty($data["problem_name"]))
            $query[] = 'problem_name = "' . $data["problem_name"] . '"';
        if (!empty($data["user"]))
            $query[] = 'username = "' . $data["user"] . '"';
        if (!empty($data["type"]))
            $query[] = 'type = "' . $data["type"] . '"';

        if (empty($data["problem_name"]) && empty($data["user"]) && empty($data["type"]))
            $query = 'SELECT * FROM submissions ORDER BY "' . $sort . '" ASC ;';
        else
            $query = 'SELECT * FROM submissions WHERE ' . join($query, " AND ") . ' ORDER BY "' . $sort . '" ASC ;';

        /** running the query */
        $results = $this->db->query($query);
        if (!is_array($results->fetchArray())) {
            return False;
        }
        $results->reset();
        return $results;
    }

    public function convert_time($timestamp)
    {
        global $conf;
        require_once dirname(__FILE__) . '/jdatetime.class.php';
        $pdate = new jDateTime(false, true, 'Asia/Tehran');
        if ($conf['lang'] == "fa")
            return $pdate->date("l j F Y H:i:s", $timestamp);
        else
            return date('l j F Y H:i:s', $timestamp);
    }

    public function valid_text($id)
    {
        $valid = $this->db->query('SELECT "valid" FROM submission_test_case WHERE submit_id = ' . $id . ';')->fetchArray();
        switch ($valid[0]) {
            case "0":
                return "درست";
            case "1":
                return "نادرست";
            case "2":
                return "خطای زمان کامپایل";
            case "3":
                return "خطای زمان اجرا";
            case "4":
                return "خطای مدت اجرا";
        }
    }

    public function delSubmissions($data)
    {

        /** get Submissions */
        $results = $this->getSubmissions($data);

        if (!$results) {
            return $this->getLang("empty_result");
        }

        while ($row = $results->fetchArray()) {
            /** Remove Uploaded Codes */
            if ($row["type"] == "test-case") {
                $targetdir = $this->getConf('upload_path');
                if (substr($targetdir, -1) != "/")
                    $targetdir .= "/";
                if (substr($targetdir, 1) != "/")
                    $targetdir = "/" . $targetdir;
                $file_pattern = $targetdir . $row["submit_id"] . ".*";
                array_map("unlink", glob("$file_pattern"));

                $this->db->query('DELETE FROM submission_test_case WHERE submit_id ="' . $row["submit_id"] . '";');
            }

            $this->db->query('DELETE FROM submissions WHERE submit_id ="' . $row["submit_id"] . '";');

        }

        return $this->getLang("delete_success");
    }

}