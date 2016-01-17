<?php
    if (class_exists('PHP_CodeSniffer_Standards_AbstractScopeSniff', true) === false) {
        throw new PHP_CodeSniffer_Exception('Class PHP_CodeSniffer_Standards_AbstractScopeSniff not found');
    }

    class Conpago_Sniffs_NamingConventions_ValidFunctionNameSniff extends PHP_CodeSniffer_Standards_AbstractScopeSniff
    {

        /**
         * A list of all PHP magic methods.
         *
         * @var array
         */
        protected $magicMethods = array(
            'construct'  => true,
            'destruct'   => true,
            'call'       => true,
            'callstatic' => true,
            'get'        => true,
            'set'        => true,
            'isset'      => true,
            'unset'      => true,
            'sleep'      => true,
            'wakeup'     => true,
            'tostring'   => true,
            'set_state'  => true,
            'clone'      => true,
            'invoke'     => true,
        );

        /**
         * A list of all PHP magic functions.
         *
         * @var array
         */
        protected $magicFunctions = array('autoload' => true);


        /**
         * Constructs a PEAR_Sniffs_NamingConventions_ValidFunctionNameSniff.
         */
        public function __construct()
        {
            parent::__construct(array(T_CLASS, T_INTERFACE, T_TRAIT), array(T_FUNCTION), true);

        }//end __construct()


        /**
         * Processes the tokens within the scope.
         *
         * @param PHP_CodeSniffer_File $phpcsFile The file being processed.
         * @param int                  $stackPtr  The position where this token was
         *                                        found.
         * @param int                  $currScope The position of the current scope.
         *
         * @return void
         */
        protected function processTokenWithinScope(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $currScope)
        {
            $methodName = $phpcsFile->getDeclarationName($stackPtr);
            if ($methodName === null) {
                // Ignore closures.
                return;
            }

            $className = $phpcsFile->getDeclarationName($currScope);
            $errorData = array($className.'::'.$methodName);

            // Is this a magic method. i.e., is prefixed with "__" ?
            if (preg_match('|^__|', $methodName) !== 0) {
                $magicPart = strtolower(substr($methodName, 2));
                if (isset($this->magicMethods[$magicPart]) === false) {
                    $error = 'Method name "%s" is invalid; only PHP magic methods should be prefixed with a double underscore';
                    $phpcsFile->addError($error, $stackPtr, 'MethodDoubleUnderscore', $errorData);
                }

                return;
            }

            // PHP4 constructors are allowed to break our rules.
            if ($methodName === $className) {
                return;
            }

            // PHP4 destructors are allowed to break our rules.
            if ($methodName === '_'.$className) {
                return;
            }

            $methodProps    = $phpcsFile->getMethodProperties($stackPtr);
            $scope          = $methodProps['scope'];
            $scopeSpecified = $methodProps['scope_specified'];

            if ($methodProps['scope'] === 'private') {
                $isPublic = false;
            } else {
                $isPublic = true;
            }

            // If it's not a private method, it must not have an underscore on the front.
            if ($isPublic === true && $scopeSpecified === true && $methodName{0} === '_') {
                $error = '%s method name "%s" must not be prefixed with an underscore';
                $data  = array(
                    ucfirst($scope),
                    $errorData[0],
                );
                $phpcsFile->addError($error, $stackPtr, 'PublicUnderscore', $data);
                return;
            }

            // If the scope was specified on the method, then the method must be
            // camel caps and an underscore should be checked for. If it wasn't
            // specified, treat it like a public method and remove the underscore
            // prefix if there is one because we cant determine if it is private or
            // public.
            $testMethodName = $methodName;
            if ($scopeSpecified === false && $methodName{0} === '_') {
                $testMethodName = substr($methodName, 1);
            }

            if (PHP_CodeSniffer::isCamelCaps($testMethodName, false, true, false) === false) {
                if ($scopeSpecified === true) {
                    $error = '%s method name "%s" is not in camel caps format';
                    $data  = array(
                        ucfirst($scope),
                        $errorData[0],
                    );
                    $phpcsFile->addError($error, $stackPtr, 'ScopeNotCamelCaps', $data);
                } else {
                    $error = 'Method name "%s" is not in camel caps format';
                    $phpcsFile->addError($error, $stackPtr, 'NotCamelCaps', $errorData);
                }

                return;
            }

        }//end processTokenWithinScope()


        /**
         * Processes the tokens outside the scope.
         *
         * @param PHP_CodeSniffer_File $phpcsFile The file being processed.
         * @param int                  $stackPtr  The position where this token was
         *                                        found.
         *
         * @return void
         */
        protected function processTokenOutsideScope(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
        {
            $functionName = $phpcsFile->getDeclarationName($stackPtr);
            if ($functionName === null) {
                return;
            }

            $errorData = array($functionName);

            // Does this function claim to be magical?
            if (preg_match('|^__|', $functionName) !== 0) {
                $error = 'Function name "%s" is invalid; only PHP magic methods should be prefixed with a double underscore';
                $phpcsFile->addError($error, $stackPtr, 'DoubleUnderscore', $errorData);
                return;
            }

            if (PHP_CodeSniffer::isCamelCaps($functionName, false, true, false) === false) {
                $error = 'Function name "%s" is not in camel caps format';
                $phpcsFile->addError($error, $stackPtr, 'NotCamelCaps', $errorData);
            }

        }


    }//end class
