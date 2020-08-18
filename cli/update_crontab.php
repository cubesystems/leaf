<?php
require_once(dirname(__FILE__) . '/prepend.cli.php');
$config = leaf_get('properties', 'crontab');
if(!empty($config))
{
    // get current crontab
    exec('crontab -l', $output, $result);
    if($result == 0)
    {
        $beginMark = "# Begin leaf generated tasks for: " . $config["name"];
        $endMark = "# End leaf generated tasks for: " . $config["name"];

        $content = array();
        $ignoreLine = false;

        foreach($output as $line)
        {
            if(!$ignoreLine && $line == $beginMark)
            {
                $ignoreLine = true;
                // remote empty line before beginMark
                $totalLines = sizeof($content);
                if($totalLines && empty($content[$totalLines - 1]))
                {
                    unset($content[$totalLines - 1]);
                }
            }

            if(!$ignoreLine)
            {
                $content[] = $line;
            }

            if($ignoreLine && $line == $endMark)
            {
                $ignoreLine = false;
            }
        }

        if(!empty($config['jobs']))
        {
            if(!empty($content))
            {
                $content[] = "";
            }
            $content[] = $beginMark;
            foreach($config['jobs'] as $job)
            {
                if(!empty($job['description']))
                {
                    $content[] = "# " . $job['description'];
                }

                $line = $job['schedule'] . ' ' . $job['command'];
                if(!empty($job['log']))
                {
                    $line .= ' >> '. $job['log'];
                }

                $content[] = $line;
            }
            $content[] = $endMark;
        }

        $tmpFile = tempnam (CACHE_PATH, 'tmpcron');
        file_put_contents($tmpFile, implode("\n", $content) . "\n");
        exec('crontab ' . $tmpFile);
        unlink($tmpFile);
    }
}
