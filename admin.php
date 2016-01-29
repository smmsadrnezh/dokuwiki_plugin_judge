<?php

class admin_plugin_judge extends DokuWiki_Admin_Plugin
{

    /**
     * handle user request
     */
    function handle()
    {

        if (!isset($_REQUEST['cmd'])) return;   // first time - nothing to do

        if (!checkSecurityToken()) return;
        if (!is_array($_REQUEST['cmd'])) return;

        $crud = plugin_load('helper', 'judge_crud', true);

        // verify valid values
        switch (key($_REQUEST['cmd'])) {
            case 'get' :
                $this->output = '<div class="table sectionedit1">
                                    <table class="inline">';
                $table = $crud->tableRender(array('problem_name' => $_REQUEST['problem_name'], 'type' => $_REQUEST['type'], 'user' => $_REQUEST['user']), "html", 1, "timestamp");
                if($table["count"] == 0){
                    $this->output .= '<p>'. $this->getLang("empty_result") .'</p>';
                    break;
                } else {
                    $this->output .= $table["submissions_table"];
                }
                $this->output .= "</table></div>";
                break;
            case 'delete' :
                $this->output = $crud->delSubmissions(array('problem_name' => $_REQUEST['problem_name'], 'type' => $_REQUEST['type'], 'user' => $_REQUEST['user']));
                break;
        }
    }

    /**
     * output appropriate html
     */
    function html()
    {
        global $ID, $auth;

        $filter['grps'] = "user";
        if ($auth->canDo('getUsers')) {  // is this feature available?
            $users = $auth->retrieveUsers(0, 0);
        }

        $html = '<p>' . $this->getLang("intro_message") . '</p>
            <form class="admin-form" action="' . wl($ID) . '" method="post">
            <label class="block">' . $this->getLang("question_name") . ': <input name="problem_name" type="text" /></label>
            <label class="block">' . $this->getLang("sender") . ':
            <select name="user">
            <option value="">همهٔ کاربران</option>';
        while ($user = current($users)){
            $html .= '<option value="' . key($users) . '">' . $user["name"] . '</option>';
            next($users);
        }

        $html .= '
            </select>
            </label>
            <label class="block">
            <input type="radio" name="type" value="test-case"> ' . $this->getLang('programming_questions') . '<br />
            <input type="radio" name="type" value="output-only"> ' . $this->getLang('outputonly_questions'). '
            </label>';

        // output hidden values to ensure dokuwiki will return back to this plugin
        $html .= '<input type="hidden" name="do"   value="admin" />'
            . '<input type="hidden" name="page" value="' . $this->getPluginName() . '" />';

        ptln($html);
        formSecurityToken();

        $html = '
        <input type="submit" name="cmd[get]"  value="' . $this->getLang('btn_get_submissions') . '" />
        <input type="submit" name="cmd[delete]"  value="' . $this->getLang('btn_delete_submissions') . '" />
        </form><h1 class="sectionedit1"></h1>
        ';
        $html .= $this->output;
        ptln($html);
    }

}