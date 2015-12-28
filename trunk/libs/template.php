<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Popy (popy.dev@gmail.com), Touzet David <dtouzet@gmail.com>
*  All rights reserved
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
***************************************************************/
/**
 * Template file leader / tools
 *
 * @author Popy <popy.dev@gmail.com>, Touzet David <dtouzet@gmail.com>
 */
class template {
	/**
	 * Subparts tree
	 * @access protected
	 * @var array
	 */
	protected $subpartsTree = null;

	/**
	 * 
	 * @access protected
	 * @var array
	 */
	protected $contextStack = array();

	/**
	 * 
	 * @access protected
	 * @var magicmarkers
	 */
	protected $magicMarkers = null;

	const MARKER_PREFIX = '###';
	const MARKER_SUFFIX = '###';
	const SUBPART_BEGIN_PREFIX = '<!-- #';
	const SUBPART_BEGIN_SUFFIX = '#begin -->';
	const SUBPART_END_PREFIX = '<!-- #';
	const SUBPART_END_SUFFIX = '#end -->';
	const SUBPART_REGEXP = '/<!-- #([^#]*)#([a-z]*) -->/';
	const SUBPART_PLACEHOLDER_PREFIX = '<!-- #';
	const SUBPART_PLACEHOLDER_SUFFIX = '#placeholder -->';
	const SUBPART_INDEXED_KEY_REGEXP = '/^(.*)\[([0-9]+)\]$/';

	/**
	 * 
	 * @access protected
	 * @var object
	 */
	protected $caller = null;

	/**
	 * 
	 * 
	 * @param object $caller = 
	 * @access public
	 * @return void 
	 */
	public static function &getInstance(&$caller) {
		/* Declare */
		$res = new template();
	
		/* Begin */
		$res->magicMarkers = magicmarkers::getInstance($caller);

		$res->caller = &$caller;

		return $res;
	}

	/**
	 * Init function : specifies the template file
	 * 
	 * @param strong $templateFile = template file path (relative, absolute, EXT:xxx/.. pattern)
	 * @access public
	 * @return void
	 */
	public function initByFile($templateFile) {
		$templateAbsPath = $templateFile;

		$content = file_get_contents($templateAbsPath);

		$this->initByContent($content);
	}

	/**
	 * Init function : specifies the template content (instead of a fileName like in initByFile method
	 * 
	 * @param string $templateContent = the template content
	 * @access public
	 * @return void 
	 */
	public function initByContent($templateContent) {
		$this->subpartsTree = array(
			'__content' => $templateContent,
		);
		$this->parseTemplate();
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public function initBySubpart(&$templateInfos) {
		$this->subpartsTree = &$templateInfos;
		$this->parseTemplate();
	}

	/**
	 * Returns the template data (= plain content and parsed informations)
	 * 
	 * @access public
	 * @return array
	 */
	public function &getTemplateContent() {
		return $this->subpartsTree['__content'];
	}


	/******************************************/
	/******  Template parsing functions  ******/
	/******************************************/

	/**
	 * Parse template content into a subpart tree
	 * 
	 * @access protected
	 * @return void 
	 */
	protected function parseTemplate() {
		$tagList = $this->getTemplateSubpartTagList();
		$this->parseTemplateSubparts($tagList);
	}

	/**
	 * Search open/close tags in the template content
	 * 
	 * @access public
	 * @return array 
	 */
	protected function getTemplateSubpartTagList() {
		/* Declare */
		$tagList = array();

		/* Begin */
		preg_match_all(self::SUBPART_REGEXP, $this->subpartsTree['__content'], $res, PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER);
		foreach ($res[0] as $k => $v) {
			$type = ($res[2][$k][0] == 'begin') ? 'open' : 'close';
			$tagList[$v[1]] = array(
				'matchedTag' => $v[0],
				'tagName' => $res[1][$k][0],
				'realTagName' => reset(explode(':', $res[1][$k][0])),
				'offset' => $v[1],
				'type' => $type,
			);
		}

		return $tagList;
	}

	/**
	 * Build the internal subpartsTree array
	 * 
	 * @param array $tagList = open/close subparts tag list (with offsets)
	 * @access public
	 * @return void 
	 */
	protected function parseTemplateSubparts($tagList) {
		/* Declare */
		$markerPattern = '/' . self::MARKER_PREFIX . '([^#]*)' . self::MARKER_SUFFIX . '/';
		$CONTENT = &$this->subpartsTree['__content'];
		$SHIFT = 0;
		$stack = array();
		$stackCounter = 0;
		$rootElement = array(
			'startTag' => 'ROOT ELEMENT',
			'subpartsCounter' => array(),
		);
		$currentNode = &$rootElement;
		$subpartPointer = &$this->subpartsTree;
		$subpartPointer['subparts'] = array();

		/* Begin */
		foreach ($tagList as $currentTag) {
			if ($currentTag['type'] == 'open') {
				//*** Openning a new subpart (as child of $currendNode), so, add $currentNode to the stack and begin new subpart process

				// Here we apply the calculated "offset shift"
				//      (as some text has been replaced during previous subparts processing,
				//       the start offset of this subpart may have change)
				$currentTag['offset'] -= $SHIFT;

				// Creating current subpart node informations
				$newNode = array(
					'startTag' => $currentTag,
					//'endTag' => null,
					'subpartFinalKey' => null,
					'subpartsCounter' => array(),
				);

				//*** Subpart key determination :
				// Init / increment subpart counter
				if (!isset($currentNode['subpartsCounter'][$currentTag['tagName']])) {
					$currentNode['subpartsCounter'][$currentTag['tagName']] = 0;
				} else {
					$currentNode['subpartsCounter'][$currentTag['tagName']]++;
				}

				// Generate a new subpart key based on this subpart-name-count
				$newNode['subpartFinalKey'] = $currentTag['tagName'] . '[' . $currentNode['subpartsCounter'][$currentTag['tagName']] . ']';

				// Creating current subpart informations
				$subpartPointer['subparts'][$newNode['subpartFinalKey']] = array(
					'__content' => null,
					'subparts' => array(),
					'__markers' => array(),
				);

				// Pushing the "parent" subpart to the stack
				$stack[$stackCounter++] = array(
					&$subpartPointer,
					&$currentNode,
				);
				
				$subpartPointer = &$subpartPointer['subparts'][$newNode['subpartFinalKey']];
				$currentNode = &$newNode;
				unset($newNode);
			} else {
				//*** Closing a subpart
				if (empty($stack)) {
					// No tag was open !
					throw new Exception('Unexpected close tag : ' . $currentTag['matchedTag'] . ' at offset ' . $currentTag['offset']);
				} elseif ($currentTag['realTagName'] != $currentNode['startTag']['realTagName']) {
					// Wrong closure !
					throw new Exception('Wrong closure for tag "' . $currentNode['startTag']['matchedTag'] . '", found "' . $currentTag['matchedTag'] . '"');
				}

				// Here we apply the calculated "offset shift"
				//      (as some text has been replaced during previous subparts and child subparts processing,
				//       the start offset and the end offset of this subpart may have change)
				$currentTag['offset'] -= $SHIFT;
				//$currentNode['endTag'] = $currentTag;

				// Registering current subpart content
				$start = $currentNode['startTag']['offset'] + strlen($currentNode['startTag']['matchedTag']);
				$subpartPointer['__content'] = substr(
					$CONTENT,
					$start,
					$currentTag['offset'] - $start
				);
				unset($start);

				$matches = array();
				preg_match_all($markerPattern, $subpartPointer['__content'], $matches);
				$subpartPointer['__markers'] = array_unique($matches[1]);

				// Generating subpart placeholder
				$placeHolder = self::SUBPART_PLACEHOLDER_PREFIX . $currentNode['subpartFinalKey'] . self::SUBPART_PLACEHOLDER_SUFFIX;

				// Replacing subpart by its placeholder
				$stop = $currentTag['offset'] + strlen($currentTag['matchedTag']);
				$CONTENT = substr_replace(
					$CONTENT,
					$placeHolder,
					$currentNode['startTag']['offset'],
					$stop - $currentNode['startTag']['offset']
				);

				// Updating offset shift (will be applied on next subparts (and parent subpart closures)
				$SHIFT += $stop - $currentNode['startTag']['offset'] - strlen($placeHolder);
				unset($stop);


				// Shift stack
				$stackCounter--;
				$subpartPointer = &$stack[$stackCounter][0];
				$currentNode = &$stack[$stackCounter][1];
				unset($stack[$stackCounter]);
			}
		}

		if (count($stack)) {
			$tmp = array();
			foreach ($stack as $v) {
				$tmp[] = $v[1]['matchedTag'];
			}
			throw new Exception('Some tags are not closed ! (' . implode(', ', $tmp));
		}
	}

	/******************************************/
	/*****  Context management functions  *****/
	/******************************************/

	/**
	 * 
	 * 
	 * @param mixed $subpartMarkerArray = current marker array (from where data for this subpart is get)
	 * @param array $subpartInfos = current subpart infos
	 * @access protected
	 * @return void 
	 */
	protected function pushToContextStack(&$subpartMarkerArray, &$subpartInfos, $subpartOriginalKey, $subpartKey) {
		array_unshift($this->contextStack, array(&$subpartMarkerArray, &$subpartInfos, $subpartOriginalKey, $subpartKey));
	}

	/**
	 * 
	 * 
	 * @access protected
	 * @return void 
	 */
	protected function pullFromContextStack() {
		array_shift($this->contextStack);
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public function &getContext($what) {
		/* Declare */
		$status = reset($this->contextStack);
	
		/* Begin */
		switch ($what) {
		case 'markers':
			return $status[0];
			break;
		case 'subpart':
			return $status[1];
			break;
		case 'subpartOriginalKey':
			return $status[2];
			break;
		case 'subpartKey':
			return $status[3];
			break;
		default;
			return null;
			break;
		}
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public function getCurrentSubpartPath() {
		/* Declare */
		$list = array_map('end', $this->contextStack);
	
		/* Begin */
		$list = array_reverse($list);

		return implode('/', $list);
	}

	/******************************************/
	/*****                                *****/
	/******************************************/

	/**
	 * Recursive version of getSubpart
	 *
	 * @param string $subpartPath = rootline of the required subpart (like dirs !)
	 *   Ex:myBigSubpart/aLittleSubpart means that I want the subpart "aLittleSubpart"
	 *      wich is a subpart of the subpart "myBigSubpart"
	 *
	 * @access public
	 * @return string
	 */
	public function &getRecursiveSubpart($subpartPath) {
		/* Declare */
		$parts = $this->trimExplode('/',$subpartPath);
		$templateInfos = &$this->subpartsTree;
		$null = null;

		/* Begin */
		while ($nextPart = array_shift($parts)) {
			if (!preg_match(self::SUBPART_INDEXED_KEY_REGEXP, $nextPart)) {
				// Append counter suffix if not already present
				$nextPart .= '[0]';
			}
			if (isset($templateInfos['subparts'][$nextPart])) {
				$templateInfos = &$templateInfos['subparts'][$nextPart];
			} else {
				$templateInfos = &$null;
				break;
			}
		}

		return $templateInfos;
	}

	public static function trimExplode($delim, $string, $onlyNonEmptyValues=0)	{
		$array = explode($delim, $string);
			// for two perfomance reasons the loop is duplicated
			//  a) avoid check for $onlyNonEmptyValues in foreach loop
			//  b) avoid unnecessary code when $onlyNonEmptyValues is not set
		if ($onlyNonEmptyValues) {
			$new_array = array();
			foreach($array as $value) {
				$value = trim($value);
				if ($value != '') {
					$new_array[] = $value;
				}
			}
				// direct return for perfomance reasons
			return $new_array;
		}

		foreach($array as &$value) {
			$value = trim($value);
		}

		return $array;
	}


	/**
	 * Replace some markers in $content (like a basic fastMarkerArray) BUT
	 *   if a marker value is an array, then the marker is used to get a subpart of $content, and each row of the marker value
	 *   is used as a markerarray to apply to this subpart
	 * 
	 * @param array $markers = the marker array
	 * @param string $subpartPath = path to the subpart wich have to be used as template
	 * @access public
	 * @return string
	 */
	public function nestedMarkerArray(&$markers, $subpartPath = null) {
		/* Declare */
		$templateInfos = &$this->getRecursiveSubpart($subpartPath);

		/* begin*/
		if (is_null($templateInfos)) {
			throw new Exception('Subpart "' . $subpartPath . '" does not exist in current template.');
		}
		$this->pushToContextStack($markers, $templateInfos, $subpartPath, $subpartPath);

		return $this->applyInSubpart($markers, $templateInfos);
	}


	/**
	 * 
	 * 
	 * @param mixed $data = marker array/object
	 * @param 
	 * @access public
	 * @return void 
	 */
	public function applyInSubpart(&$data, &$templateInfos) {
		/* Declare */
		$content = $templateInfos['__content'];

		/* Begin */
		foreach ($templateInfos['subparts'] as $subpart => &$subpartInfos) {
			// Extract subpart original name
			preg_match(self::SUBPART_INDEXED_KEY_REGEXP, $subpart, $matches);
			$subpartOriginalKey = $matches[1];

			// Push current marker array/object into "context" (to allow contexual execution of user method from template)
			$this->pushToContextStack($data, $subpartInfos, $subpartOriginalKey, $subpart);

			// Get "subpart data" (data wich have to be injected in current subpart)
			if (is_array($data)) {
				$res = isset($data[$subpartOriginalKey]) ? $data[$subpartOriginalKey] : null;
			} elseif (is_object($data)) {
				$res = $this->callMarkerUserFunc($data, $subpartOriginalKey);
			}

			$res = $this->applySubpartDataIntoSubpart($res, $subpartOriginalKey);
			 
			$this->pullFromContextStack();

			$content = str_replace(
				self::SUBPART_PLACEHOLDER_PREFIX . $subpart . self::SUBPART_PLACEHOLDER_SUFFIX,
				$res,
				$content
			);
		}

		foreach ($templateInfos['__markers'] as $mark) {
			//$res = $this->callMarkerUserFunc($data, $subpart);
			if (is_array($data)) {
				if (isset($data[self::MARKER_PREFIX . $mark . self::MARKER_SUFFIX])) {
					$res = $data[self::MARKER_PREFIX . $mark . self::MARKER_SUFFIX];
				} elseif (isset($data[$mark])) {
					$res = $data[$mark];
				} else {
					$res = null;
				}
			} elseif (is_object($data)) {
				$res = $this->callMarkerUserFunc($data, $mark);
			}

			if (is_null($res) && $this->magicMarkers->isCallable($mark)) {
				// PLACEHOLDER : magickMarker
				$res = $this->magicMarkers->call($mark, $data, null);
			}

			if (!is_null($res)) {
				$content = str_replace(
					self::MARKER_PREFIX . $mark . self::MARKER_SUFFIX,
					$res,
					$content
				);
			}
		}

		return $content;
	}

	/**
	 * 
	 * CONTEXTUAL : depend on current template context (@see pushToContextStack)
	 * 
	 * @param mixed $subpartData = current subpart "data" wich has to be injected in the subpart
	 * @access protected
	 * @return void 
	 */
	protected function applySubpartDataIntoSubpart(&$subpartData, $subpartOriginalKey) {
		/* Declare */
		$baseData = &$this->getContext('markers');
		$subpartInfos = &$this->getContext('subpart');
		$res = '';

		/* Begin */
		if (is_bool($subpartData)) { // BOOLEAN : Activate or not a subpart
			if ($subpartData) {
				$res = $this->applyInSubpart($baseData, $subpartInfos);
			} else {
				$res = '';
			}
		} elseif (is_array($subpartData)) { // ARRAY : wrapper or marker array/object
			if (count($subpartData) == 2 && isset($subpartData[0]) && isset($subpartData[1]) && is_string($subpartData[0]) && is_string($subpartData[1])) {
				// Wrapper Array
				$res = $subpartData[0] . $this->applyInSubpart($baseData, $subpartInfos) . $subpartData[1];
			} else {
				// Sub list Array
				foreach ($subpartData as $v) {
					$res .= $this->applyInSubpart($v, $subpartInfos);
				}
			}
		} elseif (is_object($subpartData)) {
			$res = $this->applyInSubpart($subpartData, $subpartInfos);
		} elseif (is_null($subpartData)) {
			// is_null($res) -> Non callable subpart : call recursively
			$res = $this->applyInSubpart($baseData, $subpartInfos);

			if ($this->magicMarkers->isCallable($subpartOriginalKey)) {
				$res = $this->magicMarkers->call($subpartOriginalKey, $baseData, $res);
			} else {
				$res =
					self::SUBPART_BEGIN_PREFIX . $subpartOriginalKey . self::SUBPART_BEGIN_SUFFIX .
					$res .
					self::SUBPART_END_PREFIX . $subpartOriginalKey . self::SUBPART_END_SUFFIX;
			}
		}

		return $res;
	}

	/**
	 * Call the method of $object requested by the marker/subpart if it respect a "callable pattern"
	 * 
	 * @param object $object =
	 * @param string $markerName = marker / subpart name
	 * @access protected
	 * @return mixed / null =  method result / null if not a method pattern
	 */
	protected function callMarkerUserFunc(&$object, $markerName) {
		/* Declare */
		$markerParts = explode(':', $markerName);
		$type = $markerParts[0];
		$callback = array(
			&$object,
			''
		);
		$parameters = array();
	
		/* Begin */
		switch ($type) {
		case 'call':
			$callback[1] = $markerParts[1];
			if (isset($markerParts[2])) {
				$parameters = explode(',', $markerParts[2]);
			}
			break;
		case 'get':
			$callback[1] = 'getProperty';
			$parameters = array($markerParts[1]);
			break;
		default:
			return null;
		}

		if (!is_callable($callback)) {
			throw new BadMethodCallException(get_class($object) . '->' . $callback[1] . ' is not callable (' . count($parameters) . ' parameters given)');
		}

		return call_user_func_array($callback, $parameters);
	}


}
?>