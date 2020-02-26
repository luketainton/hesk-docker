<?php
/**
 *
 * This file is part of HESK - PHP Help Desk Software.
 *
 * (c) Copyright Klemen Stirn. All rights reserved.
 * https://www.hesk.com
 *
 * For the full copyright and license agreement information visit
 * https://www.hesk.com/eula.php
 *
 */

/* Check if this is a valid include */
if (!defined('IN_SCRIPT')) {die('Invalid attempt');}

function getFileExtension($fileName)
{
	$parts=explode(".",$fileName);
	return $parts[count($parts)-1];
}

function get_temp_fname($tmpdir)
{
	return $tmpdir.md5(uniqid(mt_rand(), true));
}

// read config file , return value of  varname or false
function get_config($varname)
{
	global $hesk_settings;

	$tmpdir = get_temp_fname(HESK_PATH . $hesk_settings['cache_dir'] . '/');
	mkdir($tmpdir, 0777);

	$config = array(
		"TempDir" => $tmpdir,
		"AttachmentsDir" => $tmpdir,
	);

	if ( array_key_exists($varname,$config) )
	{
		return $config[$varname];
	}
	return FALSE;
}

function parser($eml_file='')
{
	$tempdir = get_config('TempDir');
	if ( ! is_dir($tempdir) )
	{
		die('The temporary directory "'.$tempdir.'" doesn\'t exist.');
	}

	// get a unique temporary file name
	$tmpfilepath = tempnam($tempdir, strval(mt_rand(1000,9999)));

    if (defined('HESK_IMAP'))
    {
        global $imap, $email_number;
        @file_put_contents($tmpfilepath, imap_fetchbody($imap, $email_number, ""));
    }
    else
    {
        // read the mail that is forwarded to the script
        // then save the mail to a temporary file
        save_forward_mail($tmpfilepath, $eml_file);
    }

	if(file_exists($tmpfilepath) === FALSE)
	{
		die('Failed to save the mail as '.$tmpfilepath.'.');
	}

	$ret = analyze($tmpfilepath,$tempdir);
    //die (print_r($ret));
	return $ret;
}


function analyze($tmpfilepath,$tempdir)
{
	$mime=new mime_parser_class;

	$mime->mbox = 0;
	$mime->decode_bodies = 1;
	$mime->ignore_syntax_errors = 1;
	$mime->track_lines = 0;

	$parameters=array(
		'File'=>$tmpfilepath,
		'SaveBody'=>$tempdir,
	);

	/* only process the first email */
	if($mime->Decode($parameters, $decoded))
	{
		if($mime->decode_bodies)
		{
			if($mime->Analyze($decoded[0], $results))
            {
            	#echo "MIME:\n\n";
            	#print_r($results);
                #echo "\nEND MIME\n\n";
				return process_results($results,$tempdir) ;
			}
            else
            {
				echo 'MIME message analyse error: '.$mime->error."\n";
			}
		}
	}
	return False;
}


function process_addrname_pairs($email_info)
{
	$result = array();

	foreach($email_info as $info)
	{
		$address = "";
		$name = "";
		$encoding = "";
		if ( array_key_exists("address", $info) )
		{
			$address = $info["address"];
		}
		if ( array_key_exists("name", $info) )
		{
			$name = $info["name"];
		}
		if ( array_key_exists("encoding", $info) )
		{
			$encoding = $info["encoding"];
		}

		$result[] = array("address"=>$address, "name"=>$name, "encoding"=>$encoding);
	}

	return $result;
}


function process_attachments($attachments)
{
	$result = array();
	foreach($attachments as $key => $info)
    {
		$orig_name = "";
		$size = 0;
		$stored_name = "";
		$type = "";

		if ( array_key_exists("Type", $info) )
        {
			$type = $info["Type"];
		}

		if ( array_key_exists("FileName", $info) )
        {
			$orig_name = $info["FileName"];
		}
        elseif ($type == 'message')
        {
            $orig_name = ($key + 1) . ".msg";
        }

		if ( ! strlen($orig_name))
        {
			continue;
		}

		if ( array_key_exists("Data", $info) )
        {
			$data = $info["Data"];
			$size = strlen($data);

			if ($size == 0)
            {
				continue;
			}

			$attachsdir = get_config("AttachmentsDir");

			if ( ! is_dir($attachsdir) )
            {
				die('The attachments directory "'.$attachsdir.'" doesn\'t exist.');
			}

			$stored_name = save_attachment($attachsdir,getFileExtension($orig_name),$data);
		}
		else
        {
			$stored_name = $info['DataFile'];
			$size = filesize($stored_name);
		}

		$result[] = array("orig_name"=>$orig_name,"size"=>$size,"stored_name"=>$stored_name,"type"=>$type);
	}

	return $result;
}

/*
save an attachment file into the predefined directory.
return stored name 
*/
function save_attachment($dir,$extension,$data)
{
	$dir = rtrim($dir,"/\\"); /* " */

	$path = "";
	$stored_name = "";
	do
    {
		$stored_name = date("YmdHis")."_".strval(mt_rand()).".".$extension;
		$path = $dir . "/" . $stored_name;
	} while(file_exists($path));

	$fp = fopen($path,"w");
	if($fp === FALSE)
    {
		die("Cannot save file ".$path." .");
	}
	fwrite($fp,$data);
	fclose($fp);

	return $stored_name;
}

function process_results($result,$tempdir)
{
	global $hesk_settings;
    
	$r = array();

	// from address and name
	$r["from"] = process_addrname_pairs($result["From"]);

	// to  address and name
	$r["to"] = process_addrname_pairs($result["To"]);

	// cc address and name
	if( array_key_exists("Cc", $result) )
    {
		$r["cc"] = process_addrname_pairs($result["Cc"]);
	}
    else
    {
		$r["cc"] = array();
	}

	// bcc address and name
	if( array_key_exists("Bcc", $result) )
    {
		$r["bcc"] = process_addrname_pairs($result["Bcc"]);
	}
    else
    {
		$r["bcc"] = array();
	}

	// reply-to address and name
	if( array_key_exists("Reply-to", $result) )
    {
		$r["reply-to"] = process_addrname_pairs($result["Reply-to"]);
	}
    else
    {
		$r["reply-to"] = array();
	}

	// subject and subject encoding
	$r["subject"] = $result["Subject"];
	$r["subject_encoding"] = isset($result["SubjectEncoding"]) ? strtoupper($result["SubjectEncoding"]) : "";

	// Message encoding
	$r["encoding"] = isset($result["Encoding"]) ? strtoupper($result["Encoding"]) : "";

	// If message is saved in a file get it from the file
	if ( ! isset($result["Data"]) )
	{
		$result["Data"] = ( isset($result["DataFile"]) && ! ( isset($result["FileDisposition"]) && $result["FileDisposition"] == "attachment") ) ? file_get_contents($result["DataFile"]) : "";
	}

	// Convert to UTF-8 before processing further
	if ($r["encoding"] != "" && $r["encoding"] != 'UTF-8')
	{
		$result["Data"] = $result["Data"] == "" ? "" : iconv($r["encoding"], 'UTF-8', $result["Data"]);
		$r["encoding"] = 'UTF-8';
	}

	// the message shall be converted to text if it is in html
	if ( $result["Type"] === "html" )
    {
		$r["message"] = convert_html_to_text($result["Data"]);
	}
    else
    {
		$r["message"] = $result["Data"];
	}

    // Fix for inline attachments
    if (isset($result["FileDisposition"]) && ($result["FileDisposition"] == "attachment" || $result["FileDisposition"] == "inline"))
    {
        $r["message"] = "";
    }

	// Message attachments
    $r["attachments"] = array();

	if ($hesk_settings['attachments'])
    {
		// Attachment with no message
		if ( isset($result["FileDisposition"]) && ($result["FileDisposition"] == "attachment" || $result["FileDisposition"] == "inline"))
		{
			$tmp = array();
			$tmp[0]['FileDisposition'] = "attachment";

			if ( isset($result["Type"]) )
			{
				$tmp[0]['Type'] = $result["Type"];
			}

			if ( isset($result["SubType"]) )
			{
				$tmp[0]['SubType'] = $result["SubType"];
			}

			if ( isset($result["Description"]) )
			{
				$tmp[0]['Description'] = $result["Description"];
			}

			if ( isset($result["DataFile"]) )
			{
				$tmp[0]['DataFile'] = $result["DataFile"];
			}

			if ( isset($result["FileName"]) )
			{
				$tmp[0]['FileName'] = $result["FileName"];
			}

			$r["attachments"] = array_merge($r["attachments"], process_attachments($tmp) );
		}

    	// File attachments
    	if ( array_key_exists("Attachments", $result) )
        {
            $r["attachments"] = array_merge($r["attachments"], process_attachments($result["Attachments"]) );
        }

    	// Save embedded files (for example embedded images)
    	if ($hesk_settings['save_embedded'] && array_key_exists("Related", $result) )
        {
            $r["attachments"] = array_merge($r["attachments"], process_attachments($result["Related"]) );
        }
	}

	// Name of the temporary folder
	$r["tempdir"] = $tempdir;

	return $r;
}

/*
  save the forwarded mail to a temporary file
  no return value
*/
function save_forward_mail($tmpfilepath, $eml_file)
{
	// create a temporary file
	$tmpfp = fopen($tmpfilepath,"w");

    // Just a line used for testing
    // $eml_file = 'C:\\Users\\4N\\Desktop\\test.txt';

	// open the stdin as a file handle or input file
    $fp = $eml_file ? fopen($eml_file, "r") : fopen("php://stdin", "r");
    $fileContent = @stream_get_contents($fp);
    fwrite($tmpfp, $fileContent);

	fclose($tmpfp);
}



#test();

function test()
{
  $results = parser();

  print_r($results);
  exit();
  
  // from address and name
  echo "from :\n";
  echo $results["from"][0]["address"]."\n";
  echo $results["from"][0]["name"]."\n";
  
  echo "\nto :\n";
  foreach( $results["to"] as $to ){
    echo $to["address"]."\n";
    echo $to["name"]."\n";
  }

  echo "\nreply-to :\n";
  foreach( $results["reply-to"] as $to ){
    echo $to["address"]."\n";
    echo $to["name"]."\n";
  }  
  
  echo "\ncc :\n";
  foreach( $results["cc"] as $to ){
    echo $to["address"]."\n";
    echo $to["name"]."\n";
  }  
  
  echo "\nbcc :\n";
  foreach( $results["bcc"] as $to ){
    echo $to["address"]."\n";
    echo $to["name"]."\n";
  }  
  
  echo "\nsubject :\n";
  echo $results["subject"]."\n";  

  echo "\nmessage :\n";
  echo $results["message"]."\n"; 
  
  echo "\nattachments :\n";
  foreach( $results["attachments"] as $attach ){
     echo $attach["orig_name"]."\n";
     echo $attach["size"]."\n";
     echo $attach["stored_name"]."\n";
     echo $attach["type"]."\n";
  }
  
}


function deleteAll($directory, $empty = false)
{
    if(substr($directory,-1) == "/")
    {
        $directory = substr($directory,0,-1);
    }

    if(!file_exists($directory) || !is_dir($directory))
    {
        return false;
    }
    elseif( ! is_readable($directory))
    {
        return false;
    }
    else
    {
        $directoryHandle = opendir($directory);

        while ($contents = readdir($directoryHandle))
        {
            if($contents != '.' && $contents != '..')
            {
                $path = $directory . "/" . $contents;

                if(is_dir($path))
                {
                    deleteAll($path);
                }
                else
                {
                    @unlink($path);
                }
            }
        }

        closedir($directoryHandle);

        if($empty == false)
        {
            if(!rmdir($directory))
            {
                return false;
            }
        }

        return true;
    }
}


function convert_html_to_text($data)
{
    $html2text = new html2text($data);
    $h2t = & ref_new($html2text);

	// Simply call the get_text() method for the class to convert
	// the HTML to the plain text. Store it into the variable.
	$text = $h2t->get_text();
    return $text;
}


class html2text
{
    /**
     *  Contains the HTML content to convert.
     *
     *  @var string $html
     *  @access public
     */
    var $html;

    /**
     *  Contains the converted, formatted text.
     *
     *  @var string $text
     *  @access public
     */
    var $text;

    /**
     *  Maximum width of the formatted text, in columns.
     *
     *  Set this value to 0 (or less) to ignore word wrapping
     *  and not constrain text to a fixed-width column.
     *
     *  @var integer $width
     *  @access public
     */
    var $width = 70;

    /**
     *  List of preg* regular expression patterns to search for,
     *  used in conjunction with $replace.
     *
     *  @var array $search
     *  @access public
     *  @see $replace
     */
    var $search = array(
        "/\r/",                                  // Non-legal carriage return
        "/[\n\t]+/",                             // Newlines and tabs
        '/[ ]{2,}/',                             // Runs of spaces, pre-handling
        '/<script[^>]*>.*?<\/script>/i',         // <script>s -- which strip_tags supposedly has problems with
        '/<style[^>]*>.*?<\/style>/i',           // <style>s -- which strip_tags supposedly has problems with
        //'/<!-- .* -->/',                         // Comments -- which strip_tags might have problem a with
        '/<h[123][^>]*>(.*?)<\/h[123]>/i',       // H1 - H3
        '/<h[456][^>]*>(.*?)<\/h[456]>/i',       // H4 - H6
		'/<div[^>]*>/i',                         // <div>
        '/<p[^>]*>/i',                           // <p>
        '/<br[^>]*>/i',                          // <br>
        '/<b[^>]*>(.*?)<\/b>/i',                 // <b>
        '/<strong[^>]*>(.*?)<\/strong>/i',       // <strong>
        '/<i[^>]*>(.*?)<\/i>/i',                 // <i>
        '/<em[^>]*>(.*?)<\/em>/i',               // <em>
        '/(<ul[^>]*>|<\/ul>)/i',                 // <ul> and </ul>
        '/(<ol[^>]*>|<\/ol>)/i',                 // <ol> and </ol>
        '/<li[^>]*>(.*?)<\/li>/i',               // <li> and </li>
        '/<li[^>]*>/i',                          // <li>
        '/<a [^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/i',
                                                 // <a href="">
        '/<hr[^>]*>/i',                          // <hr>
        '/(<table[^>]*>|<\/table>)/i',           // <table> and </table>
        '/(<tr[^>]*>|<\/tr>)/i',                 // <tr> and </tr>
        '/<td[^>]*>(.*?)<\/td>/i',               // <td> and </td>
        '/<th[^>]*>(.*?)<\/th>/i',               // <th> and </th>
    );

    /**
     *  List of pattern replacements corresponding to patterns searched.
     *
     *  @var array $replace
     *  @access public
     *  @see $search
     */
    var $replace = array(
        '',                                     // Non-legal carriage return
        ' ',                                    // Newlines and tabs
        ' ',                                    // Runs of spaces, pre-handling
        '',                                     // <script>s -- which strip_tags supposedly has problems with
        '',                                     // <style>s -- which strip_tags supposedly has problems with
        //'',                                   // Comments -- which strip_tags might have problem a with
        "\n\n\\1\n\n",					        // H1 - H3
        "\n\n\\1\n\n",             				// H4 - H6
        "\n",                         		    // <div>
        "\n\n",                                 // <p>
        "\n",                                   // <br>
        "\\1",				                    // <b>
        "\\1",				                    // <strong>
        '_\\1_',                                // <i>
        '_\\1_',                                // <em>
        "\n\n",                                 // <ul> and </ul>
        "\n\n",                                 // <ol> and </ol>
        "\t* \\1\n",                            // <li> and </li>
        "\n\t* ",                               // <li>
        "\\2:\n\\1",                            // <a href="">
        "\n-------------------------\n",        // <hr>
        "\n\n",                                 // <table> and </table>
        "\n",                                   // <tr> and </tr>
        "\t\t\\1\n",                            // <td> and </td>
        "\t\t\\1\n",				            // <th> and </th>
    );

    /**
     *  Contains a list of HTML tags to allow in the resulting text.
     *
     *  @var string $allowed_tags
     *  @access public
     *  @see set_allowed_tags()
     */
    var $allowed_tags = '';

    /**
     *  Contains the base URL that relative links should resolve to.
     *
     *  @var string $url
     *  @access public
     */
    var $url;

    /**
     *  Indicates whether content in the $html variable has been converted yet.
     *
     *  @var boolean $_converted
     *  @access private
     *  @see $html, $text
     */
    var $_converted = false;

    /**
     *  Contains URL addresses from links to be rendered in plain text.
     *
     *  @var string $_link_list
     *  @access private
     *  @see _build_link_list()
     */
    var $_link_list = '';

    /**
     *  Number of valid links detected in the text, used for plain text
     *  display (rendered similar to footnotes).
     *
     *  @var integer $_link_count
     *  @access private
     *  @see _build_link_list()
     */
    var $_link_count = 0;

    /**
     *  Constructor.
     *
     *  If the HTML source string (or file) is supplied, the class
     *  will instantiate with that source propagated, all that has
     *  to be done it to call get_text().
     *
     *  @param string $source HTML content
     *  @param boolean $from_file Indicates $source is a file to pull content from
     *  @access public
     *  @return void
     */
    function __construct( $source = '', $from_file = false )
    {
        if ( !empty($source) ) {
            $this->set_html($source, $from_file);
        }
        $this->set_base_url();
    }

    /**
     *  Loads source HTML into memory, either from $source string or a file.
     *
     *  @param string $source HTML content
     *  @param boolean $from_file Indicates $source is a file to pull content from
     *  @access public
     *  @return void
     */
    function set_html( $source, $from_file = false )
    {
        $this->html = $source;

        if ( $from_file && file_exists($source) ) {
            $fp = fopen($source, 'r');
            $this->html = fread($fp, filesize($source));
            fclose($fp);
        }

        $this->_converted = false;
    }

    /**
     *  Returns the text, converted from HTML.
     *
     *  @access public
     *  @return string
     */
    function get_text()
    {
        if ( !$this->_converted ) {
            $this->_convert();
        }

        return $this->text;
    }

    /**
     *  Prints the text, converted from HTML.
     *
     *  @access public
     *  @return void
     */
    function print_text()
    {
        print $this->get_text();
    }

    /**
     *  Alias to print_text(), operates identically.
     *
     *  @access public
     *  @return void
     *  @see print_text()
     */
    function p()
    {
        print $this->get_text();
    }

    /**
     *  Sets the allowed HTML tags to pass through to the resulting text.
     *
     *  Tags should be in the form "<p>", with no corresponding closing tag.
     *
     *  @access public
     *  @return void
     */
    function set_allowed_tags( $allowed_tags = '' )
    {
        if ( !empty($allowed_tags) ) {
            $this->allowed_tags = $allowed_tags;
        }
    }

    /**
     *  Sets a base URL to handle relative links.
     *
     *  @access public
     *  @return void
     */
    function set_base_url( $url = '' )
    {
        if ( empty($url) ) {
        	if ( !empty($_SERVER['HTTP_HOST']) ) {
	            $this->url = 'http://' . $_SERVER['HTTP_HOST'];
        	} else {
	            $this->url = '';
	        }
        } else {
            // Strip any trailing slashes for consistency (relative
            // URLs may already start with a slash like "/file.html")
            if ( substr($url, -1) == '/' ) {
                $url = substr($url, 0, -1);
            }
            $this->url = $url;
        }
    }

    /**
     *  Workhorse function that does actual conversion.
     *
     *  First performs custom tag replacement specified by $search and
     *  $replace arrays. Then strips any remaining HTML tags, reduces whitespace
     *  and newlines to a readable format, and word wraps the text to
     *  $width characters.
     *
     *  @access private
     *  @return void
     */
    function _convert()
    {
    	global $hesklang;

        // Variables used for building the link list
        $this->_link_count = 0;
        $this->_link_list = '';

        $text = trim($this->html);

        // Remove embedded image tags
        $text = preg_replace('/<img.*?src=["\']+(.*?)["\']+.*?>/i', '$1', $text);

        // Run our defined search-and-replace
        $text = preg_replace($this->search, $this->replace, $text);

		// Cleanup HTML entities and convert them to UTF-8
		$text = hesk_convert_to_utf8_and_clean_html_entities($text);

        // Strip any other HTML tags
        #$text = strip_tags($text, $this->allowed_tags);

        // Bring down number of empty lines to 2 max
		$text = preg_replace("/\n\s+\n/", "\n\n", $text);
		$text = preg_replace("/[\n]{3,}/", "\n\n", $text);

        // Add link list
        if ( !empty($this->_link_list) ) {
            $text .= "\n\nLinks:\n------\n" . $this->_link_list;
        }

        // Wrap the text to a readable format
        // for PHP versions >= 4.0.2. Default width is 75
        // If width is 0 or less, don't wrap the text.
        if ( $this->width > 0 ) {
        	$text = wordwrap($text, $this->width);
        }

        $this->text = $text;

        $this->_converted = true;
    }

    /**
     *  Helper function called by preg_replace() on link replacement.
     *
     *  Maintains an internal list of links to be displayed at the end of the
     *  text, with numeric indices to the original point in the text they
     *  appeared. Also makes an effort at identifying and handling absolute
     *  and relative links.
     *
     *  @param string $link URL of the link
     *  @param string $display Part of the text to associate number with
     *  @access private
     *  @return string
     */
    function _build_link_list( $link, $display )
    {
		if ( substr($link, 0, 7) == 'http://' || substr($link, 0, 8) == 'https://' ||
             substr($link, 0, 7) == 'mailto:' ) {
            $this->_link_count++;
            $this->_link_list .= "[" . $this->_link_count . "] $link\n";
            $additional = ' [' . $this->_link_count . ']';
		} elseif ( substr($link, 0, 11) == 'javascript:' ) {
			// Don't count the link; ignore it
			$additional = '';
		// what about href="#anchor" ?
        } else {
            $this->_link_count++;
            $this->_link_list .= "[" . $this->_link_count . "] " . $this->url;
            if ( substr($link, 0, 1) != '/' ) {
                $this->_link_list .= '/';
            }
            $this->_link_list .= "$link\n";
            $additional = ' [' . $this->_link_count . ']';
        }

        return $display . $additional;
    }

}


/*
* Converts reserved (X)HTML entities into UTF-8 chars
* Some entities are handled in a different function and are commented out
* Some entities are replace with more acceptable ones
* Some entities are ignored
*/
function hesk_convert_to_utf8_and_clean_html_entities($text)
{
	// Can we use the multibyte functionality of PHP?
	if ( function_exists('mb_decode_numericentity') )
	{
		$text = mb_decode_numericentity($text, array(0x0, 0x2FFFF, 0, 0xFFFF), 'UTF-8');
	}
    // Alternatively, use a custom function
	else
    {
		$text = preg_replace_callback('/&#([0-9a-fx]+);/mi', 'hesk_replace_num_entity', $text);
    }

	// Entities that are not case sensitive
	$html_entities = array(

		// Convert to double quotes
		'&quot;' => '"',
		'&#148;' => '"',
		'&ldquo;' => '"',
		'&rdquo;' => '"',
		'&bdquo;' => '"',
		'&prime;' => '"',

		// Convert to single quotes
		'&apos;' => '\'',
		'&lsquo;' => '\'', // '&lsquo;' => '‘',
		'&rsquo;' => '\'', // '&rsquo;' => '’',
		'&prime;' => '\'',
		'&acute;' => '\'',

        // Convert to spaces
		'&nbsp;' => ' ',
		'&ensp;' => ' ',
		'&emsp;' => ' ',
		'&thinsp;' => ' ',

		// Other chars often used
		#'&lt;' => '<',
		#'&gt;' => '>',
		'&iexcl;' => '¡',
		'&cent;' => '¢',
		'&pound;' => '£',
		'&curren;' => '¤',
		'&yen;' => '¥',
		'&brvbar;' => '¦',
		'&sect;' => '§',
		'&uml;' => '¨',
		'&copy;' => '©',
		'&ordf;' => 'ª',
		'&laquo;' => '«',
		'&not;' => '¬',
		'&shy;' => '­­',
		'&reg;' => '®',
		'&macr;' => '¯',
		'&deg;' => '°',
		'&plusmn;' => '±',
		'&sup2;' => '²',
		'&sup3;' => '³',
		'&micro;' => 'µ',
		'&para;' => '¶',
		'&middot;' => '·',
		'&cedil;' => '¸',
		'&sup1;' => '¹',
		'&ordm;' => 'º',
		'&raquo;' => '»',
		'&frac14;' => '¼',
		'&frac12;' => '½',
		'&frac34;' => '¾',
		'&iquest;' => '¿',
		'&times;' => '×',
		'&divide;' => '÷',
		'&forall;' => '∀',
		'&part;' => '∂',
		'&exist;' => '∃',
		'&empty;' => '∅',
		'&nabla;' => '∇',
		'&isin;' => '∈',
		'&notin;' => '∉',
		'&ni;' => '∋',
		'&prod;' => '∏',
		'&sum;' => '∑',
		'&minus;' => '−',
		'&lowast;' => '∗',
		'&radic;' => '√',
		'&prop;' => '∝',
		'&infin;' => '∞',
		'&ang;' => '∠',
		'&and;' => '∧',
		'&or;' => '∨',
		'&cap;' => '∩',
		'&cup;' => '∪',
		'&int;' => '∫',
		'&there4;' => '∴',
		'&sim;' => '∼',
		'&cong;' => '≅',
		'&asymp;' => '≈',
		'&ne;' => '≠',
		'&equiv;' => '≡',
		'&le;' => '≤',
		'&ge;' => '≥',
		'&sub;' => '⊂',
		'&sup;' => '⊃',
		'&nsub;' => '⊄',
		'&sube;' => '⊆',
		'&supe;' => '⊇',
		'&oplus;' => '⊕',
		'&otimes;' => '⊗',
		'&perp;' => '⊥',
		'&sdot;' => '⋅',
		'&fnof;' => 'ƒ',
		'&circ;' => 'ˆ',
		'&tilde;' => '˜',
		'&ndash;' => '–',
		'&mdash;' => '—',
		'&sbquo;' => ',',
		'&bull;' => '•',
		'&hellip;' => '…',
		'&permil;' => '‰',
		'&lsaquo;' => '‹',
		'&rsaquo;' => '›',
		'&oline;' => '‾',
		'&euro;' => '€',
		'&trade;' => '™',
		'&larr;' => '←',
		'&uarr;' => '↑',
		'&rarr;' => '→',
		'&darr;' => '↓',
		'&harr;' => '↔',
		'&loz;' => '◊',
		'&spades;' => '♠',
		'&clubs;' => '♣',
		'&hearts;' => '♥',
		'&diams;' => '♦',
	);

    $text = str_ireplace( array_keys($html_entities), array_values($html_entities), $text);

	// Case sensitive entities
	$html_entities = array(
		'&Agrave;' => 'À',
		'&Aacute;' => 'Á',
		'&Acirc;' => 'Â',
		'&Atilde;' => 'Ã',
		'&Auml;' => 'Ä',
		'&Aring;' => 'Å',
		'&AElig;' => 'Æ',
		'&Ccedil;' => 'Ç',
		'&Egrave;' => 'È',
		'&Eacute;' => 'É',
		'&Ecirc;' => 'Ê',
		'&Euml;' => 'Ë',
		'&Igrave;' => 'Ì',
		'&Iacute;' => 'Í',
		'&Icirc;' => 'Î',
		'&Iuml;' => 'Ï',
		'&ETH;' => 'Ð',
		'&Ntilde;' => 'Ñ',
		'&Ograve;' => 'Ò',
		'&Oacute;' => 'Ó',
		'&Ocirc;' => 'Ô',
		'&Otilde;' => 'Õ',
		'&Ouml;' => 'Ö',
		'&Oslash;' => 'Ø',
		'&Ugrave;' => 'Ù',
		'&Uacute;' => 'Ú',
		'&Ucirc;' => 'Û',
		'&Uuml;' => 'Ü',
		'&Yacute;' => 'Ý',
		'&THORN;' => 'Þ',
		'&szlig;' => 'ß',
		'&agrave;' => 'à',
		'&aacute;' => 'á',
		'&acirc;' => 'â',
		'&atilde;' => 'ã',
		'&auml;' => 'ä',
		'&aring;' => 'å',
		'&aelig;' => 'æ',
		'&ccedil;' => 'ç',
		'&egrave;' => 'è',
		'&eacute;' => 'é',
		'&ecirc;' => 'ê',
		'&euml;' => 'ë',
		'&igrave;' => 'ì',
		'&iacute;' => 'í',
		'&icirc;' => 'î',
		'&iuml;' => 'ï',
		'&eth;' => 'ð',
		'&ntilde;' => 'ñ',
		'&ograve;' => 'ò',
		'&oacute;' => 'ó',
		'&ocirc;' => 'ô',
		'&otilde;' => 'õ',
		'&ouml;' => 'ö',
		'&oslash;' => 'ø',
		'&ugrave;' => 'ù',
		'&uacute;' => 'ú',
		'&ucirc;' => 'û',
		'&uuml;' => 'ü',
		'&yacute;' => 'ý',
		'&thorn;' => 'þ',
		'&yuml;' => 'ÿ',
		'&Alpha;' => 'Α',
		'&Beta;' => 'Β',
		'&Gamma;' => 'Γ',
		'&Delta;' => 'Δ',
		'&Epsilon;' => 'Ε',
		'&Zeta;' => 'Ζ',
		'&Eta;' => 'Η',
		'&Theta;' => 'Θ',
		'&Iota;' => 'Ι',
		'&Kappa;' => 'Κ',
		'&Lambda;' => 'Λ',
		'&Mu;' => 'Μ',
		'&Nu;' => 'Ν',
		'&Xi;' => 'Ξ',
		'&Omicron;' => 'Ο',
		'&Pi;' => 'Π',
		'&Rho;' => 'Ρ',
		'&Sigma;' => 'Σ',
		'&Tau;' => 'Τ',
		'&Upsilon;' => 'Υ',
		'&Phi;' => 'Φ',
		'&Chi;' => 'Χ',
		'&Psi;' => 'Ψ',
        '&Omega;' => 'Ω',
		'&alpha;' => 'α',
		'&beta;' => 'β',
		'&gamma;' => 'γ',
		'&delta;' => 'δ',
		'&epsilon;' => 'ε',
		'&zeta;' => 'ζ',
		'&eta;' => 'η',
		'&theta;' => 'θ',
		'&iota;' => 'ι',
		'&kappa;' => 'κ',
		'&lambda;' => 'λ',
		'&mu;' => 'μ',
		'&nu;' => 'ν',
		'&xi;' => 'ξ',
		'&omicron;' => 'ο',
		'&pi;' => 'π',
		'&rho;' => 'ρ',
		'&sigmaf;' => 'ς',
		'&sigma;' => 'σ',
		'&tau;' => 'τ',
		'&upsilon;' => 'υ',
		'&phi;' => 'φ',
		'&chi;' => 'χ',
		'&psi;' => 'ψ',
		'&omega;' => 'ω',
		'&thetasym;' => 'ϑ',
		'&upsih;' => 'ϒ',
		'&piv;' => 'ϖ',
		'&OElig;' => 'Œ',
		'&oelig;' => 'œ',
		'&Scaron;' => 'Š',
		'&scaron;' => 'š',
		'&Yuml;' => 'Ÿ',
		'&dagger;' => '†',
		'&Dagger;' => '‡',
	);

    $text = str_replace( array_keys($html_entities), array_values($html_entities), $text);

    // Strip HTML tags
    $text = strip_tags($text);

    // Process <, > and & after all others
    $text = str_ireplace( array('&lt;', '&gt;', '&#38;', '&amp;'), array('<', '>', '&', '&'), $text);

	// Delete any unsupported entities, excess spaces and return
    return preg_replace( '/[ ]{2,}/', ' ', $text);

} // END hesk_convert_to_utf8_and_clean_html_entities()


function hesk_replace_num_entity($ord)
{
	$ord = $ord[1];
	if (preg_match('/^x([0-9a-f]+)$/i', $ord, $match))
	{
		$ord = hexdec($match[1]);
	}
	else
	{
		$ord = intval($ord);
	}

	$no_bytes = 0;
	$byte = array();

	if ($ord < 128)
	{
		return chr($ord);
	}
	elseif ($ord < 2048)
	{
		$no_bytes = 2;
	}
	elseif ($ord < 65536)
	{
		$no_bytes = 3;
	}
	elseif ($ord < 1114112)
	{
		$no_bytes = 4;
	}
	else
	{
		return;
	}

	switch($no_bytes)
	{
		case 2:
		{
			$prefix = array(31, 192);
			break;
		}
		case 3:
		{
			$prefix = array(15, 224);
			break;
		}
		case 4:
		{
			$prefix = array(7, 240);
		}
	}

	for ($i = 0; $i < $no_bytes; $i++)
	{
		$byte[$no_bytes - $i - 1] = (($ord & (63 * pow(2, 6 * $i))) / pow(2, 6 * $i)) & 63 | 128;
	}

	$byte[0] = ($byte[0] & $prefix[0]) | $prefix[1];

	$ret = '';
	for ($i = 0; $i < $no_bytes; $i++)
	{
		$ret .= chr($byte[$i]);
	}

	return $ret;
} // END hesk_replace_num_entity()


function hesk_quoted_printable_encode($str)
{
	if ( function_exists('quoted_printable_encode') )
	{
		return quoted_printable_encode($str);
	}

	define('PHP_QPRINT_MAXL', 75);

	$lp = 0;
	$ret = '';
	$hex = "0123456789ABCDEF";
	$length = strlen($str);
	$str_index = 0;

	while ($length--)
	{
		if ((($c = $str[$str_index++]) == "\015") && ($str[$str_index] == "\012") && $length > 0)
		{
			$ret .= "\015";
			$ret .= $str[$str_index++];
			$length--;
			$lp = 0;
		}
		else
		{
			if (ctype_cntrl($c)
			|| (ord($c) == 0x7f)
			|| (ord($c) & 0x80)
			|| ($c == '=')
			|| (($c == ' ') && ($str[$str_index] == "\015")))
			{
				if (($lp += 3) > PHP_QPRINT_MAXL)
				{
					$ret .= '=';
					$ret .= "\015";
					$ret .= "\012";
					$lp = 3;
				}
				$ret .= '=';
				$ret .= $hex[ord($c) >> 4];
				$ret .= $hex[ord($c) & 0xf];
			}
			else
			{
				if ((++$lp) > PHP_QPRINT_MAXL)
				{
					$ret .= '=';
					$ret .= "\015";
					$ret .= "\012";
					$lp = 1;
				}
				$ret .= $c;
			}
		}
	}

	return $ret;
} // END hesk_quoted_printable_encode()
