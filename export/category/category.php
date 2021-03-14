<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Export.category
 *
 * @copyright   Copyright (C) 2021 Alikon. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Registry\Registry;
use Joomla\CMS\Router\Route;

/**
 * Joomla! Job One plugin
 *
 * An example for a job plugin
 *
 * @since  __DEPLOY_VERSION__
 */
class PlgExportCategory extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Application object.
	 *
	 * @var    ApplicationCms
	 * @since  __DEPLOY_VERSION__
	 */
	protected $app;

	/**
	 * Database object.
	 *
	 * @var    DatabaseDriver
	 * @since  __DEPLOY_VERSION__
	 */
	protected $db;

	/**
	 * URL to send the data.
	 *
	 * @var    string
	 * @since  3.5
	 */
	protected $serverUrl = '/api/index.php/v1/.ext./categories';

	/**
	 * First step to enter the sampledata. Content.
	 *
	 * @return  array or void  Will be converted into the JSON response to the module.
	 *
	 * @since  3.8.0
	 */
	public function onAjaxCategory()
	{
		$id  = $this->app->input->get('id');
		$ext  = $this->app->input->get('ext');
		$domain = $this->params->get('url', 'http://localhost');
		$endpoint = str_replace(".ext.", $ext, $this->serverUrl);
		$this->serverUrl = $domain . $endpoint;

		// Get an instance of the generic categories model
		// Add Include Paths.
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/models/', 'CategoriesModel');
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/tables/');
		$model = JModelLegacy::getInstance('Category', 'CategoriesModel', array('ignore_request' => true));

		$item = $model->getItem($id);

		$registry = new Registry();
		$item->params = new Registry($item->params);
		$registry->set('workflow_id', '1');
		
		$item->params->merge($registry);
		unset($item->created_user_id);

		try
		{
			$response = $this->sendData($item);
		}
		catch (RuntimeException $e)
		{
			// There was an error sending data.
			$this->app->redirect(Route::_('index.php?option=com_categories&view=category&layout=edit&id=' . $id, false), ' Connection ', 'error');
		}

		if ($response->code !== 200)
		{
			$data = json_decode($response->body);

			$this->app->redirect(Route::_('index.php?option=com_categories&view=category&layout=edit&id=' . $id . '&extension=com_' . $ext, false), $response->code . ' - ' . $data->errors[0]->title, 'error');
		}

		$this->app->redirect(Route::_('index.php?option=com_categories&view=category&layout=edit&id=' . $id . '&extension=com_' . $ext, false), 'Exported', 'success');

	}

	/**
	 * Send the data to the j4 server
	 *
	 * @return  boolean
	 *
	 * @since   3.9
	 *
	 * @throws  RuntimeException  If there is an error sending the data.
	 */
	private function sendData($item)
	{
		$content = json_encode($item);
		$options = new Registry;
		$options->set('Content-Type', 'application/json');
		$headers = array('Authorization' => 'Bearer ' . $this->params->get('key'));

		return  HttpFactory::getHttp($options)->post($this->serverUrl, $content, $headers, 2);
	}
}
