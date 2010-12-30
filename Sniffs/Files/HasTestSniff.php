<?php
class Cake_Sniffs_Files_HasTestSniff implements PHP_CodeSniffer_Sniff {

/**
 * Returns an array of tokens this test wants to listen for.
 *
 * @return array
 */
	public function register() {
		return array(
			T_CLASS,
			T_INTERFACE,
		);
	}

/**
 * Processes this test, when one of its tokens is encountered.
 *
 * @param PHP_CodeSniffer_File $phpcsFile The current file being processed.
 * @param int                  $stackPtr  The position of the current token
 *                                        in the stack passed in $tokens.
 *
 * @return void
 */
	public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
		$path = $phpcsFile->getFileName();
		if (strpos($path, '.test.php') || !strpos($path, '.php')) {
			return;
		}

		$testFile = $this->_mapToTest($path);
		if (!$testFile) {
			return;
		}

		if (!file_exists($testFile)) {
      		$phpcsFile->addWarning("No test case found", $stackPtr);
		}
	}

/**
 * mapToTest method
 *
 * @param string $path
 * @param string $type
 * @return test file
 * @access protected
 */
	protected function _mapToTest($path, $type = null) {
		if (!$type) {
			$type = $this->_testType($path);
		}

		$path = str_replace('.php', '', $path);
		if ($type === 'core') {
			return preg_replace('@(.*cake[\\\/])@', '\1tests/cases/', $path) . '.test.php';
		}

		return preg_replace('@(.*)((?:(?:config|console|controllers|libs|locale|models|plugins|tests|vendors|views|webroot)[\\\/])|app_[-a-z]*$)@', '\1tests/cases/\2', $path) . '.test.php';
	}

/**
 * testType method
 *
 * @param mixed $file
 * @return void
 * @access protected
 */
	protected function _testType($file) {
		$_file = realpath($file);
		if ($_file) {
			$file = $_file;
		}
		if (preg_match('@cake[\\\/]@', $file, $match)) {
			return 'core';
		} elseif (preg_match('@plugins[\\\/]([^\\/]*)@', $file, $match)) {
			return $match[1];
		}
		return 'app';
	}
}