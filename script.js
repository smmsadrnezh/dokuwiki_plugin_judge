function inputFileKey(problem_name) {
    $realInputField = document.getElementById("code-" + problem_name);
    $realInputField.click();
}
function changeFilePath(problem_name) {
    $realInputField = document.getElementById("code-" + problem_name);
    document.getElementById("code-file-" + problem_name).value = $realInputField.files[0].name;
}