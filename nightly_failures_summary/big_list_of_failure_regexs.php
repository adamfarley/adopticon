<?php
// Note: Try to keep patterns with specific issues at the start of the array.
    $job_failure_regexs = array("/ (junit|javax?|com)\.([a-z]*\.)*[a-zA-Z]*(Exception|Error)(:|;)?(?!(((a-zA-Z)| failures: [0-9])| Some tests failed))/",
                                "/ (E|e)rror:/",
                                "/ Exception in thread/",
                                "/Segmentation error/",
                                "/ fatal:/",
                                "/ Failed . times to query or parse the adopt api\./",
                                "/ timed out /",
                                "/  Cannot load module  /",
                                "/\] Error ",
                                "/unable to initialize policy plugin/",
                                "/(P|p)ermission (D|d)enied/",
                                "/java: No such file or directory/",
                                "/There must be at least .* free to be sure of capturing diagnostics/",
                                "/\*\*FAILED\*\* Process [a-zA-Z0-9]* has timed out/")
?>