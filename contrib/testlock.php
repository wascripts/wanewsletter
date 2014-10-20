<?php

$testfile = 'wanewsletter.bat';

if (!file_exists($testfile)) {
	echo "Test file [$testfile] was not found!\n";
	exit;
}

$result = 'FAILED';
$fp = fopen($testfile, 'r');

if (flock($fp, LOCK_EX|LOCK_NB)) {
	$fw = fopen($testfile, 'r');

	if (!flock($fw, LOCK_EX|LOCK_NB)) {
		$result = 'SUCCESS';
	}
	fclose($fw);
}

flock($fp, LOCK_UN);
fclose($fp);

echo "Result: $result\n";
exit;

