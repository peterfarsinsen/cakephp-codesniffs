<?php
class Cake_Sniffs_WhiteSpace_ForceTabIndentSniff extends Generic_Sniffs_WhiteSpace_DisallowTabIndentSniff
{

    /**
     * Processes this test, when one of its tokens is encountered.
	 * 
	 * Check for any line starting with 2 spaces - which would indicate space indenting
	 * Also check for "\t " - a tab followed by a space, which is a common similar mistake
     *
     * @param PHP_CodeSniffer_File $phpcsFile All the tokens found in the document.
     * @param int                  $stackPtr  The position of the current token in
     *                                        the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $line = $tokens[$stackPtr]['line'];
        if ($stackPtr > 0 && $tokens[($stackPtr - 1)]['line'] === $line) {
            return;
        }

        if (preg_match('@^  @', $tokens[$stackPtr]['content'])) {
            $error = 'Space indented: Tabs for indents, spaces for alignment';
            $phpcsFile->addError($error, $stackPtr);
		} elseif (preg_match('@\t [^\*]@', $tokens[$stackPtr]['content'])) {
            $error = 'Tab followed by space - bad indentation';
            $phpcsFile->addWarning($error, $stackPtr);
        }

    }//end process()


}//end class