<?php defined('_JEXEC') or die;

/**
 * File       indexed_tags.php
 * Created    12/16/13 2:30 PM
 * Author     Matt Thomas | matt@betweenbrain.com | http://betweenbrain.com
 * Support    https://github.com/betweenbrain/
 * Copyright  Copyright (C) 2013 betweenbrain llc. All Rights Reserved.
 * License    GNU GPL v3 or later
 */

jimport('joomla.error.log');

// Load the K2 Plugin API
JLoader::register('K2Plugin', JPATH_ADMINISTRATOR . '/components/com_k2/lib/k2plugin.php');

// Instantiate class for K2 plugin events
class plgK2Indexed_tags extends K2Plugin
{

	var $pluginName = 'indexed_tags';
	var $pluginNameHumanReadable = 'K2 - Indexed Tags';

	/**
	 * Constructor
	 */
	function __construct(&$subject, $results)
	{
		parent::__construct($subject, $results);
		$this->app = JFactory::getApplication();
		$this->db  = JFactory::getDbo();
		$this->log = JLog::getInstance();
	}

	/**
	 * Function to Update item's extra_fields_search data with tag names
	 *
	 * @param $row
	 * @param $isNew
	 */
	function onAfterK2Save(&$row, $isNew)
	{
		if ($this->app->isAdmin())
		{
			$tags = $this->getTags($row->id);
			$this->setExtraFieldsSearchData($row->id, $tags);
			$this->setpluginsData($row->id, $tags, 'tags');
		}
	}

	/**
	 * Adds data to the extra_fields_search column of a K2 item
	 *
	 * @param $id
	 * @param $data
	 */
	private function setExtraFieldsSearchData($id, $data)
	{
		$data  = implode(' ', $data);
		$query = 'UPDATE ' . $this->db->nameQuote('#__k2_items') . '
			SET ' . $this->db->nameQuote('extra_fields_search') . ' = CONCAT(
				' . $this->db->nameQuote('extra_fields_search') . ',' . $this->db->Quote($data) . '
			)
			WHERE id = ' . $this->db->Quote($id) . '';
		$this->db->setQuery($query);
		$this->db->query();
		$this->checkDbError();
	}

	/**
	 * function to fetch a K2 item's tags
	 *
	 * @param $id
	 *
	 * @return mixed
	 */
	private function getTags($id)
	{
		$query = 'SELECT tag.name
			FROM ' . $this->db->nameQuote('#__k2_tags') . '  as tag
			LEFT JOIN ' . $this->db->nameQuote('#__k2_tags_xref') . '
			AS xref ON xref.tagID = tag.id
			WHERE xref.itemID = ' . $this->db->Quote($id) . '
			AND tag.published = 1';

		$this->db->setQuery($query);
		$tags = $this->db->loadResultArray();
		$this->checkDbError();

		return $tags;
	}

	/**
	 * Sets the plugins data for the specified K2 item
	 *
	 * @param $id
	 * @param $data
	 * @param $type
	 */
	private function setpluginsData($id, $data, $type)
	{

		$pluginsData  = $this->getpluginsData($id);
		$pluginsArray = parse_ini_string($pluginsData, false, INI_SCANNER_RAW);
		if ($data)
		{
			$pluginsArray[$type] = implode('|', $data);
		}
		else
		{
			unset($pluginsArray[$type]);
		}
		$pluginData = null;
		foreach ($pluginsArray as $key => $value)
		{
			$pluginData .= "$key=" . $value . "\n";
		}

		$query = 'UPDATE ' . $this->db->nameQuote('#__k2_items') .
			' SET ' . $this->db->nameQuote('plugins') . '=\'' . $pluginData . '\'' .
			' WHERE id = ' . $this->db->Quote($id) . '';

		$this->db->setQuery($query);
		$this->db->query();
		$this->checkDbError();
	}

	/**
	 * Gets the plugins data for the specified K2 item
	 *
	 * @param $id
	 *
	 * @return mixed
	 */
	private function getpluginsData($id)
	{
		$query = 'SELECT ' . $this->db->nameQuote('plugins') .
			' FROM ' . $this->db->nameQuote('#__k2_items') .
			' WHERE id = ' . $this->db->Quote($id) . '';

		$this->db->setQuery($query);
		$pluginsData = $this->db->loadResult();
		$this->checkDbError();

		return $pluginsData;
	}

	/**
	 * Checks for any database errors after running a query
	 *
	 * @throws Exception
	 */
	private function checkDbError($backtrace = null)
	{
		if ($error = $this->db->getErrorMsg())
		{
			if ($backtrace)
			{
				$e = new Exception();
				$error .= "\n" . $e->getTraceAsString();
			}

			$this->log->addEntry(array('LEVEL' => '1', 'STATUS' => 'Database Error:', 'COMMENT' => $error));
			JError::raiseWarning(100, $error);
		}
	}
}