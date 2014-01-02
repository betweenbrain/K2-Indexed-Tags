<?php defined('_JEXEC') or die;

/**
 * File       indexed_tags.php
 * Created    12/16/13 2:30 PM
 * Author     Matt Thomas | matt@betweenbrain.com | http://betweenbrain.com
 * Support    https://github.com/betweenbrain/
 * Copyright  Copyright (C) 2013 betweenbrain llc. All Rights Reserved.
 * License    GNU GPL v3 or later
 */

// Load the K2 Plugin API
JLoader::register('K2Plugin', JPATH_ADMINISTRATOR . '/components/com_k2/lib/k2plugin.php');

// Instantiate class for K2 plugin events
class plgK2Indexed_tags extends K2Plugin
{

	var $pluginName = 'indexed_tags';
	var $pluginNameHumanReadable = 'K2 - Indexed Tags';

	function plgK2Indexed_tags(& $subject, $results)
	{
		parent::__construct($subject, $results);
	}

	/**
	 * Function to Update item's extra_fields_search data with tag names
	 *
	 * @param $row
	 * @param $isNew
	 */
	function onAfterK2Save(&$row, $isNew)
	{

		$app = JFactory::getApplication();
		$db  = JFactory::getDbo();

		if ($app->isAdmin())
		{

			$tags     = $this->fetchTags($row->id);
			$tagNames = null;

			foreach ($tags as $tag)
			{
				$tagNames .= $tag->name . ' ';
			}

			$query = 'UPDATE ' . $db->nameQuote('#__k2_items') . '
				SET ' . $db->nameQuote('extra_fields_search') . ' = CONCAT(
					' . $db->nameQuote('extra_fields_search') . ',' . $db->Quote($tagNames) . '
				)
				WHERE id = ' . $db->Quote($row->id) . '';
			$db->setQuery($query);
			$db->query();

			$this->setTags($row->id, $tagNames);
		}
	}

	/**
	 * function to fetch an item's tags
	 *
	 * @param $id
	 *
	 * @return mixed
	 */
	private function fetchTags($id)
	{

		$db    = JFactory::getDbo();
		$query = 'SELECT tag.name, tag.id
			FROM ' . $db->nameQuote('#__k2_tags') . '  as tag
			LEFT JOIN ' . $db->nameQuote('#__k2_tags_xref') . '
			AS xref ON xref.tagID = tag.id
			WHERE xref.itemID = ' . $db->Quote($id) . '
			AND tag.published = 1';

		$db->setQuery($query);
		$tags = $db->loadObjectList();

		return $tags;
	}

	private function setTags($id, $tagNames)
	{
		$db    = JFactory::getDbo();
		$query = 'SELECT ' . $db->nameQuote('plugins') .
			' FROM ' . $db->nameQuote('#__k2_items') .
			' WHERE id = ' . $db->Quote($id) . '';

		$db->setQuery($query);
		$plugins = $db->loadResult();

		$plugins = parse_ini_string($plugins, false, INI_SCANNER_RAW);

		if (!($plugins['tags']))
		{
			$query = 'UPDATE ' . $db->nameQuote('#__k2_items') . '
			SET ' . $db->nameQuote('plugins') . ' = CONCAT(
				' . $db->nameQuote('plugins') . ',' . $db->Quote('tags=' . $tagNames . "\n") . '
			)
			WHERE id = ' . $db->Quote($id) . '';
			$db->setQuery($query);
			$db->query();
		}
	}
}