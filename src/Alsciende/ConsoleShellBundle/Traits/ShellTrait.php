<?php

namespace Alsciende\ConsoleShellBundle\Traits;

use Alsciende\ConsoleShellBundle\Annotation\ShellPolicy;
use Alsciende\ConsoleShellBundle\Exception\DuplicateCommandException;
use Alsciende\ConsoleShellBundle\Exception\UnavailableCommandException;
use Doctrine\Common\Annotations\AnnotationReader;
use LogicException;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Add a Command-Line Interpreter to your Symfony Command
 *
 * @author Cedric Bertolini <bertolini.cedric@me.com>
 */
trait ShellTrait
{
    /**
     * @var string
     */
    private $_context;
    
    /**
     * @var array
     */
    private $_commands;
    
    /**
     * @var string
     */
    private $_prompt;
    
    /**
     * @var string
     */
    private $_shellPolicy;
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws UnavailableCommandException
     */
    protected function shellLaunch(InputInterface $input, OutputInterface $output, $startingContext = "")
    {
        $this->_context = $startingContext;
        $this->_commands = [];
        $this->_prompt = "> ";
        $this->_shellPolicy = ShellPolicy::NONE;
        
        $this->_parseAnnotations(get_class($this));
        
        while(TRUE) {
            $availableCommands = $this->_getAvailableCommands();
            $args = $this->_getUserInput($input, $output, $availableCommands);
            $command = array_shift($args);
            if(isset($availableCommands[$command])) {
                $this->_executeUserCommand($availableCommands[$command]['method'], $args);
            } else {
                $output->writeln("<error>Command \"$command\" not available in context \"".$this->_context."\"</error>");
            }
        }
    }
    
    /**
     * @param string $class
     */
    private function _parseAnnotations($class)
    {
        $reflectionClass = new ReflectionClass($class);
        $this->_parseClassAnnotations($reflectionClass);
        $this->_parseMethodsAnnotations($reflectionClass);
        
    }
    
    /**
     * @param ReflectionClass $reflectionClass
     */
    private function _parseClassAnnotations(ReflectionClass $reflectionClass)
    {
        $reader = new AnnotationReader();
        
        /* @var $policyAnnotation ShellPolicy */
        $policyAnnotation = $reader->getClassAnnotation($reflectionClass, 'Alsciende\\ConsoleShellBundle\\Annotation\\ShellPolicy');
        if ($policyAnnotation) {
            $this->_shellPolicy = $policyAnnotation->getValue();
        }
    }
    
    /**
     * @param ReflectionClass $reflectionClass
     */
    private function _parseMethodsAnnotations(ReflectionClass $reflectionClass) {
        
        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            /* @var $reflectionMethod ReflectionMethod */
            $hasAnnotations = $this->_parseMethodAnnotations($reflectionMethod);
            if(!$hasAnnotations and $this->_shellPolicy === ShellPolicy::PUBLIC_METHODS and $reflectionMethod->isPublic()) {
                $this->_commands[""][$reflectionMethod->getName()] = [
                    'method' => $reflectionMethod->getName()
                ];
            }
        }
    }
    
    /**
     * @param ReflectionMethod $reflectionMethod
     * @return boolean
     * @throw DuplicateCommandException
     */
    private function _parseMethodAnnotations($reflectionMethod) {
        $command = $this->_getMethodAnnotationValue($reflectionMethod, 'Command');
        if ($command === null) {
            return FALSE;
        }
        
        $context = $this->_getMethodAnnotationValue($reflectionMethod, 'Context');
        if ($context === null) {
            $context = '';
        }
        
        $help = $this->_getMethodAnnotationValue($reflectionMethod, 'Help');
        
        if(!isset($this->_commands[$context])) {
            $this->_commands[$context] = [];
        }
        
        if(isset($this->_commands[$context][$command])) {
            throw new DuplicateCommandException("Duplicate command \"$command\" in context \"$context\"");
        }
        
        $this->_commands[$context][$command] = [
            "method" => $reflectionMethod->getName(),
            "help" => $help
        ];
        return TRUE;
    }
    
    private function _getMethodAnnotationValue($reflectionMethod, $annotationName) {
        $reader = new AnnotationReader();
        
        $annotation = $reader->getMethodAnnotation($reflectionMethod, 'Alsciende\\ConsoleShellBundle\\Annotation\\'.$annotationName);
        if(!$annotation) {
            return null;
        }
        
        return $annotation->getValue();
    }            
    
    /**
     * @return array
     */
    private function _getAvailableCommands()
    {
        $availableCommands = [];
        foreach(["", $this->_context] as $context) {
            if(!isset($this->_commands[$context])) {
                continue;
            }
            foreach($this->_commands[$context] as $command => $method) {
                $availableCommands[$command] = $method;
            }
        }
        return $availableCommands;
    }
    
    /**
     * @return array
     */
    private function _getUserInput(InputInterface $input, OutputInterface $output, $availableCommands)
    {
        $helper = new QuestionHelper();
        $question = new Question($this->_prompt);
        $question->setAutocompleterValues(array_keys($availableCommands));
        $answer = $helper->ask($input, $output, $question);
        return explode(' ', $answer);
    }
    
    /**
     * 
     * @param string $methodName
     * @param array $args
     * @return mixed
     */
    private function _executeUserCommand($methodName, $args = [])
    {
        return call_user_func_array([$this, $methodName], $args);
    }

    /**
     * @param OutputInterface $output
     * @param string $command
     */
    protected function shellHelp($output, $command = null)
    {
        $output->writeln("<comment>Help</comment>");
        foreach($this->_getAvailableCommands() as $cmd => $prop) {
            if($command !== null and $command !== $cmd) {
                continue;
            }
            $output->writeln(sprintf("  %-12s  <info>%s</info>", $cmd, $prop['help'] ?: "Help not available"));
        }
    }
    
    protected function shellContext($context)
    {
        $this->_context = $context;
    }
            
}
