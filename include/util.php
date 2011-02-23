<?php

/*
  Copyright (C) 2006 Andrew Kopciuch <akopciuch@bddf.ca>
  Copyright (C) 2006 Aaron Seigo <aseigo@bddf.ca>

  The code in this file is free software; you can redistribute it and/or
  modify it under the terms of the GNU Library General Public
  License version 2 as published by the Free Software Foundation.

  This library is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
  Library General Public License for more details.

  You should have received a copy of the GNU Library General Public License
  along with this software; see the file COPYING. If not, write to
  the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
  Boston, MA 02110-1301, USA.
*/


/*
 *  util.php
 *
 * Basically it's just shit we can't figure out a better place for.
 */

if (!function_exists("_"))
{

function _($string)
{
    return $string;
}

}

/*
 * init_var - assigns a value to a variable if it is null
 *
 * &$var: the variable in question
 * $value: the value to assign to it
 */
function init_var(&$var, $value, $iffalse = true, $emptyString = true)
{
    if (is_null($var) || ($iffalse && !$var))
    {
        $var = $value;
    }
    else if (is_string($var) && (strlen(rtrim($var)) == 0))
    {
        $var = $value;
    }
}

/*
 * random number functions
 */
function seedRand()
{
    static $rand_seeded = false;

    if ($rand_seeded)
    {
        return;
    }

    list($usec,$sec) = explode(" ", microtime());
    mt_srand(((float)$sec+ (float)$usec * 100000));

    $rand_seeded = true;
}

function generateToken($length = 20)
{
    seedRand();
    $atoms = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 0, 1, 2, 3, 4, 5, 6, 7, 8, 9);

    $num_atoms = count($atoms);
    $password = '';

    for($i = 0; $i < $length; $i++)
    {
        $password .= $atoms[mt_rand(0, $num_atoms - 1)];
    }

    return substr($password, 0, $length);
}

/*
 * initialize_var - assigns a value to a variable if it is null
 *
 * &$var: the variable in question
 * $value: the value to assign to it
 */
function initialize_var(&$var, $value)
{
    if (is_null($var))
    {
        $var = $value;
    }
}

/*
 * take an array and put it into vertical columns like a phonebook
 * $theList => an array
 * $callback => name of a function to process each entry
 * $column => # of columns to split things into (default = 3)
 */
function vertColumns($theList, $callback, $columns = 3)
{
    if (!is_array($theList))
    {
        return;
    }

    $max = sizeof($theList);
    $rows  = ceil($max / $columns);
    $keys = array_keys($theList);
    for ($currentRow = 0; $currentRow < $rows; ++$currentRow)
    {
        for ($i = 0; $i < $columns; ++$i)
        {
            $key = $keys[$currentRow + ($rows * $i)];
            $callback($key, $theList[$key], $i + 1, $columns);
        }
    }
}

/*
 * take an array and put it into vertical columns like a phonebook
 * $theList => a database query of the form (key, value)
 * $callback => name of a function to process each entry
 * $column => # of columns to split things into (default = 3)
 */
function vertColumnsFromQuery($theQuery, $callback, $columns = 3)
{
    $max = db_numRows($theQuery);
    $rows  = ceil($max / $columns);

    for ($currentRow = 0; $currentRow < $rows; ++$currentRow)
    {
        for ($i = 0; $i < $columns; ++$i)
        {
            unset($key, $value);
            if (($currentRow + ($rows * $i)) < $max)
            {
                list($key, $value) = db_row($theQuery, ($currentRow + ($rows * $i)));
            }
            $callback($key, $value, $i + 1, $columns);
        }
    }
}

/*
 * given any english word, return the root.
 * useful for searching
 */

function stem($word)
{
    $step2list = array('ational'=>'ate',
                       'tional'=>'tion',
                       'enci'=>'ence',
                       'anci'=>'ance',
                       'izer'=>'ize',
                       'iser'=>'ise',
                       'bli'=>'ble',
                       'alli'=>'al',
                       'entli'=>'ent',
                       'eli'=>'e',
                       'ousli'=>'ous',
                       'ization'=>'ize',
                       'isation'=>'ise',
                       'ation'=>'ate',
                       'ator'=>'ate',
                       'alism'=>'al',
                       'iveness'=>'ive',
                       'fulness'=>'ful',
                       'ousness'=>'ous',
                       'aliti'=>'al',
                       'iviti'=>'ive',
                       'biliti'=>'ble',
                       'logi'=>'log');

    $step3list = array('icate'=>'ic',
                       'ative'=>'',
                       'alize'=>'al',
                       'alise'=>'al',
                       'iciti'=>'ic',
                       'ical'=>'ic',
                       'ful'=>'',
                       'ness'=>'');

   $c =    "[^aeiou]";           # consonant
   $v =    "[aeiouy]";           # vowel
   $C =    "${c}[^aeiouy]*";     # consonant sequence
   $V =    "${v}[aeiou]*";       # vowel sequence

   $mgr0 = "^(${C})?${V}${C}";                # [C]VC... is m>0
   $meq1 = "^(${C})?${V}${C}(${V})?" . '$';   # [C]VC[V] is m=1
   $mgr1 = "^(${C})?${V}${C}${V}${C}";        # [C]VCVC... is m>1
   $_v   = "^(${C})?${v}";                    # vowel in stem

    if (strlen($word)<3) return $word;

    $word=preg_replace("/^y/", "Y", $word);

    // Step 1a
    $word=preg_replace("/(ss|i)es$/", "\\1", $word);    // sses-> ss, ies->es
    $word=preg_replace("/([^s])s$/", "\\1", $word);     //    ss->ss but s->null

    // Step 1b
    if (preg_match("/eed$/", $word))
    {
        $stem=preg_replace("/eed$/", "", $word);
        if (ereg("$mgr0", $stem))
        {
            $word=preg_replace("/.$/", "", $word);
        }
    }
    elseif (preg_match("/(ed|ing)$/", $word))
    {
        $stem=preg_replace("/(ed|ing)$/", "", $word);
        if (preg_match("/$_v/", $stem))
        {
            $word=$stem;

            if (preg_match("/(at|bl|iz|is)$/", $word))
            {
                $word=preg_replace("/(at|bl|iz|is)$/", "\\1e", $word);
            }
            elseif (preg_match("/([^aeiouylsz])\\1$/", $word))
            {
                $word=preg_replace("/.$/", "", $word);
            }
            elseif (preg_match("/^${C}${v}[^aeiouwxy]$/", $word))
            {
                $word.="e";
            }
        }
    }

    // Step 1c (weird rule)
    if (preg_match("/y$/", $word))
    {
        $stem=preg_replace("/y$/", "", $word);
        if (preg_match("/$_v/", $stem))
        {
            $word=$stem."i";
        }
    }

    // set up our stupidly long re's here
    $step2RE = "/(ational|tional|enci|anci|izer|iser|bli|alli|entli|eli|ousli|ization|isation|ation|ator|alism|iveness|fulness|ousness|aliti|iviti|biliti|logi)$/";
    $step3RE = "/(icate|ative|alize|alise|iciti|ical|ful|ness)$/";
    $step4RE = "/(al|ance|ence|er|ic|able|ible|ant|ement|ment|ent|ou|ism|ate|iti|ous|ive|ize|ise)$/";

    // Step 2: get ready for some long regexps
    if (preg_match($step2RE, $word, $matches))
    {
        $stem = preg_replace($step2RE, "", $word);
        $suffix = $matches[1];
        if (preg_match("/$mgr0/", $stem))
        {
            $word = $stem.$step2list[$suffix];
        }
    }

    // Step 3
    if (preg_match($step3RE, $word, $matches))
    {
        $stem=preg_replace($step3RE, "", $word);
        $suffix=$matches[1];
        if (preg_match("/$mgr0/", $stem))
        {
            $word=$stem.$step3list[$suffix];
        }
    }

    // Step 4
    if (preg_match($step4RE, $word, $matches))
    {
        $stem=preg_replace($step4RE, "", $word);
        $suffix=$matches[1];
        if (preg_match("/$mgr1/", $stem))
        {
            $word=$stem;
        }
    }
    elseif (preg_match("/(s|t)ion$/", $word))
    {
        $stem=preg_replace("/(s|t)ion$/", "\\1", $word);
        if (preg_match("/$mgr1/", $stem)) $word=$stem;
    }

    // Step 5
    if (preg_match("/e$/", $word, $matches))
    {
        $stem=preg_replace("/e$/", "", $word);
        if (preg_match("/$mgr1/", $stem) ||
            (preg_match("/$meq1/", $stem) &&
            ~preg_match("/^${C}${v}[^aeiouwxy]$/", $stem)))
        {
            $word = $stem;
        }
    }

    if (preg_match("/ll$/", $word) & preg_match("/$mgr1/", $word))
    {
        $word = preg_replace("/.$/", "", $word);
    }

    // and turn initial Y back to y
    preg_replace("/^Y/", "y", $word);

    return $word;
}


/*
 * break a string up along quotes and allow for escaping with backslashes
 *
 * $string => the string to parse
 * $stemWords => use the stem() function on the tokens, defaults to false
 *
 * returns an array of tokens
 */

function tokenize($string, $stemWords = false)
{
    $string = trim(ereg_replace('[[:space:]]+', ' ', $string));
    $numChars = strlen($string);
    $marker = '';
    $startChar = 0;
    $endChar = 0;
    $current = 0;
    $arrayPos = 0;

    for ($i = 0; $i < $numChars; ++$i, ++$current)
    {
        if ($string[$i] == '\\' &&
            $i > 0 &&
            $string[$i - 1] != '\\')
        {
            ++$i;
        }
        else if ($string[$i] == ' ')
        {
            if ($marker == '')
            {
                $endChar = $current - 1;
            }
        }
        else if ($string[$i] == '\'')
        {
            if ($marker == '\'')
            {
                if ($current - 1 == $endChar)
                {
                    ++$startChar;
                }

                $endChar = $current - 1;
            }
            else if ($marker == '')
            {
                if ($current > $startChar)
                {
                    $terms[$arrayPos] = substr($string, $startChar, $current - $startChar);
                    ++$arrayPos;
                    $startChar = $i + 1;
                }
                $marker = '\'';
                $startChar = $current + 1;
            }
        }

        $string[$current] = $string[$i];

        if ($endChar - $startChar > 1)
        {
            $temp = trim(substr($string, $startChar, $endChar - $startChar + 1));

            if ($stemWords)
            {
                $temp = stem($temp);
            }

            $terms[$arrayPos] = $temp;

            ++$arrayPos;
            $startChar = $current + 1;
            if ($marker != '') { ++$current; ++$startChar; ++$i; }
            $marker = '';
        }
    }

    if ($current - $startChar > 1)
    {
        $temp = trim(substr($string, $startChar, $current - $startChar));

        if ($stemWords)
        {
            $temp = stem($temp);
        }

        $terms[$arrayPos] = $temp;
    }

    return $terms;
}

function parseCSV($data, $callback, $headerLines = 0)
{
    //print "parseCSV($data, $callback, $header = false)<br>";
    if (!$data || !$callback || !function_exists($callback))
    {
        return;
    }

    if (!is_array($data))
    {
        $lines = preg_split("/[\\r\\n]+/", $data);
    }
    else
    {
        $lines = $data;
    }

    $numLines = count($lines);
    //    print "$numLines <== lots to process?<br>";
    unset($marker);
    $terms = array();
    for ($j = $headerLines; $j < $numLines; ++$j)
    {
        $string = trim($lines[$j]);
        //        print "{{$string}}<br>";
        if (isset($marker))
        {
            // we had reached a premature ending of the line
            $string = array_pop($terms) . $string;
        }
        else
        {
            if (count($terms) > 0)
            {
                $callback($terms);
            }
            unset($marker);
            $terms = array();
        }

        $numChars = strlen($string);
//        print "$numChars<br>";
        $startChar = 0;
        $endChar = -1;
        $current = 0;
        $arrayPos = 0;
        $i = 0;

        for (; $i < $numChars; ++$i, ++$current)
        {
            $char = $string[$i];
            if ($char == '\\' &&
                    $i > 0 &&
                    $string[$i - 1] != '\\')
            {
                ++$i;
            }
            else if ($char == ',')
            {
                if (!isset($marker))
                {
                    $endChar = $current;
                }
            }
            else if ($char == '"')// || $char == '\'')
            {
                if ($marker == $char)
                {
                    unset($marker);
                    $string[$i] = ' ';
                }
                else if (!isset($marker))
                {
                    if ($string[$i + 1] != '"')
                    {
                        $marker = $char;
                    }
                    ++$i;
                }
            }

            if ($current != $i)
            {
                $string[$current] = $string[$i];
            }

//print "            if ($i > 0 && $endChar - $startChar > -1)<br>";
            if ($i > 0 && $endChar - $startChar > -1)
            {
                if ($endChar - $startChar > 1)
                {
                    array_push($terms, trim(substr($string, $startChar, $current - $startChar)));
                }
                else
                {
                    array_push($terms, '');
                }

                $startChar = $current + 1;
                unset($marker);
            }
        }

//print "substr($string, $startChar, $current - $startChar) is: " . substr($string, $startChar, $current - $startChar) . "<br>";
        if ($current - $startChar > -1)
        {
            if ($current - $startChar > 1)
            {
                array_push($terms, substr($string, $startChar, $current - $startChar));
            }
            else
            {
                array_push($terms, '');
            }
        }
    }

    if (count($terms) > 0)
    {
        $callback($terms);
    }
}

function httpVar($name, $source = 'post')
{
    $value = null;
    $first = null;
    $second = null;

    if ($source == 'get')
    {
       $first = $_GET;
       $second = $_POST;
    }
    else
    {
       $first = $_POST;
       $second = $_GET;
    }

    if (isset($first[$name]))
    {
        $value = $first[$name];
    }

    if (!$value)
    {
        if (isset($second[$name]))
        {
            $value = $second[$name];
        }
    }

    return $value;
}

function var_dumpHTML(&$var)
{
    $export = var_export($var, true);
    print "<pre>$export</pre>";
}


function getOrPostVar($var)
{
    if (isset($_GET[$var])) {
        return $_GET[$var];
    }

    if (isset($_POST[$var])) {
        return $_POST[$var];
    }

    return null;
}

/*
 * print a status message in a nicely formatted manner
 *
 * $messageType: one of 'ERROR', 'WARNING', 'INFORMATIONAL' (case insensitive)
 * $title: the title of the message
 * $message: the actual message
 * $msgID : an Id for easy eye catching
 * $sendEmail: if $common_contactEmail is set and sendEmail is true, this message is sent to tech support
 * $preFunc: a function to run prior to printing anything out. useful for header type stuff
 * $postMsgFunc: a callback to print html after the message but still inside the error msg
 */
function print_msg($messageType, $title, $message,
                   $msgID = '', $sendMail = false, $preFunc = '', $postMsgFunc = '', $spacing = false)
{
    global $common_baseImagesURL, $common_contactEmail, $jsonContent;

    if ($preFunc && function_exists($preFunc))
    {
        $preFunc();
    }

    if ($sendMail && isset($common_contactEmail))
    {
        mail($common_contactEmail,
            "Status Message: http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}" ,
            "$messageType: $title\n\n$message");
    }

    if ($postMsgFunc && function_exists($postMsgFunc))
    {
        $postMsgFunc();
    }

    //TODO: allow multiple errors
    $content = Array('title' => $title, 'error' => $message, 'errorId' => $msgID);
    $key = strtolower($messageType) . 's';
    if (!isset($jsonContent[$key])) {
        $jsonContent[$key] = Array();
    }

    $jsonContent[$key][] = $content;
}

/*
 * handle php errors using the above status message
 * see PHP's trigger_error for information on usage
 * the important items are the definition of the $*Errors arrays
 * define a $common_contactEmail somewhere and fill up the $emailErrors array to get nice emails when errors happen
 */
function phpErrorHandler($errno, $errmsg, $filename, $linenum, $vars)
{
    global $common_contactEmail;

    if (error_reporting() == 0)
    {
        return;
    }

    // define an assoc array of error string
    // in reality the only entries we should
    // consider are 2,8,256,512 and 1024
    $handleErrors = array (
                1   =>  "Error",
                2   =>  "Warning",
                4   =>  "Parsing Error",
                /*8   =>  "Notice",*/
                16  =>  "Core Error",
                32  =>  "Core Warning",
                64  =>  "Compile Error",
                128 =>  "Compile Warning",
                256 =>  "User Error",
                512 =>  "User Warning",
                1024=>  "User Notice"
                );
    $backtraceErrors = array();
    $emailErrors = array();
    $logErrors = array();
    $dieErrors = array(4, 16, 64);

    if (!isset($handleErrors[$errno]))
    {
        return;
    }

    // set of errors for which a var trace will be saved

    $dt = date("Y-m-d H:i:s (T)");
    $err = "When: $dt<br>
           Script: $filename:$linenum<br>
           Message: $errmsg";

    if ($common_contactEmail && in_array($errno, $emailErrors))
    {
        $email = $err . '\n\n';
        foreach ($vars as $key => $value)
        {
            $email .= "Parameter: $key\nvalue: $value\n\n";
        }
        mail($common_contactEmail,
            "Error: http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}" ,
            "Error #$errno: {$handleErrors[$errno]}\n\n$email");
    }

    if (in_array($errno, $backtraceErrors))
    {
        $err .= "<p><table border=0>";
        foreach ($vars as $key => $value)
        {
            $err .= "<tr><td style=\"border-top: 1px black solid;\">parameter: $key<br>value: $value</td></tr>";
        }
        $err .= '</table>';
    }

    print_msg('error', sprintf(_("Error #%u: %s"), $errno, $handleErrors[$errno]),
              $err);

    // save to the error log, and e-mail me if there is a critical user error
    if (ini_get('error_log') && in_array($errno, $logErrors))
    {
        error_log($err, 3, ini_get('error_log'));
    }

    if (in_array($errno, $dieErrors))
    {
        die();
    }
}

// automatically set the error handler to OURS!
set_error_handler("phpErrorHandler");

function canAccessApi($addr)
{
    //uncomment the following line for debug purposes
    //return true;
    unset($where);
    db_canAccessApi($addr);
}

?>
