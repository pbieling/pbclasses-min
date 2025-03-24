<?php declare(strict_types=1);

/**
 * PbTpl - simple but effektive template engine
 *
 * @version   3.3
 * @link      http://www.media-palette.de/tools/pb-tpl/ (old version!)
 * @copyright Copyright (c) 2004 - 2016 Peter Bieling, info@media-palette.de
 * @license   MIT
 * 
 */

namespace PbClasses;

class PbTpl {

    /**
     * Usually empty lines in the template are ignored. To save a line set ! at the beginning.
     */
    const EMPTY_ROW = '!';

    /**
     * Holds all templates in an associative array. The key is the name of the template. The value the template string.
     * 
     * @var array<string,string>
     */
    protected $tplArr;

    /**
     * @see setConvert()
     * @var boolean 
     */
    protected $convert = true;

    /**
     * 
     * @param string $template  The path to a template file or a string (that could be the content of a template file)
     * @param boolean $file  if you pass a string set to false  
     * @param string $addIndex  If you pass a string or if the file only contains
     *                        one single template (or for any reason you don't want 
     *                        to declare the first index) you can add an index 
     *                        on the fly with the third parameter $addIndex (for instance "main").
     * @throws \Exception
     */
    public function __construct(string $template, bool $file = true, string $addIndex = "") {
        if ($file === true && !\file_exists($template)) {
            throw new \Exception('Missing template file ' . $template);
        }

        if ($file === false && !$template) {
            throw new \Exception('Missing template code');
        }

        $this->tplArr = array();
        //@todo: \r ?
        //  $lineArr = ($file) ? \file($template) : preg_split('/(?<=\n)/', $template);
        if ($file) {
            $lineArr = \file($template);
        } else {
            $lineArr = preg_split('/(?<=\n)/', $template);
        }
        if (!is_array($lineArr)) {
            throw new \Exception('Problem on reading template file');
        }


        // if (!\is_string($addIndex)) {
        //     throw new \Exception('3rd parameter must be a string.');
        // }
        $currIndex = $addIndex;
        if ($currIndex !== '') {
            $this->tplArr[$currIndex] = "";
        }
        foreach ($lineArr as $line) {
            $this->buildTplArr($line, $currIndex);
        }
    }

    /**
     * Whether or not tho convert the placeholder from
     * placeholdername to {PLACEHOLDERNAME}. (Uppercase in curly braces)
     * 
     * @param boolean $convert
     * @throws \Exception
     * 
     * @return void
     */
    public function setConvert(bool $convert = true) {
        $this->convert = $convert;
    }

    /**
     * @see   setConvert();
     * @param string $placeholder
     * 
     * @return string
     */
    protected function convertPlaceholder(string $placeholder) {
        return '{' . \strtoupper($placeholder) . '}';
    }

    /**
     * The method ist looking for template names in square brackets, e.g.
     * [main] and sets ist aks key. The following rows are taken as value,
     * except comment lines and empty lines that are not marked with !.
     *      
     * @param string $line The current line of the template file.
     * @param string & $currIndex
     * 
     * @return void
     */
    protected function buildTplArr(string $line, string & $currIndex) {
        $templine = \trim($line);
        if ($templine === '') {
            return;
        }
        $first = \substr($templine, 0, 1);
        if ($first === '\\' && $currIndex) {
            // $pos = \mb_strpos($line, $first);
            $pos_ = \strpos($line, $first);
            if ($pos_ === false) {
                throw new \Exception('strpos-Fehler');
            }
            $pos = $pos_;
            $replacedLine = \substr_replace($line, '', $pos, 1);
            $this->tplArr[$currIndex] .= $replacedLine;
            return;
        }

        if ($first === '[' && \substr($templine, -1) === ']') {
            $currIndex = \substr($templine, 1, -1);
            $this->tplArr[$currIndex] = "";
            return;
        }
        if ($first === '#' || $first === ';') {
            return; //comment or empty line;
        }
        if ($currIndex) {
            if ($templine === self::EMPTY_ROW) {
                $line = \str_replace(self::EMPTY_ROW, '', $line);
            }
            $this->tplArr[$currIndex] .= $line;
        }
        return;
    }

    /**
     * @param string $name  of the template 
     * @param mixed $search  array or string placeholder (Empty $search returns template without replacements.)
     * @param mixed $replace  array or string for replacement(s)
     * 
     * @return string 
     */
    public function fillTpl(string $name, $search = array(), $replace = '') {
        //Konvertieren, falls $replace kein String und kein Array ist
        if (! \is_string($replace) && ! \is_array($replace)) {
            $replace = (string) $replace;
        }
        if (\is_string($search) && \is_string($replace) && $search !== '') {
            if ($this->convert === true) {
                $search = $this->convertPlaceholder($search);
            }
            return \str_replace($search, $replace, $this->tplArr[$name]);
        }

        if (is_array($search) && $replace === "" && $this->convert === true) {
            $searchArr = array();
            foreach (\array_keys($search) as $placeholder) {
                if (is_int($placeholder)) {
                    throw new \Exception('key-value-Array expected.');
                }
                $searchArr[] = $this->convertPlaceholder($placeholder);
            }
            return \str_replace($searchArr, \array_values($search), $this->tplArr[$name]);
        }

        if (is_array($search) && $replace === "" && $this->convert === false) {
            return \str_replace(\array_keys($search), \array_values($search), $this->tplArr[$name]);
        }

        if (is_array($search) && \is_array($replace) && $this->convert === true) {
            $searchArr = array();
            foreach ($search as $placeholder) {
                $searchArr[] = $this->convertPlaceholder($placeholder);
            }
            return \str_replace($searchArr, $replace, $this->tplArr[$name]);
        }

        return $this->tplArr[$name];
    }

    /**
     * To fill a template repeated, e.g. table rows, this method should be used.
     * 
     * @param string $name
     * @param string|array<mixed> $search   If $replace is null $search must be a numberic array of associative arrays.
     *                        If $replace ist a numeric array of numeric arrays, $seach must be a numeric array
     *                        of the placeholder names.
     *                        $search may be a string, if there is only a single placeholder and $replace is a list of values.
     * @param array<mixed>|null $replace  array or array of associative arrays. (see above)
     * 
     * @return string         The filled and concatenated row templates.
     */
    public function fillRowTpl(string $name, mixed $search, ?array $replace = null) {
        //if ($replace !== null && !is_array($replace)) {
        //    throw new \Exception('3rd parameter must be null or array');
        //}
        $buffer = "";
        if ($replace === null) {
            foreach ($search as $lineArr) {
                $buffer .= $this->fillTpl($name, $lineArr, '');
            }
            return $buffer;
        }

        foreach ($replace as $lineArr) {
            $buffer .= $this->fillTpl($name, $search, $lineArr);
        }

        return $buffer;
    }

    /**
     * Checks wether or not the template exists.
     * 
     * @param string $key
     * 
     * @return boolean 
     */
    public function hasTpl(string $key) {
        if (isset($this->tplArr[$key])) {
            return true;
        }
        return false;
    }

    //Methods for manipulation of the template array (usually the content of a template file with the 
    //key-value-pairs of the template snippets.

    /**
     * Get the whole TplArr, e.g. for merging with another template object. (see below)
     * 
     * @return array<string,string>
     */
    public function getTplArr() {
        return $this->tplArr;
    }

    /**
     * Merge with other TemplateArray
     * 
     * @param array<string,string> $arr
     * 
     * @return void;
     */
    public function mergeTplArr(array $arr) {
        $this->tplArr = \array_merge($this->tplArr, $arr);
    }

    /**
     * Insert new template snippet with key and value or overwrite existing.
     * 
     * @param string $key
     * @param string $value
     * 
     * @return void
     */
    public function setTpl(string $key, string $value) {
        $this->tplArr[$key] = $value;
    }

    /**
     * Get template string by key.
     * 
     * @param string $key
     * 
     * @return string|null
     */
    public function getTpl(string $key) {
        if (!isset($this->tplArr[$key])) {
            return null;
        }
        return $this->tplArr[$key];
    }

    /**
     * 
     * @param string $key
     * 
     * @return boolean
     */
    public function removeTpl(string $key) {
        if (!isset($this->tplArr[$key])) {
            return false;
        }
        unset($this->tplArr[$key]);
        return true;
    }

}
