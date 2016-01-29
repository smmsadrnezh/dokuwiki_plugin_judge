function resultRefresh(problem_name, type, user) {

    $url = DOKU_BASE + "lib/exe/ajax.php";
    jQuery.post(
        $url,
        {
            call: 'plugin_judge',
            name: 'resultRefresh',
            user: user,
            problem_name: problem_name,
            type: type
        },
        function (data) {
            document.getElementById("result-row-" + problem_name).innerHTML = data;
        },
        'json'
    );
}

var problem_name;
var user;
var type;
var language;
var runtime;
var path;

function submitKey(problem_name, user, language, type, runtime, path) {

    /** set variables form inputs */
    window["problem_name"] = problem_name;
    window["user"] = user;
    window["type"] = type;
    window["language"] = language;
    window["runtime"] = runtime;
    window["path"] = path;

    if (type === "output-only" && !document.getElementById("user-output-" + problem_name).value) {
        document.getElementById("result-label-" + problem_name).innerHTML = LANG.plugins.judge['error'];
        document.getElementById("result-" + problem_name).innerHTML = LANG.plugins.judge['answer_not_specified'];
        document.getElementById("result-" + problem_name).className = "false";
        return 0;
    }
    if (type === "test-case" && !document.getElementById("code-file-" + problem_name).value) {
        document.getElementById("result-label-" + problem_name).innerHTML = LANG.plugins.judge['error'];
        document.getElementById("result-" + problem_name).innerHTML = LANG.plugins.judge['file_not_specified'];
        document.getElementById("result-" + problem_name).className = "false";
        return 0;
    }

    if (type === "test-case") {
        if (language == "all")
            window["language"] = document.getElementById("language-" + problem_name).value;
        testCaseUpload();
    } else
        outputAnswer();
}

function testCaseUpload() {

    $url = DOKU_BASE + "lib/exe/ajax.php";
    var r = new FileReader();
    r.onload = function (e) {
        jQuery.post(
            $url,
            {
                call: 'plugin_judge',
                name: 'upload',
                code: e.target.result,
                path: path,
                file_name: document.getElementById("code-" + problem_name).files[0].name
            },
            function (data) {
                if (data[0] != null) {
                    document.getElementById("result-label-" + problem_name).innerHTML = LANG.plugins.judge['error'];
                    document.getElementById("result-" + problem_name).innerHTML = data;
                    document.getElementById("result-" + problem_name).className = "false";
                } else
                    testCaseSubmit();
            },
            'json'
        );
    };
    r.readAsText(document.getElementById("code-" + problem_name).files[0]);

}

function testCaseSubmit() {
    /** display previous submissions after first one */
    if (jQuery("#previous_submissions-" + problem_name).css('display') == "none") {
        jQuery("#previous_submissions-" + problem_name).slideToggle();
    }

    /** open previous submissions box after new submit */
    if (jQuery("#previous_submissions-table-" + problem_name).css('display') == "none") {
        jQuery("#previous_submissions-table-" + problem_name).slideToggle();
    }

    $url = DOKU_BASE + "lib/exe/ajax.php";
    jQuery.post(
        $url,
        {
            call: 'plugin_judge',
            name: 'submit',
            user: user,
            problem_name: problem_name,
            type: type,
            language: language,
            runtime: runtime,
            status_code: 0
        },
        function (data) {
            appendResult("در حال اجرا", data.row_number, data.date);
            judge(data.id);
        },
        'json'
    );
}

function outputAnswer() {
    /** display previous submissions after first one */
    if (jQuery("#previous_submissions-" + problem_name).css('display') == "none") {
        jQuery("#previous_submissions-" + problem_name).slideToggle();
    }

    /** open previous submissions box after new submit */
    if (jQuery("#previous_submissions-table-" + problem_name).css('display') == "none") {
        jQuery("#previous_submissions-table-" + problem_name).slideToggle();
    }

    $url = DOKU_BASE + "lib/exe/ajax.php";
    jQuery.post(
        $url,
        {
            call: 'plugin_judge',
            name: 'outputonlyResult',
            user_output: document.getElementById("user-output-" + problem_name).value,
            problem_name: problem_name
        },
        function (data) {
            /** Append to Result Table **/
            outputSubmit(data[0]);
        },
        'json'
    );
}

function outputSubmit($status) {

    if ($status) {
        $name = "true";
        $fa_name = LANG.plugins.judge['correct'];
        $status_code = 1;
    } else {
        $name = "false";
        $fa_name = LANG.plugins.judge['wrong'];
        $status_code = 0;
    }

    /** Show Result **/
    document.getElementById("result-label-" + problem_name).innerHTML = LANG.plugins.judge['answer_status'];
    document.getElementById("result-" + problem_name).innerHTML = $fa_name;
    document.getElementById("result-" + problem_name).className = $name;

    $url = DOKU_BASE + "lib/exe/ajax.php";
    jQuery.post(
        $url,
        {
            call: 'plugin_judge',
            name: 'submit',
            user: user,
            problem_name: problem_name,
            type: type,
            status_code: $status_code
        },
        function (data) {
            appendResult($fa_name, data.row_number, data.date, data.id);
        },
        'json'
    );

}

function appendResult(status, number, date) {

    if (type == "test-case")
        var new_row = '<tr class="row1"><td class="col0">' + number + '</td><td class="col1">' + date + '</td><td class="col2">' + language + '</td><td class="col3"><div class="loader"></div>' + '</td></tr>';
    else
        var new_row = '<tr class="row1"><td class="col0">' + number + '</td><td class="col1">' + date + '</td><td class="col2">' + status + '</td></tr>';

    document.getElementById("result-row-" + problem_name).innerHTML += new_row;
}

function judge(id) {
    $url = DOKU_BASE + "lib/exe/ajax.php";
    var r = new FileReader();
    r.onload = function (e) {
        jQuery.post(
            $url,
            {
                call: 'plugin_judge',
                name: 'judge',
                id: id,
                code: e.target.result
            }
        );
    };
    r.readAsText(document.getElementById("code-" + problem_name).files[0]);
}