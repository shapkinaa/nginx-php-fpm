<?php
/**
 * Class for wrapping mail parts
 *
 * Copyright 2010-2011 Tim Gerundt <tim@gerundt.de>
 *
 * This file is part of NOCC. NOCC is free software under the terms of the
 * GNU General Public License. You should have received a copy of the license
 * along with NOCC.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    NOCC
 * @license    http://www.gnu.org/licenses/ GNU General Public License
 * @version    SVN: $Id: nocc_mailparts.php 2613 2014-05-07 08:19:55Z oheil $
 */

require_once 'nocc_mailstructure.php';
require_once 'nocc_mailpart.php';

/**
 * Wrapping mail parts
 *
 * @package    NOCC
 */
class NOCC_MailParts {
    /**
     * Body part
     * @var NOCC_MailPart
     * @access private
     */
    private $_bodyPart;

    /**
     * Attachments parts
     * @var array
     * @access private
     */
    private $_attachmentParts;
    
    /**
     * Initialize the wrapper
     * @param NOCC_MailStructure $mailstructure Mail structure
     * @todo Throw exception, if no vaild structure?
     */
    public function __construct($mailstructure) {
        $this->_bodyPart = null;
        $this->_attachmentParts = array();
        
        $parts = array();
        $this->_fillArrayWithParts($parts, $mailstructure);
	$body_index=-1;
	if( !empty($parts) ) {
		for( $i=0;$i<count($parts);$i++ ) {
			$bodyPart=$parts[$i];
			if( $bodyPart->getInternetMediaType()->isHtmlText() ) {
				$body_index=$i;
			}
		}
		if( $body_index==-1 ) {
			for( $i=0;$i<count($parts);$i++ ) {
				$bodyPart=$parts[$i];
				if( $bodyPart->getInternetMediaType()->isPlainText() ) {
					$body_index=$i;
				}
			}
		}
		if( $body_index>=0 ) {
			$this->_bodyPart=$parts[$body_index];
			array_splice($parts,$body_index,1);
		}
		$this->_attachmentParts = $parts;
	}
    }

    /**
     * Get the body part
     * @return NOCC_MailPart Body part
     */
    public function getBodyPart() {
        return $this->_bodyPart;
    }

    /**
     * Get the attachment parts
     * @return array Attachment parts
     */
    public function getAttachmentParts() {
        return $this->_attachmentParts;
    }

    /**
     * ...
     * Based on a function from matt@bonneau.net
     * @param array $parts Parts array
     * @param NOCC_MailStructure $mailstructure Mail structure
     * @param string $partNumber Part number
     * @access private
     * @todo Rewrite!
     */
    private function _fillArrayWithParts(&$parts, $mailstructure, $partNumber = '', $skip_message=false) {
        $this_part = $mailstructure->getStructure();
        $internetMediaType = $mailstructure->getInternetMediaType();
        if ($internetMediaType->isMultipart()) { //if multipart...
		$num_parts = count($this_part->parts);
		$found_plain=false;
		$found_html=false;
		if( $internetMediaType->isAlternativeMultipart() ) {
			// check if alternative consists of PLAIN and HTML, if yes we skip the PLAIN
			for ($i = 0; $i < $num_parts; $i++) {
				if( $this_part->parts[$i]->subtype == "PLAIN" ) {
					$found_plain=true;
				}
				if( $this_part->parts[$i]->subtype == "HTML" ) {
					$found_html=true;
				}
			}
		}
		for ($i = 0; $i < $num_parts; $i++) {
			if ($partNumber != '') {
				if (substr($partNumber, -1) != '.') $partNumber = $partNumber . '.';
			}
			if( $found_plain==true && $found_html==true ) {
				if( $this_part->parts[$i]->subtype != "PLAIN" ) {
					$this->_fillArrayWithParts($parts, new NOCC_MailStructure($this_part->parts[$i]),$partNumber.($i+1),$skip_message);
				}
			}
			else {
				$this->_fillArrayWithParts($parts, new NOCC_MailStructure($this_part->parts[$i]),$partNumber.($i+1),$skip_message);
			}
		}
        }
        else if( $internetMediaType->isMessage()) { //if message...
            if ($internetMediaType->isRfc822Message()) { //if RFC822 message...
                if (empty($partNumber)) {
                    $partNumber = '1';
                }
                $part = new NOCC_MailPart($mailstructure, $partNumber);
                array_unshift($parts, $part);
		$skip_message=true;
            }
	    if (isset($this_part->parts[0]->parts)) {
                $num_parts = count($this_part->parts[0]->parts);
                for ($i = 0; $i < $num_parts; $i++) {
			$this->_fillArrayWithParts($parts, new NOCC_MailStructure($this_part->parts[0]->parts[$i]), $partNumber . '.' . ($i + 1),$skip_message);
		}
            }
        }
        else {
		if (empty($partNumber)) {
			$partNumber = '1';
		}
		$part = new NOCC_MailPart($mailstructure, $partNumber);
		if( $mailstructure->isAttachment() || !$skip_message || ! $internetMediaType->isPlainOrHtmlText() ) { 
			array_unshift($parts, $part);
		}
        }
    }
}
?>
