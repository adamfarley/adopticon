<?php 
    include 'big_list_of_failure_regexs.php';
    echo '
        <html>
            <head>
                <style>
                    table, th, td {
                      border: none;
                    }
                    p {
                        margin: 0;
                        padding: 0;
                    }
                </style>
            </head>
            <body>
                <h2>Adopticon: Nightly Failures Summary</h2><br />
                <b>Failed Nightly Build Jobs</b><br /><br />
    ';
    $exception_regex = "/ (junit|javax?|com)\.([a-z]*\.)*[a-zA-Z]*(Exception|Error)(:|;)?(?!(((a-zA-Z)| failures: [0-9])| Some tests failed))/";
    
    $nightly_jdks = "/jdk(8|11|15|)(\-|\_)/";
    //First we identify the list of failing build jobs.
    $failing_build_jobs = array();
    foreach(preg_split("/<tr id=\"job_/", file_get_contents("https://ci.adoptopenjdk.net/view/Failing%20Builds/")) as $element){
    //If element is a failing job, store the job name if that job is a build job.
        if(preg_match("/^jdk\S* class=\" job-status-red\">.*/", $element) === 1){ 
            //But only if it's a jdk version being run as a nightly.
            if(preg_match($nightly_jdks, $element) === 1){
                array_push($failing_build_jobs,explode("\"", $element)[0]);
            }
        }
    }
    //Now inspect each job 
    $failing_build_urls_with_fail_lines = array();
    $failing_build_urls_without_fail_lines = array();
    foreach($failing_build_jobs as $job_name){
        $job_url = 'https://ci.adoptopenjdk.net/job/build-scripts/job/jobs/job/' .
                   explode("-",$job_name)[0] .
                   '/job/' . 
                   $job_name .
                   '/lastBuild';
        $fail_line_found_bool = FALSE;
        $fail_line_is_exception_bool = FALSE;
        $fail_line_array = array("", "", "");
        foreach(preg_split("/\n/", file_get_contents($job_url . '/consoleText')) as $line){
            if($fail_line_found_bool === FALSE){
                $fail_line_array[0] = $fail_line_array[1];
                $fail_line_array[1] = $fail_line_array[2];
                $fail_line_array[2] = $line;
                foreach($job_failure_regexs as $failure_regex){
                    if(preg_match($failure_regex, $line) === 1) {
                        if(preg_match($exception_regex, $line) === 1) {
                            $fail_line_is_exception_bool = TRUE;
                        }
                        $fail_line_found_bool = TRUE;
                        break;
                    }
                }
            } else {
                if(sizeof($fail_line_array) > 4){
                    if($fail_line_is_exception_bool === TRUE) {
                        if(preg_match("/\[[0-9A-Z\:\.\-]*\]\s*Caused by: /", $line) === 1){
                            array_push($fail_line_array, "...");
                            array_push($fail_line_array, $line);
                        } elseif(preg_match("/\[[0-9A-Z\:\.\-]*\]\s*(at |\.\.\.)/", $line) === 1){
                            continue;
                        } else {
                            break;
                        }
                    } else {
                        break;
                    }
                } else {
                    array_push($fail_line_array, $line);
                }
            }
        }
        if($fail_line_found_bool === FALSE){
            array_push($failing_build_urls_without_fail_lines, array($job_name, $job_url));
        } else {
            array_push($failing_build_urls_with_fail_lines, array($job_name, $job_url, $fail_line_array));
        }
    }
    //Now print out the results.
    foreach($failing_build_urls_with_fail_lines as $job_data){
        echo '<a href="' . $job_data[1] . '">' . $job_data[0] . '</a><br />';
        echo '<table cellspacing="0" cellpadding="0">';
        $job_data_index = 0;
        foreach($job_data[2] as $array_of_fail_lines){
            $color = "darkgrey";
            if($job_data_index == 2) {
                $color = "cornflowerblue";
            }
            echo '<tr> <td style="background-color:' . $color . ';">' . $job_data[2][$job_data_index] . '</td></tr>';
            $job_data_index = $job_data_index + 1;
        }
        echo '</table><br />';
    }
    foreach($failing_build_urls_without_fail_lines as $job_data){
        echo '<a href="' . $job_data[1] . '">' . $job_data[0] . '</a><br />';
        echo '<p style="background-color:hotpink;">Unable to identify failure line.</p>';
        echo '<br />';
    }
        
    echo '<b>Failed Nightly Test Jobs (WIP)</b><br /><br />';
    // Now we identify the list of failing tests
    $test_job_sources = array("https://ci.adoptopenjdk.net/view/Test_openjdk/",
                              "https://ci.adoptopenjdk.net/view/Test_openjdk_special/",
                              "https://ci.adoptopenjdk.net/view/Test_perf/",
                              "https://ci.adoptopenjdk.net/view/Test_system/",
                              "https://ci.adoptopenjdk.net/view/Test_functional/",
                              "https://ci.adoptopenjdk.net/view/Test_external/");
    $failing_or_unstable_test_jobs = array();
    foreach($test_job_sources as $test_job_source) {
        foreach(preg_split("/<tr id=\"job_/", file_get_contents($test_job_source)) as $element){
            //If element is a failing job, store the job name if that job is a build job.
            if(preg_match("/^Test_openjdk\S* class=\" job-status-(red|yellow)\">.*/", $element) === 1){ 
                //But only if it's a jdk version being run as a nightly.
                if(preg_match($nightly_jdks, $element) === 1){
                    array_push($failing_or_unstable_test_jobs,explode("\"", $element)[0]);
                }
            }
        }
    }
    //Now inspect each job 
    $failing_test_urls_with_fail_lines = array();
    $failing_test_urls_without_fail_lines = array();
    foreach($failing_or_unstable_test_jobs as $job_name){
        $job_url = 'https://ci.adoptopenjdk.net/job/' .
                   $job_name .
                   '/lastBuild';
        $fail_line_found_in_job_bool = FALSE;
        $fail_line_found_in_test_bool = FALSE;
        $fail_line_is_exception_bool = FALSE;
        $fail_line_array = array("", "", "");
        $maybe_start_of_new_primary_test_bool = FALSE;
        $primary_test = "";
        $maybe_start_of_new_secondary_test_bool = FALSE;
        $secondary_test = "";
        $skip_to_next_test = FALSE;
        $full_primary_test_output = "";
        $full_secondary_test_output = "";
        foreach(preg_split("/\n/", file_get_contents($job_url . '/consoleText')) as $line){
            //First we verify whether it's a new test.
            if($maybe_start_of_new_primary_test_bool === TRUE){
                if(preg_match("/\[[0-9A-Z\:\.\-]*\] Running test /", $line) === 1){
                    //Since we're going to start reviewing the output from a new test,
                    //first we need to see if the old test actually passed.
                    if(empty($primary_test) === FALSE){
                        if((preg_match('/' . preg_quote($primary_test) . '(_SKIPPED|_PASSED|_DISABLED)/', $full_primary_test_output) === 0) && ($skip_to_next_test === FALSE)){
                            array_push($failing_test_urls_without_fail_lines, array($job_name, $job_url, $primary_test, ""));
                            //Technically untrue, but close enough.
                            $fail_line_found_in_job_bool = TRUE;
                        }
                    }
                    $primary_test = explode(" ", $line)[3];
                    $secondary_test = "";
                    $full_primary_test_output = "";
                    $full_secondary_test_output = "";
                    $skip_to_next_test = FALSE;
                }
                $maybe_start_of_new_primary_test_bool = FALSE;
            }elseif($maybe_start_of_new_secondary_test_bool === TRUE){
                if(preg_match("/\[[0-9A-Z\:\.\-]*\] TEST: /", $line) === 1){
                    //Since we're going to start reviewing the output from a new test,
                    //first we need to see if the old test actually passed.
                    if(empty($secondary_test) === FALSE){
                        if((strpos($full_secondary_test_output,'TEST RESULT: Passed') === 0) && ($skip_to_next_test === FALSE)){
                            array_push($failing_test_urls_without_fail_lines, array($job_name, $job_url, $primary_test, $secondary_test));
                            //Technically untrue, but close enough.
                            $fail_line_found_in_job_bool = TRUE;
                        }
                    }
                    $secondary_test = explode(" ", $line)[2];
                    $full_secondary_test_output = "";
                    $skip_to_next_test = FALSE;
                }
                $maybe_start_of_new_secondary_test_bool = FALSE;
            }
            
            //Storing test output so we can check if it passed later on.
            $full_primary_test_output = $full_primary_test_output . $line;
            $full_secondary_test_output = $full_secondary_test_output . $line;
            
            //Then we check to see if it could be verified as a new test in the next line.
            //Note: we do this after verification to avoid tripping ourselves up.
            if(strpos($line, "===============================================") !== FALSE){
                $maybe_start_of_new_primary_test_bool = TRUE;
            }elseif(strpos($line,"--------------------------------------------------") !== FALSE){
                $maybe_start_of_new_secondary_test_bool = TRUE;
            }elseif(strpos($line,"++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++") !== FALSE){
                //While we're looking for dividers, we should should recognise when the last primary test ends.
                if(empty($primary_test) === FALSE){
                    if((preg_match('/' . preg_quote($primary_test) . '(_SKIPPED|_PASSED|_DISABLED)/', $full_primary_test_output) === 0) && ($skip_to_next_test === FALSE)){
                        array_push($failing_test_urls_without_fail_lines, array($job_name, $job_url, $primary_test, ""));
                        //Technically untrue, but close enough.
                        $fail_line_found_in_job_bool = TRUE;
                    }
                }
                $primary_test = "";
                $secondary_test = "";
                $skip_to_next_test = FALSE;
            }
                
            //We skip to the next test if we've already found a test failure for the test we're checking.
            if($skip_to_next_test === TRUE){
                continue;
            }
            //And now we scan test output for failure lines.
            if($fail_line_found_in_test_bool === FALSE){
                $fail_line_array[0] = $fail_line_array[1];
                $fail_line_array[1] = $fail_line_array[2];
                $fail_line_array[2] = $line;
                foreach($job_failure_regexs as $failure_regex){
                    if(preg_match($failure_regex, $line) === 1) {
                        if(preg_match($exception_regex, $line) === 1) {
                            $fail_line_is_exception_bool = TRUE;
                        }
                        $fail_line_found_in_job_bool = TRUE;
                        $fail_line_found_in_test_bool = TRUE;
                        break;
                    }
                }
            } else {
                if(sizeof($fail_line_array) > 4){
                    if($fail_line_is_exception_bool === TRUE) {
                        if(preg_match("/\[[0-9A-Z\:\.\-]*\]\s*Caused by: /", $line) === 1){
                            array_push($fail_line_array, "...");
                            array_push($fail_line_array, $line);
                        } elseif(preg_match("/\[[0-9A-Z\:\.\-]*\]\s*(at |\.\.\.)/", $line) === 1){
                            continue;
                        } else {
                            //We've finished documenting this failure. On to the next.
                            array_push($failing_test_urls_with_fail_lines, array($job_name, $job_url, $primary_test, $secondary_test, $fail_line_array));
                            $fail_line_found_in_test_bool = FALSE;
                            $fail_line_is_exception_bool = FALSE;
                            $skip_to_next_test = TRUE;
                            $fail_line_array = array("", "", "");
                            continue;
                        }
                    } else {
                        array_push($failing_test_urls_with_fail_lines, array($job_name, $job_url, $primary_test, $secondary_test, $fail_line_array));
                        $fail_line_is_exception_bool = FALSE;
                        $fail_line_found_in_test_bool = FALSE;
                        $skip_to_next_test = TRUE;
                        $fail_line_array = array("", "", "");
                        continue;
                    }
                } else {
                    array_push($fail_line_array, $line);
                }
            }
        }
        if($fail_line_found_in_job_bool === FALSE){
            array_push($failing_test_urls_without_fail_lines, array($job_name, $job_url, "", ""));
        } elseif($fail_line_found_in_test_bool === TRUE) {
            array_push($failing_test_urls_with_fail_lines, array($job_name, $job_url, $primary_test, $secondary_test, $fail_line_array));
        }
    }
    //Now print out the results.
    foreach($failing_test_urls_with_fail_lines as $job_data){
        echo '<a href="' . $job_data[1] . '">' . $job_data[0] . '</a><br />';
        if(empty($job_data[2]) === FALSE){
            if(empty($job_data[3]) === FALSE){
                echo '<p>' . $job_data[2] . ': ' . $job_data[3] . '</p>';
            } else {
                echo '<p>' . $job_data[2] . '</p>';
            }
        }
        
        echo '<table cellspacing="0" cellpadding="0">';
        $job_data_index = 0;
        foreach($job_data[4] as $array_of_fail_lines){
            $color = "darkgrey";
            if($job_data_index == 2) {
                $color = "cornflowerblue";
            }
            echo '<tr> <td style="background-color:' . $color . ';">' . $job_data[4][$job_data_index] . '</td></tr>';
            $job_data_index = $job_data_index + 1;
        }
        echo '</table><br />';
    }
    foreach($failing_test_urls_without_fail_lines as $job_data){
        echo '<a href="' . $job_data[1] . '">' . $job_data[0] . '</a><br />';
        if(empty($job_data[2]) === FALSE){
            if(empty($job_data[3]) === FALSE){
                echo '<p>' . $job_data[2] . ': ' . $job_data[3] . '</p>';
            } else {
                echo '<p>' . $job_data[2] . '</p>';
            }
        }
        echo '<p style="background-color:hotpink;">Unable to identify failure line.</p>';
        echo '<br />';
    }
    echo '
            </body>
        </html>
    '; 
?>