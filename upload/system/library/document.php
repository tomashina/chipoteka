<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Document class
*/
class Document {
	private $title;
	private $description;
	private $keywords;

	private $links = array();
	private $styles = array();
	private $scripts = array();

    /// <<< Document addTag
    private $tags = array();

    /**
     * Save any html tag with content and attributes
     *
     * Example of $data:
     * array(
     *     'name'    => 'a',  // tag name
     *     'content' => 'Click!', // content
     *     'attrs'   => array('href' => 'https://example.com'), // array of tag attributes
     *     'closing' => true // close the tag <a></a> or not <link/>
     * )
     *
     * @param array $data - tag data array
     * @param string $id - unique string to identify entry
     * @param string $group - e.g, 'header' or 'footer'
     */
    public function addTag($data, $id = '', $group = 'header') {
        if (isset($data) && is_array($data) && isset($data['name'])) {
            $tag = array(
                'name'    => $data['name'],
                'content' => isset($data['content']) ? $data['content'] : '',
                'attrs'   => isset($data['attrs']) && is_array($data['attrs']) ? $data['attrs'] : array(),
                'closing' => isset($data['closing']) ? $data['closing'] : true,
            );

            $this->tags[$group][] = array(
                'tag'     => $tag,
                'id'      => $id,
            );
        }
    }

    /**
     * @param    string    $group
     *
     * @return   array
     */
    public function getTags($group = 'header') {
        if (isset($this->tags[$group])) {
            return $this->tags[$group];
        } else {
            return array();
        }
    }
    /// Document addTag >>>

	/**
     * 
     *
     * @param	string	$title
     */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
     * 
	 * 
	 * @return	string
     */
	public function getTitle() {
		return $this->title;
	}

	/**
     * 
     *
     * @param	string	$description
     */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
     * 
     *
     * @param	string	$description
	 * 
	 * @return	string
     */
	public function getDescription() {
		return $this->description;
	}

	/**
     * 
     *
     * @param	string	$keywords
     */
	public function setKeywords($keywords) {
		$this->keywords = $keywords;
	}

	/**
     *
	 * 
	 * @return	string
     */
	public function getKeywords() {
		return $this->keywords;
	}
	
	/**
     * 
     *
     * @param	string	$href
	 * @param	string	$rel
     */
	public function addLink($href, $rel) {
		$this->links[$href] = array(
			'href' => $href,
			'rel'  => $rel
		);
	}

	/**
     * 
	 * 
	 * @return	array
     */
	public function getLinks() {
		return $this->links;
	}

	/**
     * 
     *
     * @param	string	$href
	 * @param	string	$rel
	 * @param	string	$media
     */
	public function addStyle($href, $rel = 'stylesheet', $media = 'screen', $position = 'header') {
		$this->styles[$position][$href] = array(
			'href'  => $href,
			'rel'   => $rel,
			'media' => $media
		);
	}

	/**
     * 
	 * 
	 * @return	array
     */
	public function getStyles($position = 'header') {
		if (isset($this->styles[$position])) {
			return $this->styles[$position];
		} else {
			return array();
		}
	}

	/**
     * 
     *
     * @param	string	$href
	 * @param	string	$position
     */
	public function addScript($href, $position = 'header') {
		$this->scripts[$position][$href] = $href;
	}

	/**
     * 
     *
     * @param	string	$position
	 * 
	 * @return	array
     */
	public function getScripts($position = 'header') {
		if (isset($this->scripts[$position])) {
			return $this->scripts[$position];
		} else {
			return array();
		}
	}
}