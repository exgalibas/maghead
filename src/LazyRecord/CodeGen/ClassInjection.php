<?php
namespace LazyRecord\CodeGen;
use ReflectionClass;

class ClassInjection
{
    public $lines = array();

    /**
     * contents for injection
     */
    public $contents = array();

    public $contentLength;

    public $boundary;

    public $boundaryStartLine;

    public $boundaryEndLine;

    public $targetClass;

    public $filename;

    public function __construct($class)
    {
        $this->targetClass = $class;
        $this->reflection = new ReflectionClass($class);
        $this->filename = $this->reflection->getFilename();
    }


    public function replaceContent($content)
    {
        $this->contents = array($content);
    }


    public function appendContent($content) 
    {
        $this->contents[] = $content;
    }

    public function getBoundary() 
    {
        if($this->boundary)
            return $this->boundary;
        $content = join("\n",$this->contents);
        $this->boundary = md5($content);
        return $this->boundary;
    }

    public function read() 
    {
        $filename = $this->reflection->getFilename();

        $this->lines = explode("\n",file_get_contents($filename));
        $this->contentLength = 0;
        $inBoundary = false;
        for ( $i = 0; $i < count($this->lines); $i++ ) {
            $line = $this->lines[$i];
            // parse for start boundary
            if( preg_match('/^#boundary start (\w+)/',$line,$regs) ) {
                $inBoundary = true;
                $this->boundary = $regs[1];
                $this->boundaryStartLine = $i + 1;
            }
            elseif( preg_match('/^#boundary end (\w+)/',$line,$regs) ) {
                $inBoundary = false;
                $this->boundaryEndLine = $i + 1;
            }
            if( $inBoundary ) {
                $this->contentLength++;
                $this->contents[] = $line;
            }
        }
    }


    public function buildContent()
    {
        $contents = $this->contents;
        array_unshift( $contents, '#boundary start ' . $this->getBoundary() );
        array_push(    $contents, '#boundary end ' . $this->getBoundary() );
        return $contents;
    }

    public function write() {
        if($this->boundaryStartLine && $this->boundaryEndLine ) {
            array_splice($this->lines, $this->boundaryStartLine - 1, $this->contentLength + 1, $this->buildContent() );
            file_put_contents($this->filename, join("\n",$this->lines) . "\n");
        }
        else {
            $endline = $this->reflection->getEndLine();
            array_splice($this->lines,$endline - 1,0, $this->buildContent() );
            file_put_contents( $this->filename, join("\n",$this->lines) . "\n");
        }

        // re-read content to update boundary information
        $this->read();
    }

    public function __toString()
    {
        return join("\n",$this->lines);
    }


}



