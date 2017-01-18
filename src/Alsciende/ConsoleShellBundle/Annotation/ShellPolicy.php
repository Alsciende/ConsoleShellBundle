<?php

namespace Alsciende\ConsoleShellBundle\Annotation;

/**
 * Annotation class for @ShellPolicy().
 *
 * @Annotation
 * @Target("CLASS")
 * 
 * @author Cedric Bertolini <bertolini.cedric@me.com>
 */
class ShellPolicy
{
    const NONE = 'none';
    const PUBLIC_METHODS = 'public';
    
    private $value;

    /**
     * Constructor.
     *
     * @param array $data An array of key/value parameters
     *
     */
    public function __construct (array $data)
    {
        if(!in_array($data['value'], [self::NONE, self::PUBLIC_METHODS])) {
            throw new \LogicException("Value [".$data['value']."] not allowed in annotation @ShellPolicy");
        }
        $this->value = $data['value'];
    }

    function getValue ()
    {
        return $this->value;
    }

}
