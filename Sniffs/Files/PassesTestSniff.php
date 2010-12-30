<?php
define('GOT_CAKEPHP', `which cake`);

require_once 'HasTestSniff.php';
class Cake_Sniffs_Files_PassesTestSniff extends Cake_Sniffs_Files_HasTestSniff {

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
		if (!GOT_CAKEPHP) {
			return;
		}

		$path = $phpcsFile->getFileName();
		if (!strpos($path, '.php')) {
			return;
		}

		$hasTest = $this->_mapToTest($path);
		if (!$hasTest) {
			return;
		}

		list($type, $case, $testFile) = $hasTest;

		$app = preg_replace('@^(.*)(?:(?:(?:cake|config|console|controllers|libs|locale|models|plugins|tests|vendors|views|webroot)[\\\/]).*|app_[-a-z]*)$@', '\1', $path);

		$cmd = 'cake testsuite ' . $type . ' ' . $case . ' --app ' . $app;

		$string = "";

		$return = false;
		$this->_exec($cmd, $string);

		$result = array();
		if (preg_match_all('@(Tests|Assertions|Skipped|Failures): (\d+)@', $string, $matches)) {
			foreach ($matches[1] as $i => $type) {
				$result[$type] = $matches[2][$i];
			}
		} elseif (preg_match_all('@(\d+) (tests|assertions|skipped|failures)@', $string, $matches)) {
			foreach ($matches[2] as $i => $type) {
				$type = ucwords($type);
				$result[$type] = $matches[1][$i];
			}
		}

		if (empty($result['Failures']) &&
			empty($result['Exceptions']) &&
			(!empty($result['Assertions']) || !empty ($result['Skipped']))) {
			$return = true;
		}

		if (!$return) {
			foreach($result as $i => &$row) {
				$row = "$i: $row";
			}
			$result = implode($result, ', ');
      		$phpcsFile->addError("Test Results" . $result);
		}
	}

	function _mapToTest($file, $type = null) {
		$type = $this->_testType($file);

		if (preg_match('@tests[\\\/]@', $file)) {
			$case = str_replace('.php', '', $file);
			if (preg_match('@\.test\.php$@', $file)) {
				if ($case = preg_replace('@.*tests[\\\/]cases[\\\/]@', '', $case)) {
					$case = str_replace('.test', '', $case);
					return array($type, $case, $file);
				}
			}
			return false;
		}

		$case = preg_replace('@.*((?:(?:config|console|controllers|libs|locale|models|plugins|tests|vendors|views|webroot)[\\\/])|app_[-a-z]*$)@', '\1', $file);
		$case = str_replace('.php', '', $case);
		$testFile = parent::_mapToTest($file, $type);

		if (!file_exists($testFile)) {
			return false;
		}
		return array($type, $case, $testFile);
	}

/**
 * exec method
 *
 * @param mixed $cmd
 * @param mixed $out null
 * @return void
 * @access protected
 */
	protected function _exec($cmd, &$out = null, $maxExecutionTime = 60) {
		if (DIRECTORY_SEPARATOR === '/') {
			$logFile =  tempnam(sys_get_temp_dir(), 'cakephp_test_sniff_') . '.log';
			$errFile = substr($logFile, 0, -4) . '.err';
			if ($maxExecutionTime) {
				$waitUntil = time() + $maxExecutionTime;
				$pid = pcntl_fork();
				if ($pid == -1) {
					trigger_error('Could not fork');
					return false;
				} elseif ($pid) { // parent
					$finished = false;
					while (time() < $waitUntil) {
						$status = null;
						if (pcntl_waitpid($pid, $status, WNOHANG)) {
							$finished = true;
							break;
						}
						sleep(1);
					}
					if ($finished) {
						$return = pcntl_wexitstatus($status);
					} else {
						exec("kill $pid");
						$out .= "\n---------\nProcess killed after $maxExecutionTime seconds of running";
						$return = 1;
					}
				} else { // child
					exec("$cmd > $logFile 2> $errFile", $_, $return);
					die($return);
				}
			} else {
				exec("$cmd > $logFile 2> $errFile", $_, $return);
			}

			if (file_exists($logFile)) {
				$out = file_get_contents($logFile);
				unlink($logFile);
			}
			if (file_exists($errFile)) {
				$out .= "\nERRORLOG\n" . file_get_contents($errFile);
				unlink($errFile);
			}
		} else {
			exec($cmd, $out, $return);
			$out = implode("\n", $out);
		}

		if ($return) {
			return false;
		}
		return $out?$out:true;
	}
}