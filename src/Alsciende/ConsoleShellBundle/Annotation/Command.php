<?php

namespace Alsciende\ConsoleShellBundle\Annotation;

/**
 * Annotation class for @Command().
 *
 * @Annotation
 * @Target("METHOD")
 * 
 * @author Cedric Bertolini <bertolini.cedric@me.com>
 */
class Command
{

    private $value;

    /**
     * Constructor.
     *
     * @param array $data An array of key/value parameters
     *
     */
    public function __construct (array $data)
    {
        $this->value = $data['value'];
    }

    function getValue ()
    {
        return $this->value;
    }

}
