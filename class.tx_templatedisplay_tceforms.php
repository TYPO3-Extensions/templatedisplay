<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Francois Suter (Cobweb) <typo3@cobweb.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
*
* $Id: class.tx_datadisplay_pi1.php 3938 2008-06-04 08:39:01Z fsuter $
***************************************************************/

require_once(t3lib_extMgm::extPath('basecontroller', 'lib/class.tx_basecontroller_utilities.php'));

/**
 * TCEform custom field for template mapping
 *
 * @author	Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package	TYPO3
 * @subpackage	tx_templatedisplay
 */
class tx_templatedisplay_tceforms {
	protected $extKey = 'templatedisplay';

	/**
	 * This method renders the user-defined mapping field,
	 * i.e. the screen where data is mapped to the template markers
	 *
	 * @param	array			$PA: information related to the field
	 * @param	t3lib_tceform	$fobj: reference to calling TCEforms object
	 *
	 * @return	string	The HTML for the form field
	 */
	public function mappingField($PA, $fobj) {
		$formField = '';

		try {
			// Get the related (primary) provider
			$provider = $this->getRelatedProvider($PA['row']);
			try {
				$fieldsArray = $provider->getTablesAndFields();
				

				#$GLOBALS['TBE_TEMPLATE']->loadJavascriptLib('js/common.js');
				$row = $PA['row'];

				// true when the user has defined a template.
				if($row['template'] == ''){
					$row['template'] = $this->getLL('tx_templatedisplay_displays.noTemplateFoundError');
                }
				# Retrieve the template string and init the path
				#$temporaryArray = explode('|', $row['template']);
				#$row['template'] = $temporaryArray[0];
				#$templateFile = t3lib_div::getFileAbsFileName('uploads/tx_templatedisplay/' . $row['template']);
				#$templateContent = file_get_contents($templateFile);
				$templateContent = $row['template'];

				# Initialize the select drop down which contains the fields
				$options = '';
				foreach($fieldsArray as $keyTable => $fields){
					$options .= '<optgroup label="'. $keyTable .'" class="c-divider">';
					foreach($fields['fields'] as $keyField => $field){
						$options .= '<option value="'.$keyTable.'.'.$keyField.'">'.$keyField.'</option>';
					}
					$options .= '</optgroup>';
				}
				$marker['###AVAILABLE_FIELDS###'] = $options;
					
				// Reinitializes the array pointer
				reset($fieldsArray);
				
				# Initialize some template variable
				$marker['###DEFAULT_TABLE###'] = key($fieldsArray);;
				$marker['###TEMPLATE_CONTENT_SRC###'] = $templateContent;
				$marker['###TEMPLATE_CONTENT###'] = $this->transformTemplateContent($templateContent);
				$marker['###STORED_FIELD_NAME###'] = $PA['itemFormElName'];
				$marker['###STORED_FIELD_NAME_TEMPLATE###'] = str_replace('mappings','template',$PA['itemFormElName']);
				$marker['###STORED_FIELD_VALUE###'] = $row['mappings'];
				$marker['###INFOMODULE_PATH###'] = t3lib_extMgm::extRelPath('templatedisplay').'resources/images/';
				$marker['###UID###'] = $row['uid'];
				$marker['###TEXT###'] = $this->getLL('tx_templatedisplay_displays.text');
				$marker['###IMAGE###'] = $this->getLL('tx_templatedisplay_displays.image');
				$marker['###LINK_TO_DETAIL###'] = $this->getLL('tx_templatedisplay_displays.link_to_detail');
				$marker['###LINK_TO_PAGE###'] = $this->getLL('tx_templatedisplay_displays.link_to_page');
				$marker['###LINK_TO_FILE###'] = $this->getLL('tx_templatedisplay_displays.link_to_file');
				$marker['###EMAIL###'] = $this->getLL('tx_templatedisplay_displays.email');
				$marker['###SHOW_JSON###'] = $this->getLL('tx_templatedisplay_displays.showJson');
				$marker['###EDIT_JSON###'] = $this->getLL('tx_templatedisplay_displays.editJson');
				$marker['###EDIT_HTML###'] = $this->getLL('tx_templatedisplay_displays.editHtml');
				$marker['###MAPPING###'] = $this->getLL('tx_templatedisplay_displays.mapping');
				$marker['###TYPES###'] = $this->getLL('tx_templatedisplay_displays.types');
				$marker['###FIELDS###'] = $this->getLL('tx_templatedisplay_displays.fields');
				$marker['###CONFIGURATION###'] = $this->getLL('tx_templatedisplay_displays.configuration');
				$marker['###SAVE_FIELD_CONFIGURATION###'] = $this->getLL('tx_templatedisplay_displays.saveFieldConfiguration');

				# Parse the template and render it.
				$backendTemplatefile = t3lib_div::getFileAbsFileName('EXT:templatedisplay/resources/templates/templatedisplay.html');
				$formField .= t3lib_parsehtml::substituteMarkerArray(file_get_contents($backendTemplatefile), $marker);
			}
			catch (Exception $e) {
				$formField .= tx_basecontroller_utilities::wrapMessage($e->getMessage());
			}

		}
		catch (Exception $e) {
			$formField .= tx_basecontroller_utilities::wrapMessage($e->getMessage());
		}
		return $formField;
	}
	
	/**
	 * Transformes $templateContent, this method is also util for Ajax called. In this case, the method is called externally.
	 * 2) wrap IF markers with a different background
	 * 2) wrap LOOP markers with a different background
	 * 1) wrap FIELD markers with a clickable href
	 *
	 * @param	string	$templateContent
	 * @return	string	$templateContent, the content transformed
	 */
	public function transformTemplateContent($templateContent) {
		$templateContent = htmlspecialchars($templateContent);

		# Wrap IF markers with a different background
		$pattern = $replacement = array();
		$pattern[] = "/(&lt;!-- *IF *\(.+--&gt;)/isU";
		$replacement[] = '<span class="templatedisplay_if">$1</span>';

		$pattern[] = "/(&lt;!-- *ENDIF *--&gt;)/isU";
		$replacement[] = '<span class="templatedisplay_if">$1</span>';

		# Wrap LOOP markers with a different background
		$pattern[] = "/(&lt;!-- *LOOP *\(.+--&gt;)/isU";
		$replacement[] = '<span class="templatedisplay_loop">$1</span>';

		$pattern[] = "/(&lt;!-- *ENDLOOP *--&gt;)/isU";
		$replacement[] = '<span class="templatedisplay_loop">$1</span>';

		# Wrap FIELD markers with a clickable href
		$pattern[] = '/(#{3}FIELD.+#{3})/isU';
		$path = t3lib_extMgm::extRelPath('templatedisplay').'resources/images/';
		$_replacement = '<span class="mapping_pictogrammBox">';
		$_replacement .= '<a href="#" onclick="return false">$1</a>';
		$_replacement .= '<img src="'.$path.'empty.png" alt="" class="mapping_pictogramm1"/>';
		$_replacement .= '<img src="'.$path.'empty.png" alt="" class="mapping_pictogramm2"/>';
		$_replacement .= '</span>';
		$replacement[] = $_replacement;
		
		return preg_replace($pattern, $replacement, $templateContent);
	}

	/**
	 * Return the translated string according to the key
	 *
	 * @param string key of label
	 */
	private function getLL($key){
		$langReference = 'LLL:EXT:templatedisplay/locallang_db.xml:';
		return $GLOBALS['LANG']->sL($langReference . $key);
	}

	/**
	 * This method returns the name of the table where the relations between
	 * Data Providers and Controllers are saved
	 * (this has been abstracted in a method in case the was of retrieving this table mame is changed in the future
	 * e.g. by defining a bidirection MM-relation to the display controller, in which case the name
	 * would be retrieved from the TCA instead)
	 *
	 * @return	string	Name of the table
	 */
	protected function getMMTableName() {
		return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['mm_table'];
	}

	/**
	 * This method retrieves the controller which calls this specific instance of template display
	 *
	 * @param	array	$row: database record corresponding the instance of template display
	 */
	protected function getRelatedProvider($row) {
		// Get the tt_content record(s) the template display instance is related to
		$mmTable = $this->getMMTableName();
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid_local', $mmTable, "uid_foreign = '".$row['uid']."' AND tablenames = 'tx_templatedisplay_displays'");
		$numRows = count($rows);

		// The template display instance is not related yet
		if ($numRows == 0) {
			throw new Exception('No controller found');
		}

		// The template display instance is related to exactly one tt_content record (easy case)
		// TODO: check back that situation. It must be possible for a provider to be related to more than one controller
		else {
//		elseif ($numRows == 1) {
			$tt_contentRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('CType', 'tt_content', "uid = '".$rows[0]['uid_local']."'");
			$controller = t3lib_div::makeInstanceService('datacontroller', $tt_contentRecord[0]['CType']);
			$controller->loadControllerData($rows[0]['uid_local']);
				// NOTE: getPrimaryProvider() may throw an exception, but we just let it pass at this point
			$provider = $controller->getPrimaryProvider();
			return $provider;
		}
/*
		// The template display instance is related to more than one tt_content records
		// Some additional checks must be performed
		else {
			throw new Exception('More than one controller found');
		}
*/
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templatedisplay/class.tx_templatedisplay_tceforms.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templatedisplay/class.tx_templatedisplay_tceforms.php']);
}

?>