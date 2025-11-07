<?php

/** 
 * Kaya Studio - Admin Dashboard Plugins Class
 * Manages Kaya Studio admin list of plugins.
 *
 * @version 1.0.0
 */

if (!defined('ABSPATH'))
{
	exit; // Exit if accessed directly
}

if (!class_exists('WP_KayaStudio_Plugins_List_Admin_Dashboard'))
{
	class WP_KayaStudio_Plugins_List_Admin_Dashboard
	{
		/**
		 * KayaStudio Plugins List
		 * @var Array 
		 */
		private $pluginsList;

		/**
		 * WP_KayaStudio_Plugins_List_Admin_Dashboard Constructor
		 */
		function __construct()
		{
			$this->pluginsList = array();
		}

		/**
		 * Adds a plugin information in the plugins list
		 */
		function addPluginInList(array $pluginInfos)
		{
			if (!empty($pluginInfos['title']) && !empty($pluginInfos['page_name']) && !empty($pluginInfos['page_slug']) && !empty($pluginInfos['page_text']))
			{
				$this->pluginsList[] = $pluginInfos;
			}
		}

		/**
		 * Return the plugins list
		 *
		 * @return array
		 */
		function getPluginList()
		{
			return $this->pluginsList;
		}
	}
}
