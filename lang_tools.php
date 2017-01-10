<?php

switch ($argc){
	case 2:
		$todo = $argv[1];
		if ($todo === '-s' || $todo === '--source'){
			echo "###################################################################" . PHP_EOL;
			echo "Creating source file" . PHP_EOL;
			
			createSourceArrayFile();
			
			echo "Done. File is located at " . __DIR__ . "/lang_str.php" . PHP_EOL;
			echo "###################################################################" . PHP_EOL;
		} else {
			help();
		}
		break;
	case 3:
		$todo = $argv[1];
		$lang = $argv[2];

		if ($todo === '-c' || $todo === '--check'){
			echo "###################################################################" . PHP_EOL;
			
            
            if ($lang === 'all'){
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator(__DIR__ . '/app/source/languages', \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($files as $file) {
                    $l = pathinfo($file)['filename'];
                    echo "Checking $l against source file" . PHP_EOL;
			        checkLanguageAgainstEn($l);
                    echo "Done. File is located at " . __DIR__ . "/" . $l . "_str.php" . PHP_EOL;
                }
            } else {
                echo "Checking $lang against source file" . PHP_EOL;
			    checkLanguageAgainstEn($lang);
                echo "Done. File is located at " . __DIR__ . "/" . $lang . "_str.php" . PHP_EOL;
            }
			echo "###################################################################" . PHP_EOL;
		} else {
			help();
		}
		break;
	default:
		help();
		break;
}

function createSourceArrayFile(){
	$lang = require __DIR__ . '/app/source/languages/en-us.php';

	file_put_contents(
		'lang_str.php',
		createReturnArrayString(array_keys($lang))
	);
}

function checkLanguageAgainstEn($lang){
	$source = require __DIR__ . '/app/source/languages/en-us.php';
	if (is_file(__DIR__ . "/app/source/languages/$lang.php")){
		$other  = require __DIR__ . "/app/source/languages/$lang.php";
	} else {
		echo $lang . " wasn't found at " . __DIR__ . "/app/source/languages/$lang.php" . PHP_EOL;
		return;
	}

	file_put_contents(
		$lang."_str.php", 
		createReturnArrayString(
			array_diff(
				array_keys($source),
				array_keys($other)
			)
		)
	);
}

function createReturnArrayString($array){
	return 	'<?php' . chr(13) . 'return [' . chr(13) . implode(
		        ', ' . chr(13), 
		        array_map(
		            function($val){
		                return "'" . $val . "'";
		            },
		            $array
		        )
	    	) . chr(13) . '];';
}

function help(){
	echo '-h, --help, --h, -?: shows this help' . PHP_EOL;
	echo '-s, --source: creates a lang_str.php with all language strings' . PHP_EOL;
	echo '-c language, --check  language: checks language against the english strings' . PHP_EOL;
    echo '-c all, --check all: checks all languages against the english strings' . PHP_EOL;
}
